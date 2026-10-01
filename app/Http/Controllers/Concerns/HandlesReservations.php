<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Gemeinsame Logik fuer Raum- und Geraetevormerkungen. Frueher war beides
 * doppelt implementiert - mit unterschiedlichen Ergebnissen.
 */
trait HandlesReservations
{
    protected function reservationRules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'end_time'   => ['required', 'date_format:H:i'],
            'purpose'    => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     *
     * @throws ValidationException
     */
    protected function reservationWindow(array $data): array
    {
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['start_date'].' '.$data['start_time']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $data['end_date'].' '.$data['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'end_time' => __('Das Ende muss nach dem Beginn liegen.'),
            ]);
        }

        return [$start, $end];
    }

    /**
     * Wortlimit serverseitig erzwingen. preg_split zaehlt zuverlaessiger als
     * str_word_count, das mit Umlauten nicht sauber umgeht.
     *
     * @throws ValidationException
     */
    protected function assertWordLimit(?string $purpose, int $limit = 100): ?string
    {
        $purpose = trim((string) $purpose);

        if ($purpose === '') {
            return null;
        }

        $words = count(preg_split('/\s+/u', $purpose, -1, PREG_SPLIT_NO_EMPTY));

        if ($words > $limit) {
            throw ValidationException::withMessages([
                'purpose' => __('Bitte höchstens :limit Wörter. (Aktuell: :count)', [
                    'limit' => $limit, 'count' => $words,
                ]),
            ]);
        }

        return $purpose;
    }

    /**
     * @throws ValidationException
     */
    protected function assertNoOverlap(Model $reservable, Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        if (Reservation::overlaps($reservable, $start, $end, $ignoreId)) {
            throw ValidationException::withMessages([
                'start_date' => __('Dieser Zeitraum überschneidet sich mit einer bestehenden Vormerkung.'),
            ]);
        }
    }

    /** Auf einen fremden Namen vormerken darf nur die Moderation. */
    protected function reservedByName(?string $requested): string
    {
        $user = Auth::user();

        return $user->isModerator() && filled($requested) ? $requested : $user->name;
    }
}
