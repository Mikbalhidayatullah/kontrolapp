<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LrfkVersionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lrfk_navigation_offers_all_dataset_versions(): void
    {
        $response = $this->actingAs($this->admin())->get(route('lrfk.index'));

        $response
            ->assertOk()
            ->assertSee('LRFK Lama')
            ->assertSee('Data Rapat')
            ->assertSee('LRFK Perubahan')
            ->assertDontSee('LRFK Data Olahan')
            ->assertSee(route('lrfk.index', ['version' => 'lama']), false)
            ->assertSee(route('lrfk.index', ['version' => 'perubahan']), false)
            ->assertSee(route('lrfk.index', ['version' => 'data_olahan']), false);
    }

    public function test_processed_dataset_version_is_shown_on_the_lrfk_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'data_olahan']))
            ->assertOk()
            ->assertSee('LRFK Perubahan');
    }

    public function test_selected_version_is_shown_on_the_lrfk_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('lrfk.index', ['version' => 'perubahan']))
            ->assertOk()
            ->assertSee('Data Rapat');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
