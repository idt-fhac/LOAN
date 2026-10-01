import './bootstrap';

// Alpine trägt die Dropdowns und Dialoge in den Blade-Komponenten.
// Es stand schon in der package.json, wurde aber nie eingebunden - ohne das
// hier bleiben x-data-Elemente stumm.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
