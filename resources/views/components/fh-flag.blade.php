@props(['orientation' => 'vertical'])

{{--
    FH-Flagge: der Farbdreiklang Mint / Weiß / Schwarz als Dreiteilung,
    in den Proportionen der Positionsmarke (jedes Feld 1:3).

    Das ist NICHT die Positionsmarke der FH Aachen, sondern ein Platzhalter.
    Die offizielle Marke liegt als Positiv-Version in
    public/img/fh-positionsmarke.svg und schreibt auf dunklem Grund schwarze
    Schrift auf Schwarz - deshalb vorerst nur die Farben.

    ==> VOR DEM DEPLOYMENT: mit der Gestaltungsstelle der FH klären, ob diese
        Ableitung zulässig ist, oder durch die freigegebene Marke ersetzen.
        Siehe README, Abschnitt "Vor dem Deployment zu klären".

    Der Trick mit den drei Feldern: eines davon ist immer der Untergrund.
    Auf Schwarz tragen Mint und Weiß, auf Weiß tragen Mint und Schwarz.
    Dieselbe Datei funktioniert deshalb auf beiden Gründen.
--}}

@if ($orientation === 'horizontal')
    <svg viewBox="0 0 9 1" aria-hidden="true" focusable="false"
         {{ $attributes->merge(['class' => 'block h-2 w-auto']) }}>
        <rect x="0" y="0" width="3" height="1" fill="#00B2A9" />
        <rect x="3" y="0" width="3" height="1" fill="#FFFFFF" />
        <rect x="6" y="0" width="3" height="1" fill="#000000" />
    </svg>
@else
    <svg viewBox="0 0 1 9" aria-hidden="true" focusable="false"
         {{ $attributes->merge(['class' => 'block w-4 h-auto']) }}>
        <rect x="0" y="0" width="1" height="3" fill="#00B2A9" />
        <rect x="0" y="3" width="1" height="3" fill="#FFFFFF" />
        <rect x="0" y="6" width="1" height="3" fill="#000000" />
    </svg>
@endif
