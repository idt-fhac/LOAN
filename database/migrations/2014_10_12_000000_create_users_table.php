<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konten.
 *
 * role ist bewusst ein String, kein Enum: Enums lassen sich in SQLite nicht
 * nachtraeglich erweitern, und die Rollenliste steht ohnehin im Modell
 * (App\Models\User::ROLE_LEVELS), wo auch die Hierarchie definiert ist.
 *
 * Der Default ist die NIEDRIGSTE Rolle. Vorher war es die hoehere, weshalb
 * jedes neue Konto faktisch Verwaltungsrechte hatte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 32)->default('user');
            $table->string('locale', 5)->default('de');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
