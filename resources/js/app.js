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
        
        // ✅ GLOBAL ERROR HANDLER pour voir les erreurs Vue (MODE DEBUG)
        app.config.errorHandler = (err, instance, info) => {
            console.error('🔴 VUE ERROR:', err);
            console.error('📍 Component:', instance);
            console.error('ℹ️ Info:', info);
            console.error('📊 Stack:', err.stack);
            
            // NOTE: Affichage sur page désactivé pour production
            // Décommentez pour debug visuel si nécessaire
            /*
            document.body.insertAdjacentHTML('beforeend', `
                <div style="position:fixed;top:0;left:0;right:0;background:red;color:white;padding:20px;z-index:9999;font-family:monospace;white-space:pre-wrap;">
                    <strong>🔴 VUE ERROR:</strong><br>
                    ${err.message}<br><br>
                    <strong>Stack:</strong><br>
                    ${err.stack || 'No stack trace'}
                </div>
            `);
            */
        };
        
        // ✅ Activer les devtools en production
        app.config.performance = true;
        app.config.devtools = true;
        
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
