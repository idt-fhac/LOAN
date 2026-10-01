<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zielmodell der Geraeteausleihe.
 *
 * Ersetzt das gewachsene Schema, in dem der Ausleihzustand als vier Spalten an
 * der Geraetetabelle hing und Raum- und Geraetevormerkungen zwei getrennte
 * Modelle waren. Neu:
 *
 *   device_models  Geraetetyp (Kamera XY) - traegt Name, Bild, Kategorie
 *   devices        Exemplar mit Inventarnummer - das, was physisch rausgeht
 *   loans          jeder Ausleihvorgang als eigene Zeile, offen = returned_at NULL
 *   reservations   polymorph fuer Raeume UND Geraete, ein Zeitstempelpaar
 *
 * Die Migration setzt voraus, dass keine produktiven Daten existieren: die
 * Alttabellen werden verworfen, nicht ueberfuehrt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['device_reservations', 'device_histories', 'reservations', 'devices', 'rooms', 'categories'] as $legacy) {
            Schema::dropIfExists($legacy);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Geraetetyp: alles, was fuer alle baugleichen Exemplare gilt.
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('image')->nullable();
            $table->text('accessories')->nullable();
            $table->unsignedSmallInteger('default_loan_days')->default(14);
            $table->timestamps();

            $table->index('category_id');
        });

        // Exemplar: das physische Geraet mit eigener Inventarnummer.
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_no')->unique();
            $table->string('serial_no')->nullable();
            $table->string('condition')->nullable();
            $table->text('note')->nullable();
            // Ausgemustert oder in Reparatur: bleibt im Bestand, ist aber nicht ausleihbar.
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['device_model_id', 'active']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('bookable')->default(true);
            $table->timestamps();
        });

        // Ein Ausleihvorgang. Offen, solange returned_at NULL ist.
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();

            // Nutzerkonto der ausleihenden Person. Nullable, weil an der Theke
            // auch an Gaeste ohne Konto verliehen wird - dann traegt
            // borrower_name allein die Zuordnung.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('borrower_name');

            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_to_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('checked_out_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();

            $table->string('purpose')->nullable();
            $table->text('condition_note')->nullable();
            $table->timestamps();

            // Traegt die "ist dieses Geraet gerade draussen"-Abfrage und die
            // Ueberfaelligkeitsliste.
            $table->index(['device_id', 'returned_at']);
            $table->index(['user_id', 'returned_at']);
            $table->index(['due_at', 'returned_at']);
        });

        // Vormerkung - polymorph fuer Raeume und Geraete.
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->morphs('reservable'); // legt reservable_type + reservable_id samt Index an
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reserved_by_name')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('purpose', 500)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'fulfilled'])->default('pending');

            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            // Die Ausleihe, die aus dieser Vormerkung entstanden ist.
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();

            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id', 'starts_at', 'ends_at'], 'reservations_window_index');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['reservations', 'loans', 'rooms', 'devices', 'device_models', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
