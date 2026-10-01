<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */

     public function show()
     {
         $user = Auth::user();
         return view('profile.show', compact('user'));
     }

     public function edit()
     {
         $user = Auth::user();
         return view('profile.edit', compact('user'));
     }

    /**
     * Name, E-Mail und optional Passwort aendern.
     *
     * Ein neues Passwort setzt das aktuelle voraus - sonst koennte jede Person
     * mit Zugriff auf eine offene Sitzung das Konto uebernehmen.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'         => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->fill([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Eine neue Adresse ist nicht bestaetigt.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.show')->with('status', __('Profil erfolgreich aktualisiert.'));
    }

    // Kein destroy(): Konten loescht nur die Administration (UserController),
    // wo UserPolicy das Entfernen der letzten Administration verhindert.
}
