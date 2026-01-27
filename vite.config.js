import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    css: {
        postcss: './postcss.config.cjs',
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'crm.loc',
            protocol: 'http',
        },
        cors: {
            origin: 'http://crm.loc',
            credentials: true,
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
