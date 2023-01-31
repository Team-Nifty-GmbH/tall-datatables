module.exports = {
    presets: [
        require('../../wireui/wireui/tailwind.config.js')
    ],
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',

        '../../wireui/wireui/resources/**/*.blade.php',
        '../../wireui/wireui/ts/**/*.ts',
        '../../wireui/wireui/src/View/**/*.php'
    ],
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
