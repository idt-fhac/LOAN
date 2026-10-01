<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceReservationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Oeffentlich (ohne Login)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('devices.overview')
        : redirect()->route('login');
});

Route::view('/impressum', 'layouts.impressum')->name('impressum');
Route::view('/datenschutz', 'layouts.datenschutz')->name('datenschutz');

Route::post('/locale', function (Request $request) {
    $data = $request->validate(['locale' => 'required|in:de,en']);

    session(['locale' => $data['locale']]);

    if (Auth::check()) {
        $user = Auth::user();
        if ($user->locale !== $data['locale']) {
            $user->locale = $data['locale'];
            $user->save();
        }
    }

    return back();
})->middleware('throttle:30,1')->name('locale.switch');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:10,1']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Angemeldet - jede Rolle
|--------------------------------------------------------------------------
| Lesen, selbst ausleihen, selbst vormerken, eigene Vorgaenge verwalten.
| Die Eigentuemerpruefung passiert in den Policies, nicht hier.
*/

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/logs', 'layouts.logs')->name('logs');
    Route::view('/product', 'layouts.product')->name('product');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Geraete: lesen
    Route::get('/devices/overview', [DeviceController::class, 'overview'])->name('devices.overview');
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/devices/{device}', [DeviceController::class, 'show'])
        ->whereNumber('device')->name('devices.show');

    // Ausleihe und Rueckgabe - die Policy entscheidet, ob fuer sich oder fuer andere
    Route::post('/devices/loan', [DeviceController::class, 'loan'])->name('devices.loan');
    Route::post('/devices/return', [DeviceController::class, 'return'])->name('devices.return');

    // Geraete-Vormerkungen
    Route::get('/devices/{device}/reserve', [DeviceReservationController::class, 'create'])
        ->whereNumber('device')->name('devices.reservations.create');
    Route::post('/devices/{device}/reserve', [DeviceReservationController::class, 'store'])
        ->whereNumber('device')->name('devices.reservations.store');
    Route::delete('/devices/reservations/{reservation}', [DeviceReservationController::class, 'destroy'])
        ->whereNumber('reservation')->name('devices.reservations.destroy');

    // Raeume: lesen und buchen
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}/reserve', [RoomController::class, 'reserve'])
        ->whereNumber('room')->name('rooms.reserve');
    Route::post('/rooms/{room}/reserve', [RoomController::class, 'storeReservation'])
        ->whereNumber('room')->name('rooms.storeReservation');

    // Buchungen: die Liste ist im Controller auf die eigenen eingegrenzt
    Route::get('/reservations', [RoomController::class, 'reservations'])->name('reservations.index');
    Route::get('/reservations/archived', [RoomController::class, 'archived'])->name('reservations.archived');
    Route::get('/reservations/{reservation}/edit', [RoomController::class, 'editReservation'])
        ->whereNumber('reservation')->name('reservations.edit');
    Route::patch('/reservations/{reservation}', [RoomController::class, 'updateReservation'])
        ->whereNumber('reservation')->name('reservations.update');
    Route::delete('/reservations/{reservation}', [RoomController::class, 'cancelReservation'])
        ->whereNumber('reservation')->name('reservations.cancel');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
});

/*
|--------------------------------------------------------------------------
| Moderation und hoeher - Stammdaten und fremde Vorgaenge
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:moderation'])->group(function () {
    Route::get('/devices/create', [DeviceController::class, 'create'])->name('devices.create');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::get('/devices/{device}/edit', [DeviceController::class, 'edit'])
        ->whereNumber('device')->name('devices.edit');
    Route::put('/devices/{device}', [DeviceController::class, 'update'])
        ->whereNumber('device')->name('devices.update');
    Route::patch('/devices/{device}', [DeviceController::class, 'update'])->whereNumber('device');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])
        ->whereNumber('device')->name('devices.destroy');

    Route::get('/log', [DeviceController::class, 'log'])->name('devices.log');

    // Vormerkung genehmigen oder ablehnen
    Route::patch('/devices/reservations/{reservation}/decide', [DeviceReservationController::class, 'decide'])
        ->whereNumber('reservation')->name('devices.reservations.decide');

    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])
        ->whereNumber('room')->name('rooms.edit');
    Route::patch('/rooms/{room}', [RoomController::class, 'update'])
        ->whereNumber('room')->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])
        ->whereNumber('room')->name('rooms.destroy');
});

/*
|--------------------------------------------------------------------------
| Administration - Nutzerverwaltung
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:administration'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/auth.php';
