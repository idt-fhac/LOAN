<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Eine Passwortregel fuer alle Wege: Anlegen durch die Administration,
        // Profil, Passwort-Reset. Vorher galten 12 Zeichen beim Anlegen, aber 8
        // beim Aendern - ein Konto liess sich nachtraeglich abschwaechen.
        Password::defaults(fn () => Password::min(12)->letters()->numbers());
    }
}
