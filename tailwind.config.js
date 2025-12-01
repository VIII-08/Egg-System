import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Add your custom colors here
            colors: {
                'brand-green': '#6A994E',      // A sample darker green for buttons
                'brand-light-green': '#A7C957',// A sample lighter green for accents
                'brand-dark': '#386641',      // A sample very dark green for text/borders
                'brand-bg-green': '#F2E8CF',   // A sample background green if needed
            },
        },
    },

    plugins: [forms],
};
