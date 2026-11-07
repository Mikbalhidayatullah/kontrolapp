<?php

namespace Tests\Feature;

use App\Models\PerjadinEntry;
use App\Models\User;
use App\Services\PerjadinReceiptExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PerjadinReceiptFundingFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_funding_dropdown_contains_all_existing_categories_without_duplicates(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->entry($user, 'GU 1');
        $this->entry($user, 'GU 1');
        $this->entry($user, 'LS');

        $this->actingAs($user)->get(route('perjadin', ['month' => 1, 'year' => 2000]))
            ->assertOk()
            ->assertViewHas('receiptFundingCategories', fn ($categories) => collect($categories)->all() === ['GU 1', 'LS'])
            ->assertSee('data-receipt-funding-options', false)
            ->assertSee('data-receipt-funding-all', false)
            ->assertSee('data-receipt-funding-clear', false)
            ->assertSee('max-height: 144px;', false)
            ->assertSeeText('Pilih Semua')
            ->assertSeeText('Batalkan Semua')
            ->assertSee('name="receipt_funding_categories[]" value="GU 1"', false)
            ->assertSee('name="receipt_funding_categories[]" value="LS"', false);
    }

    public function test_funding_suggestions_are_searchable_and_unique(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->entry($user, 'GU 1');
        $this->entry($user, 'GU 1');
        $this->entry($user, 'GU 10');
        $this->entry($user, 'LS');

        $this->actingAs($user)->getJson(route('perjadin.receipts.suggestions', [
            'type' => 'funding', 'q' => 'GU',
        ]))->assertOk()->assertExactJson(['GU 1', 'GU 10']);
    }

    public function test_receipt_export_only_includes_the_exact_funding_category(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $selected = $this->entry($user, 'GU 1');
        $this->entry($user, 'GU 10');
        $this->entry($user, 'LS');
        $path = tempnam(sys_get_temp_dir(), 'receipt-test-');

        $this->mock(PerjadinReceiptExcelExporter::class, function ($mock) use ($selected, $path): void {
            $mock->shouldReceive('export')->once()->withArgs(
                fn (Collection $entries): bool => $entries->modelKeys() === [$selected->id]
            )->andReturn($path);
        });

        try {
            $this->actingAs($user)->get(route('perjadin.receipts.export.xlsx', [
                'receipt_filter_type' => 'funding', 'receipt_filter_value' => 'GU 1',
                'month' => 1, 'year' => 2000,
            ]))->assertOk()->assertDownload();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_receipt_export_combines_selected_funding_categories(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $first = $this->entry($user, 'GU 1');
        $second = $this->entry($user, 'GU 2');
        $this->entry($user, 'GU 10');
        $this->entry($user, 'LS');
        $path = tempnam(sys_get_temp_dir(), 'receipt-test-');
        $this->mock(PerjadinReceiptExcelExporter::class, function ($mock) use ($first, $second, $path): void {
            $mock->shouldReceive('export')->once()->withArgs(
                fn (Collection $entries): bool => $entries->modelKeys() === [$first->id, $second->id]
            )->andReturn($path);
        });
        try {
            $this->actingAs($user)->get(route('perjadin.receipts.export.xlsx', [
                'receipt_filter_type' => 'funding', 'receipt_funding_categories' => ['GU 1', 'GU 2'],
            ]))->assertOk()->assertDownload();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_funding_export_requires_at_least_one_category(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($user)->getJson(route('perjadin.receipts.export.xlsx', [
            'receipt_filter_type' => 'funding',
        ]))->assertUnprocessable()->assertJsonValidationErrors('receipt_funding_categories');
    }

    private function entry(User $user, string $funding): PerjadinEntry
    {
        return PerjadinEntry::unguarded(fn () => PerjadinEntry::query()->create([
            'category' => 'Perjadin Dalam Daerah', 'funding_category' => $funding,
            'skpd_name' => 'Dinas Pendidikan', 'executor_name' => 'Pelaksana '.$funding,
            'position_name' => 'Staf', 'grade' => '3A',
            'start_date' => '2026-10-11', 'end_date' => '2026-10-12',
            'assignment_number' => 'ST-'.$funding, 'assignment_date' => '2026-10-10',
            'destination_city' => 'Ternate', 'grand_total' => 100000, 'created_by' => $user->id,
        ]));
    }
}
