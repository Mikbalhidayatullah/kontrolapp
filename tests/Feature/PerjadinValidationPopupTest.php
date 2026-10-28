<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerjadinValidationPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_perjadin_form_shows_a_popup_with_all_missing_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('add-perjadin'))
            ->followingRedirects()
            ->post(route('add-perjadin.store'), []);

        $response
            ->assertOk()
            ->assertSee('data-validation-summary-modal', false)
            ->assertSeeText('Data Perjadin belum lengkap')
            ->assertSeeText('Kolom Kategori perjadin masih perlu diisi.')
            ->assertSeeText('Kolom Nama pelaksana masih perlu diisi.')
            ->assertSeeText('Kolom Nomor surat tugas masih perlu diisi.')
            ->assertSeeText('Tutup dan periksa form');
    }
}
