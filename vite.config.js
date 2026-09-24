import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/islands.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: [
            // React islands use the page-wide Alpine toaster instead of their own sonner instance.
            { find: /^sonner$/, replacement: fileURLToPath(new URL('./resources/js/react/shims/sonner.js', import.meta.url)) },
        ],
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (/node_modules[/\\](react|react-dom|scheduler|react-router|react-router-dom)[/\\]/.test(id)) {
                            return 'react-vendor';
                        }
                        if (/[/\\](lucide-react|sonner)[/\\]/.test(id)) {
                            return 'ui-vendor';
                        }
                    }
                    return undefined;
                },
            },
        },
    },
});
