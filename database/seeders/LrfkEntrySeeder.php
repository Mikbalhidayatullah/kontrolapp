<?php

namespace Database\Seeders;

use App\Models\LrfkEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LrfkEntrySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/lrfk_entries.json');

        if (
            ! File::exists($path)
            || LrfkEntry::query()->where('dataset_version', LrfkEntry::DATASET_LAMA)->exists()
        ) {
            return;
        }

        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $currentSubKegiatanId = null;

        foreach ($rows as $row) {
            $level = (string) ($row['level'] ?? '');
            $entry = LrfkEntry::query()->create([
                ...$row,
                'dataset_version' => LrfkEntry::DATASET_LAMA,
                'parent_id' => $level === 'rekening' ? $currentSubKegiatanId : null,
            ]);

            if ($level === 'sub_kegiatan') {
                $currentSubKegiatanId = $entry->id;
            }
        }
    }
}
