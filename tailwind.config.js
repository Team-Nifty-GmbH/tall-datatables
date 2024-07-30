module.exports = {
    content: [
        __dirname + '/js/**/*.js',
        __dirname + '/resources/views/**/*.blade.php',
        __dirname + '/src/**/*.php'
    ],
    darkMode: 'class',
    plugins: [require('@tailwindcss/forms')],
}
