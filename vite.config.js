import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/admin/main.jsx',
                'resources/js/web/main.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '127.0.0.1',
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        chunkSizeWarningLimit: 900,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('recharts')) return 'recharts';
                    if (id.includes('@radix-ui')) return 'radix-ui';
                },
            },
        },
    },
});
