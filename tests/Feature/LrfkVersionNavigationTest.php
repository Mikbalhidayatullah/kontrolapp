<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LrfkVersionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lrfk_navigation_only_offers_active_dataset_versions(): void
    {
        $response = $this->actingAs($this->admin())->get(route('lrfk.index'));

        $response
            ->assertOk()
            ->assertSee('LRFK Lama')
            ->assertDontSee('Data Rapat')
            ->assertSee('LRFK Perubahan')
            ->assertSee('LRFK Perjadin')
            ->assertDontSee('LRFK Data Olahan')
            ->assertSee(route('lrfk.index', ['version' => 'lama']), false)
            ->assertDontSee(route('lrfk.index', ['version' => 'perubahan']), false)
            ->assertSee(route('lrfk.index', ['version' => 'data_olahan']), false)
            ->assertSee(route('lrfk.index', ['version' => 'perjadin']), false);
    }

    public function test_processed_dataset_version_is_shown_on_the_lrfk_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk()
            ->assertSee('LRFK Perubahan');
    }

    public function test_retired_meeting_dataset_page_is_not_accessible(): void
    {
        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'perubahan']))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->get(route('lrfk.export.xlsx', ['version' => 'perubahan']))
            ->assertNotFound();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
