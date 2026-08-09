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
                'resources/js/lyrics/main.jsx',
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
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: [
                    'legacy-js-api',
                    'import',
                    'global-builtin',
                    'color-functions',
                    'if-function',
                ],
            },
        },
    },
    build: {
        chunkSizeWarningLimit: 900,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    // Do NOT split recharts into a shared chunk — with a multi-entry
                    // admin+web build, Vite's modulepreload helper can land in a shared
                    // chunk and force every public page to download ~110KB gzip of charts.
                    // Lazy Dashboard keeps recharts inside the admin-only async chunk.
                    if (id.includes('@radix-ui')) return 'radix-ui';
                },
            },
        },
    },
});
