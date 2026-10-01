<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Ausleihvorgang. Offen, solange returned_at NULL ist - daraus ergibt sich
 * der Status des Geraets, statt ihn daneben zu fuehren.
 */
class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'user_id',
        'borrower_name',
        'issued_by_id',
        'returned_to_id',
        'checked_out_at',
        'due_at',
        'returned_at',
        'purpose',
        'condition_note',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'due_at'         => 'datetime',
        'returned_at'    => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Konto der ausleihenden Person - null bei Ausleihe an Gaeste ohne Konto. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function returnedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to_id');
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function belongsToUser(?User $user): bool
    {
        return $user !== null && $this->user_id !== null && (int) $this->user_id === (int) $user->id;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->where('due_at', '<', now());
    }
}
