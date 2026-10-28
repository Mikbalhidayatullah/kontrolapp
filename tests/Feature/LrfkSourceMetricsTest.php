<?php

namespace Tests\Feature;

use App\Models\LrfkEntry;
use App\Models\User;
use App\Services\LrfkPerjadinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LrfkSourceMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_olahan_metrics_use_authoritative_source_rows(): void
    {
        $entries = $this->entries('data_olahan');
        $metrics = app(LrfkPerjadinService::class)->metrics($entries);
        $root = $entries->firstWhere('level', 'dinas');

        $this->assertSame(360_632_883_181, $metrics[$root->id]['contract']);
        $this->assertSame(350_577_909_498, $metrics[$root->id]['realization']);
    }

    public function test_filtered_source_page_retains_version_wide_source_summary(): void
    {
        $response = $this->actingAs($this->admin())->get(route('lrfk.index', [
            'version' => 'data_olahan',
            'keyword' => 'Belanja Perjalanan Dinas Biasa',
        ]));

        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertSame(360_632_883_181, $summary['contract']);
        $this->assertSame(350_577_909_498, $summary['realization']);
    }

    public function test_source_pages_count_main_and_owned_detail_rows(): void
    {
        $olahan = $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk();

        $this->assertSame(582, $olahan->viewData('summary')['count']);
        $this->assertTrue($olahan->viewData('entries')->every(
            fn (LrfkEntry $entry): bool => $entry->relationLoaded('details')
        ));
    }

    private function entries(string $version)
    {
        return LrfkEntry::query()
            ->where('dataset_version', $version)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
