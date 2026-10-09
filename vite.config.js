import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                './resources/js/tall-datatables.js',
                './resources/css/tall-datatables.css',
            ],
            refresh: false,
            buildDirectory: 'build',
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'dist/build',
        rollupOptions: {
            output: {
                entryFileNames: 'assets/tall-datatables-[hash].js',
                chunkFileNames: 'assets/tall-datatables-[hash].js',
                assetFileNames: 'assets/tall-datatables-[hash].[ext]',
            },
        },
        manifest: 'manifest.json',
    },
});
