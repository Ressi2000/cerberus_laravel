import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // controlado por Alpine + localStorage

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                cerberus: {
                    // ── Modo oscuro ──────────────────────────────────────────
                    dark:     '#0D1B2A',
                    mid:      '#1B263B',
                    steel:    '#415A77',
                    accent:   '#778DA9',
                    light:    '#A9D6E5',
                    primary:  '#1E40AF',
                    hover:    '#1E3A8A',

                    // ── Modo claro ───────────────────────────────────────────
                    lightbase: '#E2E8F0',  // fondo general
                    lightcard: '#FFFFFF',  // cards / panels
                    textdark:  '#1E293B',  // texto principal
                    textsoft:  '#475569',  // texto secundario
                    border:    '#CBD5E1',  // bordes y divisores
                    blue:      '#3B82F6',  // acento celeste
                    success:   '#16A34A',
                    error:     '#DC2626',
                    warning:   '#FACC15',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                cerberus: '0 4px 12px rgba(0, 0, 0, 0.2)',
            },
        },
    },

    plugins: [forms, flowbite],
};
