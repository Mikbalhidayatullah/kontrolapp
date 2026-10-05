<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DATASETS = [
        'perubahan' => 'seeders/data/lrfk_entries_perubahan.json',
        'data_olahan' => 'seeders/data/lrfk_entries_data_olahan.json',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            throw new RuntimeException('Kolom versi dataset LRFK belum tersedia.');
        }

        DB::transaction(function (): void {
            foreach (self::DATASETS as $version => $path) {
                $this->syncDataset($version, $path);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            return;
        }

        DB::transaction(function (): void {
            $this->deleteDataset('data_olahan');

            DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->where('level', 'rekening')
                ->where('pagu_anggaran', 0)
                ->delete();

            $this->resequenceDataset('perubahan');
        });
    }

    private function syncDataset(string $version, string $relativePath): void
    {
        $path = database_path($relativePath);
        if (! File::exists($path)) {
            throw new RuntimeException('Dataset LRFK tidak ditemukan: '.$path);
        }

        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        if (! DB::table('lrfk_entries')->where('dataset_version', $version)->exists()) {
            $this->insertCompleteDataset($version, $rows);

            return;
        }

        $existingCounts = [];
        $parentIdsByCode = [];

        DB::table('lrfk_entries as entries')
            ->leftJoin('lrfk_entries as parents', 'parents.id', '=', 'entries.parent_id')
            ->where('entries.dataset_version', $version)
            ->orderBy('entries.sort_order')
            ->orderBy('entries.id')
            ->get([
                'entries.id',
                'entries.level',
                'entries.kode',
                'entries.kode_rekening',
                'entries.program_kegiatan',
                'entries.pagu_anggaran',
                'parents.kode_rekening as parent_code',
            ])
            ->each(function ($entry) use (&$existingCounts, &$parentIdsByCode): void {
                $key = $this->rowKey([
                    'level' => $entry->level,
                    'kode' => $entry->kode,
                    'kode_rekening' => $entry->kode_rekening,
                    'program_kegiatan' => $entry->program_kegiatan,
                    'pagu_anggaran' => $entry->pagu_anggaran,
                ], (string) ($entry->parent_code ?? ''));

                $existingCounts[$key] = ($existingCounts[$key] ?? 0) + 1;

                if ($entry->level === 'sub_kegiatan') {
                    $parentIdsByCode[(string) $entry->kode_rekening] = (int) $entry->id;
                }
            });

        $seenCounts = [];
        $currentSubKegiatanCode = '';
        $now = now();

        foreach ($rows as $row) {
            $level = (string) ($row['level'] ?? '');
            if ($level === 'sub_kegiatan') {
                $currentSubKegiatanCode = (string) ($row['kode_rekening'] ?? '');
            }

            $parentCode = $level === 'rekening' ? $currentSubKegiatanCode : '';
            $key = $this->rowKey($row, $parentCode);
            $seenCounts[$key] = ($seenCounts[$key] ?? 0) + 1;

            if ($seenCounts[$key] <= ($existingCounts[$key] ?? 0)) {
                continue;
            }

            $parentId = $level === 'rekening'
                ? ($parentIdsByCode[$parentCode] ?? null)
                : null;

            if ($level === 'rekening' && $parentId === null) {
                throw new RuntimeException('Rekening '.$version.' tidak memiliki sub kegiatan induk.');
            }

            $sortOrder = (int) ($row['sort_order'] ?? 0);
            if (
                DB::table('lrfk_entries')
                    ->where('dataset_version', $version)
                    ->where('sort_order', $sortOrder)
                    ->exists()
            ) {
                DB::table('lrfk_entries')
                    ->where('dataset_version', $version)
                    ->where('sort_order', '>=', $sortOrder)
                    ->increment('sort_order');
            }

            $entryId = DB::table('lrfk_entries')->insertGetId([
                ...$row,
                'dataset_version' => $version,
                'parent_id' => $parentId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($level === 'sub_kegiatan') {
                $parentIdsByCode[$currentSubKegiatanCode] = $entryId;
            }
        }
    }

    private function insertCompleteDataset(string $version, array $rows): void
    {
        $now = now();
        $currentSubKegiatanId = null;

        foreach ($rows as $row) {
            $level = (string) ($row['level'] ?? '');
            $parentId = $level === 'rekening' ? $currentSubKegiatanId : null;

            if ($level === 'rekening' && $parentId === null) {
                throw new RuntimeException('Rekening '.$version.' tidak memiliki sub kegiatan induk.');
            }

            $entryId = DB::table('lrfk_entries')->insertGetId([
                ...$row,
                'dataset_version' => $version,
                'parent_id' => $parentId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($level === 'sub_kegiatan') {
                $currentSubKegiatanId = $entryId;
            }
        }
    }

    private function rowKey(array $row, string $parentCode): string
    {
        return implode("\x1F", [
            (string) ($row['level'] ?? ''),
            (string) ($row['kode'] ?? ''),
            (string) ($row['kode_rekening'] ?? ''),
            (string) ($row['program_kegiatan'] ?? ''),
            (string) ((int) ($row['pagu_anggaran'] ?? 0)),
            $parentCode,
        ]);
    }

    private function deleteDataset(string $version): void
    {
        $entryIds = DB::table('lrfk_entries')
            ->where('dataset_version', $version)
            ->pluck('id');

        if (
            $entryIds->isNotEmpty()
            && Schema::hasColumn('perjadin_entries', 'lrfk_entry_id')
        ) {
            DB::table('perjadin_entries')
                ->whereIn('lrfk_entry_id', $entryIds)
                ->update(['lrfk_entry_id' => null]);
        }

        DB::table('lrfk_entries')
            ->where('dataset_version', $version)
            ->where('level', 'rekening')
            ->delete();

        DB::table('lrfk_entries')
            ->where('dataset_version', $version)
            ->delete();
    }

    private function resequenceDataset(string $version): void
    {
        DB::table('lrfk_entries')
            ->where('dataset_version', $version)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id, $index): void {
                DB::table('lrfk_entries')
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            });
    }
};
