<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Gilt fuer Raum- und Geraetevormerkungen gleichermassen - seit beide auf
 * derselben Tabelle liegen, gibt es dafuer nur noch eine Policy.
 */
class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->isOwnedBy($user) || $user->isModerator();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $reservation->isOwnedBy($user) || $user->isModerator();
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $reservation->isOwnedBy($user) || $user->isModerator();
    }

    /** Alle Vormerkungen aller Personen einsehen. */
    public function viewAll(User $user): bool
    {
        return $user->isModerator();
    }

    /** Genehmigen oder ablehnen - ausschliesslich Moderation. */
    public function decide(User $user, Reservation $reservation): bool
    {
        return $user->isModerator();
    }

    /** Eine Vormerkung in eine Ausleihe ueberfuehren (Abholung an der Theke). */
    public function fulfill(User $user, Reservation $reservation): bool
    {
        return $user->isModerator();
    }
}
