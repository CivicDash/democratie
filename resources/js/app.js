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
        
        // 🔧 Debug: Activer les logs Vue
        app.config.errorHandler = (err, instance, info) => {
            console.error('🔴 Vue Error:', err);
            console.error('Component:', instance);
            console.error('Info:', info);
        };
        
        app.config.warnHandler = (msg, instance, trace) => {
            console.warn('🟡 Vue Warning:', msg);
            console.warn('Trace:', trace);
        };
        
        // Performance tracking
        app.config.performance = true;
        
        console.log('🚀 Vue App initializing...', { props });
        
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
