<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER           = 'user';
    public const ROLE_MODERATION     = 'moderation';
    public const ROLE_ADMINISTRATION = 'administration';

    /**
     * Rollenhierarchie: eine hoehere Rolle schliesst alle niedrigeren ein.
     */
    public const ROLE_LEVELS = [
        self::ROLE_USER           => 1,
        self::ROLE_MODERATION     => 2,
        self::ROLE_ADMINISTRATION => 3,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $attributes = [
        'role'   => self::ROLE_USER,
        'locale' => 'de',
    ];

    public static function assignableRoles(): array
    {
        return array_keys(self::ROLE_LEVELS);
    }

    /**
     * Prueft, ob der Nutzer mindestens die angegebene Rolle besitzt.
     */
    public function hasRole(string $role): bool
    {
        $required = self::ROLE_LEVELS[$role] ?? PHP_INT_MAX;
        $actual   = self::ROLE_LEVELS[$this->role] ?? 0;

        return $actual >= $required;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(self::ROLE_ADMINISTRATION);
    }

    /**
     * Darf Stammdaten (Geraete, Raeume, Kategorien) und fremde Vorgaenge verwalten.
     */
    public function isModerator(): bool
    {
        return $this->hasRole(self::ROLE_MODERATION);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function deviceReservations(): HasMany
    {
        return $this->hasMany(DeviceReservation::class);
    }

    /**
     * Geraete, die aktuell auf dieses Konto ausgeliehen sind.
     */
    public function borrowedDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'borrowed_by_user_id');
    }
}
