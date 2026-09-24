import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';
import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { gzipSync, constants as zlib } from 'node:zlib';

/**
 * Writes a .gz copy next to every built text asset so nginx can serve it with `gzip_static on`
 * (compressed once at build time at max level instead of on every request).
 */
function precompressAssets() {
    const outDir = fileURLToPath(new URL('./public/build', import.meta.url));
    const compressible = /\.(js|mjs|css|svg|json|html|txt|map)$/;
    const walk = (dir) =>
        readdirSync(dir).flatMap((name) => {
            const path = join(dir, name);
            return statSync(path).isDirectory() ? walk(path) : [path];
        });

    return {
        name: 'hresume:precompress',
        apply: 'build',
        closeBundle() {
            for (const file of walk(outDir)) {
                if (!compressible.test(file) || statSync(file).size < 1024) continue;
                writeFileSync(`${file}.gz`, gzipSync(readFileSync(file), { level: zlib.Z_BEST_COMPRESSION }));
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/islands.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
        precompressAssets(),
    ],
    resolve: {
        alias: [
            // React islands use the page-wide Alpine toaster instead of their own sonner instance.
            { find: /^sonner$/, replacement: fileURLToPath(new URL('./resources/js/react/shims/sonner.js', import.meta.url)) },
        ],
    },
    build: {
        // Production output: minified JS (esbuild) and CSS (lightningcss via Tailwind), no source maps.
        minify: 'esbuild',
        cssMinify: true,
        sourcemap: false,
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
