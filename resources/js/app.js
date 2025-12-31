import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'CivicDash';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    
    // ✅ LAZY LOADING - Chaque page charge seulement son code
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue', { eager: false }), // ← eager: false pour lazy loading
        ),
    
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // ✅ GLOBAL ERROR HANDLER - Log uniquement en console (pas d'affichage écran)
        app.config.errorHandler = (err, instance, info) => {
            console.error('🔴 VUE ERROR:', err.message);
            console.error('📊 Stack:', err.stack);
        };
        
        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    
    // ✅ Progress bar amélioré
    progress: {
        color: '#3b82f6', // Bleu CivicDash
        showSpinner: true,
    },
});
