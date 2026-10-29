<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\PerjadinEntry;
use App\Models\PerjadinPaymentGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class LrfkPerjadinReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('lrfk_entry_details')->delete();
        DB::table('lrfk_entries')->delete();
    }

    public function test_report_uses_old_hierarchy_and_only_paid_perjadin_values(): void
    {
        [$admin, $program, $kegiatan, $subKegiatan, $linkedAccount, $unusedAccount] = $this->reportFixture();

        $response = $this->actingAs($admin)
            ->get(route('lrfk.index', ['version' => 'perjadin']))
            ->assertOk();

        $response
            ->assertSeeText('LRFK Perjadin')
            ->assertSeeText('PROGRAM PERJADIN PERUBAHAN')
            ->assertSeeText('KEGIATAN PERJADIN PERUBAHAN')
            ->assertSeeText('SUB KEGIATAN PERJADIN PERUBAHAN')
            ->assertSeeText('REKENING TERPAKAI VERSI PERUBAHAN')
            ->assertSeeText('REKENING BELUM TERPAKAI VERSI PERUBAHAN')
            ->assertSeeText('Rp 4.000.000')
            ->assertSeeText('Rp 1.500.000')
            ->assertSeeText('Rp 2.500.000')
            ->assertSeeText('Rp 250.000')
            ->assertSeeText('ST-PAID-001 / 10/10/2026')
            ->assertSeeText('Pelaksana Dibayar')
            ->assertSeeText('Monitoring sekolah')
            ->assertDontSeeText('ST-UNPAID-001')
            ->assertDontSeeText('DATA LRFK NON PERJADIN')
            ->assertDontSeeText('PROGRAM DATA RAPAT')
            ->assertDontSeeText('Rp 3.000.000')
            ->assertDontSeeText('Rp 999.000')
            ->assertDontSeeText('Rp 888.000')
            ->assertDontSeeText('Tambah LRFK');
    }

    public function test_report_excel_uses_change_layout_and_only_perjadin_values(): void
    {
        [$admin] = $this->reportFixture();

        $response = $this->actingAs($admin)
            ->get(route('lrfk.export.xlsx', ['version' => 'perjadin']))
            ->assertOk()
            ->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $worksheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('LRFK Perjadin', $worksheet);
        $this->assertStringContainsString('Nomor / Tanggal', $worksheet);
        $this->assertStringContainsString('Selisih', $worksheet);
        $this->assertStringContainsString('ST-PAID-001 / 10/10/2026', $worksheet);
        $this->assertStringContainsString('Pelaksana Dibayar', $worksheet);
        $this->assertStringContainsString('Monitoring sekolah', $worksheet);
        $this->assertStringNotContainsString('ST-UNPAID-001', $worksheet);
        $this->assertStringNotContainsString('DATA LRFK NON PERJADIN', $worksheet);
        $this->assertStringNotContainsString('999000', $worksheet);
        $this->assertStringContainsString('<v>1500000</v>', $worksheet);
        $this->assertStringContainsString('<v>2500000</v>', $worksheet);
        $this->assertMatchesRegularExpression('/<c r="[A-Z]+\d+" s="\d+"><v>250000<\/v><\/c>/', $worksheet);
    }

    public function test_report_excel_writes_each_perjadin_to_a_separate_row(): void
    {
        [$admin, , , , , , $changedLinkedAccount] = $this->reportFixture();
        $this->perjadin(
            $admin,
            $changedLinkedAccount,
            'ST-PAID-001',
            'Pelaksana Dibayar Kedua',
            125_000,
            now(),
        );

        $response = $this->actingAs($admin)
            ->get(route('lrfk.export.xlsx', ['version' => 'perjadin']))
            ->assertOk()
            ->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $worksheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertSame(2, substr_count($worksheet, 'ST-PAID-001 / 10/10/2026'));
        $this->assertStringContainsString('Pelaksana Dibayar</t>', $worksheet);
        $this->assertStringContainsString('Pelaksana Dibayar Kedua</t>', $worksheet);
        $this->assertStringNotContainsString('Pelaksana Dibayar, Pelaksana Dibayar Kedua', $worksheet);
    }

    public function test_report_shows_paid_perjadin_that_still_needs_an_lrfk_account(): void
    {
        [$admin] = $this->reportFixture();

        $unlinked = PerjadinEntry::unguarded(fn (): PerjadinEntry => PerjadinEntry::query()->create([
            'category' => 'Perjadin Dalam Daerah',
            'skpd_name' => 'Dinas Pendidikan dan Kebudayaan',
            'executor_name' => 'Pelaksana Lama',
            'position_name' => 'Staf',
            'grade' => 'III',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'assignment_number' => 'ST-LAMA-001',
            'assignment_date' => '2026-08-31',
            'destination_city' => 'Tidore',
            'grand_total' => 975_000,
            'paid_at' => now(),
            'created_by' => $admin->id,
        ]));

        $this->actingAs($admin)
            ->get(route('lrfk.index', ['version' => 'perjadin']))
            ->assertOk()
            ->assertSeeText('Perjadin belum terhubung ke rekening LRFK Perubahan')
            ->assertSeeText('1 data')
            ->assertSeeText('Rp 975.000')
            ->assertSeeText('ST-LAMA-001')
            ->assertSeeText('Pelaksana Lama')
            ->assertSeeText('Pilih Rekening')
            ->assertSee(route('perjadin.edit', $unlinked), false);
    }

    public function test_report_requests_remapping_for_paid_perjadin_linked_to_old_lrfk(): void
    {
        [$admin, , , , $oldAccount] = $this->reportFixture();
        $oldLinked = $this->perjadin(
            $admin,
            $oldAccount,
            'ST-LINK-LAMA-001',
            'Pelaksana Tautan Lama',
            425_000,
            now(),
        );

        $this->actingAs($admin)
            ->get(route('lrfk.index', ['version' => 'perjadin']))
            ->assertOk()
            ->assertSeeText('Perjadin belum terhubung ke rekening LRFK Perubahan')
            ->assertSeeText('ST-LINK-LAMA-001')
            ->assertSeeText('Pelaksana Tautan Lama')
            ->assertSee(route('perjadin.edit', $oldLinked), false);
    }

    public function test_report_renders_each_paid_perjadin_as_a_separate_detail_row(): void
    {
        [$admin, , , , , , $changedLinkedAccount] = $this->reportFixture();
        $this->perjadin(
            $admin,
            $changedLinkedAccount,
            'ST-PAID-001',
            'Pelaksana Dibayar Kedua',
            125_000,
            now(),
        );

        $html = $this->actingAs($admin)
            ->get(route('lrfk.index', ['version' => 'perjadin']))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-lrfk-perjadin-detail='));
        $this->assertStringContainsString('Pelaksana Dibayar', $html);
        $this->assertStringContainsString('Pelaksana Dibayar Kedua', $html);
        $this->assertStringNotContainsString('Pelaksana Dibayar, Pelaksana Dibayar Kedua', $html);
    }

    private function reportFixture(): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $program = $this->entry('program', 'PROGRAM PERJADIN', '1.01.01', 1, 3_000_000);
        $kegiatan = $this->entry('kegiatan', 'KEGIATAN PERJADIN', '1.01.01.1.01', 2, 3_000_000);
        $subKegiatan = $this->entry('sub_kegiatan', 'SUB KEGIATAN PERJADIN', '1.01.01.1.01.0001', 3, 3_000_000);
        $linkedAccount = $this->entry('rekening', 'REKENING TERPAKAI PERJADIN', '5.1.02.04.001.00001', 4, 1_000_000, [
            'parent_id' => $subKegiatan->id,
            'contract_value' => 999_000,
            'financial_realization' => 888_000,
            'output' => 'DATA LRFK NON PERJADIN',
        ]);
        $unusedAccount = $this->entry('rekening', 'REKENING BELUM TERPAKAI', '5.1.02.04.001.00002', 5, 2_000_000, [
            'parent_id' => $subKegiatan->id,
            'contract_value' => 777_000,
            'financial_realization' => 666_000,
        ]);

        $this->entry('program', 'PROGRAM PERJADIN PERUBAHAN', '1.01.01', 1, 4_000_000, [], LrfkEntry::DATASET_DATA_OLAHAN);
        $this->entry('kegiatan', 'KEGIATAN PERJADIN PERUBAHAN', '1.01.01.1.01', 2, 4_000_000, [], LrfkEntry::DATASET_DATA_OLAHAN);
        $changedSubKegiatan = $this->entry('sub_kegiatan', 'SUB KEGIATAN PERJADIN PERUBAHAN', '1.01.01.1.01.0001', 3, 4_000_000, [], LrfkEntry::DATASET_DATA_OLAHAN);
        $this->entry('rekening', 'REKENING TERPAKAI VERSI PERUBAHAN', '5.1.02.04.001.00001', 4, 1_500_000, [
            'parent_id' => $changedSubKegiatan->id,
        ], LrfkEntry::DATASET_DATA_OLAHAN);
        $this->entry('rekening', 'REKENING BELUM TERPAKAI VERSI PERUBAHAN', '5.1.02.04.001.00002', 5, 2_500_000, [
            'parent_id' => $changedSubKegiatan->id,
        ], LrfkEntry::DATASET_DATA_OLAHAN);

        LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => LrfkEntry::DATASET_PERUBAHAN,
            'sort_order' => 6,
            'level' => 'program',
            'kode' => 'Program',
            'kode_rekening' => '9.99.99',
            'program_kegiatan' => 'PROGRAM DATA RAPAT',
            'pagu_anggaran' => 9_000_000,
        ]));

        PerjadinPaymentGroup::query()->create([
            'assignment_number' => 'ST-PAID-001',
            'assignment_date' => '2026-10-10',
            'purpose' => 'Monitoring sekolah',
        ]);

        $changedLinkedAccount = LrfkEntry::query()
            ->where('dataset_version', LrfkEntry::DATASET_DATA_OLAHAN)
            ->where('kode_rekening', '5.1.02.04.001.00001')
            ->firstOrFail();

        $this->perjadin($admin, $changedLinkedAccount, 'ST-PAID-001', 'Pelaksana Dibayar', 250_000, now());
        $this->perjadin($admin, $changedLinkedAccount, 'ST-UNPAID-001', 'Pelaksana Belum Dibayar', 750_000, null);

        return [$admin, $program, $kegiatan, $subKegiatan, $linkedAccount, $unusedAccount, $changedLinkedAccount];
    }

    private function entry(
        string $level,
        string $name,
        string $code,
        int $sortOrder,
        int $budget,
        array $extra = [],
        string $dataset = LrfkEntry::DATASET_LAMA,
    ): LrfkEntry {
        return LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => $dataset,
            'sort_order' => $sortOrder,
            'level' => $level,
            'kode' => ucfirst(str_replace('_', ' ', $level)),
            'kode_rekening' => $code,
            'program_kegiatan' => $name,
            'pagu_anggaran' => $budget,
            ...$extra,
        ]));
    }

    private function perjadin(
        User $user,
        LrfkEntry $account,
        string $assignmentNumber,
        string $executor,
        int $amount,
        mixed $paidAt,
    ): PerjadinEntry {
        return PerjadinEntry::unguarded(fn (): PerjadinEntry => PerjadinEntry::query()->create([
            'category' => 'Perjadin Dalam Daerah',
            'lrfk_entry_id' => $account->id,
            'skpd_name' => 'Dinas Pendidikan dan Kebudayaan',
            'executor_name' => $executor,
            'position_name' => 'Staf',
            'grade' => 'IV',
            'start_date' => '2026-10-11',
            'end_date' => '2026-10-12',
            'assignment_number' => $assignmentNumber,
            'assignment_date' => '2026-10-10',
            'destination_city' => 'Ternate',
            'grand_total' => $amount,
            'paid_at' => $paidAt,
            'created_by' => $user->id,
        ]));
    }
}
