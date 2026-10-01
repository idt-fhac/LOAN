{{-- Statusfarbe "error" aus dem FH-Design-System. Nur Löschen und Überfälligkeit. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 h-11 px-5 rounded-sm bg-white border border-red-600 font-bold text-sm text-red-600 hover:bg-red-600 hover:text-white transition-colors duration-fast ease-standard']) }}>
    {{ $slot }}
</button>
