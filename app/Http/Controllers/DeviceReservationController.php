<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesReservations;
use App\Models\Device;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Geraetevormerkungen. Liegen seit dem Zielmodell auf derselben Tabelle wie
 * Raumbuchungen - die gemeinsame Logik steckt in HandlesReservations.
 */
class DeviceReservationController extends Controller
{
    use HandlesReservations;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Device $device)
    {
        $this->authorize('create', Reservation::class);

        return view('devices.reservations.create', compact('device'));
    }

    public function store(Request $request, Device $device)
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validate($this->reservationRules());

        [$start, $end] = $this->reservationWindow($validated);
        $purpose       = $this->assertWordLimit($validated['purpose'] ?? null);
        $this->assertNoOverlap($device, $start, $end);

        $device->reservations()->create([
            'user_id'          => Auth::id(),
            'reserved_by_name' => $this->reservedByName($request->input('reserved_by_name')),
            'starts_at'        => $start,
            'ends_at'          => $end,
            'purpose'          => $purpose,
            'status'           => Reservation::STATUS_PENDING,
        ]);

        return redirect()->route('devices.show', $device)
            ->with('success', __('Gerät wurde erfolgreich vorgemerkt.'));
    }

    public function destroy(Reservation $reservation)
    {
        // Policy: Eigentuemer oder Moderation.
        $this->authorize('delete', $reservation);

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        return back()->with('status', __('Die Vormerkung wurde widerrufen.'));
    }

    /** Vormerkung genehmigen oder ablehnen - nur Moderation. */
    public function decide(Request $request, Reservation $reservation)
    {
        $this->authorize('decide', $reservation);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        $reservation->update([
            'status' => $validated['decision'] === 'approve'
                ? Reservation::STATUS_APPROVED
                : Reservation::STATUS_REJECTED,
            'decided_by_id' => Auth::id(),
            'decided_at'    => now(),
        ]);

        return back()->with('status', $validated['decision'] === 'approve'
            ? __('Die Vormerkung wurde genehmigt.')
            : __('Die Vormerkung wurde abgelehnt.'));
    }
}
