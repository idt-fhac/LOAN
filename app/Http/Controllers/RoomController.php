<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesReservations;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    use HandlesReservations;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::orderBy('name')->get();

        return view('rooms.index', compact('rooms'));
    }

    /** Aktuelle Raumbuchungen. Nutzende sehen nur die eigenen, Moderation alle. */
    public function reservations()
    {
        $this->authorize('viewAny', Reservation::class);

        $rooms = Room::orderBy('name')->get();

        $reservations = $this->scopedRoomReservations()->upcoming()->orderBy('starts_at')->get();

        return view('reservations.index', compact('rooms', 'reservations'));
    }

    public function archived()
    {
        $this->authorize('viewAny', Reservation::class);

        $rooms = Room::orderBy('name')->get();

        $reservations = $this->scopedRoomReservations()->past()->orderByDesc('starts_at')->get();

        return view('reservations.archived', compact('rooms', 'reservations'));
    }

    public function create()
    {
        $this->authorize('create', Room::class);

        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Room::class);

        $validated = $request->validate($this->roomRules());

        Room::create($validated);

        return redirect()->route('rooms.index')->with('status', __('Raum erfolgreich hinzugefügt!'));
    }

    public function edit(Room $room)
    {
        $this->authorize('update', $room);

        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $this->authorize('update', $room);

        // Frueher $room->update($request->all()) - damit liess sich jedes
        // fillable Feld ueberschreiben. Jetzt nur noch geprueftes Eingabe.
        $room->update($request->validate($this->roomRules()));

        return redirect()->route('rooms.index')->with('status', __('Raum erfolgreich aktualisiert!'));
    }

    public function destroy(Room $room)
    {
        $this->authorize('delete', $room);

        $room->delete();

        return redirect()->route('rooms.index')->with('status', __('Raum erfolgreich gelöscht!'));
    }

    public function reserve(Room $room)
    {
        $this->authorize('reserve', $room);

        return view('rooms.reserve', compact('room'));
    }

    public function storeReservation(Request $request, Room $room)
    {
        $this->authorize('reserve', $room);

        $validated = $request->validate($this->reservationRules());

        [$start, $end] = $this->reservationWindow($validated);
        $purpose       = $this->assertWordLimit($validated['purpose'] ?? null);
        $this->assertNoOverlap($room, $start, $end);

        $room->reservations()->create([
            'user_id'          => Auth::id(),
            'reserved_by_name' => $this->reservedByName($request->input('reserved_by_name')),
            'starts_at'        => $start,
            'ends_at'          => $end,
            'purpose'          => $purpose,
            'status'           => Reservation::STATUS_APPROVED, // Raeume werden nicht genehmigt
        ]);

        return redirect()->route('rooms.index')->with('status', __('Raum erfolgreich reserviert!'));
    }

    public function editReservation(Reservation $reservation)
    {
        $this->authorize('update', $reservation);

        $room = $reservation->room;

        return view('reservations.edit', compact('reservation', 'room'));
    }

    public function updateReservation(Request $request, Reservation $reservation)
    {
        $this->authorize('update', $reservation);

        $validated = $request->validate($this->reservationRules());

        [$start, $end] = $this->reservationWindow($validated);
        $purpose       = $this->assertWordLimit($validated['purpose'] ?? null);
        $this->assertNoOverlap($reservation->reservable, $start, $end, $reservation->id);

        // Objekt und Eigentuemer bleiben unveraendert - sonst liesse sich eine
        // Buchung per Formularfeld einem anderen Konto unterschieben.
        $reservation->update([
            'starts_at' => $start,
            'ends_at'   => $end,
            'purpose'   => $purpose,
        ]);

        return redirect()->route('reservations.index')
            ->with('status', __('Reservierung erfolgreich aktualisiert!'));
    }

    public function cancelReservation(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $reservation->delete();

        return redirect()->route('reservations.index')
            ->with('status', __('Reservierung erfolgreich aufgehoben!'));
    }

    private function roomRules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'location'    => ['required', 'string', 'max:255'],
            'capacity'    => ['nullable', 'integer', 'min:1', 'max:2000'],
        ];
    }

    /** Basisquery: Moderation sieht alle Buchungen, alle anderen nur die eigenen. */
    private function scopedRoomReservations()
    {
        $user = Auth::user();

        return Reservation::query()
            ->forRooms()
            ->with(['reservable', 'user'])
            ->unless($user->isModerator(), fn ($q) => $q->where('user_id', $user->id));
    }
}
