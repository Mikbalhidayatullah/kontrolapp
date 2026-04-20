<?php

namespace Database\Seeders;

use App\Models\ControlEntry;
use App\Models\SavingAllocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonthlyDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('role', 'admin')->value('id') ?? User::query()->value('id');

        if (! $adminId) {
            return;
        }

        foreach ($this->savingRows() as $row) {
            $existing = SavingAllocation::query()
                ->where('period_month', $row['period_month'])
                ->where('period_year', $row['period_year'])
                ->where('source_name', $row['source_name'])
                ->where('amount', $row['amount'])
                ->where('sort_order', $row['sort_order'])
                ->first();

            if (! $existing) {
                SavingAllocation::query()->create($row);
            }
        }

        foreach ($this->controlRows($adminId) as $row) {
            ControlEntry::query()->firstOrCreate(
                [
                    'entry_date' => $row['entry_date'],
                    'fund_source' => $row['fund_source'],
                    'purpose' => $row['purpose'],
                ],
                $row
            );
        }
    }

    private function savingRows(): array
    {
        return [
            [
                'period_month' => 1,
                'period_year' => 2026,
                'source_name' => 'DANSAV MAKMUR',
                'amount' => 20000000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'period_month' => 1,
                'period_year' => 2026,
                'source_name' => 'DANSAV MAKMUR',
                'amount' => 5000000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'period_month' => 1,
                'period_year' => 2026,
                'source_name' => 'DANSAV UPY',
                'amount' => 10000000,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'period_month' => 1,
                'period_year' => 2026,
                'source_name' => 'DANSAV GU',
                'amount' => 10000000,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'period_month' => 3,
                'period_year' => 2026,
                'source_name' => 'DANSAV MAKMUR',
                'amount' => 25000000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'period_month' => 3,
                'period_year' => 2026,
                'source_name' => 'DANSAV UPY',
                'amount' => 12000000,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'period_month' => 3,
                'period_year' => 2026,
                'source_name' => 'DANSAV TERAS ATIQAH',
                'amount' => 10000000,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'period_month' => 3,
                'period_year' => 2026,
                'source_name' => 'DANSAV GU',
                'amount' => 10000000,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];
    }

    private function controlRows(int $adminId): array
    {
        return [
            [
                'entry_date' => '2026-01-05',
                'handover_time' => 'Pagi',
                'amount_out' => 7500000,
                'amount_in' => 7500000,
                'third_party' => 'CV Sumber Jaya',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Ternate',
                'purpose' => 'Belanja operasional awal bulan',
                'fund_source' => 'DANSAV MAKMUR',
                'status' => 'LUNAS',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-01-11',
                'handover_time' => 'Siang',
                'amount_out' => 2200000,
                'amount_in' => 2200000,
                'third_party' => 'Fauzin Pratama',
                'receiving_officer' => 'Vivi',
                'appointed_official' => 'Vivi',
                'location' => 'Sofifi',
                'purpose' => 'ATK Januari',
                'fund_source' => 'VIVI',
                'status' => 'HUTANG',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-01-18',
                'handover_time' => 'Sore',
                'amount_out' => 4500000,
                'amount_in' => 4500000,
                'third_party' => 'SPBU Kota Baru',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Sofifi',
                'purpose' => 'BBM operasional kegiatan',
                'fund_source' => 'DANSAV UPY',
                'status' => 'LUNAS',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-01-26',
                'handover_time' => 'Malam',
                'amount_out' => 3000000,
                'amount_in' => 3000000,
                'third_party' => 'Toko Melati',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Ternate',
                'purpose' => 'Pembelian perlengkapan kegiatan',
                'fund_source' => 'YULIA',
                'status' => 'BAYAR SEBAGIAN',
                'partial_payment_amount' => 1000000,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-03-03',
                'handover_time' => 'Pagi',
                'amount_out' => 6800000,
                'amount_in' => 6800000,
                'third_party' => 'Toko Sejahtera',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Sofifi',
                'purpose' => 'Pengadaan konsumsi rapat besar',
                'fund_source' => 'DANSAV TERAS ATIQAH',
                'status' => 'LUNAS',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-03-09',
                'handover_time' => 'Siang',
                'amount_out' => 9000000,
                'amount_in' => 9000000,
                'third_party' => 'CV Mitra Karya',
                'receiving_officer' => 'Vivi',
                'appointed_official' => 'Vivi',
                'location' => 'Ternate',
                'purpose' => 'Pembayaran vendor perlengkapan',
                'fund_source' => 'DANSAV MAKMUR',
                'status' => 'LUNAS',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-03-15',
                'handover_time' => 'Sore',
                'amount_out' => 1500000,
                'amount_in' => 1500000,
                'third_party' => 'Toko Bintang',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Sofifi',
                'purpose' => 'Perlengkapan pendukung lapangan',
                'fund_source' => 'TERAS ATIQAH',
                'status' => 'HUTANG',
                'partial_payment_amount' => 0,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
            [
                'entry_date' => '2026-03-24',
                'handover_time' => 'Malam',
                'amount_out' => 2600000,
                'amount_in' => 2600000,
                'third_party' => 'Koperasi Mitra',
                'receiving_officer' => 'Yulia',
                'appointed_official' => 'Vivi',
                'location' => 'Ternate',
                'purpose' => 'Belanja konsumsi tambahan',
                'fund_source' => 'DANSAV GU',
                'status' => 'BAYAR SEBAGIAN',
                'partial_payment_amount' => 1300000,
                'proof_path' => null,
                'proof_original_name' => null,
                'created_by' => $adminId,
            ],
        ];
    }
}
