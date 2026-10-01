<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, Device $device): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->isModerator();
    }

    /** Ausleihe auf einen beliebigen Namen buchen (Theke). */
    public function loanToAnyone(User $user): bool
    {
        return $user->isModerator();
    }

    /** Selbstausleihe: jede angemeldete Person, sofern das Exemplar frei ist. */
    public function loan(User $user, Device $device): bool
    {
        return $device->isAvailable();
    }

    /** Rueckgabe: Moderation fuer alle, Nutzende nur fuer die eigene Ausleihe. */
    public function return(User $user, Device $device): bool
    {
        return $device->isLoaned()
            && ($user->isModerator() || $device->isBorrowedBy($user));
    }

    /** Die vollstaendige Ausleihhistorie inkl. fremder Namen. */
    public function viewHistory(User $user, Device $device): bool
    {
        return $user->isModerator() || $device->isBorrowedBy($user);
    }
}
