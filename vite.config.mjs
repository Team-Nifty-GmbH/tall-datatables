/** @type {import('vite').UserConfig} */
import tailwindcss from '@tailwindcss/vite';

export default {
    build: {
        assetsDir: '',
        manifest: true,
        rollupOptions: {
            input: [
                './resources/js/tall-datatables.js',
                './resources/css/tall-datatables.css',
            ],
        },
    },
    plugins: [tailwindcss()],
};
