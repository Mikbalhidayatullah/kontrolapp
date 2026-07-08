<?php

namespace App\Services;

use App\Models\TaxEntry;
use App\Models\TaxTuEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class TaxExcelExporter
{
    private const HEADERS = [
        'Tanggal',
        'No. Bukti',
        'Uraian',
        'Kode Rekening',
        'Nama Rekening',
        'ID Billing',
        'NTPN',
        'Penerimaan',
        'Pengeluaran',
        'Saldo',
    ];

    private const MONEY_COLUMNS = [8, 9, 10];

    private const TU_HEADERS = [
        'No',
        'Kode Kegiatan',
        'Nama Belanja',
        'Nomor SP2D',
        'Tanggal SP2D',
        'Pagu',
        'Jumlah yang diminta',
        'I',
        'Tgl Real I',
        'II',
        'Tgl Real II',
        'III',
        'Tgl Real III',
        'IV',
        'Tgl Real IV',
        'Sisa Dana TU',
        'Nomor',
        'Nilai',
        'Tgl',
        'Sisa Dana TU',
        'Nilai',
        'ID Billing',
        'NTPN',
        'Nilai',
        'ID Billing',
        'NTPN',
        'Nilai',
        'ID Billing',
        'NTPN',
        'Nilai',
        'ID Billing',
        'NTPN',
        'ket',
    ];

    public function export(Collection $entries, ?Collection $tuEntries = null): string
    {
        $tuEntries ??= collect();
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/pajak-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel tidak bisa dibuat.');
        }

        $sheetGroups = $this->sheetGroups($entries, $tuEntries);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheetGroups->count()));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetGroups));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml($sheetGroups->count()));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $sheetGroups->values()->each(function (array $sheet, int $index) use ($zip): void {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $sheet['type'] === 'tu'
                    ? $this->tuWorksheetXml($sheet['entries'])
                    : $this->worksheetXml($sheet['entries'])
            );
        });

        $zip->close();

        return $path;
    }

    private function sheetGroups(Collection $entries, Collection $tuEntries): Collection
    {
        if ($entries->isEmpty() && $tuEntries->isEmpty()) {
            return collect([
                [
                    'name' => 'Data Pajak',
                    'type' => 'gu_ls',
                    'entries' => collect(),
                ],
            ]);
        }

        $standardSheets = $entries
            ->groupBy('category')
            ->sortKeysUsing(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->map(fn (Collection $categoryEntries, string $category): array => [
                'name' => $category,
                'type' => 'gu_ls',
                'entries' => $categoryEntries
                    ->sortBy([
                        ['entry_date', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values(),
            ])
            ->values();

        $tuSheets = $tuEntries
            ->groupBy('category')
            ->sortKeysUsing(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->map(fn (Collection $categoryEntries, string $category): array => [
                'name' => $category,
                'type' => 'tu',
                'entries' => $categoryEntries
                    ->sortBy([
                        ['sp2d_date', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values(),
            ])
            ->values();

        return $standardSheets->merge($tuSheets)->values();
    }

    private function worksheetXml(Collection $entries): string
    {
        $rows = [];
        $merges = [];
        $rowNumber = 1;

        $headerCells = [];
        foreach (self::HEADERS as $index => $header) {
            $headerCells[] = $this->stringCell($index + 1, $header, 1);
        }
        $rows[] = $this->rowXml($rowNumber++, $headerCells, 38);

        $rows[] = $this->rowXml($rowNumber++, [
            $this->stringCell(1, '', 2),
            $this->stringCell(2, '', 2),
            $this->stringCell(3, '', 2),
            $this->stringCell(4, '', 2),
            $this->stringCell(5, '', 2),
            $this->stringCell(6, '', 2),
            $this->stringCell(7, 'Saldo Sebelumnya', 2),
            $this->numberCell(8, 0, 3),
            $this->numberCell(9, 0, 3),
            $this->numberCell(10, 0, 3),
        ], 16);

        foreach ($entries as $entry) {
            $rows[] = $this->rowXml($rowNumber++, $this->entryCells($entry), 22);
        }

        $receiptTotal = (int) $entries->sum('receipt_amount');
        $expenseTotal = (int) $entries->sum('expense_amount');
        $balanceTotal = (int) $entries->sum('balance_amount');

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, '', 5),
            $this->stringCell(2, 'T  o  t  a  l', 5),
            $this->stringCell(3, '', 5),
            $this->stringCell(4, '', 5),
            $this->stringCell(5, '', 5),
            $this->stringCell(6, '', 5),
            $this->stringCell(7, '', 5),
            $this->numberCell(8, $receiptTotal, 6),
            $this->numberCell(9, $expenseTotal, 6),
            $this->numberCell(10, $balanceTotal, 6),
        ], 19);
        $merges[] = 'B'.$rowNumber.':G'.$rowNumber;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= $this->columnsXml();
        $xml .= '<sheetData>'.implode('', $rows).'</sheetData>';

        if ($merges !== []) {
            $xml .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $merge) {
                $xml .= '<mergeCell ref="'.$merge.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function tuWorksheetXml(Collection $entries): string
    {
        $rows = [];
        $merges = [];
        $rowNumber = 1;

        $headerCells = [];
        foreach (self::TU_HEADERS as $index => $header) {
            $headerCells[] = $this->stringCell($index + 1, $header, 1);
        }
        $rows[] = $this->rowXml($rowNumber++, $headerCells, 34);

        $subHeaderCells = [];
        foreach (range(1, count(self::TU_HEADERS)) as $column) {
            $subHeaderCells[] = $this->stringCell($column, '', 5);
        }
        $subHeaderCells[7] = $this->stringCell(8, 'Realisasi', 5);
        $subHeaderCells[16] = $this->stringCell(17, 'Surat Tanda Setoran', 5);
        $subHeaderCells[20] = $this->stringCell(21, 'PPN', 5);
        $subHeaderCells[23] = $this->stringCell(24, 'PPH 21', 5);
        $subHeaderCells[26] = $this->stringCell(27, 'PPH 22', 5);
        $subHeaderCells[29] = $this->stringCell(30, 'PPH 23', 5);
        $rows[] = $this->rowXml($rowNumber++, $subHeaderCells, 20);
        $merges = [
            'H2:O2',
            'Q2:T2',
            'U2:W2',
            'X2:Z2',
            'AA2:AC2',
            'AD2:AF2',
        ];

        $number = 1;
        foreach ($entries as $entry) {
            $rows[] = $this->rowXml($rowNumber, $this->tuEntryCells($entry, $number++, $rowNumber), 25);
            $rowNumber++;
        }

        $rows[] = $this->rowXml($rowNumber, $this->tuTotalCells($entries, $rowNumber), 20);
        $merges[] = 'A'.$rowNumber.':F'.$rowNumber;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= $this->tuColumnsXml();
        $xml .= '<sheetData>'.implode('', $rows).'</sheetData>';
        $xml .= '<mergeCells count="'.count($merges).'">';
        foreach ($merges as $merge) {
            $xml .= '<mergeCell ref="'.$merge.'"/>';
        }
        $xml .= '</mergeCells>';
        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function tuEntryCells(TaxTuEntry $entry, int $number, int $rowNumber): array
    {
        $tuBalance = $entry->tuBalance();
        $depositBalance = $entry->depositBalance();

        return [
            $this->numberCell(1, $number, 8),
            $this->stringCell(2, $entry->kode_kegiatan ?: '', 7),
            $this->stringCell(3, $entry->nama_belanja ?: '', 7),
            $this->stringCell(4, $entry->sp2d_number ?: '', 7),
            $this->stringCell(5, optional($entry->sp2d_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->numberCell(6, (int) $entry->pagu_amount, 4),
            $this->numberCell(7, (int) $entry->requested_amount, 4),
            $this->numberCell(8, (int) $entry->realization_1_amount, 4),
            $this->stringCell(9, optional($entry->realization_1_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->numberCell(10, (int) $entry->realization_2_amount, 4),
            $this->stringCell(11, optional($entry->realization_2_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->numberCell(12, (int) $entry->realization_3_amount, 4),
            $this->stringCell(13, optional($entry->realization_3_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->numberCell(14, (int) $entry->realization_4_amount, 4),
            $this->stringCell(15, optional($entry->realization_4_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->formulaCell(16, '=G'.$rowNumber.'-SUM(H'.$rowNumber.',J'.$rowNumber.',L'.$rowNumber.',N'.$rowNumber.')', $tuBalance, 4),
            $this->stringCell(17, $entry->deposit_letter_number ?: '', 7),
            $this->numberCell(18, (int) $entry->deposit_amount, 4),
            $this->stringCell(19, optional($entry->deposit_date)->translatedFormat('d/m/Y') ?: '', 8),
            $this->formulaCell(20, '=P'.$rowNumber.'-R'.$rowNumber, $depositBalance, 4),
            $this->numberCell(21, (int) $entry->ppn_amount, 4),
            $this->stringCell(22, $entry->ppn_billing_id ?: '', 8),
            $this->stringCell(23, $entry->ppn_ntpn ?: '', 8),
            $this->numberCell(24, (int) $entry->pph21_amount, 4),
            $this->stringCell(25, $entry->pph21_billing_id ?: '', 8),
            $this->stringCell(26, $entry->pph21_ntpn ?: '', 8),
            $this->numberCell(27, (int) $entry->pph22_amount, 4),
            $this->stringCell(28, $entry->pph22_billing_id ?: '', 8),
            $this->stringCell(29, $entry->pph22_ntpn ?: '', 8),
            $this->numberCell(30, (int) $entry->pph23_amount, 4),
            $this->stringCell(31, $entry->pph23_billing_id ?: '', 8),
            $this->stringCell(32, $entry->pph23_ntpn ?: '', 8),
            $this->stringCell(33, $entry->notes ?: '', 7),
        ];
    }

    private function tuTotalCells(Collection $entries, int $rowNumber): array
    {
        return [
            $this->stringCell(1, 'Total', 5),
            $this->stringCell(2, '', 5),
            $this->stringCell(3, '', 5),
            $this->stringCell(4, '', 5),
            $this->stringCell(5, '', 5),
            $this->stringCell(6, '', 5),
            $this->numberCell(7, (int) $entries->sum('requested_amount'), 6),
            $this->numberCell(8, (int) $entries->sum('realization_1_amount'), 6),
            $this->stringCell(9, '', 5),
            $this->numberCell(10, (int) $entries->sum('realization_2_amount'), 6),
            $this->stringCell(11, '', 5),
            $this->numberCell(12, (int) $entries->sum('realization_3_amount'), 6),
            $this->stringCell(13, '', 5),
            $this->numberCell(14, (int) $entries->sum('realization_4_amount'), 6),
            $this->stringCell(15, '', 5),
            $this->formulaCell(16, '=SUM(P3:P'.max(3, $rowNumber - 1).')', (int) $entries->sum(fn (TaxTuEntry $entry): int => $entry->tuBalance()), 6),
            $this->stringCell(17, '', 5),
            $this->numberCell(18, (int) $entries->sum('deposit_amount'), 6),
            $this->stringCell(19, '', 5),
            $this->formulaCell(20, '=SUM(T3:T'.max(3, $rowNumber - 1).')', (int) $entries->sum(fn (TaxTuEntry $entry): int => $entry->depositBalance()), 6),
            $this->numberCell(21, (int) $entries->sum('ppn_amount'), 6),
            $this->stringCell(22, '', 5),
            $this->stringCell(23, '', 5),
            $this->numberCell(24, (int) $entries->sum('pph21_amount'), 6),
            $this->stringCell(25, '', 5),
            $this->stringCell(26, '', 5),
            $this->numberCell(27, (int) $entries->sum('pph22_amount'), 6),
            $this->stringCell(28, '', 5),
            $this->stringCell(29, '', 5),
            $this->numberCell(30, (int) $entries->sum('pph23_amount'), 6),
            $this->stringCell(31, '', 5),
            $this->stringCell(32, '', 5),
            $this->stringCell(33, '', 5),
        ];
    }

    private function entryCells(TaxEntry $entry): array
    {
        return [
            $this->stringCell(1, optional($entry->entry_date)->translatedFormat('d F Y') ?: '', 7),
            $this->stringCell(2, $entry->proof_number ?: '', 7),
            $this->stringCell(3, $entry->description ?: '', 7),
            $this->stringCell(4, $entry->account_code ?: '', 7),
            $this->stringCell(5, $entry->account_name ?: '', 8),
            $this->stringCell(6, $entry->billing_id ?: '', 8),
            $this->stringCell(7, $entry->ntpn ?: '', 8),
            $this->numberCell(8, (int) $entry->receipt_amount, 4),
            $this->numberCell(9, (int) $entry->expense_amount, 4),
            $this->numberCell(10, (int) $entry->balance_amount, 4),
        ];
    }

    private function rowXml(int $rowNumber, array $cells, ?int $height = null): string
    {
        return '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>'.implode('', $cells).'</row>';
    }

    private function stringCell(int $column, string $value, int $style = 0): string
    {
        return '<c t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(int $column, int $value, int $style = 0): string
    {
        return '<c'.($style ? ' s="'.$style.'"' : '').'><v>'.$value.'</v></c>';
    }

    private function formulaCell(int $column, string $formula, int $value, int $style = 0): string
    {
        return '<c'.($style ? ' s="'.$style.'"' : '').'><f>'.$this->escape(ltrim($formula, '=')).'</f><v>'.$value.'</v></c>';
    }

    private function columnsXml(): string
    {
        $widths = [
            1 => 13,
            2 => 22,
            3 => 46,
            4 => 22,
            5 => 14,
            6 => 20,
            7 => 26,
            8 => 18,
            9 => 18,
            10 => 18,
        ];

        $xml = '<cols>';
        foreach ($widths as $column => $width) {
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function tuColumnsXml(): string
    {
        $widths = [
            1 => 6,
            2 => 24,
            3 => 38,
            4 => 32,
            5 => 14,
            6 => 16,
            7 => 18,
            8 => 14,
            9 => 14,
            10 => 14,
            11 => 14,
            12 => 14,
            13 => 14,
            14 => 14,
            15 => 14,
            16 => 18,
            17 => 22,
            18 => 16,
            19 => 14,
            20 => 18,
            21 => 16,
            22 => 22,
            23 => 24,
            24 => 16,
            25 => 22,
            26 => 24,
            27 => 16,
            28 => 22,
            29 => 24,
            30 => 18,
            31 => 22,
            32 => 24,
            33 => 18,
        ];

        $xml = '<cols>';
        foreach ($widths as $column => $width) {
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function safeSheetName(string $name): string
    {
        $name = trim(preg_replace('/[\[\]\:\*\?\/\\\\]+/', ' ', $name) ?: 'Sheet');
        $name = $name !== '' ? $name : 'Sheet';

        return mb_substr($name, 0, 31);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $sheetOverrides = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
'.$sheetOverrides.'
</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    }

    private function workbookXml(Collection $sheetGroups): string
    {
        $sheets = '';
        $usedNames = [];

        $sheetGroups->values()->each(function (array $sheet, int $index) use (&$sheets, &$usedNames): void {
            $name = $this->uniqueSheetName($this->safeSheetName($sheet['name']), $usedNames);
            $sheets .= '<sheet name="'.$this->escape($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        });

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>'.$sheets.'</sheets>
</workbook>';
    }

    private function uniqueSheetName(string $name, array &$usedNames): string
    {
        $base = $name;
        $counter = 1;

        while (in_array(mb_strtolower($name), $usedNames, true)) {
            $suffix = ' '.$counter++;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        $usedNames[] = mb_strtolower($name);

        return $name;
    }

    private function workbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
'.$relationships.'
</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot;* #,##0;[Red]&quot;Rp&quot;* #,##0;&quot;Rp&quot;* -"/></numFmts>
<fonts count="5">
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="12"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="9"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
</fonts>
<fills count="4">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFCE4D6"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="9">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="bottom"/></xf>
<xf numFmtId="164" fontId="2" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="bottom"/></xf>
<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="3" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export Data Pajak</dc:title>
<dc:creator>Kontrol App</dc:creator>
<cp:lastModifiedBy>Kontrol App</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>
</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
<Application>Kontrol App</Application>
</Properties>';
    }
}
