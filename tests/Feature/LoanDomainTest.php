<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fachliche Tests zum Zielmodell: Ausleihe als eigener Vorgang, Exemplare je
 * Geraetetyp, gemeinsame Ueberschneidungspruefung fuer Raeume und Geraete.
 */
class LoanDomainTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = User::ROLE_USER, string $name = 'Test'): User
    {
        return User::create([
            'name'     => $name,
            'email'    => strtolower($name).uniqid().'@fh-aachen.de',
            'password' => Hash::make('geheimes-testpasswort'),
            'role'     => $role,
        ]);
    }

    private function category(string $name = 'Messtechnik'): Category
    {
        return Category::firstOrCreate(['name' => $name], ['slug' => strtolower($name)]);
    }

    private function device(string $inventoryNo = 'FB09-001'): Device
    {
        $model = DeviceModel::create([
            'category_id' => $this->category()->id,
            'name'        => 'Oszilloskop',
        ]);

        return $model->devices()->create(['inventory_no' => $inventoryNo]);
    }

    /* ------------------------------------------------- Status aus der Ausleihe */

    public function test_geraetestatus_ergibt_sich_aus_der_offenen_ausleihe(): void
    {
        $device = $this->device();
        $owner  = $this->user(name: 'Owner');

        $this->assertSame(Device::STATUS_AVAILABLE, $device->status);

        $loan = $device->loans()->create([
            'user_id'        => $owner->id,
            'borrower_name'  => $owner->name,
            'checked_out_at' => now(),
            'due_at'         => now()->addWeek(),
        ]);

        $this->assertSame(Device::STATUS_LOANED, $device->fresh()->status);

        $loan->update(['returned_at' => now()]);

        $this->assertSame(Device::STATUS_AVAILABLE, $device->fresh()->status);
    }

    public function test_ausgemustertes_exemplar_ist_nicht_ausleihbar(): void
    {
        $device = $this->device();
        $device->update(['active' => false]);

        $this->assertSame(Device::STATUS_UNAVAILABLE, $device->fresh()->status);
        $this->assertFalse($device->fresh()->isAvailable());
    }

    public function test_accessoren_bilden_die_alten_feldnamen_ab(): void
    {
        $device = $this->device('FB09-042');
        $owner  = $this->user(name: 'Owner');

        $device->loans()->create([
            'user_id'        => $owner->id,
            'borrower_name'  => 'Owner',
            'checked_out_at' => now(),
            'due_at'         => now()->addDays(3),
            'purpose'        => 'Praktikum',
        ]);

        $device = $device->fresh();

        // Diese Zugriffe stehen so in den bestehenden Blade-Templates.
        $this->assertSame('Oszilloskop', $device->title);
        $this->assertStringNotContainsString('FB09-042', $device->title);
        $this->assertSame('Messtechnik', $device->group);
        $this->assertSame('Owner', $device->borrower_name);
        $this->assertSame('Praktikum', $device->loan_purpose);
        $this->assertNotNull($device->loan_end_date);
    }

    /* --------------------------------------------------------------- Exemplare */

    public function test_mehrere_exemplare_werden_gemeinsam_angelegt(): void
    {
        $moderator = $this->user(User::ROLE_MODERATION, 'Theke');

        $this->actingAs($moderator)->post(route('devices.store'), [
            'title'       => 'HTC Vive Pro 2',
            'category_id' => $this->category('VR')->id,
            'quantity'    => 3,
        ])->assertRedirect();

        $this->assertDatabaseCount('device_models', 1);
        $this->assertDatabaseCount('devices', 3);

        // Die Inventarnummer wird automatisch vergeben und ist eindeutig,
        // auch wenn sie in der Oberflaeche nicht vorkommt.
        $nummern = Device::pluck('inventory_no');
        $this->assertCount(3, $nummern->unique());
        $this->assertTrue($nummern->every(fn ($n) => filled($n) && ! str_starts_with($n, 'tmp-')));
    }

    public function test_geraet_kann_ohne_kategorie_angelegt_werden(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATION, 'Theke'))
             ->post(route('devices.store'), ['title' => 'Ungeordnetes Gerät'])
             ->assertRedirect();

        $this->assertDatabaseHas('device_models', [
            'name'        => 'Ungeordnetes Gerät',
            'category_id' => null,
        ]);

        $this->assertSame('Ohne Kategorie', Device::first()->group);
    }

    public function test_letztes_exemplar_nimmt_den_geraetetyp_mit(): void
    {
        $device = $this->device();

        $this->actingAs($this->user(User::ROLE_MODERATION, 'Theke'))
             ->delete(route('devices.destroy', $device))
             ->assertRedirect();

        $this->assertDatabaseCount('devices', 0);
        $this->assertDatabaseCount('device_models', 0);
    }

    public function test_ausgeliehenes_geraet_kann_nicht_geloescht_werden(): void
    {
        $device = $this->device();
        $owner  = $this->user(name: 'Owner');

        $device->loans()->create([
            'user_id'        => $owner->id,
            'borrower_name'  => 'Owner',
            'checked_out_at' => now(),
            'due_at'         => now()->addWeek(),
        ]);

        $this->actingAs($this->user(User::ROLE_MODERATION, 'Theke'))
             ->delete(route('devices.destroy', $device))
             ->assertRedirect();

        $this->assertDatabaseHas('devices', ['id' => $device->id]);
    }

    public function test_kategorie_mit_geraeten_kann_nicht_geloescht_werden(): void
    {
        $this->device();

        $this->actingAs($this->user(User::ROLE_MODERATION, 'Theke'))
             ->delete(route('categories.destroy', $this->category()))
             ->assertRedirect();

        $this->assertDatabaseCount('categories', 1);
    }

    /* ----------------------------------------------------------- Vormerkungen */

    public function test_ueberschneidung_wird_fuer_raeume_und_geraete_gleich_geprueft(): void
    {
        $owner = $this->user(name: 'Owner');
        $room  = Room::create(['name' => 'SR1', 'location' => 'Jülich']);
        $device = $this->device();

        foreach ([$room, $device] as $reservable) {
            $reservable->reservations()->create([
                'user_id'   => $owner->id,
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at'   => now()->addDay()->setTime(12, 0),
                'status'    => Reservation::STATUS_APPROVED,
            ]);

            $this->assertTrue(Reservation::overlaps(
                $reservable,
                now()->addDay()->setTime(11, 0),
                now()->addDay()->setTime(13, 0)
            ), 'Überschneidung nicht erkannt für '.$reservable::class);

            // Halboffenes Intervall: direkt anschliessend ist keine Kollision.
            $this->assertFalse(Reservation::overlaps(
                $reservable,
                now()->addDay()->setTime(12, 0),
                now()->addDay()->setTime(14, 0)
            ), 'Anschlusstermin faelschlich als Kollision gewertet für '.$reservable::class);
        }
    }

    public function test_stornierte_vormerkung_blockiert_den_zeitraum_nicht(): void
    {
        $owner = $this->user(name: 'Owner');
        $device = $this->device();

        $device->reservations()->create([
            'user_id'   => $owner->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at'   => now()->addDay()->setTime(12, 0),
            'status'    => Reservation::STATUS_CANCELLED,
        ]);

        $this->assertFalse(Reservation::overlaps(
            $device,
            now()->addDay()->setTime(10, 30),
            now()->addDay()->setTime(11, 30)
        ));
    }

    public function test_raumbuchung_verhindert_ueberschneidung(): void
    {
        $room  = Room::create(['name' => 'SR1', 'location' => 'Jülich']);
        $owner = $this->user(name: 'Owner');

        $room->reservations()->create([
            'user_id'   => $owner->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at'   => now()->addDay()->setTime(12, 0),
            'status'    => Reservation::STATUS_APPROVED,
        ]);

        $this->actingAs($this->user(name: 'Other'))
             ->post(route('rooms.storeReservation', $room), [
                 'start_date' => now()->addDay()->format('Y-m-d'),
                 'start_time' => '11:00',
                 'end_date'   => now()->addDay()->format('Y-m-d'),
                 'end_time'   => '13:00',
                 'purpose'    => 'Kollision',
             ])
             ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_abholung_loest_die_eigene_vormerkung_ein(): void
    {
        $owner  = $this->user(name: 'Owner');
        $device = $this->device();

        $reservation = $device->reservations()->create([
            'user_id'   => $owner->id,
            'starts_at' => now()->startOfDay(),
            'ends_at'   => now()->addDays(3)->endOfDay(),
            'status'    => Reservation::STATUS_APPROVED,
        ]);

        $this->actingAs($owner)->post(route('devices.loan'), [
            'device_id'       => $device->id,
            'borrower_name'   => $owner->name,
            'loan_start_date' => now()->format('Y-m-d'),
            'loan_end_date'   => now()->addDays(3)->format('Y-m-d'),
        ])->assertRedirect();

        $reservation->refresh();

        $this->assertSame(Reservation::STATUS_FULFILLED, $reservation->status);
        $this->assertNotNull($reservation->loan_id);
    }

    public function test_nur_moderation_entscheidet_ueber_vormerkungen(): void
    {
        $owner  = $this->user(name: 'Owner');
        $device = $this->device();

        $reservation = $device->reservations()->create([
            'user_id'   => $owner->id,
            'starts_at' => now()->addDay(),
            'ends_at'   => now()->addDays(2),
            'status'    => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($owner)
             ->patch(route('devices.reservations.decide', $reservation), ['decision' => 'approve'])
             ->assertForbidden();

        $this->actingAs($this->user(User::ROLE_MODERATION, 'Theke'))
             ->patch(route('devices.reservations.decide', $reservation), ['decision' => 'approve'])
             ->assertRedirect();

        $this->assertSame(Reservation::STATUS_APPROVED, $reservation->fresh()->status);
    }

    public function test_ueberfaellige_ausleihen_sind_abfragbar(): void
    {
        $owner = $this->user(name: 'Owner');

        $overdue = $this->device('FB09-100');
        $overdue->loans()->create([
            'user_id'        => $owner->id,
            'borrower_name'  => 'Owner',
            'checked_out_at' => now()->subWeeks(3),
            'due_at'         => now()->subWeek(),
        ]);

        $onTime = $this->device('FB09-101');
        $onTime->loans()->create([
            'user_id'        => $owner->id,
            'borrower_name'  => 'Owner',
            'checked_out_at' => now(),
            'due_at'         => now()->addWeek(),
        ]);

        $this->assertSame(1, Device::overdue()->count());
        $this->assertSame(2, Device::loaned()->count());
    }
}
