<?php

namespace Tests\Feature;

use App\Models\FlightTicketSbu;
use App\Models\User;
use Database\Seeders\FlightTicketSbuSeeder;
use Database\Seeders\TravelDestinationRegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerjadinAdditionalDestinationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_copies_existing_flight_rates_without_overwriting_or_duplicating_data(): void
    {
        $surabaya = $this->flight('SURABAYA', 19000000, 6000000);
        $jakarta = $this->flight('JAKARTA', 11000000, 7000000);
        $migration = require database_path('migrations/2026_11_10_090000_add_malang_and_banten_travel_destinations.php');
        $migration->up();
        $migration->up();

        foreach (['MALANG' => $surabaya, 'BANTEN' => $jakarta] as $city => $source) {
            $this->assertDatabaseCount('flight_ticket_sbus', 4);
            $this->assertDatabaseHas('flight_ticket_sbus', [
                'destination_city' => $city, 'business_amount' => $source->business_amount,
                'economy_amount' => $source->economy_amount, 'is_active' => true,
            ]);
        }
        FlightTicketSbu::where('destination_city', 'MALANG')->update(['economy_amount' => 1234567]);
        $migration->up();
        $this->assertDatabaseHas('flight_ticket_sbus', ['destination_city' => 'MALANG', 'economy_amount' => 1234567]);
        $this->assertSame(6000000, $surabaya->fresh()->economy_amount);
        $this->assertSame(7000000, $jakarta->fresh()->economy_amount);
    }

    public function test_seeded_destinations_appear_on_form_with_correct_province_and_flight_reference(): void
    {
        $this->seed([FlightTicketSbuSeeder::class, TravelDestinationRegionSeeder::class]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $options = collect($this->actingAs($admin)->get(route('add-perjadin'))->assertOk()->viewData('outsideDestinationOptions'))->keyBy('value');
        $this->assertSame('JAWA TIMUR', $options['Malang']['province_name']);
        $this->assertSame('BANTEN', $options['Banten']['province_name']);
        foreach (['Malang' => 'Surabaya', 'Banten' => 'Jakarta'] as $city => $source) {
            $this->assertSame($options[$source]['business_amount'], $options[$city]['business_amount']);
            $this->assertSame($options[$source]['economy_amount'], $options[$city]['economy_amount']);
        }
    }

    private function flight(string $city, int $business, int $economy): FlightTicketSbu
    {
        return FlightTicketSbu::create([
            'origin_city' => 'TERNATE', 'destination_city' => $city,
            'business_amount' => $business, 'economy_amount' => $economy,
            'is_active' => true, 'sort_order' => 1,
        ]);
    }
}
