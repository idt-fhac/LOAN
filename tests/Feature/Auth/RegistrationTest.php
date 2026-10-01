<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Selbstregistrierung ist abgeschaltet: Konten legt die Administration an
 * (spaeter das SSO der FH). Die Tests halten fest, dass das so bleibt.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrierungsseite_existiert_nicht(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registrierung_per_post_legt_kein_konto_an(): void
    {
        $this->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing(User::class, ['email' => 'test@example.com']);
    }
}
