<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DATASET_PATH = 'seeders/data/lrfk_entries_perubahan.json';

    public function up(): void
    {
        if (! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            Schema::table('lrfk_entries', function (Blueprint $table): void {
                $table->string('dataset_version', 20)
                    ->default('lama')
                    ->after('parent_id');
                $table->index(
                    ['dataset_version', 'level', 'sort_order'],
                    'lrfk_entries_dataset_level_sort_index'
                );
            });
        }

        if (DB::table('lrfk_entries')->where('dataset_version', 'perubahan')->exists()) {
            return;
        }

        $path = database_path(self::DATASET_PATH);
        if (! File::exists($path)) {
            throw new RuntimeException('Dataset LRFK Perubahan tidak ditemukan: '.$path);
        }

        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $now = now();
        $currentSubKegiatanId = null;

        DB::transaction(function () use ($rows, $now, &$currentSubKegiatanId): void {
            foreach ($rows as $row) {
                $level = (string) ($row['level'] ?? '');
                $parentId = $level === 'rekening' ? $currentSubKegiatanId : null;

                if ($level === 'rekening' && $parentId === null) {
                    throw new RuntimeException('Rekening LRFK Perubahan tidak memiliki sub kegiatan induk.');
                }

                $entryId = DB::table('lrfk_entries')->insertGetId([
                    ...$row,
                    'dataset_version' => 'perubahan',
                    'parent_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($level === 'sub_kegiatan') {
                    $currentSubKegiatanId = $entryId;
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            return;
        }

        DB::table('lrfk_entries')
            ->where('dataset_version', 'perubahan')
            ->delete();

        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->dropIndex('lrfk_entries_dataset_level_sort_index');
            $table->dropColumn('dataset_version');
        });
    }
};
