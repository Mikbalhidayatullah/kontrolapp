<?php

namespace Database\Seeders;

use App\Models\LocalTransportSbu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LocalTransportSbuSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/local_transport_sbus_malut.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $destinationRegencyMap = [
            'KOTA TERNATE' => 'Kota Ternate',
            'KOTA TIDORE KEPULAUAN' => 'Kota Tidore Kepulauan',
            'KABUPATEN HALMAHERA TENGAH' => 'Kabupaten Halmahera Tengah',
            'KABUPATEN HALMAHERA BARAT' => 'Kabupaten Halmahera Barat',
            'KABUPATEN HALMAHERA TIMUR' => 'Kabupaten Halmahera Timur',
            'KABUPATEN PULAU MOROTAI' => 'Kabupaten Pulau Morotai',
            'KABUPATEN HALMAHERA UTARA' => 'Kabupaten Halmahera Utara',
            'KABUPATEN HALMAHERA SELATAN' => 'Kabupaten Halmahera Selatan',
            'KABUPATEN KEPULAUAN SULA' => 'Kabupaten Kepulauan Sula',
            'KABUPATEN PULAU TALIABU' => 'Kabupaten Pulau Taliabu',
        ];

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$areaName, $rowCode, $originLabel, $destinationLabel, $amount, $notes] = array_pad(explode('|', $line), 6, '');

            $destinationRegency = $destinationRegencyMap[$areaName] ?? $areaName;
            $payload = [
                'component_key' => 'local_transport_other',
                'area_name' => $areaName,
                'row_code' => $rowCode,
                'origin_regency' => $destinationRegency,
                'origin_label' => $originLabel,
                'destination_regency' => $destinationRegency,
                'destination_label' => $destinationLabel,
                'route_name' => trim($originLabel.' -> '.$destinationLabel),
                'unit_label' => 'Orang/kali',
                'amount' => (int) preg_replace('/\D/', '', $amount),
                'notes' => $notes !== '' ? $notes : 'One Way / PP',
                'is_active' => true,
                'sort_order' => $index + 1,
            ];

            LocalTransportSbu::updateOrCreate(
                [
                    'area_name' => $payload['area_name'],
                    'row_code' => $payload['row_code'],
                ],
                $payload,
            );
        }
    }
}
