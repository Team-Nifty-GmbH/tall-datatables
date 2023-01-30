import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/tall-datatables.js',
                'resources/css/tall-datatables.css',
            ],
            publicDirectory: 'dist',
            buildDirectory: 'build',
            refresh: true
        }),
    ],
});
