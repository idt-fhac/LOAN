<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_startseite_schickt_gaeste_zur_anmeldung(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_startseite_schickt_angemeldete_zur_uebersicht(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('devices.overview'));
    }
}
