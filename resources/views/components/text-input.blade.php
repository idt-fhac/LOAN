@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'h-11 w-full rounded-sm border-gray-300 px-3.5 text-body text-gray-800 focus:border-yellow-400 focus:ring-0 disabled:bg-gray-100']) !!}>
