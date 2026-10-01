@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'block w-full ps-3 pe-4 py-2 border-l-[3px] border-yellow-400 text-start text-base font-semibold text-gray-900 bg-gray-100'
        : 'block w-full ps-3 pe-4 py-2 border-l-[3px] border-transparent text-start text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
