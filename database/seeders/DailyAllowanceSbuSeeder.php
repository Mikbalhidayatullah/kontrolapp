<?php

namespace Database\Seeders;

use App\Models\DailyAllowanceSbu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DailyAllowanceSbuSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/daily_allowance_sbus.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$provinceName, $unitLabel, $outsideCityAmount, $sofifiAmount, $diklatAmount] = array_pad(explode('|', $line), 5, '');

            DailyAllowanceSbu::updateOrCreate(
                ['province_name' => trim($provinceName)],
                [
                    'unit_label' => trim($unitLabel) !== '' ? trim($unitLabel) : 'OH',
                    'outside_city_amount' => (int) preg_replace('/\D/', '', $outsideCityAmount) ?: null,
                    'sofifi_inside_city_over_8_hours_amount' => (int) preg_replace('/\D/', '', $sofifiAmount) ?: null,
                    'diklat_amount' => (int) preg_replace('/\D/', '', $diklatAmount) ?: null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
