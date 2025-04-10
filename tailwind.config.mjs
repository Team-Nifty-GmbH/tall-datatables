import forms from '@tailwindcss/forms';

export default {
    presets: [require('./vendor/tallstackui/tallstackui/tailwind.config.js')],
    content: [
        __dirname + '/resources/js/**/*.js',
        __dirname + '/resources/views/**/*.blade.php',
        __dirname + '/src/**/*.php',
        './vendor/tallstackui/tallstackui/src/**/*.php',
    ],
    darkMode: 'selector',
    plugins: [forms],
};
