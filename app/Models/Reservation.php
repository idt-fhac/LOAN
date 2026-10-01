<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Vormerkung - polymorph fuer Raeume und Geraete.
 *
 * Frueher gab es dafuer zwei Tabellen mit unterschiedlichen Spaltentypen
 * (date+time fuer Raeume, datetime fuer Geraete) und damit zwei Implementierungen
 * derselben Ueberschneidungspruefung. Jetzt traegt ein Zeitstempelpaar beides.
 */
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FULFILLED = 'fulfilled';

    /** Zustaende, die einen Zeitraum blockieren. */
    public const BLOCKING = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_FULFILLED];

    protected $fillable = [
        'reservable_type',
        'reservable_id',
        'user_id',
        'reserved_by_name',
        'starts_at',
        'ends_at',
        'purpose',
        'status',
        'decided_by_id',
        'decided_at',
        'loan_id',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'decided_at' => 'datetime',
    ];

    /* ------------------------------------------------------------ Beziehungen */

    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /* ----------------------------------------------------------------- Scopes */

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING);
    }

    public function scopeForRooms(Builder $query): Builder
    {
        return $query->where('reservable_type', (new Room)->getMorphClass());
    }

    public function scopeForDevices(Builder $query): Builder
    {
        return $query->where('reservable_type', (new Device)->getMorphClass());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('ends_at', '<=', now());
    }

    /**
     * Ueberschneidet sich das Fenster mit einer bestehenden, blockierenden
     * Vormerkung desselben Objekts? Halboffenes Intervall: ein Ende um 12:00
     * kollidiert nicht mit einem Beginn um 12:00.
     */
    public static function overlaps(Model $reservable, Carbon $start, Carbon $end, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('reservable_type', $reservable->getMorphClass())
            ->where('reservable_id', $reservable->getKey())
            ->blocking()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    /* ------------------------------------------------------------- Accessoren */
    /* Halten die frueheren Feldnamen beider Vormerkungsmodelle am Leben, damit
       die bestehenden Views unveraendert funktionieren. */

    public function getStartAtAttribute(): ?Carbon
    {
        return $this->starts_at;
    }

    public function getEndAtAttribute(): ?Carbon
    {
        return $this->ends_at;
    }

    public function getStartDateAttribute(): ?Carbon
    {
        return $this->starts_at?->copy()->startOfDay();
    }

    public function getEndDateAttribute(): ?Carbon
    {
        return $this->ends_at?->copy()->startOfDay();
    }

    public function getStartTimeAttribute(): ?string
    {
        return $this->starts_at?->format('H:i');
    }

    public function getEndTimeAttribute(): ?string
    {
        return $this->ends_at?->format('H:i');
    }

    public function getRoomAttribute(): ?Room
    {
        return $this->reservable instanceof Room ? $this->reservable : null;
    }

    public function getRoomIdAttribute(): ?int
    {
        return $this->reservable_type === (new Room)->getMorphClass() ? (int) $this->reservable_id : null;
    }

    public function getDeviceAttribute(): ?Device
    {
        return $this->reservable instanceof Device ? $this->reservable : null;
    }
}
