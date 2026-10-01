<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // Liste wird im Controller auf die eigenen Vorgaenge eingegrenzt
    }

    public function view(User $user, Loan $loan): bool
    {
        return $loan->belongsToUser($user) || $user->isModerator();
    }

    /** Alle Ausleihen aller Personen einsehen (Protokoll, Ueberfaelligkeitsliste). */
    public function viewAll(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->isModerator();
    }

    /** Rueckgabe buchen. */
    public function close(User $user, Loan $loan): bool
    {
        return $loan->isOpen() && ($user->isModerator() || $loan->belongsToUser($user));
    }

    /** Verlaengern: die Moderation immer, Nutzende nur solange nichts ueberfaellig ist. */
    public function extend(User $user, Loan $loan): bool
    {
        if (! $loan->isOpen()) {
            return false;
        }

        return $user->isModerator() || ($loan->belongsToUser($user) && ! $loan->isOverdue());
    }
}
