<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    type: { type: String, required: true }, // depute, senateur, maire, loi, scrutin, amendement
    identifier: { type: String, required: true },
    position: { type: String, default: 'top' }, // top, bottom, left, right
});

const isLoading = ref(true);
const error = ref(null);
const data = ref(null);
const showCard = ref(false);
const cardStyle = ref({});

const triggerRef = ref(null);

// Charger les données au hover
async function loadData() {
    if (data.value) return; // Déjà chargé
    
    isLoading.value = true;
    error.value = null;
    
    try {
        const response = await fetch(`/api/references/preview/${props.type}/${props.identifier}`);
        if (!response.ok) throw new Error('Not found');
        data.value = await response.json();
    } catch (e) {
        error.value = 'Données non disponibles';
    } finally {
        isLoading.value = false;
    }
}

function showPreview() {
    showCard.value = true;
    loadData();
    positionCard();
}

function hidePreview() {
    showCard.value = false;
}

function positionCard() {
    if (!triggerRef.value) return;
    
    const rect = triggerRef.value.getBoundingClientRect();
    const cardWidth = 320;
    const cardHeight = 200;
    
    // Positionner au-dessus par défaut
    cardStyle.value = {
        position: 'fixed',
        left: `${Math.max(10, Math.min(rect.left, window.innerWidth - cardWidth - 10))}px`,
        top: `${rect.top - cardHeight - 10}px`,
        width: `${cardWidth}px`,
        zIndex: 9999,
    };
    
    // Si pas assez de place en haut, positionner en bas
    if (rect.top - cardHeight - 10 < 10) {
        cardStyle.value.top = `${rect.bottom + 10}px`;
    }
}

// Icône selon le type
const typeIcon = computed(() => {
    return {
        depute: '👤',
        senateur: '🏛️',
        maire: '🏘️',
        loi: '📜',
        scrutin: '🗳️',
        amendement: '📝',
    }[props.type] || '📋';
});

const typeLabel = computed(() => {
    return {
        depute: 'Député',
        senateur: 'Sénateur',
        maire: 'Maire',
        loi: 'Loi',
        scrutin: 'Scrutin',
        amendement: 'Amendement',
    }[props.type] || 'Référence';
});

const typeColor = computed(() => {
    return {
        depute: 'bg-blue-500',
        senateur: 'bg-rose-500',
        maire: 'bg-amber-500',
        loi: 'bg-indigo-500',
        scrutin: 'bg-emerald-500',
        amendement: 'bg-purple-500',
    }[props.type] || 'bg-gray-500';
});
</script>

<template>
    <span 
        ref="triggerRef"
        class="relative inline-flex items-center cursor-pointer"
        @mouseenter="showPreview"
        @mouseleave="hidePreview"
        @focus="showPreview"
        @blur="hidePreview"
    >
        <slot>
            <span 
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-medium text-white"
                :class="typeColor"
            >
                {{ typeIcon }} @{{ type }}:{{ identifier }}
            </span>
        </slot>

        <!-- Card Preview -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div 
                    v-if="showCard"
                    :style="cardStyle"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Header -->
                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2" :class="typeColor">
                        <span class="text-white">{{ typeIcon }}</span>
                        <span class="text-sm font-medium text-white">{{ typeLabel }}</span>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <!-- Loading -->
                        <div v-if="isLoading" class="flex items-center justify-center py-6">
                            <div class="animate-spin w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full"></div>
                        </div>

                        <!-- Error -->
                        <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
                            <span class="text-2xl block mb-2">⚠️</span>
                            {{ error }}
                        </div>

                        <!-- Élu (député, sénateur, maire) -->
                        <template v-else-if="['depute', 'senateur', 'maire'].includes(type) && data">
                            <div class="flex items-start gap-3">
                                <img 
                                    v-if="data.photo_url"
                                    :src="data.photo_url" 
                                    :alt="data.nom_complet"
                                    class="w-16 h-16 rounded-full object-cover border-2 border-white shadow"
                                />
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ data.nom_complet }}
                                    </h4>
                                    <p v-if="data.groupe" class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ data.groupe }}
                                    </p>
                                    <p v-if="data.circonscription" class="text-xs text-gray-500 dark:text-gray-500 truncate">
                                        {{ data.circonscription }}
                                    </p>
                                    <a 
                                        :href="data.url"
                                        class="inline-block mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        Voir le profil →
                                    </a>
                                </div>
                            </div>
                        </template>

                        <!-- Loi -->
                        <template v-else-if="type === 'loi' && data">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 mb-2">
                                {{ data.titre }}
                            </h4>
                            <div class="flex items-center gap-2 mb-2">
                                <span 
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': data.etat === 'promulgue',
                                        'bg-amber-100 text-amber-700': data.etat === 'en_cours',
                                        'bg-gray-100 text-gray-700': true,
                                    }"
                                >
                                    {{ data.etat_label || data.etat }}
                                </span>
                                <span class="text-xs text-gray-500">{{ data.annee }}</span>
                            </div>
                            <a 
                                :href="data.url"
                                class="inline-block text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Voir la loi →
                            </a>
                        </template>

                        <!-- Scrutin -->
                        <template v-else-if="type === 'scrutin' && data">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 mb-2">
                                {{ data.titre }}
                            </h4>
                            <div class="flex items-center gap-3 text-sm mb-2">
                                <span class="text-emerald-600">👍 {{ data.pour }}</span>
                                <span class="text-rose-600">👎 {{ data.contre }}</span>
                                <span class="text-gray-500">🤷 {{ data.abstention }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">{{ data.date }}</p>
                            <a 
                                :href="data.url"
                                class="inline-block text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Voir le scrutin →
                            </a>
                        </template>

                        <!-- Fallback -->
                        <template v-else-if="data">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ data.titre || data.nom_complet || identifier }}
                            </p>
                            <a 
                                v-if="data.url"
                                :href="data.url"
                                class="inline-block mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Voir les détails →
                            </a>
                        </template>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </span>
</template>
