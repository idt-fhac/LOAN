<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prueft die Mindestrolle. Mehrere Rollen koennen mit Komma oder Pipe uebergeben
 * werden ("role:moderation"), wobei eine hoehere Rolle die niedrigeren einschliesst -
 * eine Administration erfuellt also auch "role:moderation".
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        foreach ($roles as $role) {
            foreach (preg_split('/[|,]/', $role) as $candidate) {
                if ($user->hasRole(trim($candidate))) {
                    return $next($request);
                }
            }
        }

        abort(403, 'Fuer diese Aktion fehlt die Berechtigung.');
    }
}
