<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\Loan;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Eager Loading fuer alles, was die Accessoren des Device brauchen. */
    private function deviceQuery()
    {
        return Device::with(['deviceModel.category', 'openLoan.user']);
    }

    public function index()
    {
        $this->authorize('viewAny', Device::class);

        $devices = $this->deviceQuery()->get()
            ->sortBy(fn (Device $d) => [$d->group ?? '', $d->title])
            ->values();

        return view('devices.index', compact('devices'));
    }

    public function show(Device $device)
    {
        $this->authorize('view', $device);

        $device->load(['deviceModel.category', 'openLoan.user']);

        // Die Views erwarten eine flache Vorgangsliste. Aus jeder Ausleihe
        // werden bis zu zwei Eintraege: Ausgabe und Rueckgabe.
        $histories = Auth::user()->can('viewHistory', $device)
            ? $this->historyFor($device)
            : collect();

        return view('devices.show', compact('device', 'histories'));
    }

    private function historyFor(Device $device)
    {
        return $device->loans()
            ->with(['issuedBy', 'returnedTo'])
            ->latest('checked_out_at')
            ->get()
            ->flatMap(function (Loan $loan) {
                $entries = [(object) [
                    'created_at' => $loan->checked_out_at,
                    'action'     => 'loaned',
                    'user_name'  => $loan->borrower_name,
                    'action_by'  => $loan->issuedBy?->name ?? $loan->borrower_name,
                ]];

                if ($loan->returned_at) {
                    $entries[] = (object) [
                        'created_at' => $loan->returned_at,
                        'action'     => 'returned',
                        'user_name'  => $loan->borrower_name,
                        'action_by'  => $loan->returnedTo?->name ?? $loan->borrower_name,
                    ];
                }

                return $entries;
            })
            ->sortByDesc('created_at')
            ->values();
    }

    public function overview()
    {
        $this->authorize('viewAny', Device::class);

        $user = Auth::user();

        $devices = $this->deviceQuery()->loaned()->get()
            ->sortBy(fn (Device $d) => [$d->group ?? '', $d->title])
            ->values();

        // Vormerkungen: Moderation sieht alle, Nutzende nur die eigenen - sonst
        // waeren Zweck und Name fremder Vormerkungen fuer alle lesbar.
        $reservations = Reservation::query()
            ->forDevices()
            ->with(['reservable.deviceModel', 'user:id,name'])
            ->blocking()
            ->unless($user->isModerator(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        return view('devices.overview', compact('devices', 'reservations'));
    }

    /**
     * Ausleihe buchen. Die Moderation darf auf einen beliebigen Namen ausleihen,
     * alle anderen ausschliesslich auf den eigenen.
     *
     * Offene oder genehmigte Vormerkungen anderer Personen im gewuenschten
     * Zeitraum verhindern eine Selbstausleihe. Die Moderation darf sie
     * uebergehen - die Vormerkung bleibt dabei bestehen, und die Moderation
     * bekommt einen Hinweis.
     */
    public function loan(Request $request)
    {
        $validated = $request->validate([
            'device_id'       => ['required', 'integer', 'exists:devices,id'],
            'borrower_name'   => ['required', 'string', 'max:255'],
            'loan_start_date' => ['required', 'date'],
            'loan_end_date'   => ['required', 'date', 'after_or_equal:loan_start_date'],
            'loan_purpose'    => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        $uebergangen = DB::transaction(function () use ($validated, $user) {
            // Sperre verhindert, dass zwei gleichzeitige Anfragen dasselbe
            // Exemplar doppelt verleihen.
            $device = Device::whereKey($validated['device_id'])->lockForUpdate()->firstOrFail();

            $this->authorize('loan', $device);

            $mayOverride  = $user->can('loanToAnyone', Device::class);
            $borrowerName = $validated['borrower_name'];
            $borrowerId   = null;

            if (! $mayOverride) {
                // Selbstausleihe: Name serverseitig setzen, damit niemand auf
                // einen fremden Namen bucht.
                $borrowerName = $user->name;
                $borrowerId   = $user->id;
            }

            $checkedOut = Carbon::parse($validated['loan_start_date'])->startOfDay();
            $due        = Carbon::parse($validated['loan_end_date'])->endOfDay();

            // Vormerkungen fuer dieses Exemplar, die den Zeitraum beruehren und
            // noch nicht eingeloest sind.
            $imZeitraum = Reservation::query()
                ->forDevices()
                ->where('reservable_id', $device->id)
                ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_APPROVED])
                ->where('starts_at', '<', $due)
                ->where('ends_at', '>', $checkedOut);

            // Welche davon gehoert der entleihenden Person? Bei Selbstausleihe
            // ueber das Konto, an der Theke ueber den eingetragenen Namen.
            $eigene = fn ($q) => $borrowerId
                ? $q->where('user_id', $borrowerId)
                : $q->where('reserved_by_name', $borrowerName);

            $fremde = (clone $imZeitraum)
                ->where(fn ($q) => $borrowerId
                    ? $q->where('user_id', '!=', $borrowerId)
                    : $q->whereNull('reserved_by_name')->orWhere('reserved_by_name', '!=', $borrowerName))
                ->with('user:id,name')
                ->orderBy('starts_at')
                ->get();

            if ($fremde->isNotEmpty() && ! $mayOverride) {
                throw ValidationException::withMessages([
                    'loan_end_date' => $this->konfliktMeldung($fremde->first(), $checkedOut),
                ]);
            }

            $loan = Loan::create([
                'device_id'      => $device->id,
                'user_id'        => $borrowerId,
                'borrower_name'  => $borrowerName,
                'issued_by_id'   => $user->id,
                'checked_out_at' => $checkedOut,
                'due_at'         => $due,
                'purpose'        => $validated['loan_purpose'] ?? null,
            ]);

            // Die eigene Vormerkung gilt mit der Abholung als eingeloest.
            // Fremde bleiben unangetastet - auch wenn die Moderation sie
            // uebergeht, wollen die Vormerkenden das Geraet weiterhin.
            (clone $imZeitraum)->where($eigene)->update([
                'status'  => Reservation::STATUS_FULFILLED,
                'loan_id' => $loan->id,
            ]);

            return $fremde;
        });

        $message = __('Das Gerät wurde erfolgreich verliehen.');

        if ($uebergangen->isNotEmpty()) {
            $message .= ' '.__('Achtung: Der Zeitraum überschneidet sich mit der Vormerkung von :namen. Die Vormerkung bleibt bestehen.', [
                'namen' => $uebergangen
                    ->map(fn (Reservation $r) => sprintf(
                        '%s (%s – %s)',
                        $r->reserved_by_name ?? $r->user?->name ?? '—',
                        $r->starts_at->format('d.m.Y H:i'),
                        $r->ends_at->format('d.m.Y H:i'),
                    ))
                    ->join(', ', ' und '),
            ]);
        }

        return redirect()->route('devices.index')->with('status', $message);
    }

    /**
     * Fehlermeldung fuer eine Selbstausleihe, die in eine fremde Vormerkung
     * faellt. Nennt keine Namen - fremde Vormerkungen sind fuer Nutzende
     * nicht einsehbar - schlaegt aber das spaeteste moegliche Rueckgabedatum
     * vor, falls es eines gibt.
     */
    private function konfliktMeldung(Reservation $konflikt, Carbon $checkedOut): string
    {
        $meldung = __('Das Gerät ist vom :start bis :end für eine andere Person vorgemerkt.', [
            'start' => $konflikt->starts_at->format('d.m.Y H:i'),
            'end'   => $konflikt->ends_at->format('d.m.Y H:i'),
        ]);

        // Rueckgabe gilt bis Tagesende. Der letzte freie Tag ist also der Tag
        // vor Beginn der Vormerkung - sofern der nicht vor dem Ausleihbeginn liegt.
        $letzterTag = $konflikt->starts_at->copy()->subDay()->startOfDay();

        if ($letzterTag->greaterThanOrEqualTo($checkedOut)) {
            $meldung .= ' '.__('Eine Ausleihe bis einschließlich :datum ist möglich.', [
                'datum' => $letzterTag->format('d.m.Y'),
            ]);
        }

        return $meldung;
    }

    /**
     * Rueckgabe buchen. Nutzende koennen nur die eigene Ausleihe zurueckgeben,
     * die Moderation jede.
     */
    public function return(Request $request)
    {
        $validated = $request->validate([
            'device_id'      => ['required', 'integer', 'exists:devices,id'],
            'condition_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($validated, $user) {
            $device = Device::whereKey($validated['device_id'])->lockForUpdate()->firstOrFail();

            $this->authorize('return', $device);

            $loan = $device->loans()->open()->lockForUpdate()->firstOrFail();

            $loan->update([
                'returned_at'    => now(),
                'returned_to_id' => $user->id,
                'condition_note' => $validated['condition_note'] ?? $loan->condition_note,
            ]);
        });

        return redirect()->route('devices.index')
            ->with('status', __('Das Gerät wurde erfolgreich zurückgegeben.'));
    }

    /** Gesamtprotokoll aller Ausleihvorgaenge (Moderation und hoeher). */
    public function log()
    {
        $this->authorize('viewAll', Loan::class);

        $loans = Loan::with(['device.deviceModel', 'user:id,name', 'issuedBy:id,name', 'returnedTo:id,name'])
            ->latest('checked_out_at')
            ->paginate(50);

        return view('devices.log', compact('loans'));
    }

    public function create()
    {
        $this->authorize('create', Device::class);

        $categories = Category::orderBy('name')->get();

        return view('devices.create', compact('categories'));
    }

    /**
     * Legt einen Geraetetyp samt Exemplaren an. Bei quantity > 1 bekommen die
     * Exemplare durchnummerierte Inventarnummern.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Device::class);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:5120'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'quantity'     => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);

        DB::transaction(function () use ($request, $validated, $quantity) {
            $model = DeviceModel::create([
                'category_id'  => $validated['category_id'] ?? null,
                'name'         => $validated['title'],
                'description'  => $validated['description'] ?? null,
                'manufacturer' => $validated['manufacturer'] ?? null,
                'image'        => $request->hasFile('image')
                    ? $request->file('image')->store('images', 'public')
                    : null,
            ]);

            for ($i = 0; $i < $quantity; $i++) {
                $this->createDevice($model);
            }
        });

        $message = $quantity === 1
            ? __('Gerät erfolgreich hinzugefügt.')
            : __(':count Exemplare erfolgreich hinzugefügt.', ['count' => $quantity]);

        return redirect()->route('devices.index')->with('success', $message);
    }

    /**
     * Legt ein Exemplar an und vergibt die Inventarnummer automatisch.
     *
     * Sie taucht in der Oberflaeche nicht auf, muss aber eindeutig sein. Die
     * laufende ID liefert genau das - sie steht erst nach dem Einfuegen fest,
     * deshalb wird sie in zwei Schritten gesetzt.
     */
    private function createDevice(DeviceModel $model): Device
    {
        $device = $model->devices()->create([
            'inventory_no' => 'tmp-'.Str::uuid(),
        ]);

        $device->update([
            'inventory_no' => 'INV-'.str_pad((string) $device->id, 5, '0', STR_PAD_LEFT),
        ]);

        return $device;
    }

    public function edit(Device $device)
    {
        $this->authorize('update', $device);

        $categories = Category::orderBy('name')->get();

        return view('devices.edit', compact('device', 'categories'));
    }

    /**
     * Aendert den Geraetetyp (gilt fuer alle Exemplare) und die Stammdaten
     * dieses einen Exemplars.
     */
    public function update(Request $request, Device $device)
    {
        $this->authorize('update', $device);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:5120'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'serial_no'    => ['nullable', 'string', 'max:128'],
            'active'       => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $validated, $device) {
            $model = $device->deviceModel;

            $payload = [
                'category_id'  => $validated['category_id'] ?? null,
                'name'         => $validated['title'],
                'description'  => $validated['description'] ?? null,
                'manufacturer' => $validated['manufacturer'] ?? null,
            ];

            if ($request->hasFile('image')) {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
                $payload['image'] = $request->file('image')->store('images', 'public');
            }

            $model->update($payload);

            $device->update([
                'serial_no'    => $validated['serial_no'] ?? $device->serial_no,
                'active'       => $request->boolean('active', $device->active),
            ]);
        });

        return redirect()->route('devices.index')
            ->with('success', __('Gerät erfolgreich aktualisiert.'));
    }

    public function destroy(Device $device)
    {
        $this->authorize('delete', $device);

        if ($device->isLoaned()) {
            return back()->with('error', __('Ein ausgeliehenes Gerät kann nicht gelöscht werden.'));
        }

        DB::transaction(function () use ($device) {
            $model = $device->deviceModel;
            $device->delete();

            // War das das letzte Exemplar, verschwindet auch der Gerätetyp -
            // sonst bleiben leere Typen in der Liste stehen.
            if ($model && $model->devices()->count() === 0) {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
                $model->delete();
            }
        });

        return redirect()->route('devices.index')
            ->with('success', __('Gerät erfolgreich gelöscht.'));
    }
}
