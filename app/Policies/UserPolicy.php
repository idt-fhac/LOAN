<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdministrator() || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Ein Administrationskonto darf sich nicht selbst loeschen und das letzte
     * Administrationskonto darf nicht entfernt werden - sonst sperrt sich die
     * Einrichtung dauerhaft aus.
     */
    public function delete(User $user, User $target): Response
    {
        if (! $user->isAdministrator()) {
            return Response::deny('Fuer diese Aktion fehlt die Berechtigung.');
        }

        if ($user->is($target)) {
            return Response::deny('Das eigene Konto kann nicht geloescht werden.');
        }

        if ($this->isLastAdministrator($target)) {
            return Response::deny('Das letzte Administrationskonto kann nicht geloescht werden.');
        }

        return Response::allow();
    }

    /**
     * Rollenwechsel: das letzte Administrationskonto darf nicht herabgestuft
     * werden, und niemand aendert die eigene Rolle.
     */
    public function changeRole(User $user, User $target): Response
    {
        if (! $user->isAdministrator()) {
            return Response::deny('Fuer diese Aktion fehlt die Berechtigung.');
        }

        if ($user->is($target)) {
            return Response::deny('Die eigene Rolle kann nicht geaendert werden.');
        }

        if ($this->isLastAdministrator($target)) {
            return Response::deny('Das letzte Administrationskonto kann nicht herabgestuft werden.');
        }

        return Response::allow();
    }

    private function isLastAdministrator(User $target): bool
    {
        if ($target->role !== User::ROLE_ADMINISTRATION) {
            return false;
        }

        return User::where('role', User::ROLE_ADMINISTRATION)->count() <= 1;
    }
}
