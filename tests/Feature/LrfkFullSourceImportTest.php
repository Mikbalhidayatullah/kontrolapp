<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\User;
use App\Services\LrfkSourceDatasetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class LrfkFullSourceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_updates_every_main_source_field_without_changing_ids(): void
    {
        $before = LrfkEntry::query()
            ->where('dataset_version', 'perubahan')
            ->where('level', 'dinas')
            ->firstOrFail();

        $this->importer()->sync('perubahan', $this->fixture('lrfk_full_perubahan.json'));

        $after = $before->fresh();
        $this->assertSame($before->id, $after->id);
        $this->assertSame(6, $after->source_row);
        $this->assertSame(366_701_836_399, $after->contract_value);
        $this->assertSame(357_117_006_436, $after->financial_realization);
        $this->assertSame('45.60480978', $after->financial_percent);
        $this->assertSame('82.27628869', $after->physical_percent);
        $this->assertSame(9_584_829_963, $after->variance);
    }

    public function test_import_links_all_details_to_the_preceding_account(): void
    {
        $this->importer()->sync('perubahan', $this->fixture('lrfk_full_perubahan.json'));

        $details = DB::table('lrfk_entry_details as details')
            ->join('lrfk_entries as entries', 'entries.id', '=', 'details.lrfk_entry_id')
            ->where('entries.dataset_version', 'perubahan')
            ->orderBy('details.source_row')
            ->get('details.*');
        $this->assertCount(3, $details);

        $detail = $details->firstWhere('source_row', 204);
        $owner = LrfkEntry::query()->findOrFail($detail->lrfk_entry_id);

        $this->assertSame(203, $owner->source_row);
        $this->assertSame('rekening', $owner->level);
        $this->assertSame(56_710_788, (int) $detail->contract_value);
        $this->assertSame(-56_710_788, (int) $detail->budget_balance);
    }

    public function test_import_preserves_identical_duplicate_account_occurrences(): void
    {
        $query = LrfkEntry::query()
            ->where('dataset_version', 'perubahan')
            ->where('level', 'rekening')
            ->where('kode_rekening', '5.2.03.01.002.00013')
            ->where('program_kegiatan', 'Belanja Modal Bangunan Gedung Tempat Tinggal Lainnya')
            ->orderBy('sort_order')
            ->orderBy('id');

        $idsBefore = (clone $query)->pluck('id')->all();
        $this->assertCount(2, $idsBefore);

        $this->importer()->sync('perubahan', $this->fixture('lrfk_full_perubahan.json'));

        $rowsAfter = (clone $query)->get(['id', 'source_row']);
        $this->assertSame($idsBefore, $rowsAfter->pluck('id')->all());
        $this->assertCount(2, $rowsAfter->pluck('source_row')->unique());
    }

    public function test_import_is_idempotent(): void
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

    public function test_import_rejects_an_orphan_detail_transactionally(): void
    {
        $rows = $this->fixture('lrfk_full_perubahan.json');
        $this->importer()->sync('perubahan', $rows);
        $before = DB::table('lrfk_entry_details')->count();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Baris rincian 999');

        try {
            $this->importer()->sync('perubahan', [[
                'kind' => 'detail',
                'source_row' => 999,
                'parent_source_row' => 998,
            ]]);
        } finally {
            $this->assertSame($before, DB::table('lrfk_entry_details')->count());
        }
    }

    public function test_import_does_not_touch_old_lrfk_or_perjadin_links(): void
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

        $this->importer()->sync('perubahan', $this->fixture('lrfk_full_perubahan.json'));

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
