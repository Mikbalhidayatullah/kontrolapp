<?php

namespace Tests\Feature;

use App\Models\PerjadinEntry;
use App\Models\User;
use App\Services\PerjadinBpkExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PerjadinBpkFundingFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_bpk_export_filters_multiple_exact_categories_and_ignores_page_period(): void
    {
        $user = User::factory()->create(['role' => 'bendahara', 'is_active' => true]);
        $ids = [];
        foreach (['GU 1', 'GU 2', 'GU 10', 'LS'] as $funding) {
            $entry = PerjadinEntry::unguarded(fn () => PerjadinEntry::query()->create([
                'category' => 'Perjadin Dalam Daerah', 'funding_category' => $funding,
                'skpd_name' => 'Dinas Pendidikan', 'executor_name' => $funding,
                'position_name' => 'Staf', 'grade' => '3A', 'start_date' => '2026-10-11',
                'end_date' => '2026-10-12', 'assignment_number' => 'ST-'.$funding,
                'assignment_date' => '2026-10-10', 'destination_city' => 'Ternate',
                'grand_total' => 100000, 'created_by' => $user->id,
            ]));
            if (in_array($funding, ['GU 1', 'GU 2'])) $ids[] = $entry->id;
        }
        $path = tempnam(sys_get_temp_dir(), 'bpk-test-');
        $this->mock(PerjadinBpkExcelExporter::class, function ($mock) use ($ids, $path): void {
            $mock->shouldReceive('export')->once()->withArgs(fn (Collection $entries) => $entries->modelKeys() === $ids)->andReturn($path);
        });
        try {
            $this->actingAs($user)->get(route('perjadin.export.bpk.xlsx', [
                'bpk_filter_type' => 'funding', 'bpk_funding_categories' => ['GU 1', 'GU 2'],
                'month' => 1, 'year' => 2000,
            ]))->assertOk()->assertDownload();
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    public function test_bpk_funding_requires_selection_and_rejects_unknown_categories(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($user)->getJson(route('perjadin.export.bpk.xlsx', ['bpk_filter_type' => 'funding']))
            ->assertUnprocessable()->assertJsonValidationErrors('bpk_funding_categories');
        $this->getJson(route('perjadin.export.bpk.xlsx', [
            'bpk_filter_type' => 'funding', 'bpk_funding_categories' => ['Unknown'],
        ]))->assertUnprocessable()->assertJsonValidationErrors('bpk_funding_categories.0');
    }

    public function test_verifikator_cannot_access_bpk_export_or_its_funding_controls(): void
    {
        $user = User::factory()->create(['role' => 'verifikator', 'is_active' => true]);
        $this->actingAs($user)->get(route('perjadin.export.bpk.xlsx'))->assertForbidden();
        $this->get(route('perjadin'))->assertOk()->assertDontSee('data-bpk-funding-form', false);
    }

    public function test_admin_can_see_bpk_funding_controls(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($user)->get(route('perjadin'))->assertOk()
            ->assertSee('data-bpk-funding-form', false)->assertSee('data-bpk-funding-all', false)
            ->assertSee('data-bpk-funding-clear', false);
    }
}
