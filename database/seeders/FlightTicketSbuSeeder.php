<?php

namespace Database\Seeders;

use App\Models\FlightTicketSbu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FlightTicketSbuSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/flight_ticket_sbus_pp.txt');

        if (! File::exists($dataPath)) {
            return;
        }

        $rows = collect(preg_split('/\r\n|\r|\n/', trim(File::get($dataPath))))
            ->filter()
            ->values();

        foreach ($rows as $index => $line) {
            [$originCity, $destinationCity, $businessAmount, $economyAmount] = array_pad(explode('|', $line), 4, '');

            FlightTicketSbu::updateOrCreate(
                [
                    'origin_city' => trim($originCity),
                    'destination_city' => trim($destinationCity),
                ],
                [
                    'business_amount' => (int) preg_replace('/\D/', '', $businessAmount) ?: null,
                    'economy_amount' => (int) preg_replace('/\D/', '', $economyAmount) ?: null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
