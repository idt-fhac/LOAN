{{--
    Herkunftszeile: "Fachbereich 09 · Campus Jülich".

    Welche Teile erscheinen, steht in config/loan.php. Sind alle leer, rendert
    die Komponente nichts — dann trägt die Oberfläche keine Zuordnung zu einem
    einzelnen Fachbereich mehr.
--}}
@php
    $teile = collect([config('loan.unit'), config('loan.campus')])
        ->filter(fn ($teil) => filled($teil));
@endphp

@if ($teile->isNotEmpty())
    <p {{ $attributes->merge(['class' => 'text-overline font-bold uppercase text-gray-500']) }}>
        {{ $teile->implode(' · ') }}
    </p>
@endif
