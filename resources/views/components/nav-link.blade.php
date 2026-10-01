@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'relative inline-flex items-center px-1 pt-1 text-sm font-semibold text-gray-900 border-b-[3px] border-yellow-400'
        : 'relative inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 border-b-[3px] border-transparent hover:text-gray-900 hover:border-gray-300 transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
