<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isModerator();
    }

    /** Buchen darf jede angemeldete Person. */
    public function reserve(User $user, Room $room): bool
    {
        return true;
    }
}
