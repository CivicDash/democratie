<template>
    <button
        @click="handleClick"
        class="fc-button"
        :class="{ 'fc-button-disabled': disabled }"
        :disabled="disabled"
        type="button"
    >
        <svg class="fc-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65 65">
            <!-- Logo FranceConnect+ officiel -->
            <g fill="none">
                <path d="M0 0h65v65H0z" fill="#FFF"/>
                <path d="M48.8 17.4c-.7 0-1.2.5-1.2 1.2v8.2c0 .7.5 1.2 1.2 1.2.7 0 1.2-.5 1.2-1.2v-8.2c0-.7-.5-1.2-1.2-1.2M42.5 17.4c-.7 0-1.2.5-1.2 1.2v8.2c0 .7.5 1.2 1.2 1.2.7 0 1.2-.5 1.2-1.2v-8.2c0-.7-.5-1.2-1.2-1.2M55.2 17.4c-.7 0-1.2.5-1.2 1.2v8.2c0 .7.5 1.2 1.2 1.2.7 0 1.2-.5 1.2-1.2v-8.2c0-.7-.6-1.2-1.2-1.2" fill="#003189"/>
                <path d="M32.5 0C14.6 0 0 14.6 0 32.5S14.6 65 32.5 65 65 50.4 65 32.5 50.4 0 32.5 0zm21.1 37.3c-1.5 1.5-3.5 2.3-5.7 2.3-2.2 0-4.2-.8-5.7-2.3-.3-.3-.3-.8 0-1.1l3.5-3.5h-12V21.3c0-.4.3-.7.7-.7h4.6c.4 0 .7.3.7.7v7.5h3.5l-3.5-3.5c-.3-.3-.3-.8 0-1.1 3.1-3.1 8.2-3.1 11.4 0 1.5 1.5 2.3 3.5 2.3 5.7 0 2.2-.8 4.2-2.3 5.7l-11.2 11.2c-.3.3-.3.8 0 1.1 3.1 3.1 8.2 3.1 11.4 0 .3-.3.8-.3 1.1 0 .4.3.4.8.1 1.1z" fill="#003189"/>
            </g>
        </svg>
        <span class="fc-label">S'identifier avec FranceConnect+</span>
    </button>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
});

const handleClick = () => {
    if (!props.disabled) {
        window.location.href = route('franceconnect.redirect');
    }
};
</script>

<style scoped>
/**
 * Design guide officiel FranceConnect+
 * Source : https://docs.partenaires.franceconnect.gouv.fr/fs/fs-integration/fs-integration-fc-button/
 * 
 * Couleurs officielles :
 * - Bleu FranceConnect : #003189
 * - Fond blanc : #FFFFFF
 * - Hover : #002469
 */

.fc-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    
    /* Couleurs officielles */
    background-color: #FFFFFF;
    border: 1px solid #003189;
    color: #003189;
    
    /* Dimensions */
    min-height: 48px;
    padding: 12px 24px;
    border-radius: 6px;
    
    /* Typography */
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    font-size: 16px;
    font-weight: 500;
    text-decoration: none;
    
    /* Interactions */
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    
    /* Accessibilité */
    outline-offset: 2px;
}

.fc-button:hover:not(.fc-button-disabled) {
    background-color: #003189;
    color: #FFFFFF;
    box-shadow: 0 4px 8px rgba(0, 49, 137, 0.2);
    transform: translateY(-1px);
}

.fc-button:active:not(.fc-button-disabled) {
    background-color: #002469;
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 49, 137, 0.2);
}

.fc-button:focus-visible {
    outline: 2px solid #003189;
    outline-offset: 2px;
}

.fc-button-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.fc-logo {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}

.fc-button:hover:not(.fc-button-disabled) .fc-logo path {
    fill: #FFFFFF;
}

.fc-label {
    white-space: nowrap;
}

/* Mobile responsive */
@media (max-width: 640px) {
    .fc-button {
        width: 100%;
        min-height: 52px; /* Touch target 44px+ */
        font-size: 16px; /* Éviter zoom iOS */
    }
    
    .fc-label {
        font-size: 15px;
    }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .fc-button {
        background-color: #F6F6F6;
    }
    
    .fc-button:hover:not(.fc-button-disabled) {
        background-color: #003189;
    }
}
</style>

