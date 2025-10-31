<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
        <!-- En-tête -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtres avancés
            </h3>
            <button
                @click="resetFilters"
                class="text-sm text-gray-600 hover:text-red-600 transition-colors"
            >
                Réinitialiser
            </button>
        </div>

        <!-- Thématiques (multi-sélection) -->
        <div v-if="showThematiques" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Thématiques
            </label>
            <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1">
                <label
                    v-for="thematique in availableThematiques"
                    :key="thematique.code"
                    class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer transition-colors"
                >
                    <input
                        type="checkbox"
                        :value="thematique.code"
                        v-model="localFilters.thematiques"
                        @change="emitFilters"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="text-sm text-gray-700">
                        {{ thematique.icone }} {{ thematique.nom }}
                    </span>
                </label>
            </div>
            <p class="text-xs text-gray-500">
                {{ localFilters.thematiques.length }} thématique(s) sélectionnée(s)
            </p>
        </div>

        <!-- Date range -->
        <div v-if="showDateRange" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Période
            </label>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Du</label>
                    <input
                        type="date"
                        v-model="localFilters.dateFrom"
                        @change="emitFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Au</label>
                    <input
                        type="date"
                        v-model="localFilters.dateTo"
                        @change="emitFilters"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Source (Assemblée/Sénat) -->
        <div v-if="showSource" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Source
            </label>
            <div class="flex gap-2">
                <label
                    v-for="source in availableSources"
                    :key="source.value"
                    class="flex-1 relative"
                >
                    <input
                        type="checkbox"
                        :value="source.value"
                        v-model="localFilters.sources"
                        @change="emitFilters"
                        class="sr-only peer"
                    />
                    <div class="px-4 py-2 text-center border-2 border-gray-300 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">
                        <span class="text-sm font-medium">{{ source.label }}</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Statut -->
        <div v-if="showStatut" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Statut
            </label>
            <div class="space-y-1">
                <label
                    v-for="statut in availableStatuts"
                    :key="statut.value"
                    class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer transition-colors"
                >
                    <input
                        type="checkbox"
                        :value="statut.value"
                        v-model="localFilters.statuts"
                        @change="emitFilters"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="text-sm text-gray-700">{{ statut.label }}</span>
                </label>
            </div>
        </div>

        <!-- Tri -->
        <div v-if="showSort" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Trier par
            </label>
            <select
                v-model="localFilters.sortBy"
                @change="emitFilters"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            >
                <option value="date_desc">Plus récent</option>
                <option value="date_asc">Plus ancien</option>
                <option value="title_asc">Titre (A-Z)</option>
                <option value="title_desc">Titre (Z-A)</option>
                <option value="votes_desc" v-if="showVotesSort">Plus de votes</option>
                <option value="votes_asc" v-if="showVotesSort">Moins de votes</option>
            </select>
        </div>

        <!-- Bouton Appliquer -->
        <div class="pt-2 border-t border-gray-200">
            <button
                @click="applyFilters"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Appliquer les filtres
            </button>
        </div>

        <!-- Nombre de résultats -->
        <div v-if="resultCount !== null" class="text-sm text-gray-600 text-center">
            {{ resultCount }} résultat(s) trouvé(s)
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    showThematiques: {
        type: Boolean,
        default: true,
    },
    showDateRange: {
        type: Boolean,
        default: true,
    },
    showSource: {
        type: Boolean,
        default: true,
    },
    showStatut: {
        type: Boolean,
        default: true,
    },
    showSort: {
        type: Boolean,
        default: true,
    },
    showVotesSort: {
        type: Boolean,
        default: false,
    },
    availableThematiques: {
        type: Array,
        default: () => [],
    },
    resultCount: {
        type: Number,
        default: null,
    },
    autoApply: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:filters', 'apply']);

const availableSources = [
    { value: 'assemblee', label: 'Assemblée' },
    { value: 'senat', label: 'Sénat' },
];

const availableStatuts = [
    { value: 'en_cours', label: 'En cours' },
    { value: 'adopte', label: 'Adopté' },
    { value: 'rejete', label: 'Rejeté' },
    { value: 'retire', label: 'Retiré' },
];

const localFilters = ref({
    thematiques: [],
    dateFrom: '',
    dateTo: '',
    sources: [],
    statuts: [],
    sortBy: 'date_desc',
});

const emitFilters = () => {
    emit('update:filters', { ...localFilters.value });
    
    if (props.autoApply) {
        applyFilters();
    }
    
    // Sauvegarder dans localStorage
    localStorage.setItem('advancedFilters', JSON.stringify(localFilters.value));
};

const applyFilters = () => {
    emit('apply', { ...localFilters.value });
};

const resetFilters = () => {
    localFilters.value = {
        thematiques: [],
        dateFrom: '',
        dateTo: '',
        sources: [],
        statuts: [],
        sortBy: 'date_desc',
    };
    emitFilters();
    applyFilters();
};

// Charger les filtres sauvegardés au montage
onMounted(() => {
    const saved = localStorage.getItem('advancedFilters');
    if (saved) {
        try {
            localFilters.value = { ...localFilters.value, ...JSON.parse(saved) };
            emitFilters();
        } catch (error) {
            console.error('Erreur lors du chargement des filtres:', error);
        }
    }
});
</script>

<style scoped>
/* Scrollbar personnalisé */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

