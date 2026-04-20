<?php

namespace Database\Seeders;

use App\Models\ControlEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ImportFebruaryControlEntriesSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('role', 'admin')->value('id') ?? User::query()->value('id');

        if (! $adminId) {
            throw new RuntimeException('User admin tidak ditemukan untuk proses import Februari.');
        }

        $workbookPath = base_path('tmp_lembar_kontrol.xlsx');

        if (! file_exists($workbookPath)) {
            throw new RuntimeException('File tmp_lembar_kontrol.xlsx tidak ditemukan.');
        }

        $zip = new ZipArchive();

        if ($zip->open($workbookPath) !== true) {
            throw new RuntimeException('Workbook Februari tidak bisa dibuka.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveSheetPath($zip, 'Februari');
        $sheet = simplexml_load_string($zip->getFromName($sheetPath));

        if (! $sheet instanceof SimpleXMLElement) {
            $zip->close();

            throw new RuntimeException('Sheet Februari tidak bisa diparsing.');
        }

        $sheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        $activeDate = null;

        foreach ($sheet->xpath('//main:sheetData/main:row') ?: [] as $row) {
            $rowNumber = (int) $row['r'];

            if ($rowNumber < 6 || $rowNumber > 161) {
                continue;
            }

            $cells = $this->extractCells($row, $sharedStrings);

            if (! empty($cells['B'])) {
                $activeDate = $this->excelDateToSqlDate((float) $cells['B']);
            }

            $amountOut = (int) round((float) ($cells['C'] ?? 0));
            $amountIn = (int) round((float) ($cells['D'] ?? 0));
            $partialPayment = (int) round((float) ($cells['N'] ?? 0));

            $hasMeaningfulData = $amountOut > 0
                || $amountIn > 0
                || $partialPayment > 0
                || ! empty(trim((string) ($cells['E'] ?? '')))
                || ! empty(trim((string) ($cells['J'] ?? '')));

            if (! $hasMeaningfulData || ! $activeDate) {
                continue;
            }

            $rows[] = [
                'entry_date' => $activeDate,
                'handover_time' => trim((string) ($cells['H'] ?? '-')) ?: '-',
                'amount_out' => $amountOut,
                'amount_in' => $amountIn,
                'third_party' => $this->nullableCell($cells['E'] ?? null),
                'receiving_officer' => trim((string) ($cells['F'] ?? '-')) ?: '-',
                'appointed_official' => trim((string) ($cells['G'] ?? '-')) ?: '-',
                'location' => trim((string) ($cells['I'] ?? '-')) ?: '-',
                'purpose' => trim((string) ($cells['J'] ?? '-')) ?: '-',
                'fund_source' => strtoupper(trim((string) ($cells['L'] ?? ''))),
                'status' => strtoupper(trim((string) ($cells['M'] ?? 'LUNAS'))),
                'partial_payment_amount' => $partialPayment,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $zip->close();

        DB::transaction(function () use ($rows): void {
            ControlEntry::query()->delete();
            ControlEntry::query()->insert($rows);
        });
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml === false) {
            return [];
        }

        $sharedStrings = simplexml_load_string($sharedStringsXml);

        if (! $sharedStrings instanceof SimpleXMLElement) {
            return [];
        }

        $values = [];

        foreach ($sharedStrings->si as $stringItem) {
            $text = '';

            foreach ($stringItem->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                $text .= (string) $textNode;
            }

            if ($text === '') {
                $text = (string) ($stringItem->t ?? '');
            }

            $values[] = $text;
        }

        return $values;
    }

    private function resolveSheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $relations = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));

        if (! $workbook instanceof SimpleXMLElement || ! $relations instanceof SimpleXMLElement) {
            throw new RuntimeException('Workbook relasi tidak bisa dibaca.');
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $relations->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $sheetRelationId = null;

        foreach ($workbook->xpath('//main:sheets/main:sheet') ?: [] as $sheet) {
            if ((string) $sheet['name'] === $sheetName) {
                $sheetRelationId = (string) $sheet->attributes('r', true)->id;
                break;
            }
        }

        if (! $sheetRelationId) {
            throw new RuntimeException('Sheet Februari tidak ditemukan di workbook.');
        }

        foreach ($relations->xpath('//rel:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] === $sheetRelationId) {
                return 'xl/'.ltrim((string) $relationship['Target'], '/');
            }
        }

        throw new RuntimeException('Relasi sheet Februari tidak ditemukan.');
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<string, string>
     */
    private function extractCells(SimpleXMLElement $row, array $sharedStrings): array
    {
        $row->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = [];

        foreach ($row->xpath('./main:c') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $reference) ?: '';
            $cell->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $valueNode = $cell->xpath('./main:v');
            $value = (string) ($valueNode[0] ?? '');

            if ((string) $cell['t'] === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            }

            $cells[$column] = $value;
        }

        return $cells;
    }

    private function excelDateToSqlDate(float $serial): string
    {
        $base = strtotime('1899-12-30');

        return date('Y-m-d', $base + ((int) round($serial) * 86400));
    }

    private function nullableCell(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
