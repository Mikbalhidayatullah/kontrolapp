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

        if (! File::exists($path) || LrfkEntry::query()->exists()) {
            return;
        }

        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $now = now();

        foreach (array_chunk($rows, 100) as $chunk) {
            LrfkEntry::query()->insert(array_map(fn (array $row): array => [
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }
}
