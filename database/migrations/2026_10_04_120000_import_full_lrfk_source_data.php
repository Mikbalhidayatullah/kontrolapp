<?php

use App\Services\LrfkSourceDatasetImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private const DATASETS = [
        'perubahan' => 'seeders/data/lrfk_full_perubahan.json',
        'data_olahan' => 'seeders/data/lrfk_full_data_olahan.json',
    ];

    public function up(): void
    {
        $importer = app(LrfkSourceDatasetImporter::class);

        foreach (self::DATASETS as $version => $relativePath) {
            $path = database_path($relativePath);
            if (! File::exists($path)) {
                throw new RuntimeException('Dataset lengkap LRFK tidak ditemukan: '.$path);
            }

            $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
            $importer->sync($version, $rows);
        }
    }

    public function down(): void
    {
        DB::table('lrfk_entry_details')->delete();
    }
};
