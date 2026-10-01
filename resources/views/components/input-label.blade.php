@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[13px] font-semibold text-gray-900']) }}>
    {{ $value ?? $slot }}
</label>
