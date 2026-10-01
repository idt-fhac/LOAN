<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Geraetetyp darf ohne Kategorie existieren ("Keine Kategorie").
 *
 * SQLite kann NOT NULL nicht nachtraeglich loesen, dort wird die Tabelle neu
 * aufgebaut. Zwei Fallstricke stecken darin:
 *
 *  - Beim Umbenennen wandern Indizes mit, behalten aber ihren Namen. Solange
 *    ein Index laut Schema zu "device_models_tmp" gehoert, verweigert SQLite
 *    jedes Umbenennen auf diesen Namen. Die Indizes muessen also VOR dem
 *    Umbenennen weg.
 *  - PRAGMA foreign_keys wirkt innerhalb einer Transaktion nicht. Deshalb
 *    laeuft diese Migration ohne Transaktion.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement(
                'ALTER TABLE `device_models` MODIFY `category_id` BIGINT UNSIGNED NULL'
            ),
            'pgsql'  => DB::statement('ALTER TABLE device_models ALTER COLUMN category_id DROP NOT NULL'),
            'sqlite' => $this->rebuildSqlite(nullable: true),
            default  => throw new RuntimeException('Nicht unterstuetzter DB-Treiber.'),
        };
    }

    public function down(): void
    {
        if (DB::table('device_models')->whereNull('category_id')->exists()) {
            throw new RuntimeException(
                'Es gibt Geraetetypen ohne Kategorie. Diese zuerst einer Kategorie zuordnen.'
            );
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement(
                'ALTER TABLE `device_models` MODIFY `category_id` BIGINT UNSIGNED NOT NULL'
            ),
            'pgsql'  => DB::statement('ALTER TABLE device_models ALTER COLUMN category_id SET NOT NULL'),
            'sqlite' => $this->rebuildSqlite(nullable: false),
            default  => null,
        };
    }

    private function rebuildSqlite(bool $nullable): void
    {
        // Ein frueherer Versuch ist mittendrin abgebrochen: die Zieltabelle
        // steht schon, die Daten liegen noch daneben. Dann nur zu Ende fuehren.
        if (Schema::hasTable('device_models_tmp')) {
            $this->finishFromTmp();

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            $spalten = Schema::getColumnListing('device_models');

            // Neue Tabelle unter eigenem Namen aufbauen, damit kein Index- oder
            // Tabellenname kollidiert.
            $this->createTarget('device_models_new', $nullable, withIndex: false);
            $this->copyRows('device_models', 'device_models_new', $spalten);

            $this->dropIndexesOf('device_models');
            Schema::drop('device_models');

            DB::statement('ALTER TABLE device_models_new RENAME TO device_models');
            DB::statement('CREATE INDEX "device_models_category_id_index" ON "device_models" ("category_id")');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /** Zieltabelle steht bereits, die Daten stehen noch in device_models_tmp. */
    private function finishFromTmp(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $spalten = array_values(array_intersect(
                Schema::getColumnListing('device_models_tmp'),
                Schema::getColumnListing('device_models')
            ));

            $liste = implode(', ', array_map(static fn ($s) => '"'.$s.'"', $spalten));

            DB::statement(
                "INSERT INTO device_models ({$liste}) SELECT {$liste} FROM device_models_tmp ".
                'WHERE "id" NOT IN (SELECT "id" FROM device_models)'
            );

            $this->dropIndexesOf('device_models_tmp');
            Schema::drop('device_models_tmp');

            if (! $this->hasIndex('device_models_category_id_index')) {
                DB::statement('CREATE INDEX "device_models_category_id_index" ON "device_models" ("category_id")');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function createTarget(string $name, bool $nullable, bool $withIndex): void
    {
        Schema::create($name, function (Blueprint $table) use ($nullable, $withIndex) {
            $table->id();
            $spalte = $table->foreignId('category_id');
            if ($nullable) {
                $spalte->nullable();
            }
            $spalte->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('image')->nullable();
            $table->text('accessories')->nullable();
            $table->unsignedSmallInteger('default_loan_days')->default(14);
            $table->timestamps();

            if ($withIndex) {
                $table->index('category_id');
            }
        });
    }

    private function copyRows(string $von, string $nach, array $spalten): void
    {
        $gemeinsam = array_values(array_intersect($spalten, Schema::getColumnListing($nach)));
        $liste     = implode(', ', array_map(static fn ($s) => '"'.$s.'"', $gemeinsam));

        DB::statement("INSERT INTO {$nach} ({$liste}) SELECT {$liste} FROM {$von}");
    }

    private function dropIndexesOf(string $tabelle): void
    {
        foreach (DB::select(
            "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name NOT LIKE 'sqlite_%'",
            [$tabelle]
        ) as $index) {
            DB::statement('DROP INDEX IF EXISTS "'.$index->name.'"');
        }
    }

    private function hasIndex(string $name): bool
    {
        return DB::select("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = ?", [$name]) !== [];
    }
};
