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
                    // ── Tokens adaptativos (CSS variables — cambian con :root/.dark) ──
                    // Formato "R G B" para soportar modificadores de opacidad (/20, /40…)
                    dark:     'rgb(var(--cerberus-dark)    / <alpha-value>)',
                    mid:      'rgb(var(--cerberus-mid)     / <alpha-value>)',
                    steel:    'rgb(var(--cerberus-steel)   / <alpha-value>)',
                    accent:   'rgb(var(--cerberus-accent)  / <alpha-value>)',
                    light:    'rgb(var(--cerberus-light)   / <alpha-value>)',
                    primary:  'rgb(var(--cerberus-primary) / <alpha-value>)',
                    hover:    'rgb(var(--cerberus-hover)   / <alpha-value>)',
                    darkest:  'rgb(var(--cerberus-darkest) / <alpha-value>)',

                    // ── Colores estáticos de utilidad ─────────────────────────────
                    lightbase: '#E2E8F0',
                    lightcard: '#FFFFFF',
                    textdark:  '#1E293B',
                    textsoft:  '#475569',
                    border:    '#CBD5E1',
                    blue:      '#3B82F6',
                    success:   '#16A34A',
                    error:     '#DC2626',
                    warning:   '#FACC15',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, flowbite],
};
