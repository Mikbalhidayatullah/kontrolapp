<?php

namespace Tests\Feature;

use Database\Seeders\LrfkEntrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LrfkChangeDatasetMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_meeting_dataset_is_absent_after_migrations(): void
    {
        $this->assertSame(
            0,
            DB::table('lrfk_entries')->where('dataset_version', 'perubahan')->count()
        );
    }

    public function test_cleanup_migration_deletes_only_the_retired_dataset(): void
    {
        $retiredId = DB::table('lrfk_entries')->insertGetId([
            'dataset_version' => 'perubahan',
            'sort_order' => 999,
            'level' => 'rekening',
            'kode_rekening' => '5.1.02.04.001.99999',
            'program_kegiatan' => 'DATA RAPAT YANG DIHAPUS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $activeId = DB::table('lrfk_entries')->insertGetId([
            'dataset_version' => 'data_olahan',
            'sort_order' => 999,
            'level' => 'rekening',
            'kode_rekening' => '5.1.02.04.001.99998',
            'program_kegiatan' => 'LRFK PERUBAHAN TETAP ADA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('lrfk_entry_details')->insert([
            'lrfk_entry_id' => $retiredId,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_28_090000_remove_retired_lrfk_meeting_dataset.php');
        $migration->up();

        $this->assertDatabaseMissing('lrfk_entries', ['id' => $retiredId]);
        $this->assertDatabaseHas('lrfk_entries', ['id' => $activeId]);
        $this->assertDatabaseMissing('lrfk_entry_details', ['lrfk_entry_id' => $retiredId]);
    }

    public function test_old_dataset_seeder_still_runs_without_restoring_the_retired_dataset(): void
    {
        $this->seed(LrfkEntrySeeder::class);

        $this->assertSame(350, DB::table('lrfk_entries')->where('dataset_version', 'lama')->count());
        $this->assertSame(0, DB::table('lrfk_entries')->where('dataset_version', 'perubahan')->count());
    }

    public function test_migration_keeps_the_complete_processed_dataset(): void
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
}
