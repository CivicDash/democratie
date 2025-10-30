import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
    
    // ✅ CODE SPLITTING & BUILD OPTIMIZATIONS
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Vendor chunks séparés pour meilleur cache
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vue-vendor';
                        }
                        if (id.includes('axios')) {
                            return 'axios';
                        }
                        return 'vendor';
                    }
                    
                    // Composants UI réutilisables
                    if (id.includes('/Components/')) {
                        return 'ui-components';
                    }
                    
                    // Pages groupées par feature
                    if (id.includes('/Pages/Topics/')) {
                        return 'topics';
                    }
                    if (id.includes('/Pages/Vote/')) {
                        return 'vote';
                    }
                    if (id.includes('/Pages/Budget/')) {
                        return 'budget';
                    }
                    if (id.includes('/Pages/Moderation/')) {
                        return 'moderation';
                    }
                },
            },
        },
        
        // Optimisation chunks
        chunkSizeWarningLimit: 600,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Retirer console.log en production
                drop_debugger: true,
            },
        },
        
        // CSS code splitting
        cssCodeSplit: true,
    },
    
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            usePolling: true,
        },
        cors: {
            origin: '*',
            credentials: true,
        },
    },
});
