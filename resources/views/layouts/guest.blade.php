<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LOAN</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Lato als freier Ersatz für die Hausschrift FF Clan - gleicher Gestalter.
         Bunny Fonts statt Google Fonts wegen der Datenübertragung. --}}
    <link href="https://fonts.bunny.net/css?family=lato:300,400,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-gray-800">
    <div class="flex min-h-screen flex-col lg:flex-row">

        <div class="flex shrink-0 flex-col bg-black px-8 py-10 text-white lg:w-1/3 lg:px-11 lg:py-14">
            <a href="{{ url('/') }}" aria-label="LOAN" class="self-start">
                <x-fh-flag class="block h-auto w-[22px]" />
            </a>
            <div class="flex-1 lg:min-h-[120px]"></div>
            <x-origin-line class="mb-3 mt-8 text-overline font-bold uppercase text-gray-500 lg:mt-0" />
            <h1 class="m-0 text-display font-black text-yellow-400">LOAN</h1>
            <div class="flex-1"></div>
            <div class="mt-8 h-1 w-24 bg-yellow-400"></div>
        </div>

        <div class="flex flex-1 items-center justify-center px-4 py-12 lg:px-14">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
