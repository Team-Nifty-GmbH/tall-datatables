import forms from '@tailwindcss/forms';

export default {
    content: [
        __dirname + '/resources/js/**/*.js',
        __dirname + '/resources/views/**/*.blade.php',
        __dirname + '/src/**/*.php'
    ],
    darkMode: 'selector',
    plugins: [forms],
}
