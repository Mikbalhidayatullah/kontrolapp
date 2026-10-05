<?php

namespace App\Services;

use App\Models\LrfkEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LrfkSourceDatasetImporter
{
    private const SOURCE_FIELDS = [
        'source_row',
        'contract_value',
        'contract_number_date',
        'implementer',
        'output',
        'volume',
        'unit',
        'financial_realization',
        'financial_percent',
        'physical_percent',
        'budget_balance',
        'cash_plan_october',
        'cash_plan_november',
        'cash_plan_december',
        'cash_plan_quarter',
        'location',
        'notes',
        'variance',
        'variance_note',
    ];

    private const DETAIL_FIELDS = [
        'source_row',
        'sort_order',
        'contract_value',
        'contract_number_date',
        'implementer',
        'output',
        'volume',
        'unit',
        'financial_realization',
        'financial_percent',
        'physical_percent',
        'budget_balance',
        'cash_plan_october',
        'cash_plan_november',
        'cash_plan_december',
        'cash_plan_quarter',
        'location',
        'notes',
        'variance',
        'variance_note',
    ];

    public function sync(string $version, array $rows): void
    {
        if (! in_array($version, [LrfkEntry::DATASET_PERUBAHAN, LrfkEntry::DATASET_DATA_OLAHAN], true)) {
            throw new RuntimeException('Versi dataset LRFK sumber tidak didukung: '.$version);
        }

        DB::transaction(function () use ($version, $rows): void {
            $entries = LrfkEntry::query()
                ->where('dataset_version', $version)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $pools = [];
            foreach ($entries as $entry) {
                $pools[$this->identityKey($entry->toArray(), $entry->parent_id)][] = $entry;
            }

            $entriesBySourceRow = [];
            $detailRows = [];
            $currentSubKegiatanId = null;

            foreach ($rows as $row) {
                $kind = (string) ($row['kind'] ?? '');
                $sourceRow = (int) ($row['source_row'] ?? 0);

                if ($kind === 'detail') {
                    $detailRows[] = $row;

                    continue;
                }

                if ($kind !== 'entry') {
                    throw new RuntimeException('Jenis baris sumber tidak valid pada baris '.$sourceRow.'.');
                }

                $level = (string) ($row['level'] ?? '');
                $parentId = $level === 'rekening' ? $currentSubKegiatanId : null;
                if ($level === 'rekening' && $parentId === null) {
                    throw new RuntimeException('Rekening sumber pada baris '.$sourceRow.' tidak memiliki sub kegiatan induk.');
                }

                $key = $this->identityKey($row, $parentId);
                $pool = $pools[$key] ?? [];
                $entry = array_shift($pool);
                $pools[$key] = $pool;
                if (! $entry instanceof LrfkEntry) {
                    throw new RuntimeException('Baris utama LRFK sumber '.$sourceRow.' tidak menemukan pasangan data yang stabil.');
                }

                $entry->forceFill($this->onlyFields($row, self::SOURCE_FIELDS))->save();
                $entriesBySourceRow[$sourceRow] = $entry;

                if ($level === 'sub_kegiatan') {
                    $currentSubKegiatanId = $entry->id;
                }
            }

            foreach ($detailRows as $row) {
                $sourceRow = (int) ($row['source_row'] ?? 0);
                $parentSourceRow = (int) ($row['parent_source_row'] ?? 0);
                $owner = $entriesBySourceRow[$parentSourceRow] ?? null;

                if (! $owner instanceof LrfkEntry || $owner->level !== 'rekening') {
                    throw new RuntimeException('Baris rincian '.$sourceRow.' tidak memiliki rekening induk sumber yang valid.');
                }
            }

            DB::table('lrfk_entry_details')
                ->whereIn('lrfk_entry_id', $entries->pluck('id'))
                ->delete();

            $now = now();
            foreach ($detailRows as $row) {
                $owner = $entriesBySourceRow[(int) $row['parent_source_row']];

                DB::table('lrfk_entry_details')->insert([
                    'lrfk_entry_id' => $owner->id,
                    ...$this->onlyFields($row, self::DETAIL_FIELDS),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function identityKey(array $row, mixed $parentId): string
    {
        return implode("\x1F", [
            (string) ($row['level'] ?? ''),
            trim((string) ($row['kode'] ?? '')),
            trim((string) ($row['kode_rekening'] ?? '')),
            trim((string) ($row['program_kegiatan'] ?? '')),
            (string) ((int) ($row['pagu_anggaran'] ?? 0)),
            (string) ($parentId ?? ''),
        ]);
    }

    private function onlyFields(array $row, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $row[$field] ?? match ($field) {
                'source_row' => null,
                'contract_number_date', 'implementer', 'output', 'volume', 'unit',
                'location', 'notes', 'variance_note' => null,
                default => 0,
            };
        }

        return $values;
    }
}
