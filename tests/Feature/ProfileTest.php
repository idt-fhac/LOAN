<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_konto_kann_nicht_ueber_das_profil_geloescht_werden(): void
    {
        // Sonst koennte sich die letzte Administration selbst entfernen -
        // an UserPolicy vorbei, die genau das verhindern soll.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }

    public function test_neues_passwort_verlangt_das_aktuelle(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile/edit')
            ->patch('/profile', [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'password'              => 'neues-Passwort-2026',
                'password_confirmation' => 'neues-Passwort-2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_falsches_aktuelles_passwort_wird_abgelehnt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile/edit')
            ->patch('/profile', [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'current_password'      => 'falsch',
                'password'              => 'neues-Passwort-2026',
                'password_confirmation' => 'neues-Passwort-2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_passwort_laesst_sich_mit_aktuellem_passwort_aendern(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'current_password'      => 'password',
                'password'              => 'neues-Passwort-2026',
                'password_confirmation' => 'neues-Passwort-2026',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('neues-Passwort-2026', $user->refresh()->password));
    }

    public function test_zu_schwaches_passwort_wird_abgelehnt(): void
    {
        // Neun Zeichen: frueher erlaubt (min:8), jetzt unter der gemeinsamen Regel.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile/edit')
            ->patch('/profile', [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'current_password'      => 'password',
                'password'              => 'zuKurz123',
                'password_confirmation' => 'zuKurz123',
            ])
            ->assertSessionHasErrors('password');
    }
}
