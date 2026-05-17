<?php

namespace Database\Seeders;

use App\Models\TravelDestinationRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TravelDestinationRegionSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/travel_destination_regions.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$cityName, $provinceName] = array_pad(explode('|', $line), 2, '');

            TravelDestinationRegion::updateOrCreate(
                ['city_name' => trim($cityName)],
                [
                    'province_name' => trim($provinceName),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
