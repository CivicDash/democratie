<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    /** Type de contenu : 'topic', 'post', 'comment' */
    reportableType: { type: String, required: true },
    /** ID du contenu à signaler */
    reportableId: { type: [Number, String], required: true },
    /** Style du bouton : 'icon', 'text', 'full' */
    variant: { type: String, default: 'icon' },
    /** Taille : 'sm', 'md', 'lg' */
    size: { type: String, default: 'sm' },
});

const emit = defineEmits(['reported']);

const page = usePage();
const isOpen = ref(false);
const isSubmitting = ref(false);
const selectedReason = ref('');
const description = ref('');
const submitted = ref(false);
const error = ref('');

// Catégories de signalement
const reportReasons = [
    { value: 'spam', label: 'Spam / Publicité', icon: '🚫', description: 'Contenu promotionnel non sollicité' },
    { value: 'harassment', label: 'Harcèlement', icon: '😠', description: 'Comportement hostile ou intimidant' },
    { value: 'hate_speech', label: 'Discours haineux / Racisme', icon: '🚨', description: 'Propos discriminatoires, racistes, homophobes...' },
    { value: 'violence', label: 'Incitation à la violence', icon: '⚠️', description: 'Appels à la violence ou menaces' },
    { value: 'misinformation', label: 'Désinformation', icon: '❌', description: 'Fausses informations présentées comme vraies' },
    { value: 'inappropriate', label: 'Contenu inapproprié', icon: '🔞', description: 'Contenu choquant ou non adapté' },
    { value: 'off_topic', label: 'Hors sujet', icon: '📌', description: 'N\'a pas sa place dans cette discussion' },
    { value: 'impersonation', label: 'Usurpation d\'identité', icon: '👤', description: 'Prétend être quelqu\'un d\'autre' },
    { value: 'personal_data', label: 'Données personnelles', icon: '🔒', description: 'Divulgation d\'informations privées' },
    { value: 'other', label: 'Autre', icon: '❓', description: 'Autre raison non listée' },
];

const isAuthenticated = computed(() => !!page.props?.auth?.user);

const canSubmit = computed(() => {
    return selectedReason.value && !isSubmitting.value;
});

function openModal() {
    if (!isAuthenticated.value) {
        // Rediriger vers login
        router.visit('/login', { 
            data: { intended: window.location.pathname }
        });
        return;
    }
    isOpen.value = true;
    submitted.value = false;
    error.value = '';
}

function closeModal() {
    isOpen.value = false;
    selectedReason.value = '';
    description.value = '';
}

async function submitReport() {
    if (!canSubmit.value) return;
    
    isSubmitting.value = true;
    error.value = '';
    
    try {
        const response = await fetch('/api/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                reportable_type: props.reportableType,
                reportable_id: props.reportableId,
                reason: selectedReason.value,
                description: description.value || null,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            submitted.value = true;
            emit('reported', { reason: selectedReason.value });
            
            // Fermer après 2s
            setTimeout(() => {
                closeModal();
            }, 2000);
        } else {
            error.value = data.message || 'Une erreur est survenue';
        }
    } catch (err) {
        console.error('Erreur signalement:', err);
        error.value = 'Impossible d\'envoyer le signalement';
    } finally {
        isSubmitting.value = false;
    }
}

const buttonClasses = computed(() => {
    const base = 'inline-flex items-center justify-center rounded transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
    const sizes = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-3 py-1.5 text-sm',
        lg: 'px-4 py-2 text-base',
    };
    return `${base} ${sizes[props.size]} text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20`;
});
</script>

<template>
    <!-- Bouton de signalement -->
    <button
        @click="openModal"
        :class="buttonClasses"
        :title="'Signaler ce contenu'"
    >
        <svg v-if="variant === 'icon' || variant === 'full'" class="w-4 h-4" :class="{ 'mr-1.5': variant === 'full' || variant === 'text' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span v-if="variant === 'text' || variant === 'full'">Signaler</span>
    </button>

    <!-- Modal de signalement -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
                
                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="isOpen" class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-2xl">
                            <!-- Header -->
                            <div class="border-b border-gray-200 dark:border-slate-700 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="text-red-500">🚩</span>
                                        Signaler ce contenu
                                    </h3>
                                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Aidez-nous à maintenir un espace de discussion respectueux
                                </p>
                            </div>

                            <!-- Contenu -->
                            <div class="px-6 py-4">
                                <!-- Confirmation de succès -->
                                <div v-if="submitted" class="text-center py-8">
                                    <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">Merci pour votre signalement</h4>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Nos modérateurs examineront ce contenu dans les plus brefs délais.
                                    </p>
                                </div>

                                <!-- Formulaire -->
                                <div v-else>
                                    <!-- Erreur -->
                                    <div v-if="error" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm">
                                        {{ error }}
                                    </div>

                                    <!-- Raisons -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Pourquoi signalez-vous ce contenu ?
                                        </label>
                                        <div class="grid grid-cols-1 gap-2 max-h-[300px] overflow-y-auto pr-2">
                                            <label
                                                v-for="reason in reportReasons"
                                                :key="reason.value"
                                                :class="[
                                                    'flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all',
                                                    selectedReason === reason.value
                                                        ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                                        : 'border-gray-200 dark:border-slate-600 hover:border-gray-300 dark:hover:border-slate-500'
                                                ]"
                                            >
                                                <input
                                                    type="radio"
                                                    :value="reason.value"
                                                    v-model="selectedReason"
                                                    class="mt-1 h-4 w-4 text-red-500 focus:ring-red-500 border-gray-300 dark:border-slate-600"
                                                />
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-lg">{{ reason.icon }}</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ reason.label }}</span>
                                                    </div>
                                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ reason.description }}</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Description optionnelle -->
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Détails supplémentaires (optionnel)
                                        </label>
                                        <textarea
                                            v-model="description"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                            placeholder="Expliquez brièvement le problème..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div v-if="!submitted" class="border-t border-gray-200 dark:border-slate-700 px-6 py-4 flex justify-end gap-3">
                                <button
                                    @click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="submitReport"
                                    :disabled="!canSubmit"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                        canSubmit
                                            ? 'bg-red-500 text-white hover:bg-red-600'
                                            : 'bg-gray-200 dark:bg-slate-600 text-gray-400 cursor-not-allowed'
                                    ]"
                                >
                                    <span v-if="isSubmitting" class="flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Envoi...
                                    </span>
                                    <span v-else>Envoyer le signalement</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
