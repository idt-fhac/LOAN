/** @type {import('tailwindcss').Config} */

/*
 * Corporate Design der FH Aachen.
 *
 * Quelle der Werte ist das Design System "FH Aachen Design System"
 * (Claude-Artifact, project/tokens.json). Weicht hier etwas ab, gilt dort.
 *
 * Zwei Ebenen:
 *
 *   fh-*            Die Tokens des Design Systems, 1:1. Neuer Code nimmt diese.
 *   gray-*, yellow-* Brücke für das Altmarkup, das durchgehend bg-gray-600 und
 *                   text-yellow-700 verwendet. Die Namen bleiben, die Werte
 *                   sind FH-Werte. Diese Ebene verschwindet, sobald die letzte
 *                   Alt-View umgestellt ist - neuer Code benutzt sie nicht.
 *
 * Kontrast (kritische Regel des CD): reines Mint traegt weder Fliesstext auf
 * Weiss (2,1:1) noch Weiss als Schrift (2,5:1).
 *
 *   fh-mint       #00B2A9  Flaechen, Marker, grosse Displayzeilen - kein Text auf Weiss
 *   fh-mint-700   #007A74  Text auf Weiss 4,6:1  |  Weiss darauf 4,6:1   (AA)
 *   fh-mint-800   #00524E  Text auf Weiss 7,9:1                          (AAA)
 *
 * Schrift auf Mint ist Schwarz. Beim Hover wechselt die primaere Schaltflaeche
 * auf mint-700 und die Schrift auf Weiss - so schreibt es das Design System.
 */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Hausschrift ist FF Clan (Lukasz Dziedzic), lizenzpflichtig und
                // nicht mitgeliefert. Lato ist vom selben Gestalter und der
                // naechste freie Ersatz; Verdana ist der Office-Fallback des CD.
                // Liegt Clan irgendwann als Webfont vor, greift der erste Eintrag.
                sans: ['FH Clan', 'Lato', 'Verdana', 'Helvetica Neue', 'Arial', 'sans-serif'],
                mono: ['SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
            },

            colors: {
                fh: {
                    mint:   '#00B2A9', // Pantone 326 C
                    black:  '#000000',
                    white:  '#FFFFFF',
                    silver: '#8A8D8F', // Schmuckfarbe, nur bei besonderen Anlaessen

                    'mint-300':  '#4DCBC4',
                    'mint-500':  '#00B2A9',
                    'mint-700':  '#007A74',
                    'mint-800':  '#00524E',
                    'mint-900':  '#002B29',
                    'mint-tint': '#B3E6E3',
                    'mint-wash': '#E6F7F6',

                    'gray-100': '#F2F2F2',
                    'gray-200': '#E0E0E0',
                    'gray-300': '#CCCCCC',
                    'gray-400': '#B3B3B3',
                    'gray-500': '#808080',
                    'gray-600': '#666666',
                    'gray-700': '#4D4D4D',
                    'gray-900': '#1A1A1A',

                    // Statusfarben des Design Systems. Das reine CD kennt nur
                    // Mint, Schwarz und Graustufen - diese vier sind dort als
                    // digitale Ergaenzung festgelegt.
                    'success':      '#1E7A45',
                    'success-wash': '#E5F2EA',
                    'warning':      '#9A6B00',
                    'warning-wash': '#FBF1DC',
                    'error':        '#B3261E',
                    'error-wash':   '#F8E6E5',
                },

                // --- Bruecke fuer das Altmarkup, siehe Kopf ----------------------
                yellow: {
                    50:  '#E6F7F6', // fh-mint-wash
                    100: '#B3E6E3', // fh-mint-tint
                    200: '#80D6D1',
                    300: '#4DCBC4', // fh-mint-300
                    400: '#00B2A9', // fh-mint
                    500: '#009B94',
                    600: '#007A74', // fh-mint-700
                    700: '#00524E', // fh-mint-800
                    800: '#003D3A',
                    900: '#002B29', // fh-mint-900
                },
                gray: {
                    50:  '#FAFAFA',
                    100: '#F2F2F2',
                    200: '#E0E0E0',
                    300: '#CCCCCC',
                    400: '#B3B3B3',
                    500: '#808080',
                    // Abweichung vom Design System (dort #666666): das Altmarkup
                    // benutzt bg-gray-600 als dunkle Kartenflaeche. Bis die Views
                    // auf bg-fh-black umgestellt sind, traegt diese Stufe die
                    // Schwarz/Weiss-Polaritaet des CD.
                    600: '#1F1F1F',
                    700: '#4D4D4D', // fh-gray-700, Sekundaertext
                    800: '#0A0A0A',
                    900: '#000000',
                },
                red: {
                    50:  '#F8E6E5', // fh-error-wash
                    100: '#F7D5D1',
                    500: '#B3261E',
                    600: '#B3261E', // fh-error
                    700: '#8C1D17',
                },
                green: {
                    100: '#E5F2EA', // fh-success-wash
                    500: '#1E7A45',
                    600: '#1E7A45', // fh-success
                    700: '#165C34',
                },
            },

            // Das CD ist geometrisch: kleine Radien, keine weichen Ecken.
            borderRadius: {
                none: '0',
                sm: '2px',
                DEFAULT: '2px',
                md: '4px',
                lg: '8px',
                xl: '8px',
                '2xl': '8px',
                '3xl': '8px',
                full: '9999px',
            },

            // Leicht und kuehl, nie schwer.
            boxShadow: {
                sm: '0 1px 2px rgba(0,0,0,0.06)',
                DEFAULT: '0 2px 8px rgba(0,0,0,0.08)',
                md: '0 2px 8px rgba(0,0,0,0.08)',
                lg: '0 8px 24px rgba(0,0,0,0.10)',
                focus: '0 0 0 3px rgba(0,178,169,0.45)',
                none: 'none',
            },

            transitionTimingFunction: {
                standard: 'cubic-bezier(0.2, 0, 0.2, 1)',
                out: 'cubic-bezier(0, 0, 0.2, 1)',
            },
            transitionDuration: {
                fast: '120ms',
                base: '200ms',
                slow: '320ms',
            },

            fontSize: {
                // Modulare Skala ~1,25 aus dem Design System
                display: ['60px', { lineHeight: '1.08', letterSpacing: '-0.02em' }],
                h1:      ['38px', { lineHeight: '1.08', letterSpacing: '-0.01em' }],
                h2:      ['28px', { lineHeight: '1.25', letterSpacing: '-0.01em' }],
                h3:      ['21px', { lineHeight: '1.25' }],
                'body-lg': ['18px', { lineHeight: '1.55' }],
                body:      ['16px', { lineHeight: '1.55' }],
                small:     ['14px', { lineHeight: '1.55' }],
                caption:   ['13px', { lineHeight: '1.55' }],
                overline:  ['12px', { lineHeight: '1.25', letterSpacing: '0.14em' }],
            },

            maxWidth: {
                container: '1200px',
                prose: '68ch',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
