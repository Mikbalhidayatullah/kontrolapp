<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SavingAllocationSeeder::class);
        $this->call(TravelDestinationRegionSeeder::class);
        User::updateOrCreate(
            ['email' => 'admin@kontrol-app.test'],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'is_active' => true,
                'password' => Hash::make('Admin#2026!'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'bendahara@kontrol-app.test'],
            [
                'name' => 'Bendahara',
                'role' => 'bendahara',
                'is_active' => true,
                'password' => Hash::make('Bendahara#2026!'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'verifikator@kontrol-app.test'],
            [
                'name' => 'Verifikator',
                'role' => 'verifikator',
                'is_active' => true,
                'password' => Hash::make('Verifikator#2026!'),
            ]
        );

        $this->call(MonthlyDummyDataSeeder::class);
    }
}
