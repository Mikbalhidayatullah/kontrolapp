<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\User;
use App\Services\LrfkPerjadinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class LrfkDatasetIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            Schema::table('lrfk_entries', function (Blueprint $table): void {
                $table->string('dataset_version')->default('lama');
            });
        }

        DB::table('lrfk_entries')->delete();
    }

    public function test_index_only_shows_entries_from_the_selected_version(): void
    {
        $this->entry('lama', 'PROGRAM KHUSUS LAMA');
        $this->entry('perubahan', 'PROGRAM KHUSUS PERUBAHAN');
        $this->entry('data_olahan', 'PROGRAM KHUSUS DATA OLAHAN');

        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'lama']))
            ->assertOk()
            ->assertSee('PROGRAM KHUSUS LAMA')
            ->assertDontSee('PROGRAM KHUSUS PERUBAHAN')
            ->assertDontSee('PROGRAM KHUSUS DATA OLAHAN');

        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'perubahan']))
            ->assertOk()
            ->assertSee('PROGRAM KHUSUS PERUBAHAN')
            ->assertDontSee('PROGRAM KHUSUS LAMA')
            ->assertDontSee('PROGRAM KHUSUS DATA OLAHAN');

        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk()
            ->assertSee('PROGRAM KHUSUS DATA OLAHAN')
            ->assertDontSee('PROGRAM KHUSUS LAMA')
            ->assertDontSee('PROGRAM KHUSUS PERUBAHAN');
    }

    public function test_change_form_only_offers_change_sub_activities(): void
    {
        $this->entry('lama', 'SUB KEGIATAN LAMA', 'sub_kegiatan', '1.01.01.1.01.0001');
        $this->entry('perubahan', 'SUB KEGIATAN PERUBAHAN', 'sub_kegiatan', '1.01.01.1.01.0002');

        $this->actingAs($this->admin())
            ->get(route('lrfk.create', ['version' => 'perubahan']))
            ->assertOk()
            ->assertSee('SUB KEGIATAN PERUBAHAN')
            ->assertDontSee('SUB KEGIATAN LAMA')
            ->assertSee('name="version" value="perubahan"', false);
    }

    public function test_new_entry_is_saved_in_the_selected_version(): void
    {
        $this->actingAs($this->admin())->post(route('lrfk.store'), [
            'version' => 'perubahan',
            'level' => 'program',
            'kode' => 'Program',
            'kode_rekening' => '9.99.99',
            'program_kegiatan' => 'PROGRAM BARU PERUBAHAN',
            'pagu_anggaran' => '1.000.000',
            'contract_value' => '',
            'contract_number_date' => '',
            'implementer' => '',
            'output' => '',
            'volume' => '',
            'unit' => '',
            'financial_realization' => '',
            'location' => '',
            'notes' => '',
        ])->assertRedirect(route('lrfk.index', ['version' => 'perubahan']));

        $this->assertDatabaseHas('lrfk_entries', [
            'dataset_version' => 'perubahan',
            'program_kegiatan' => 'PROGRAM BARU PERUBAHAN',
            'pagu_anggaran' => 1_000_000,
        ]);
    }

    public function test_change_account_cannot_use_an_old_sub_activity_parent(): void
    {
        $oldParent = $this->entry(
            'lama',
            'SUB KEGIATAN INDUK LAMA',
            'sub_kegiatan',
            '1.01.01.1.01.0003'
        );

        $this->actingAs($this->admin())->post(route('lrfk.store'), [
            'version' => 'perubahan',
            'level' => 'rekening',
            'parent_id' => $oldParent->id,
            'kode_rekening' => '5.1.02.04.001.00001',
            'program_kegiatan' => 'REKENING SALAH VERSI',
            'pagu_anggaran' => '1.000.000',
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('lrfk_entries', [
            'dataset_version' => 'perubahan',
            'program_kegiatan' => 'REKENING SALAH VERSI',
        ]);
    }

    public function test_perjadin_hierarchy_keeps_using_old_lrfk(): void
    {
        $this->entry('lama', 'PROGRAM PILIHAN PERJADIN LAMA');
        $this->entry('perubahan', 'PROGRAM YANG BELUM DIPAKAI PERJADIN');
        $this->entry('data_olahan', 'PROGRAM OLAHAN YANG TIDAK DIPAKAI PERJADIN');

        $hierarchy = app(LrfkPerjadinService::class)->hierarchy();

        $this->assertCount(1, $hierarchy);
        $this->assertSame('PROGRAM PILIHAN PERJADIN LAMA', $hierarchy[0]['nama']);
    }

    public function test_excel_export_only_contains_the_selected_version(): void
    {
        $this->entry('lama', 'PROGRAM EKSPOR LAMA');
        $this->entry('perubahan', 'PROGRAM EKSPOR PERUBAHAN');

        $response = $this->actingAs($this->admin())
            ->get(route('lrfk.export.xlsx', ['version' => 'perubahan']));

        $response->assertOk()->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $worksheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Data Rapat', $worksheet);
        $this->assertStringContainsString('PROGRAM EKSPOR PERUBAHAN', $worksheet);
        $this->assertStringNotContainsString('PROGRAM EKSPOR LAMA', $worksheet);

        @unlink($path);
    }

    public function test_level_filter_keeps_rollup_metrics_from_account_rows(): void
    {
        $this->entry('lama', 'PROGRAM ROLLUP');
        $this->entry('lama', 'KEGIATAN ROLLUP', 'kegiatan', '1.01.01.1.01');
        $this->entry('lama', 'SUB KEGIATAN ROLLUP', 'sub_kegiatan', '1.01.01.1.01.0001');
        $account = $this->entry('lama', 'REKENING ROLLUP', 'rekening', '5.1.02.04.001.00001');
        $account->update(['contract_value' => 500_000]);

        $this->actingAs($this->admin())
            ->get(route('lrfk.index', [
                'version' => 'lama',
                'level' => 'program',
            ]))
            ->assertOk()
            ->assertSee('Rp 500.000');
    }

    private function entry(
        string $version,
        string $name,
        string $level = 'program',
        string $accountCode = '1.01.01'
    ): LrfkEntry {
        return LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => $version,
            'sort_order' => ((int) LrfkEntry::query()->max('sort_order')) + 1,
            'level' => $level,
            'kode' => $level === 'program' ? 'Program' : 'Sub Kegiatan',
            'kode_rekening' => $accountCode,
            'program_kegiatan' => $name,
            'pagu_anggaran' => 1_000_000,
        ]));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
