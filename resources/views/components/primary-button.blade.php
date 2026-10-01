{{--
    Primäre Aktion: Mint-Fläche mit schwarzer Schrift (8,2:1).
    Beim Hover geht die Fläche auf mint-700 und die Schrift auf Weiß (4,6:1) —
    so legt es das FH-Design-System fest. Weiß auf reinem Mint wäre 2,5:1.
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 h-11 px-5 rounded-sm bg-yellow-400 border border-yellow-400 font-bold text-sm text-black hover:bg-yellow-600 hover:border-yellow-600 hover:text-white active:bg-yellow-700 active:border-yellow-700 disabled:opacity-40 transition-colors duration-fast ease-standard']) }}>
    {{ $slot }}
</button>
