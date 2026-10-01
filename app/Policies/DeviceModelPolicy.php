<?php

namespace App\Policies;

use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeviceModelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeviceModel $deviceModel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function update(User $user, DeviceModel $deviceModel): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, DeviceModel $deviceModel): bool
    {
        return $user->isModerator();
    }
}
