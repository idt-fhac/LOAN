@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'border-l-[3px] border-yellow-400 bg-gray-100 px-4 py-3 text-[13px] text-gray-900']) }}>
        {{ $status }}
    </div>
@endif
