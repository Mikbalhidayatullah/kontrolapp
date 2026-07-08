<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $badRow = DB::table('lrfk_entries')
            ->where('kode_rekening', '5.1.02.04.001.00001')
            ->where('pagu_anggaran', 345635950)
            ->where('contract_value', 38232000)
            ->where('program_kegiatan', 'like', 'Belanja Perjalanan Dinas Biasa 11/02/2026%')
            ->first();

        if (! $badRow) {
            return;
        }

        $detailRows = [
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Dinas Pendidikan dan Kebudayaan -Kota Ternate',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2026',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Dinas Pariwisata-Halmahera Timur',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2027',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Dinas Pendidikan dan Kebudayaan-Halmahera Barat',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2028',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Cabang Dinas-Halmahera Tengah',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2029',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Cabang Dinas-Kota Tidore Kepulauan',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2030',
            ],
            [
                'contract_number_date' => '12/02/2026 s/d 14/02/2026',
                'implementer' => 'Dinas Pendidikan dan Kebudayaan-Pulau Morotai',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2031',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Dinas Pariwisata Kebudayaan dan Ekonomi Kreatif-Halmahera Selatan',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2032',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 14/02/2026',
                'implementer' => 'Dinas Pariwisata dan Kebudayaan-Kepulauan Sula',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2033',
            ],
            [
                'contract_number_date' => '11/02/2026 s/d 13/02/2026',
                'implementer' => 'Cabang Dinas-Halmahera Utara',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2034',
            ],
        ];

        DB::table('lrfk_entries')
            ->where('sort_order', '>', $badRow->sort_order)
            ->increment('sort_order', count($detailRows));

        DB::table('lrfk_entries')
            ->where('id', $badRow->id)
            ->update([
                'program_kegiatan' => 'Belanja Perjalanan Dinas Biasa',
                'contract_number_date' => '11/02/2026 s/d 14/02/2026',
                'implementer' => 'Dinas Pariwisata-Pulau Taliabu',
                'output' => 'Melaksanakan Monitoring dan Evaluasi Pokok Pikiran Kebudayaan Daerah (PPKD) Tahun Anggaran 2025',
                'volume' => '1 Dokumen',
                'unit' => 'Dokumen',
                'updated_at' => now(),
            ]);

        $now = now();
        $insertRows = [];

        foreach ($detailRows as $index => $detailRow) {
            $insertRows[] = [
                'sort_order' => $badRow->sort_order + $index + 1,
                'level' => 'rekening',
                'kode' => '',
                'kode_rekening' => '',
                'program_kegiatan' => '',
                'pagu_anggaran' => 0,
                'contract_value' => 0,
                'contract_number_date' => $detailRow['contract_number_date'],
                'implementer' => $detailRow['implementer'],
                'output' => $detailRow['output'],
                'volume' => '',
                'unit' => '',
                'financial_realization' => 0,
                'financial_percent' => 0,
                'physical_percent' => 0,
                'location' => '',
                'notes' => '',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('lrfk_entries')->insert($insertRows);
    }

    public function down(): void
    {
        $fixedRow = DB::table('lrfk_entries')
            ->where('kode_rekening', '5.1.02.04.001.00001')
            ->where('pagu_anggaran', 345635950)
            ->where('contract_value', 38232000)
            ->where('program_kegiatan', 'Belanja Perjalanan Dinas Biasa')
            ->where('contract_number_date', '11/02/2026 s/d 14/02/2026')
            ->first();

        if (! $fixedRow) {
            return;
        }

        DB::table('lrfk_entries')
            ->where('level', 'rekening')
            ->where('kode_rekening', '')
            ->where('program_kegiatan', '')
            ->whereBetween('sort_order', [$fixedRow->sort_order + 1, $fixedRow->sort_order + 9])
            ->delete();

        DB::table('lrfk_entries')
            ->where('sort_order', '>', $fixedRow->sort_order + 9)
            ->decrement('sort_order', 9);

        DB::table('lrfk_entries')
            ->where('id', $fixedRow->id)
            ->update([
                'program_kegiatan' => 'Belanja Perjalanan Dinas Biasa',
                'contract_number_date' => null,
                'implementer' => null,
                'output' => null,
                'volume' => null,
                'unit' => null,
                'updated_at' => now(),
            ]);
    }
};
