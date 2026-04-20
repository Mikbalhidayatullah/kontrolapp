<?php

namespace Database\Seeders;

use App\Models\SavingAllocation;
use Illuminate\Database\Seeder;

class SavingAllocationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['source_name' => 'DANSAV MAKMUR', 'amount' => 90000000, 'sort_order' => 1],
            ['source_name' => 'DANSAV UPY', 'amount' => 73425827, 'sort_order' => 2],
            ['source_name' => 'DANSAV TERAS ATIQAH', 'amount' => 54664000, 'sort_order' => 3],
            ['source_name' => 'DANSAV GU', 'amount' => 0, 'sort_order' => 4],
        ];

        foreach ($rows as $row) {
            SavingAllocation::query()->updateOrCreate(
                [
                    'period_month' => 2,
                    'period_year' => 2026,
                    'source_name' => $row['source_name'],
                ],
                [
                    'amount' => $row['amount'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
