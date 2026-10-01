<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ein physisches Exemplar mit eigener Inventarnummer.
 *
 * Der Ausleihzustand ist keine Spalte mehr, sondern ergibt sich aus der offenen
 * Ausleihe. Die Accessoren unten bilden die frueheren Spaltennamen weiter ab,
 * damit bestehende Views unveraendert funktionieren.
 */
class Device extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_LOANED      = 'loaned';
    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'device_model_id',
        'inventory_no',
        'serial_no',
        'condition',
        'note',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Spiegelt den Datenbank-Default. Eloquent liest Defaults nach einem
     * INSERT nicht zurueck - ohne diese Zeile waere ein frisch angelegtes
     * Exemplar im Speicher inaktiv, bis es neu geladen wird.
     */
    protected $attributes = [
        'active' => true,
    ];

    protected $with = ['deviceModel'];

    /* ------------------------------------------------------------ Beziehungen */

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** Die laufende Ausleihe, falls das Geraet gerade draussen ist. */
    public function openLoan(): HasOne
    {
        return $this->hasOne(Loan::class)->whereNull('returned_at')->latestOfMany();
    }

    public function reservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    /* --------------------------------------------------------------- Zustand */

    public function isLoaned(): bool
    {
        return $this->openLoan !== null;
    }

    public function isAvailable(): bool
    {
        return $this->active && ! $this->isLoaned();
    }

    public function isBorrowedBy(?User $user): bool
    {
        return $user !== null
            && $this->openLoan !== null
            && (int) $this->openLoan->user_id === (int) $user->id;
    }

    /* ---------------------------------------------------- Scopes fuer Queries */

    public function scopeLoaned(Builder $query): Builder
    {
        return $query->whereHas('openLoan');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('active', true)->whereDoesntHave('openLoan');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereHas('openLoan', fn ($q) => $q->where('due_at', '<', now()));
    }

    /* ------------------------------------------------------------- Accessoren */
    /* Bilden die frueheren devices-Spalten ab, damit die Views unveraendert
       weiterlaufen. Neuer Code greift direkt auf deviceModel bzw. openLoan zu. */

    public function getTitleAttribute(): string
    {
        // Die Inventarnummer steht bewusst nicht im Titel - sie wird automatisch
        // vergeben und ist nur intern relevant.
        return $this->deviceModel?->name ?? '—';
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->deviceModel?->description;
    }

    public function getImageAttribute(): ?string
    {
        return $this->deviceModel?->image;
    }

    public function getCategoryAttribute(): ?Category
    {
        return $this->deviceModel?->category;
    }

    public function getCategoryIdAttribute(): ?int
    {
        return $this->deviceModel?->category_id;
    }

    /**
     * Frueher eine eigene Spalte, heute der Kategoriename. Ein Geraetetyp darf
     * ohne Kategorie existieren - die Views gruppieren danach, deshalb braucht
     * dieser Fall eine Beschriftung statt eines leeren Werts.
     */
    public function getGroupAttribute(): string
    {
        return $this->deviceModel?->category?->name ?? __('Ohne Kategorie');
    }

    public function getStatusAttribute(): string
    {
        if (! $this->active) {
            return self::STATUS_UNAVAILABLE;
        }

        return $this->isLoaned() ? self::STATUS_LOANED : self::STATUS_AVAILABLE;
    }

    public function getBorrowerNameAttribute(): ?string
    {
        return $this->openLoan?->borrower_name;
    }

    public function getLoanStartDateAttribute()
    {
        return $this->openLoan?->checked_out_at;
    }

    public function getLoanEndDateAttribute()
    {
        return $this->openLoan?->due_at;
    }

    public function getLoanPurposeAttribute(): ?string
    {
        return $this->openLoan?->purpose;
    }
}
