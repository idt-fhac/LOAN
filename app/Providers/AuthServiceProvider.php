<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\DeviceModelPolicy;
use App\Policies\DevicePolicy;
use App\Policies\LoanPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RoomPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Category::class    => CategoryPolicy::class,
        Device::class      => DevicePolicy::class,
        DeviceModel::class => DeviceModelPolicy::class,
        Loan::class        => LoanPolicy::class,
        Reservation::class => ReservationPolicy::class,
        Room::class        => RoomPolicy::class,
        User::class        => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Kurznamen statt Klassenpfaden in reservable_type - damit bleibt die
        // Tabelle lesbar und ein spaeterer Namespace-Umzug bricht keine Daten.
        Relation::enforceMorphMap([
            'room'   => Room::class,
            'device' => Device::class,
        ]);

        // Stammdatenpflege: Geraete, Raeume, Kategorien, fremde Vorgaenge.
        Gate::define('manage-inventory', fn (User $user) => $user->isModerator());

        // Nutzerverwaltung.
        Gate::define('manage-users', fn (User $user) => $user->isAdministrator());
    }
}
