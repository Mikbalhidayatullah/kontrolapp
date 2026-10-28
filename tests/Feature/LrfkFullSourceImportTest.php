<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\User;
use App\Services\LrfkSourceDatasetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LrfkFullSourceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_processed_import_is_idempotent(): void
    {
        $rows = $this->fixture('lrfk_full_data_olahan.json');

        $this->importer()->sync('data_olahan', $rows);
        $entryIds = LrfkEntry::query()
            ->where('dataset_version', 'data_olahan')
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->importer()->sync('data_olahan', $rows);

        $this->assertSame($entryIds, LrfkEntry::query()
            ->where('dataset_version', 'data_olahan')
            ->orderBy('sort_order')
            ->pluck('id')
            ->all());
        $this->assertSame(218, DB::table('lrfk_entry_details as details')
            ->join('lrfk_entries as entries', 'entries.id', '=', 'details.lrfk_entry_id')
            ->where('entries.dataset_version', 'data_olahan')
            ->count());
    }

    public function test_processed_import_does_not_touch_old_lrfk_or_perjadin_links(): void
    {
        $user = User::factory()->create();
        $old = LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => 'lama',
            'sort_order' => 1,
            'level' => 'rekening',
            'kode_rekening' => '5.1.02.04.001.00001',
            'program_kegiatan' => 'Rekening Lama',
            'pagu_anggaran' => 123_456,
            'contract_value' => 654_321,
        ]));

        $perjadinId = DB::table('perjadin_entries')->insertGetId([
            'category' => 'Perjadin Dalam Daerah',
            'skpd_name' => 'Dinas Pendidikan dan Kebudayaan',
            'executor_name' => 'Penguji',
            'position_name' => 'Staf',
            'grade' => 'III',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-02',
            'assignment_number' => 'TEST/1',
            'assignment_date' => '2026-01-01',
            'destination_city' => 'Sofifi',
            'lrfk_entry_id' => $old->id,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->importer()->sync('data_olahan', $this->fixture('lrfk_full_data_olahan.json'));

        $this->assertDatabaseHas('lrfk_entries', [
            'id' => $old->id,
            'dataset_version' => 'lama',
            'pagu_anggaran' => 123_456,
            'contract_value' => 654_321,
        ]);
        $this->assertSame($old->id, (int) DB::table('perjadin_entries')->where('id', $perjadinId)->value('lrfk_entry_id'));
    }

    private function importer(): LrfkSourceDatasetImporter
    {
        return app(LrfkSourceDatasetImporter::class);
    }

    private function fixture(string $name): array
    {
        return json_decode(
            File::get(database_path('seeders/data/'.$name)),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
