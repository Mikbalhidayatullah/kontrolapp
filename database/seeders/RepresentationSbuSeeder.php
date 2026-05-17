<?php

namespace Database\Seeders;

use App\Models\RepresentationSbu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RepresentationSbuSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/representation_sbus.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$positionGroup, $unitLabel, $outsideCityAmount, $insideCityAmount] = array_pad(explode('|', $line), 4, '');

            RepresentationSbu::updateOrCreate(
                ['position_group' => trim($positionGroup)],
                [
                    'unit_label' => trim($unitLabel) !== '' ? trim($unitLabel) : 'OH',
                    'outside_city_amount' => (int) preg_replace('/\D/', '', $outsideCityAmount) ?: null,
                    'inside_city_over_8_hours_amount' => (int) preg_replace('/\D/', '', $insideCityAmount) ?: null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
