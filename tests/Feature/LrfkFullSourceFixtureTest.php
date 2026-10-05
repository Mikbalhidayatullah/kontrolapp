<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LrfkFullSourceFixtureTest extends TestCase
{
    public function test_perubahan_fixture_preserves_all_physical_rows_and_source_values(): void
    {
        $rows = $this->fixture('lrfk_full_perubahan.json');

        $this->assertCount(352, $rows);
        $this->assertCount(349, array_filter($rows, fn (array $row): bool => $row['kind'] === 'entry'));
        $this->assertCount(3, array_filter($rows, fn (array $row): bool => $row['kind'] === 'detail'));

        $root = $this->sourceRow($rows, 6);
        $this->assertSame(366_701_836_399, $root['contract_value']);
        $this->assertSame(357_117_006_436, $root['financial_realization']);
        $this->assertSame(45.60480978, $root['financial_percent']);
        $this->assertSame(82.27628869, $root['physical_percent']);
        $this->assertSame(9_584_829_963, $root['variance']);

        $detail = $this->sourceRow($rows, 204);
        $this->assertSame('detail', $detail['kind']);
        $this->assertSame(203, $detail['parent_source_row']);
        $this->assertSame(56_710_788, $detail['contract_value']);
        $this->assertSame(56_710_788, $detail['financial_realization']);
        $this->assertSame(-56_710_788, $detail['budget_balance']);
        $this->assertSame(-56_710_788, $detail['cash_plan_october']);
    }

    public function test_data_olahan_fixture_preserves_all_physical_rows_and_source_values(): void
    {
        $rows = $this->fixture('lrfk_full_data_olahan.json');

        $this->assertCount(582, $rows);
        $this->assertCount(364, array_filter($rows, fn (array $row): bool => $row['kind'] === 'entry'));
        $this->assertCount(218, array_filter($rows, fn (array $row): bool => $row['kind'] === 'detail'));

        $root = $this->sourceRow($rows, 6);
        $this->assertSame(360_632_883_181, $root['contract_value']);
        $this->assertSame(350_577_909_498, $root['financial_realization']);
        $this->assertSame(97.21185334, $root['financial_percent']);
        $this->assertSame(100.0, $root['physical_percent']);
        $this->assertSame(10_054_973_683, $root['variance']);

        $detail = $this->sourceRow($rows, 69);
        $this->assertSame('detail', $detail['kind']);
        $this->assertSame(68, $detail['parent_source_row']);
        $this->assertSame('GU IIII', $detail['contract_number_date']);
        $this->assertSame('Vivi Irianti', $detail['implementer']);
        $this->assertSame(100.0, $detail['financial_percent']);
        $this->assertSame(100.0, $detail['physical_percent']);
    }

    public function test_blank_cells_are_normalized_without_none_strings(): void
    {
        foreach (['lrfk_full_perubahan.json', 'lrfk_full_data_olahan.json'] as $fixture) {
            $rows = $this->fixture($fixture);

            $this->assertStringNotContainsString('"None"', json_encode($rows, JSON_THROW_ON_ERROR));

            foreach ($rows as $row) {
                foreach (['contract_value', 'financial_realization', 'budget_balance', 'variance'] as $field) {
                    $this->assertIsInt($row[$field]);
                }
            }
        }
    }

    private function fixture(string $name): array
    {
        $path = database_path('seeders/data/'.$name);
        $this->assertFileExists($path);

        return json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function sourceRow(array $rows, int $sourceRow): array
    {
        $matches = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['source_row'] === $sourceRow,
        ));

        $this->assertCount(1, $matches, 'Nomor baris sumber harus unik.');

        return $matches[0];
    }
}
