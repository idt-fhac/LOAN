<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regressionstests fuer die Rechtepruefung.
 *
 * Jeder Test bildet eine konkrete Luecke ab, die vor dem Hardening ausnutzbar
 * war. Faellt einer dieser Tests, ist die Luecke zurueck.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $inventoryCounter = 0;

    private function makeUser(string $role, string $name = 'Test'): User
    {
        return User::create([
            'name'     => $name,
            'email'    => strtolower($name).uniqid().'@fh-aachen.de',
            'password' => Hash::make('geheimes-testpasswort'),
            'role'     => $role,
        ]);
    }

    private function makeRoom(): Room
    {
        return Room::create([
            'name'        => 'Seminarraum 1',
            'description' => 'Testraum',
            'location'    => 'Campus Jülich',
        ]);
    }

    private function makeDevice(): Device
    {
        $category = Category::firstOrCreate(['name' => 'Messtechnik'], ['slug' => 'messtechnik']);

        $model = DeviceModel::firstOrCreate(
            ['category_id' => $category->id, 'name' => 'Oszilloskop'],
            ['description' => 'Testgerät']
        );

        return $model->devices()->create([
            'inventory_no' => 'FB09-'.str_pad((string) ++$this->inventoryCounter, 3, '0', STR_PAD_LEFT),
        ]);
    }

    private function loanTo(Device $device, User $user): Loan
    {
        return $device->loans()->create([
            'user_id'        => $user->id,
            'borrower_name'  => $user->name,
            'issued_by_id'   => $user->id,
            'checked_out_at' => now(),
            'due_at'         => now()->addWeek(),
        ]);
    }

    private function bookRoom(Room $room, User $user): Reservation
    {
        return $room->reservations()->create([
            'user_id'   => $user->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at'   => now()->addDay()->setTime(12, 0),
            'purpose'   => 'Praktikum',
            'status'    => Reservation::STATUS_APPROVED,
        ]);
    }

    /* ---------------------------------------------------------------- Raeume */

    public function test_nutzer_darf_keinen_raum_loeschen(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->makeUser(User::ROLE_USER))
             ->delete(route('rooms.destroy', $room))
             ->assertForbidden();

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_nutzer_darf_keinen_raum_anlegen_oder_bearbeiten(): void
    {
        $user = $this->makeUser(User::ROLE_USER);
        $room = $this->makeRoom();

        $this->actingAs($user)->get(route('rooms.create'))->assertForbidden();
        $this->actingAs($user)->patch(route('rooms.update', $room), [
            'name' => 'Gekapert', 'location' => 'y',
        ])->assertForbidden();

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'Seminarraum 1']);
    }

    public function test_moderation_darf_raum_loeschen(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->makeUser(User::ROLE_MODERATION))
             ->delete(route('rooms.destroy', $room))
             ->assertRedirect();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    /* --------------------------------------------------------- Raumbuchungen */

    public function test_nutzer_darf_fremde_raumbuchung_nicht_stornieren(): void
    {
        $owner = $this->makeUser(User::ROLE_USER, 'Owner');
        $other = $this->makeUser(User::ROLE_USER, 'Other');
        $res   = $this->bookRoom($this->makeRoom(), $owner);

        $this->actingAs($other)
             ->delete(route('reservations.cancel', $res))
             ->assertForbidden();

        $this->assertDatabaseHas('reservations', ['id' => $res->id]);
    }

    public function test_nutzer_darf_fremde_raumbuchung_nicht_bearbeiten(): void
    {
        $owner = $this->makeUser(User::ROLE_USER, 'Owner');
        $other = $this->makeUser(User::ROLE_USER, 'Other');
        $res   = $this->bookRoom($this->makeRoom(), $owner);

        $this->actingAs($other)->get(route('reservations.edit', $res))->assertForbidden();
    }

    public function test_nutzer_darf_eigene_raumbuchung_stornieren(): void
    {
        $owner = $this->makeUser(User::ROLE_USER, 'Owner');
        $res   = $this->bookRoom($this->makeRoom(), $owner);

        $this->actingAs($owner)
             ->delete(route('reservations.cancel', $res))
             ->assertRedirect();

        $this->assertDatabaseMissing('reservations', ['id' => $res->id]);
    }

    public function test_buchungsliste_zeigt_nutzenden_nur_eigene_buchungen(): void
    {
        $owner = $this->makeUser(User::ROLE_USER, 'Owner');
        $other = $this->makeUser(User::ROLE_USER, 'Other');
        $this->bookRoom($this->makeRoom(), $owner);

        $response = $this->actingAs($other)->get(route('reservations.index'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('reservations'));
    }

    public function test_moderation_sieht_alle_buchungen(): void
    {
        $owner = $this->makeUser(User::ROLE_USER, 'Owner');
        $this->bookRoom($this->makeRoom(), $owner);

        $response = $this->actingAs($this->makeUser(User::ROLE_MODERATION))
                         ->get(route('reservations.index'));

        $this->assertCount(1, $response->viewData('reservations'));
    }

    /* ---------------------------------------------------------------- Geraete */

    public function test_nutzer_darf_kein_geraet_anlegen_oder_loeschen(): void
    {
        $user   = $this->makeUser(User::ROLE_USER);
        $device = $this->makeDevice();

        $this->actingAs($user)->post(route('devices.store'), [
            'title' => 'Fremd', 'category_id' => 1, 'inventory_no' => 'X-1',
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('devices.destroy', $device))->assertForbidden();

        $this->assertDatabaseHas('devices', ['id' => $device->id]);
    }

    public function test_nutzer_leiht_nur_auf_den_eigenen_namen_aus(): void
    {
        $user   = $this->makeUser(User::ROLE_USER, 'Julian');
        $device = $this->makeDevice();

        $this->actingAs($user)->post(route('devices.loan'), [
            'device_id'       => $device->id,
            'borrower_name'   => 'Jemand Anderes',
            'loan_start_date' => now()->format('Y-m-d'),
            'loan_end_date'   => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect();

        $loan = $device->fresh()->openLoan;

        $this->assertNotNull($loan);
        $this->assertSame('Julian', $loan->borrower_name);
        $this->assertSame($user->id, $loan->user_id);
    }

    public function test_moderation_leiht_auf_beliebigen_namen_aus(): void
    {
        $moderator = $this->makeUser(User::ROLE_MODERATION, 'Theke');
        $device    = $this->makeDevice();

        $this->actingAs($moderator)->post(route('devices.loan'), [
            'device_id'       => $device->id,
            'borrower_name'   => 'Gast Person',
            'loan_start_date' => now()->format('Y-m-d'),
            'loan_end_date'   => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertSame('Gast Person', $device->fresh()->openLoan->borrower_name);
    }

    public function test_nutzer_darf_fremde_ausleihe_nicht_zurueckgeben(): void
    {
        $owner  = $this->makeUser(User::ROLE_USER, 'Owner');
        $other  = $this->makeUser(User::ROLE_USER, 'Other');
        $device = $this->makeDevice();
        $this->loanTo($device, $owner);

        $this->actingAs($other)
             ->post(route('devices.return'), ['device_id' => $device->id])
             ->assertForbidden();

        $this->assertTrue($device->fresh()->isLoaned());
    }

    public function test_nutzer_darf_eigene_ausleihe_zurueckgeben(): void
    {
        $owner  = $this->makeUser(User::ROLE_USER, 'Owner');
        $device = $this->makeDevice();
        $loan   = $this->loanTo($device, $owner);

        $this->actingAs($owner)
             ->post(route('devices.return'), ['device_id' => $device->id])
             ->assertRedirect();

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertFalse($device->fresh()->isLoaned());
    }

    /* ------------------------------------------------------- Nutzerverwaltung */

    public function test_nur_administration_erreicht_die_nutzerverwaltung(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_USER))
             ->get(route('users.index'))->assertForbidden();

        $this->actingAs($this->makeUser(User::ROLE_MODERATION))
             ->get(route('users.index'))->assertForbidden();

        $this->actingAs($this->makeUser(User::ROLE_ADMINISTRATION))
             ->get(route('users.index'))->assertOk();
    }

    public function test_letztes_administrationskonto_ist_geschuetzt(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMINISTRATION, 'Admin');

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_administration_kann_rolle_nicht_selbst_aendern(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMINISTRATION, 'Admin');

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'email' => $admin->email,
            'role'  => User::ROLE_USER,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_ADMINISTRATION, $admin->refresh()->role);
    }

    public function test_rollenhierarchie_schliesst_niedrigere_rollen_ein(): void
    {
        $this->assertTrue($this->makeUser(User::ROLE_ADMINISTRATION)->isModerator());
        $this->assertFalse($this->makeUser(User::ROLE_MODERATION)->isAdministrator());
        $this->assertFalse($this->makeUser(User::ROLE_USER)->isModerator());
    }

    public function test_neue_konten_sind_standardmaessig_einfache_nutzende(): void
    {
        $user = User::create([
            'name'     => 'Ohne Rolle',
            'email'    => 'ohne-rolle@fh-aachen.de',
            'password' => Hash::make('geheimes-testpasswort'),
        ]);

        $this->assertSame(User::ROLE_USER, $user->refresh()->role);
    }
}
