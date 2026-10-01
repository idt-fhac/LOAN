@extends('layouts.app')

@section('content')
<div class="flex min-h-screen flex-col lg:flex-row">

    {{-- Linkes Drittel: die Marke. Entspricht der Dreiteilung der Positionsmarke. --}}
    <div class="flex shrink-0 flex-col bg-black px-8 py-10 text-white lg:w-1/3 lg:px-11 lg:py-14">
        {{-- Platzhalter statt Positionsmarke, siehe x-fh-flag. --}}
        <x-fh-flag class="block h-auto w-[22px]" />

        <div class="flex-1 lg:min-h-[120px]"></div>

        <x-origin-line class="mb-3 mt-8 text-overline font-bold uppercase text-gray-500 lg:mt-0" />
        <h1 class="m-0 mb-5 text-display font-black text-yellow-400">LOAN</h1>
        <p class="m-0 max-w-[30ch] text-[17px] leading-relaxed text-gray-200">
            {{ __('Geräteausleihe und Raumbuchung für Praktika, Projektarbeiten und Lehre.') }}
        </p>

        <div class="flex-1"></div>
        <div class="mt-8 h-1 w-24 bg-yellow-400"></div>
    </div>

    {{-- Rechts: die Anmeldung --}}
    <div class="flex flex-1 items-center justify-center px-4 py-12 lg:px-14">
        <div class="w-full max-w-sm">
            <h2 class="m-0 mb-1.5 text-h2 font-bold">{{ __('Anmelden') }}</h2>
            <p class="m-0 mb-7 text-sm text-gray-500">{{ __('Mit der Kennung des Fachbereichs.') }}</p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            @if ($errors->any())
                <div class="mb-5 border-l-[3px] border-red-600 bg-gray-100 px-4 py-3" role="alert">
                    <ul class="m-0 list-none space-y-1 p-0 text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
                @csrf

                <label class="flex flex-col gap-2">
                    <span class="text-[13px] font-semibold text-gray-900">{{ __('E-Mail') }}</span>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" required autofocus
                           class="h-12 w-full border-gray-300 px-3.5 text-[15px] focus:border-gray-900 focus:ring-0">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-[13px] font-semibold text-gray-900">{{ __('Passwort') }}</span>
                    <input type="password" name="password" id="password"
                           autocomplete="current-password" required
                           class="h-12 w-full border-gray-300 px-3.5 text-[15px] focus:border-gray-900 focus:ring-0">
                </label>

                <label class="flex items-center gap-2.5 text-sm">
                    <input type="checkbox" name="remember" id="remember"
                           class="h-4 w-4 border-gray-400 text-yellow-400 focus:ring-0">
                    {{ __('Angemeldet bleiben') }}
                </label>

                {{-- Mint-Fläche mit schwarzer Schrift: 8,2:1. Weiß auf Mint wäre 2,5:1. --}}
                <button type="submit"
                        class="mt-1 flex h-12 items-center justify-center gap-2.5 rounded-sm bg-yellow-400 px-5 text-[15px] font-bold text-black transition-colors duration-fast ease-standard hover:bg-yellow-600 hover:text-white">
                    {{ __('Anmelden') }}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>
                    </svg>
                </button>

                <p class="m-0 text-[13px] leading-relaxed text-gray-500">
                    {{ __('Konten werden von der Geräteausgabe angelegt.') }}
                    <a href="{{ route('password.request') }}" class="text-yellow-700 hover:text-black">{{ __('Passwort vergessen?') }}</a>
                </p>

                <div class="mt-2 flex gap-5 border-t border-gray-300 pt-5 text-xs text-gray-500">
                    <a href="{{ url('/impressum') }}" class="text-gray-500 hover:text-black">{{ __('Impressum') }}</a>
                    <a href="{{ url('/datenschutz') }}" class="text-gray-500 hover:text-black">{{ __('Datenschutz') }}</a>
                    <form method="POST" action="{{ route('locale.switch') }}" class="ml-auto">
                        @csrf
                        <input type="hidden" name="locale" value="{{ app()->getLocale() === 'de' ? 'en' : 'de' }}">
                        <button type="submit" class="uppercase tracking-wider text-gray-500 hover:text-black">
                            {{ app()->getLocale() === 'de' ? 'EN' : 'DE' }}
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
