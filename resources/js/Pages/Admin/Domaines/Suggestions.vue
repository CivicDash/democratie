<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    suggestions: Array,
    domaines: Array,
});

// Sélection
const selectedSuggestions = ref([]);
const selectAll = ref(false);

// Modifications manuelles de domaine pour certaines suggestions
const modifications = ref({});

// Toggle select all
function toggleSelectAll() {
    if (selectAll.value) {
        selectedSuggestions.value = props.suggestions.map(s => s.fonction);
    } else {
        selectedSuggestions.value = [];
    }
}

// Domaine effectif (modifié ou suggéré)
function getEffectiveDomaineId(suggestion) {
    return modifications.value[suggestion.fonction] ?? suggestion.domaine_suggere?.id;
}

// Appliquer les suggestions sélectionnées
function applySuggestions() {
    const assignments = selectedSuggestions.value.map(fonction => {
        const suggestion = props.suggestions.find(s => s.fonction === fonction);
        return {
            fonction: fonction,
            domaine_id: getEffectiveDomaineId(suggestion),
        };
    }).filter(a => a.domaine_id);
    
    if (assignments.length === 0) {
        alert('Aucune suggestion sélectionnée avec un domaine valide');
        return;
    }
    
    router.post(route('admin.domaines.suggestions.apply'), {
        assignments: assignments,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedSuggestions.value = [];
            selectAll.value = false;
        },
    });
}

// Stats
const totalPostes = computed(() => {
    return props.suggestions.reduce((sum, s) => sum + s.count, 0);
});

// Grouper par domaine suggéré
const suggestionsByDomaine = computed(() => {
    const groups = {};
    props.suggestions.forEach(s => {
        const domaineName = s.domaine_suggere?.nom || 'Non déterminé';
        if (!groups[domaineName]) {
            groups[domaineName] = {
                domaine: s.domaine_suggere,
                suggestions: [],
                totalPostes: 0,
            };
        }
        groups[domaineName].suggestions.push(s);
        groups[domaineName].totalPostes += s.count;
    });
    return Object.values(groups).sort((a, b) => b.totalPostes - a.totalPostes);
});
</script>

<template>
    <Head title="Suggestions de Catégorisation" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                🤖 Suggestions Automatiques
                            </h1>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                {{ suggestions.length }} fonctions identifiées • {{ totalPostes }} postes concernés
                            </p>
                        </div>
                        <Link 
                            :href="route('admin.domaines.index')"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg"
                        >
                            ← Retour
                        </Link>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">💡</span>
                        <div>
                            <h3 class="font-medium text-amber-800 dark:text-amber-200">Comment ça marche ?</h3>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                L'algorithme analyse les intitulés de fonction et suggère un domaine basé sur des mots-clés. 
                                Vérifiez les suggestions, modifiez si nécessaire, puis appliquez.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Actions globales -->
                <div v-if="selectedSuggestions.length > 0" class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl p-4 mb-6 sticky top-0 z-10">
                    <div class="flex items-center justify-between">
                        <span class="text-blue-800 dark:text-blue-200 font-medium">
                            {{ selectedSuggestions.length }} suggestion(s) sélectionnée(s)
                        </span>
                        <div class="flex gap-3">
                            <button 
                                @click="selectedSuggestions = []; selectAll = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg"
                            >
                                Désélectionner tout
                            </button>
                            <button 
                                @click="applySuggestions"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-2"
                            >
                                ✅ Appliquer les {{ selectedSuggestions.length }} suggestions
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Groupes par domaine -->
                <div class="space-y-6">
                    <div 
                        v-for="group in suggestionsByDomaine" 
                        :key="group.domaine?.id || 'unknown'"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden"
                    >
                        <!-- Header du groupe -->
                        <div 
                            class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                            :style="{ borderLeftWidth: '4px', borderLeftColor: group.domaine?.couleur || '#6b7280' }"
                        >
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: group.domaine?.couleur || '#6b7280' }"
                                >
                                    {{ group.domaine?.sigle || '?' }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">
                                        {{ group.domaine?.nom || 'Non déterminé' }}
                                    </h3>
                                    <span class="text-sm text-gray-500">
                                        {{ group.suggestions.length }} fonctions • {{ group.totalPostes }} postes
                                    </span>
                                </div>
                            </div>
                            <button 
                                @click="group.suggestions.forEach(s => { 
                                    if (!selectedSuggestions.includes(s.fonction)) {
                                        selectedSuggestions.push(s.fonction);
                                    }
                                })"
                                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                            >
                                Tout sélectionner
                            </button>
                        </div>
                        
                        <!-- Liste des fonctions -->
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div 
                                v-for="suggestion in group.suggestions" 
                                :key="suggestion.fonction"
                                class="px-4 py-3 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="suggestion.fonction"
                                    v-model="selectedSuggestions"
                                    class="rounded border-gray-300 dark:border-gray-600"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white truncate">
                                        {{ suggestion.fonction }}
                                    </div>
                                </div>
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded">
                                    {{ suggestion.count }} postes
                                </span>
                                <select 
                                    v-model="modifications[suggestion.fonction]"
                                    class="text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option :value="undefined">
                                        {{ suggestion.domaine_suggere?.nom || '-- Choisir --' }}
                                    </option>
                                    <option v-for="d in domaines" :key="d.id" :value="d.id">
                                        {{ d.nom }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pas de suggestions -->
                <div v-if="suggestions.length === 0" class="text-center py-12">
                    <div class="text-6xl mb-4">🎉</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        Aucune suggestion disponible
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Toutes les fonctions reconnaissables ont déjà été catégorisées.
                    </p>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
