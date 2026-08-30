import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: 'rgb(var(--brand-primary, 79 70 229) / <alpha-value>)',
                    hover: 'rgb(var(--brand-primary-hover, 67 56 202) / <alpha-value>)',
                    light: 'var(--brand-light)',
                    border: 'var(--brand-border)',
                },
            },
        },
    },

    plugins: [forms],
};
