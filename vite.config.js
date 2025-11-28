import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

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
    ],
    build: {
        outDir: 'dist',
        rollupOptions: {
            output: {
                entryFileNames: 'build/assets/tall-datatables-[hash].js',
                chunkFileNames: 'build/assets/tall-datatables-[hash].js',
                assetFileNames: 'build/assets/tall-datatables-[hash].[ext]',
            },
        },
        manifest: 'build/manifest.json',
    },
});
