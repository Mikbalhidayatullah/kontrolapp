<?php

namespace Database\Seeders;

use App\Models\NationalLodgingSbu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NationalLodgingSbuSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/national_lodging_sbus.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$provinceName, $unitLabel, $headRegionAmount, $memberEselon2Amount, $eselon3Gol4Amount, $eselon4Gol321Amount] = array_pad(explode('|', $line), 6, '');

            NationalLodgingSbu::updateOrCreate(
                ['province_name' => trim($provinceName)],
                [
                    'unit_label' => trim($unitLabel) !== '' ? trim($unitLabel) : 'OH',
                    'head_region_amount' => (int) preg_replace('/\D/', '', $headRegionAmount) ?: null,
                    'member_eselon_2_amount' => (int) preg_replace('/\D/', '', $memberEselon2Amount) ?: null,
                    'eselon_3_gol_4_amount' => (int) preg_replace('/\D/', '', $eselon3Gol4Amount) ?: null,
                    'eselon_4_gol_3_2_1_amount' => (int) preg_replace('/\D/', '', $eselon4Gol321Amount) ?: null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
