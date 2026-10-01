<footer class="mt-auto bg-black px-4 py-10 text-gray-300 lg:px-10">
    <div class="mx-auto flex max-w-screen-xl flex-col gap-8 md:flex-row md:gap-14">

        <div class="max-w-sm">
            <div class="flex items-center gap-4">
                <x-fh-flag class="block h-auto w-[11px]" />
                <span class="text-lg font-bold tracking-tight text-white">LOAN</span>
            </div>
            <p class="mt-4 text-[13px] leading-relaxed">
                {{ __('Geräteausleihe und Raumbuchung.') }}
            </p>
            <x-origin-line class="mt-2 text-overline font-bold uppercase text-gray-500" />
            {{-- Namensnennung ist Bedingung der CC-BY-NC-SA-Lizenz. --}}
            <p class="mt-4 text-xs leading-relaxed text-gray-500">
                Basiert auf <a href="https://github.com/dusanvin/LOAN" target="_blank" rel="noopener"
                    class="text-gray-400 underline hover:text-white">LOAN</a>
                von Vincent Dusanek,
                <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/" target="_blank" rel="noopener"
                   class="text-gray-400 underline hover:text-white">CC BY-NC-SA 4.0</a>.
                {{ __('Bearbeitet für die FH Aachen.') }}
            </p>
        </div>

        <div class="grid flex-1 grid-cols-2 gap-8 sm:grid-cols-3">
            <div>
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">{{ __('Ausleihe') }}</p>
                <ul class="m-0 list-none space-y-1.5 p-0 text-[13px]">
                    <li><a href="{{ route('devices.index') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Geräte') }}</a></li>
                    <li><a href="{{ route('rooms.index') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Räume') }}</a></li>
                    <li><a href="{{ route('reservations.archived') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Archiv') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">{{ __('Über') }}</p>
                <ul class="m-0 list-none space-y-1.5 p-0 text-[13px]">
                    <li><a href="{{ url('/product') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Produkt') }}</a></li>
                    <li><a href="{{ url('/logs') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Änderungen') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">{{ __('Rechtliches') }}</p>
                <ul class="m-0 list-none space-y-1.5 p-0 text-[13px]">
                    <li><a href="{{ url('/impressum') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Impressum') }}</a></li>
                    <li><a href="{{ url('/datenschutz') }}" class="text-gray-300 no-underline hover:text-white">{{ __('Datenschutz') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mx-auto mt-10 flex max-w-screen-xl items-center gap-4 border-t border-gray-700 pt-6">
        <x-fh-flag orientation="horizontal" class="block h-[7px] w-auto" />
        @php
            $absender = collect([
                config('loan.organisation'),
                config('loan.organisation_suffix'),
                config('loan.unit'),
            ])->filter(fn ($teil) => filled($teil));
        @endphp
        @if ($absender->isNotEmpty())
            <p class="m-0 text-xs text-gray-500">{{ $absender->implode(' · ') }}</p>
        @endif
    </div>
</footer>
