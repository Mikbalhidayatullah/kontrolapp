<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LrfkSourceRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_perubahan_uses_data_rapat_columns_without_nomor_tanggal(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'perubahan']))
            ->assertOk();

        $response->assertSeeText('Sisa Pagu Anggaran');
        $response->assertSeeText('Oktober');
        $response->assertSeeText('November');
        $response->assertSeeText('Desember');
        $response->assertSeeText('Triwulan IV');
        $response->assertSeeText('Selisih');
        $response->assertDontSeeText('Nomor / Tanggal');
    }

    public function test_data_olahan_uses_its_source_columns_without_cash_plan(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk();

        $response->assertSeeText('Nomor / Tanggal');
        $response->assertSeeText('Lokasi');
        $response->assertSeeText('Ket.');
        $response->assertSeeText('Selisih');
        $response->assertDontSeeText('Sisa Pagu Anggaran');
        $response->assertDontSeeText('Triwulan IV');
    }

    public function test_detail_row_follows_its_account_with_four_empty_source_cells(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('lrfk.index', [
                'version' => 'perubahan',
                'keyword' => 'Belanja Modal Pagar',
            ]))
            ->assertOk()
            ->getContent();

        $accountPosition = strpos($html, 'data-lrfk-source-row="203"');
        $detailPosition = strpos($html, 'data-lrfk-detail="204"');

        $this->assertNotFalse($accountPosition);
        $this->assertNotFalse($detailPosition);
        $this->assertGreaterThan($accountPosition, $detailPosition);
        $this->assertMatchesRegularExpression(
            '/data-lrfk-detail="204"[^>]*>\s*(?:<td[^>]*>\s*<\/td>\s*){4}/s',
            $html,
        );
    }

    public function test_source_values_negative_amounts_percentages_and_notes_render_in_place(): void
    {
        $perubahan = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'perubahan']))
            ->assertOk();
        $olahan = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk();

        $perubahan->assertSeeText('CV. RINSATAMA CIPTA MANDIRI');
        $perubahan->assertSeeText('-56.710.788');
        $perubahan->assertSeeText('100,00%');
        $perubahan->assertDontSeeText('100,00000000%');
        $perubahan->assertSeeText('GU 8');
        $olahan->assertSeeText('GU IIII');
        $olahan->assertSeeText('Vivi Irianti');
    }

    public function test_multiline_volume_and_unit_values_render_one_item_per_line(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('lrfk.index', [
                'version' => 'data_olahan',
                'keyword' => 'Amplop putih panjang',
            ]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td class="whitespace-pre-line px-4 py-4 align-top">10\s+50\s+100\s+100\s+100\s+200/s',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<td class="whitespace-pre-line px-4 py-4 align-top">Dos\s+Dos\s+Dos\s+Dos\s+Dos\s+Buah/s',
            $html,
        );
    }

    public function test_lrfk_lama_keeps_the_existing_table(): void
    {
        LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => 'lama',
            'sort_order' => 1,
            'level' => 'program',
            'kode' => 'Program',
            'kode_rekening' => '1.01.01',
            'program_kegiatan' => 'PROGRAM LAMA',
            'pagu_anggaran' => 1_000_000,
        ]));

        $response = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'lama']))
            ->assertOk();

        $response->assertSeeText('Kontrak Nilai');
        $response->assertSeeText('Nomor / Tanggal');
        $response->assertDontSee('data-lrfk-detail=', false);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
