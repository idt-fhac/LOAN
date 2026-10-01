{{-- Sekundär: Kontur, beim Hover füllt sie sich schwarz. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 h-11 px-5 rounded-sm bg-white border border-gray-300 font-bold text-sm text-gray-900 hover:bg-gray-900 hover:border-gray-900 hover:text-white disabled:opacity-40 transition-colors duration-fast ease-standard']) }}>
    {{ $slot }}
</button>
