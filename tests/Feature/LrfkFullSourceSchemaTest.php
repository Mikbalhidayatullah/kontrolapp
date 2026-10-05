<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LrfkFullSourceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_source_columns_and_detail_table_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('lrfk_entries', [
            'source_row',
            'budget_balance',
            'cash_plan_october',
            'cash_plan_november',
            'cash_plan_december',
            'cash_plan_quarter',
            'variance',
            'variance_note',
        ]));

        $this->assertTrue(Schema::hasColumns('lrfk_entry_details', [
            'lrfk_entry_id',
            'source_row',
            'sort_order',
            'contract_value',
            'contract_number_date',
            'implementer',
            'output',
            'volume',
            'unit',
            'financial_realization',
            'financial_percent',
            'physical_percent',
            'budget_balance',
            'cash_plan_october',
            'cash_plan_november',
            'cash_plan_december',
            'cash_plan_quarter',
            'location',
            'notes',
            'variance',
            'variance_note',
        ]));
    }

    public function test_detail_rows_are_ordered_and_owned_by_their_account(): void
    {
        $entry = $this->account();

        $entry->details()->create(['source_row' => 12, 'sort_order' => 2]);
        $first = $entry->details()->create(['source_row' => 11, 'sort_order' => 1]);

        $this->assertSame([11, 12], $entry->details()->pluck('source_row')->all());
        $this->assertTrue($first->entry->is($entry));
    }

    public function test_signed_source_amounts_accept_negative_values(): void
    {
        $entry = $this->account();
        $entry->update([
            'budget_balance' => -56_710_788,
            'cash_plan_october' => -56_710_788,
            'variance' => -56_710_788,
        ]);

        $detail = $entry->details()->create([
            'source_row' => 204,
            'sort_order' => 1,
            'budget_balance' => -56_710_788,
            'cash_plan_october' => -56_710_788,
            'variance' => -56_710_788,
            'financial_percent' => 45.60480978,
            'physical_percent' => 82.27628869,
        ]);

        $this->assertSame(-56_710_788, $entry->fresh()->budget_balance);
        $this->assertSame(-56_710_788, $detail->fresh()->cash_plan_october);
        $this->assertSame('45.60480978', $detail->fresh()->financial_percent);
        $this->assertSame('82.27628869', $detail->fresh()->physical_percent);
    }

    public function test_deleting_an_account_cascades_to_its_source_details(): void
    {
        $entry = $this->account();
        $detail = $entry->details()->create(['source_row' => 204, 'sort_order' => 1]);

        $entry->delete();

        $this->assertDatabaseMissing('lrfk_entry_details', ['id' => $detail->id]);
    }

    private function account(): LrfkEntry
    {
        return LrfkEntry::unguarded(fn (): LrfkEntry => LrfkEntry::query()->create([
            'dataset_version' => LrfkEntry::DATASET_PERUBAHAN,
            'sort_order' => 1,
            'level' => 'rekening',
            'kode_rekening' => '5.1.02.04.001.00001',
            'program_kegiatan' => 'Belanja Perjalanan Dinas Biasa',
            'pagu_anggaran' => 100_000_000,
        ]));
    }
}
