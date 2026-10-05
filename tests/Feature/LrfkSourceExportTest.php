<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class LrfkSourceExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_perubahan_export_uses_data_rapat_layout_and_all_physical_rows(): void
    {
        $xml = $this->exportXml('perubahan', ['keyword' => 'tidak boleh membatasi export']);

        $this->assertStringContainsString('Data Rapat', $xml);
        $this->assertStringContainsString('Sisa Pagu Anggaran', $xml);
        $this->assertStringContainsString('Triwulan IV', $xml);
        $this->assertStringContainsString('Selisih', $xml);
        $this->assertStringNotContainsString('Nomor / Tanggal', $xml);
        $this->assertSame(352, $this->physicalRowCount($xml));
        $this->assertLessThan(
            strpos($xml, 'CV. RINSATAMA CIPTA MANDIRI'),
            strpos($xml, 'Belanja Modal Pagar'),
        );
        $this->assertMatchesRegularExpression('/<c r="[A-Z]+\d+" s="\d+"><v>-56710788<\/v><\/c>/', $xml);
    }

    public function test_data_olahan_export_uses_its_layout_and_all_physical_rows(): void
    {
        $xml = $this->exportXml('data_olahan', ['level' => 'program']);

        $this->assertStringContainsString('LRFK Perubahan', $xml);
        $this->assertStringContainsString('Nomor / Tanggal', $xml);
        $this->assertStringContainsString('Lokasi', $xml);
        $this->assertStringContainsString('Ket.', $xml);
        $this->assertStringContainsString('Selisih', $xml);
        $this->assertStringNotContainsString('Sisa Pagu Anggaran', $xml);
        $this->assertSame(582, $this->physicalRowCount($xml));
        $this->assertLessThan(strpos($xml, 'GU IIII'), strpos($xml, 'Belanja Bahan-Bahan Bakar dan Pelumas'));
        $this->assertStringContainsString('Vivi Irianti', $xml);
    }

    public function test_source_amounts_and_percentages_are_numeric_cells(): void
    {
        $xml = $this->exportXml('perubahan');

        $this->assertMatchesRegularExpression('/<c r="[A-Z]+\d+" s="\d+"><v>-56710788<\/v><\/c>/', $xml);
        $this->assertMatchesRegularExpression('/<c r="[A-Z]+\d+" s="\d+"><v>1<\/v><\/c>/', $xml);
        $this->assertStringNotContainsString('<t xml:space="preserve">-56710788</t>', $xml);
    }

    public function test_lama_export_keeps_its_fifteen_column_layout(): void
    {
        $xml = $this->exportXml('lama');

        $this->assertStringContainsString('r="O5"', $xml);
        $this->assertStringNotContainsString('r="P5"', $xml);
        $this->assertStringContainsString('Nomor / Tanggal', $xml);
    }

    private function exportXml(string $version, array $filters = []): string
    {
        $response = $this->actingAs($this->admin())->get(route('lrfk.export.xlsx', [
            'version' => $version,
            ...$filters,
        ]));
        $response->assertOk()->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }

    private function physicalRowCount(string $xml): int
    {
        return substr_count($xml, '<row ') - 4;
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
