@php
    // Seitentitel aus der Route ableiten - die Views liefern keinen eigenen.
    $pageTitles = [
        'devices.overview'   => [__('Gebuchte Geräte'), __('Was gerade draußen ist')],
        'devices.index'      => [__('Geräte'), __('Bestand des Fachbereichs')],
        'devices.create'     => [__('Gerät anlegen'), __('Gerätetyp und Exemplare')],
        'devices.edit'       => [__('Gerät bearbeiten'), ''],
        'devices.show'       => [__('Gerät'), ''],
        'devices.log'        => [__('Ausleihprotokoll'), __('Alle Vorgänge')],
        'rooms.index'        => [__('Räume'), __('Buchbare Räume')],
        'rooms.create'       => [__('Raum anlegen'), ''],
        'rooms.edit'         => [__('Raum bearbeiten'), ''],
        'rooms.reserve'      => [__('Raum buchen'), ''],
        'reservations.index' => [__('Gebuchte Räume'), __('Aktuelle Buchungen')],
        'reservations.archived' => [__('Archiv'), __('Vergangene Buchungen')],
        'reservations.edit'  => [__('Buchung bearbeiten'), ''],
        'categories.index'   => [__('Kategorien'), __('Gliederung des Bestands')],
        'categories.create'  => [__('Kategorie anlegen'), ''],
        'categories.edit'    => [__('Kategorie bearbeiten'), ''],
        'users.index'        => [__('Konten'), __('Nutzerverwaltung')],
        'users.create'       => [__('Konto hinzufügen'), ''],
        'users.edit'         => [__('Konto bearbeiten'), ''],
        'profile.show'       => [__('Profil'), ''],
        'profile.edit'       => [__('Profil bearbeiten'), ''],
    ];
    $current  = Route::currentRouteName();
    $pageMeta = $pageTitles[$current] ?? ['LOAN', ''];

    $nav = [
        ['route' => 'devices.overview', 'label' => __('Gebuchte Geräte'), 'active' => 'devices.overview',
         'icon'  => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/>'],
        ['route' => 'devices.index', 'label' => __('Geräte'),
         // nicht devices.* - das schlösse devices.overview (Gebuchte Geräte) mit ein
         'active' => ['devices.index', 'devices.show', 'devices.create', 'devices.edit', 'devices.reservations.*'],
         'icon'  => '<rect x="3" y="4" width="18" height="16"/><path d="M3 9h18"/><path d="M8 4v5"/>'],
        ['route' => 'rooms.index', 'label' => __('Räume'), 'active' => 'rooms.*',
         'icon'  => '<path d="M4 3h10v18H4z"/><path d="M14 3l6 3v12l-6 3"/><circle cx="11" cy="12" r="1"/>'],
        ['route' => 'reservations.index', 'label' => __('Gebuchte Räume'), 'active' => 'reservations.*',
         'icon'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        ['route' => 'categories.index', 'label' => __('Kategorien'), 'active' => 'categories.*',
         'icon'  => '<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="7.5" cy="7.5" r="1.5"/>'],
    ];

    $adminNav = [
        ['route' => 'users.index', 'label' => __('Konten'), 'active' => 'users.*',
         'icon'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.5 3.5 0 0 1 0 6.6"/><path d="M17.5 14.4A6.5 6.5 0 0 1 21.5 20"/>'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LOAN &middot; {{ $pageMeta[0] }}</title>

    {{-- Bunny Fonts statt Google Fonts: gleiche Schriften, aber ohne
         Datenübertragung in die USA - an einer Hochschule das kleinere Problem. --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Lato als freier Ersatz für die Hausschrift FF Clan - gleicher Gestalter.
         Bunny Fonts statt Google Fonts wegen der Datenübertragung. --}}
    <link href="https://fonts.bunny.net/css?family=lato:300,400,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-gray-800">

    @auth
        <div class="flex min-h-screen">

            {{-- Positionsleiste: greift die Dreiteilung der Marke auf --}}
            <nav class="fh-rail hidden w-[88px] shrink-0 flex-col bg-black md:flex"
                 aria-label="{{ __('Hauptnavigation') }}">
                {{-- Platzhalter statt Positionsmarke, siehe x-fh-flag. --}}
                <a href="{{ route('devices.overview') }}"
                   class="flex flex-col items-center gap-3 px-2 py-6"
                   aria-label="LOAN &ndash; {{ __('Startseite') }}">
                    <x-fh-flag class="block h-auto w-[14px]" />
                    <span class="text-[13px] font-bold tracking-tight text-white">LOAN</span>
                </a>

                <div class="flex flex-col">
                    @foreach (array_merge($nav, Auth::user()->isAdministrator() ? $adminNav : []) as $item)
                        @php $on = Route::is(...(array) $item['active']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @if ($on) aria-current="page" @endif
                           class="relative flex flex-col items-center gap-1.5 px-1 py-3.5 no-underline transition-colors
                                  {{ $on ? 'text-yellow-400' : 'text-gray-400 hover:text-white' }}">
                            @if ($on)
                                <span class="absolute inset-y-2 left-0 w-[3px] bg-yellow-400"></span>
                            @endif
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $item['icon'] !!}
                            </svg>
                            <span class="text-center text-[9px] uppercase leading-tight tracking-wider">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="flex-1"></div>

                @can('manage-inventory')
                    <a href="{{ route('devices.log') }}"
                       class="flex flex-col items-center gap-1.5 px-1 py-3.5 text-gray-400 no-underline hover:text-white">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 3h11l3 3v15H5z"/><path d="M8 9h8"/><path d="M8 13h8"/><path d="M8 17h5"/>
                        </svg>
                        <span class="text-center text-[9px] uppercase leading-tight tracking-wider">{{ __('Protokoll') }}</span>
                    </a>
                @endcan

                @if (filled(config('loan.unit_short')))
                    <div class="pb-4 pt-2 text-center text-[9px] tracking-widest text-gray-500">
                        {{ config('loan.unit_short') }}
                    </div>
                @endif
            </nav>

            <div class="flex min-w-0 flex-1 flex-col">

                {{-- Kopfzeile --}}
                <header class="flex shrink-0 flex-wrap items-center gap-x-6 gap-y-3 border-b border-gray-300 bg-white px-4 py-4 lg:px-10">
                    <div class="min-w-0">
                        <h1 class="m-0 truncate text-h2 font-bold">{{ $pageMeta[0] }}</h1>
                        @if ($pageMeta[1])
                            <p class="m-0 mt-0.5 text-caption text-gray-500">{{ $pageMeta[1] }}</p>
                        @endif
                    </div>

                    <div class="flex-1"></div>

                    <div class="flex items-center gap-3">
                        {{-- Sprache --}}
                        <form method="POST" action="{{ route('locale.switch') }}" class="flex items-center gap-1">
                            @csrf
                            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'de' ? 'en' : 'de' }}">
                            <button type="submit"
                                    class="border border-gray-300 px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-700 hover:border-gray-900 hover:text-gray-900">
                                {{ app()->getLocale() === 'de' ? 'EN' : 'DE' }}
                            </button>
                        </form>

                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 no-underline">
                            <span class="flex h-9 w-9 items-center justify-center bg-black text-[13px] font-semibold text-yellow-400">
                                {{ Str::of(Auth::user()->name)->explode(' ')->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode('') }}
                            </span>
                            <span class="hidden flex-col leading-tight sm:flex">
                                <span class="text-[13px] font-semibold text-gray-900">{{ Auth::user()->name }}</span>
                                <span class="text-[11px] uppercase tracking-wide text-gray-500">
                                    @switch(Auth::user()->role)
                                        @case('administration') {{ __('Administration') }} @break
                                        @case('moderation') {{ __('Moderation') }} @break
                                        @default {{ __('Nutzer:in') }}
                                    @endswitch
                                </span>
                            </span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex h-9 items-center gap-2 border border-gray-300 px-3 text-[13px] font-semibold text-gray-700 hover:border-gray-900 hover:text-gray-900"
                                    title="{{ __('Ausloggen') }}">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M17 8l4 4-4 4"/><path d="M21 12H9"/>
                                </svg>
                                <span class="hidden lg:inline">{{ __('Ausloggen') }}</span>
                            </button>
                        </form>
                    </div>
                </header>

                {{-- Navigation auf schmalen Bildschirmen --}}
                <nav class="flex gap-1 overflow-x-auto border-b border-gray-300 bg-black px-2 md:hidden"
                     aria-label="{{ __('Hauptnavigation') }}">
                    @foreach (array_merge($nav, Auth::user()->isAdministrator() ? $adminNav : []) as $item)
                        @php $on = Route::is(...(array) $item['active']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @if ($on) aria-current="page" @endif
                           class="relative whitespace-nowrap px-3 py-3 text-[13px] font-semibold no-underline
                                  {{ $on ? 'text-yellow-400' : 'text-gray-400' }}">
                            @if ($on)
                                <span class="absolute inset-x-2 top-0 h-[3px] bg-yellow-400"></span>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <main class="flex-1 px-4 py-8 lg:px-10">
                    @yield('content')
                </main>

                @include('layouts.footer')
            </div>
        </div>
    @else
        {{-- Ohne Anmeldung: keine Navigation. Die Anmeldeseite bringt ihre eigene
             Marke mit, alle anderen offenen Seiten bekommen eine schmale Leiste. --}}
        @unless (Route::is('login'))
            <header class="flex items-center gap-4 bg-black px-4 py-4 lg:px-10">
                <a href="{{ url('/') }}" aria-label="LOAN" class="flex items-center gap-3">
                    <x-fh-flag orientation="horizontal" class="block h-[7px] w-auto" />
                </a>
                <span class="text-lg font-bold tracking-tight text-white">LOAN</span>
                <span class="flex-1"></span>
                <a href="{{ route('login') }}"
                   class="border border-gray-500 px-3 py-1.5 text-[13px] font-semibold text-white no-underline hover:border-yellow-400 hover:text-yellow-400">
                    {{ __('Anmelden') }}
                </a>
            </header>
        @endunless

        <main class="min-h-screen">
            @yield('content')
        </main>
    @endauth

    @yield('scripts')
</body>

</html>
