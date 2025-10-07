import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    
    return {
        server: {
            host: '127.0.0.1',
            port: 5173,
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
        resolve: {
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },
        build: {
            rollupOptions: {
                output: {
                    manualChunks: {
                        vendor: ['vue', 'vue-router'],
                        charts: ['chart.js', 'vue-chartjs'],
                        ui: ['@headlessui/vue', '@heroicons/vue', 'sweetalert2'],
                    },
                },
            },
            chunkSizeWarningLimit: 1000,
        },
        define: {
            // Remove static Stripe key - now using dynamic API-based configuration
            // 'process.env.MIX_STRIPE_PUBLISHABLE_KEY': JSON.stringify(env.MIX_STRIPE_PUBLISHABLE_KEY),
        },
    };
}); 