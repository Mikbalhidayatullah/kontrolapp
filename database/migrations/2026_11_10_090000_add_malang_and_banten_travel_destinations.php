<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ([
                ['city' => 'MALANG', 'reference' => 'SURABAYA', 'province' => 'JAWA TIMUR'],
                ['city' => 'BANTEN', 'reference' => 'JAKARTA', 'province' => 'BANTEN'],
            ] as $destination) {
                $references = DB::table('flight_ticket_sbus')
                    ->whereRaw('UPPER(TRIM(destination_city)) = ?', [$destination['reference']])
                    ->where('is_active', true)
                    ->get();

                foreach ($references as $reference) {
                    $exists = DB::table('flight_ticket_sbus')
                        ->where('origin_city', $reference->origin_city)
                        ->whereRaw('UPPER(TRIM(destination_city)) = ?', [$destination['city']])
                        ->exists();
                    if (! $exists) {
                        DB::table('flight_ticket_sbus')->insert([
                            'origin_city' => $reference->origin_city,
                            'destination_city' => $destination['city'],
                            'business_amount' => $reference->business_amount,
                            'economy_amount' => $reference->economy_amount,
                            'is_active' => true,
                            'sort_order' => ((int) DB::table('flight_ticket_sbus')->max('sort_order')) + 1,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }

                $region = DB::table('travel_destination_regions')
                    ->whereRaw('UPPER(TRIM(city_name)) = ?', [$destination['city']])
                    ->first();
                if ($region) {
                    DB::table('travel_destination_regions')->where('id', $region->id)->update([
                        'province_name' => $destination['province'], 'is_active' => true, 'updated_at' => now(),
                    ]);
                } else {
                    DB::table('travel_destination_regions')->insert([
                        'city_name' => $destination['city'], 'province_name' => $destination['province'],
                        'is_active' => true,
                        'sort_order' => ((int) DB::table('travel_destination_regions')->max('sort_order')) + 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Keep destination references used by existing Perjadin records.
    }
};
