<?php

namespace Tests\Feature;

use Database\Seeders\LrfkEntrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LrfkChangeDatasetMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_imports_the_complete_change_dataset(): void
    {
        $this->assertTrue(Schema::hasColumn('lrfk_entries', 'dataset_version'));

        $query = DB::table('lrfk_entries')->where('dataset_version', 'perubahan');

        $this->assertSame(349, $query->count());
        $this->assertSame(264, (clone $query)->where('level', 'rekening')->count());
        $this->assertSame(62, (clone $query)->where('level', 'sub_kegiatan')->count());
        $this->assertSame(
            783_068_733_634,
            (int) (clone $query)->where('level', 'dinas')->value('pagu_anggaran')
        );
        $this->assertSame(366_701_836_399, (int) (clone $query)->where('level', 'dinas')->value('contract_value'));
        $this->assertSame(357_117_006_436, (int) (clone $query)->where('level', 'dinas')->value('financial_realization'));
    }

    public function test_old_dataset_seeder_still_runs_when_change_dataset_exists(): void
    {
        $this->seed(LrfkEntrySeeder::class);

        $this->assertSame(
            350,
            DB::table('lrfk_entries')->where('dataset_version', 'lama')->count()
        );
        $this->assertSame(
            349,
            DB::table('lrfk_entries')->where('dataset_version', 'perubahan')->count()
        );
        $this->assertSame(
            0,
            DB::table('lrfk_entries')
                ->where('dataset_version', 'lama')
                ->where('level', 'rekening')
                ->whereNull('parent_id')
                ->count()
        );
    }

    public function test_change_dataset_keeps_duplicate_account_rows_from_the_source_sheet(): void
    {
        $this->assertSame(
            2,
            DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->where('level', 'rekening')
                ->where('kode_rekening', '5.2.03.01.002.00013')
                ->where('program_kegiatan', 'Belanja Modal Bangunan Gedung Tempat Tinggal Lainnya')
                ->count()
        );

        $this->assertSame(
            2,
            DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->where('level', 'rekening')
                ->where('kode_rekening', '5.2.03.04.001.00004')
                ->where('program_kegiatan', 'Belanja Modal Pagar')
                ->where('parent_id', function ($query): void {
                    $query->select('id')
                        ->from('lrfk_entries')
                        ->where('dataset_version', 'perubahan')
                        ->where('level', 'sub_kegiatan')
                        ->where('kode_rekening', '1.01.02.1.02.0010')
                        ->limit(1);
                })
                ->count()
        );
    }

    public function test_migration_imports_the_complete_processed_dataset(): void
    {
        $query = DB::table('lrfk_entries')->where('dataset_version', 'data_olahan');

        $this->assertSame(364, $query->count());
        $this->assertSame(279, (clone $query)->where('level', 'rekening')->count());
        $this->assertSame(62, (clone $query)->where('level', 'sub_kegiatan')->count());
        $this->assertSame(
            783_371_115_634,
            (int) (clone $query)->where('level', 'dinas')->value('pagu_anggaran')
        );
        $this->assertSame(360_632_883_181, (int) (clone $query)->where('level', 'dinas')->value('contract_value'));
        $this->assertSame(350_577_909_498, (int) (clone $query)->where('level', 'dinas')->value('financial_realization'));
    }

    public function test_processed_dataset_keeps_every_duplicate_account_row(): void
    {
        $query = DB::table('lrfk_entries')
            ->where('dataset_version', 'data_olahan')
            ->where('level', 'rekening');

        $this->assertSame(5, (clone $query)->where('kode_rekening', '5.2.03.01.001.00010')->where('parent_id', function ($subquery): void {
            $subquery->select('id')
                ->from('lrfk_entries')
                ->where('dataset_version', 'data_olahan')
                ->where('level', 'sub_kegiatan')
                ->where('kode_rekening', '1.01.01.1.09.0009')
                ->limit(1);
        })->count());

        $this->assertSame(5, (clone $query)->where('kode_rekening', '5.1.02.02.008.00019')->where('parent_id', function ($subquery): void {
            $subquery->select('id')
                ->from('lrfk_entries')
                ->where('dataset_version', 'data_olahan')
                ->where('level', 'sub_kegiatan')
                ->where('kode_rekening', '1.01.02.1.01.0003')
                ->limit(1);
        })->count());
        $this->assertSame(2, (clone $query)->where('kode_rekening', '5.2.03.01.001.00030')->count());
    }

    public function test_full_sync_overwrites_source_fields_while_preserving_the_existing_id(): void
    {
        $preservedEntry = DB::table('lrfk_entries')
            ->where('dataset_version', 'perubahan')
            ->where('level', 'rekening')
            ->where('pagu_anggaran', '>', 0)
            ->first();

        DB::table('lrfk_entries')
            ->where('id', $preservedEntry->id)
            ->update([
                'contract_value' => 987_654_321,
                'output' => 'Realisasi yang harus dipertahankan',
            ]);

        $migration = require database_path('migrations/2026_10_04_120000_import_full_lrfk_source_data.php');
        $migration->up();

        $source = collect(json_decode(
            file_get_contents(database_path('seeders/data/lrfk_full_perubahan.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        ))->first(fn (array $row): bool => $row['kind'] === 'entry'
            && $row['level'] === 'rekening'
            && $row['kode_rekening'] === $preservedEntry->kode_rekening
            && $row['program_kegiatan'] === $preservedEntry->program_kegiatan
            && $row['pagu_anggaran'] === (int) $preservedEntry->pagu_anggaran);

        $this->assertDatabaseHas('lrfk_entries', [
            'id' => $preservedEntry->id,
            'dataset_version' => 'perubahan',
            'contract_value' => $source['contract_value'],
            'output' => $source['output'],
        ]);
        $this->assertSame(
            349,
            DB::table('lrfk_entries')->where('dataset_version', 'perubahan')->count()
        );
    }
}
