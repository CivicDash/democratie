<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

// Simple debounce function
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

const props = defineProps({
    regions: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    elus: { type: Object, default: () => ({ deputes: [], senateurs: [], maires: [] }) },
    loiCod: { type: String, default: null },
    loiTitre: { type: String, default: null },
});

// ============================================================================
// WIZARD STEPS
// ============================================================================
const currentStep = ref(1);
const totalSteps = 6;

const steps = [
    { id: 1, title: 'Type', icon: '💡', description: 'Quel type de contribution ?' },
    { id: 2, title: 'Contenu', icon: '✍️', description: 'Rédigez votre idée' },
    { id: 3, title: 'Échelle', icon: '🗺️', description: 'Portée géographique' },
    { id: 4, title: 'Thèmes', icon: '🏷️', description: 'Catégorisez votre idée' },
    { id: 5, title: 'Loi liée', icon: '📜', description: 'Rattacher à une loi (optionnel)' },
    { id: 6, title: 'Élus', icon: '👤', description: 'Liez des élus (optionnel)' },
];

// ============================================================================
// FORM DATA
// ============================================================================
const form = useForm({
    idea_type: 'proposal',
    title: props.loiTitre ? `À propos de : ${props.loiTitre}` : '',
    description: '',
    scope: 'national',
    region_id: null,
    department_id: null,
    loi_cod: props.loiCod,
    tag_ids: [],
    elus: [],
    is_interpellation: false,
});

// ============================================================================
// LOI SEARCH (STEP 5)
// ============================================================================
const loiSearch = ref('');
const loiSearchResults = ref([]);
const isSearchingLois = ref(false);
const selectedLoi = ref(props.loiCod ? { code: props.loiCod, titre: props.loiTitre } : null);

async function searchLois() {
    if (loiSearch.value.length < 3) {
        loiSearchResults.value = [];
        return;
    }
    
    isSearchingLois.value = true;
    
    try {
        const response = await fetch(`/api/lois/search?q=${encodeURIComponent(loiSearch.value)}&limit=10`);
        const data = await response.json();
        
        if (data.success) {
            loiSearchResults.value = data.results;
        }
    } catch (error) {
        console.error('Erreur recherche lois:', error);
    } finally {
        isSearchingLois.value = false;
    }
}

const debouncedSearchLois = debounce(searchLois, 300);

watch(loiSearch, () => {
    if (loiSearch.value.length >= 3) {
        debouncedSearchLois();
    }
});

function selectLoi(loi) {
    selectedLoi.value = loi;
    form.loi_cod = loi.code;
    loiSearch.value = '';
    loiSearchResults.value = [];
}

function removeLoi() {
    selectedLoi.value = null;
    form.loi_cod = null;
}

// ============================================================================
// IDEA TYPES
// ============================================================================
const ideaTypes = [
    { 
        value: 'proposal', 
        label: 'Proposition', 
        icon: '💡', 
        color: 'emerald',
        description: 'Proposez une idée, une amélioration ou un projet'
    },
    { 
        value: 'question', 
        label: 'Question', 
        icon: '❓', 
        color: 'sky',
        description: 'Posez une question sur un sujet politique'
    },
    { 
        value: 'debate', 
        label: 'Débat', 
        icon: '💬', 
        color: 'amber',
        description: 'Lancez un débat sur un sujet de société'
    },
    { 
        value: 'petition', 
        label: 'Pétition', 
        icon: '📜', 
        color: 'violet',
        description: 'Créez une pétition pour rassembler des signatures'
    },
    { 
        value: 'interpellation', 
        label: 'Interpellation', 
        icon: '📣', 
        color: 'rose',
        description: 'Interpellez directement un élu sur un sujet'
    },
];

// ============================================================================
// SCOPES
// ============================================================================
const scopes = [
    { value: 'national', label: 'National', icon: '🇫🇷', description: 'Concerne tout le pays' },
    { value: 'regional', label: 'Régional', icon: '🗺️', description: 'Concerne une région' },
    { value: 'departemental', label: 'Départemental', icon: '📍', description: 'Concerne un département' },
    { value: 'communal', label: 'Communal', icon: '🏘️', description: 'Concerne une commune' },
];

// ============================================================================
// COMPUTED
// ============================================================================
const filteredDepartments = computed(() => {
    if (!form.region_id) return [];
    return props.departments.filter(d => d.region_id === form.region_id);
});

const selectedTags = computed(() => {
    return props.tags.filter(t => form.tag_ids.includes(t.id));
});

const selectedElus = computed(() => {
    return form.elus.map(e => {
        const list = e.type === 'depute' ? props.elus.deputes :
                     e.type === 'senateur' ? props.elus.senateurs :
                     props.elus.maires;
        return { ...e, data: list?.find(x => x.id === e.id || x.uid === e.id) };
    }).filter(e => e.data);
});

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1: return !!form.idea_type;
        case 2: return form.title.length >= 10 && form.description.length >= 50;
        case 3: return !!form.scope;
        case 4: return true; // Tags optionnels
        case 5: return true; // Loi optionnelle
        case 6: return true; // Élus optionnels
        default: return true;
    }
});

const progress = computed(() => (currentStep.value / totalSteps) * 100);

// ============================================================================
// METHODS
// ============================================================================
function nextStep() {
    if (currentStep.value < totalSteps && canProceed.value) {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function goToStep(step) {
    if (step <= currentStep.value || canProceed.value) {
        currentStep.value = step;
    }
}

function toggleTag(tagId) {
    const index = form.tag_ids.indexOf(tagId);
    if (index > -1) {
        form.tag_ids.splice(index, 1);
    } else if (form.tag_ids.length < 3) {
        form.tag_ids.push(tagId);
    }
}

function addElu(type, id) {
    if (!form.elus.find(e => e.type === type && e.id === id)) {
        form.elus.push({ type, id });
    }
}

function removeElu(type, id) {
    const index = form.elus.findIndex(e => e.type === type && e.id === id);
    if (index > -1) {
        form.elus.splice(index, 1);
    }
}

// ============================================================================
// ELUS SEARCH & SUGGESTIONS
// ============================================================================
const eluSearch = ref('');
const eluSearchType = ref('all'); // 'all', 'depute', 'senateur', 'maire'
const suggestedElus = ref({ deputes: [], senateurs: [], maires: [] });
const isLoadingElus = ref(false);
const showElusSuggestions = ref(false);

const page = usePage();

async function searchElus() {
    if (eluSearch.value.length < 2 && form.scope === 'national') {
        suggestedElus.value = { deputes: [], senateurs: [], maires: [] };
        return;
    }
    
    isLoadingElus.value = true;
    
    try {
        const params = new URLSearchParams({
            scope: form.scope,
            search: eluSearch.value,
            limit: '15',
        });
        
        if (form.region_id) params.append('region_id', form.region_id);
        if (form.department_id) params.append('department_id', form.department_id);
        if (eluSearchType.value !== 'all') {
            params.append('types[]', eluSearchType.value);
        }
        
        const response = await fetch(`/api/legislation/elus/suggest?${params}`);
        const data = await response.json();
        
        if (data.success) {
            suggestedElus.value = data.results;
        }
    } catch (error) {
        console.error('Erreur recherche élus:', error);
    } finally {
        isLoadingElus.value = false;
    }
}

const debouncedSearchElus = debounce(searchElus, 300);

// Watch for search input changes
watch(eluSearch, () => {
    if (eluSearch.value.length >= 2 || form.scope !== 'national') {
        debouncedSearchElus();
    }
});

// Load suggestions when entering step 6 (élus)
watch(currentStep, (newStep) => {
    if (newStep === 6) {
        searchElus();
    }
});

// When scope changes, reload suggestions
watch([() => form.scope, () => form.region_id, () => form.department_id], () => {
    if (currentStep.value === 6) {
        searchElus();
    }
});

function selectElu(elu) {
    if (!form.elus.find(e => e.type === elu.type && e.id === elu.id)) {
        form.elus.push({ 
            type: elu.type, 
            id: elu.id, 
            nom_complet: elu.nom_complet,
            photo_url: elu.photo_url,
            groupe: elu.groupe,
            groupe_couleur: elu.groupe_couleur,
            circonscription: elu.circonscription || elu.commune,
        });
    }
}

const allSuggestedElus = computed(() => {
    const all = [];
    if (eluSearchType.value === 'all' || eluSearchType.value === 'depute') {
        all.push(...suggestedElus.value.deputes.map(e => ({ ...e, typeLabel: 'Député' })));
    }
    if (eluSearchType.value === 'all' || eluSearchType.value === 'senateur') {
        all.push(...suggestedElus.value.senateurs.map(e => ({ ...e, typeLabel: 'Sénateur' })));
    }
    if (eluSearchType.value === 'all' || eluSearchType.value === 'maire') {
        all.push(...suggestedElus.value.maires.map(e => ({ ...e, typeLabel: 'Maire' })));
    }
    return all;
});

function submit() {
    form.post(route('participation.ideas.store'), {
        onSuccess: () => {
            // Redirigé automatiquement par le controller
        },
    });
}

// Reset région/département quand scope change
watch(() => form.scope, (newScope) => {
    if (newScope === 'national') {
        form.region_id = null;
        form.department_id = null;
    } else if (newScope === 'regional') {
        form.department_id = null;
    }
});

// Breadcrumbs
const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Participation', href: route('participation.hub'), icon: '💬' },
    { label: 'Idées Citoyennes', href: route('participation.ideas.index'), icon: '💡' },
    { label: 'Nouvelle idée', current: true, icon: '✨' },
];
</script>

<template>
    <Head title="Nouvelle idée citoyenne" />

    <AuthenticatedLayout>
        <!-- Hero Section Full Width -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 flex items-center gap-4">
                    <span class="text-4xl">✨</span>
                    Nouvelle contribution citoyenne
                </h1>
                <p class="text-emerald-200 text-lg">
                    Partagez votre idée, posez une question ou interpellez vos élus
                </p>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Progress Bar -->
                <Card class="mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Étape {{ currentStep }} sur {{ totalSteps }}
                        </span>
                        <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                            {{ Math.round(progress) }}%
                        </span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </Card>

                <!-- Steps Navigation -->
                <div class="flex justify-between mb-8 overflow-x-auto pb-2">
                    <button
                        v-for="step in steps"
                        :key="step.id"
                        @click="goToStep(step.id)"
                        :class="[
                            'flex flex-col items-center px-4 py-2 rounded-xl transition-all min-w-[80px]',
                            currentStep === step.id 
                                ? 'bg-emerald-100 dark:bg-emerald-900/30 border-2 border-emerald-500 text-emerald-700 dark:text-emerald-400' 
                                : step.id < currentStep 
                                    ? 'bg-gray-100 dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="step.id > currentStep && !canProceed"
                    >
                        <span class="text-2xl mb-1">{{ step.icon }}</span>
                        <span class="text-xs font-medium">{{ step.title }}</span>
                    </button>
                </div>

                <!-- Step Content -->
                <Card class="p-8">
                    
                    <!-- STEP 1: Type -->
                    <div v-if="currentStep === 1" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                💡 Quel type de contribution ?
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                Choisissez le format qui correspond le mieux à votre besoin
                            </p>
                        </div>

                        <div class="grid gap-4">
                            <button
                                v-for="type in ideaTypes"
                                :key="type.value"
                                @click="form.idea_type = type.value"
                                :class="[
                                    'flex items-start gap-4 p-5 rounded-xl border-2 transition-all text-left',
                                    form.idea_type === type.value
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <span class="text-3xl">{{ type.icon }}</span>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ type.label }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ type.description }}</p>
                                </div>
                                <div 
                                    v-if="form.idea_type === type.value"
                                    class="ml-auto w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Content -->
                    <div v-if="currentStep === 2" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                ✍️ Rédigez votre {{ ideaTypes.find(t => t.value === form.idea_type)?.label.toLowerCase() }}
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                Soyez clair et précis pour faciliter la compréhension
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Titre <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-3"
                                placeholder="Un titre accrocheur et descriptif"
                                maxlength="255"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ form.title.length }}/255 caractères (minimum 10)
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="8"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-3 resize-y"
                                placeholder="Décrivez votre idée en détail. Expliquez le contexte, les enjeux et ce que vous proposez concrètement..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ form.description.length }} caractères (minimum 50)
                            </p>
                        </div>

                        <!-- Loi liée -->
                        <div v-if="loiCod" class="p-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-xl">
                            <p class="text-sm text-sky-700 dark:text-sky-300">
                                🔗 Cette contribution sera liée à la loi : <strong>{{ loiTitre }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- STEP 3: Scope -->
                    <div v-if="currentStep === 3" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                🗺️ Quelle portée géographique ?
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                À quel niveau territorial s'applique votre idée ?
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <button
                                v-for="scope in scopes"
                                :key="scope.value"
                                @click="form.scope = scope.value"
                                :class="[
                                    'p-5 rounded-xl border-2 transition-all text-center',
                                    form.scope === scope.value
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-3xl block mb-2">{{ scope.icon }}</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ scope.label }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ scope.description }}</p>
                            </button>
                        </div>

                        <!-- Sélection région -->
                        <div v-if="form.scope === 'regional' || form.scope === 'departemental'" class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Région <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.region_id"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-3"
                            >
                                <option :value="null">-- Sélectionnez une région --</option>
                                <option v-for="region in regions" :key="region.id" :value="region.id">
                                    {{ region.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Sélection département -->
                        <div v-if="form.scope === 'departemental' && form.region_id" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Département <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.department_id"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-3"
                            >
                                <option :value="null">-- Sélectionnez un département --</option>
                                <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                    {{ dept.code }} - {{ dept.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- STEP 4: Tags -->
                    <div v-if="currentStep === 4" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                🏷️ Choisissez des thématiques
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                Sélectionnez jusqu'à 3 thèmes pour catégoriser votre idée
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in tags"
                                :key="tag.id"
                                @click="toggleTag(tag.id)"
                                :disabled="form.tag_ids.length >= 3 && !form.tag_ids.includes(tag.id)"
                                :class="[
                                    'px-4 py-2 rounded-full text-sm font-medium transition-all',
                                    form.tag_ids.includes(tag.id)
                                        ? 'bg-emerald-500 text-white'
                                        : form.tag_ids.length >= 3
                                            ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                ]"
                            >
                                {{ tag.icone }} {{ tag.nom }}
                            </button>
                        </div>

                        <div v-if="selectedTags.length > 0" class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Thèmes sélectionnés :</p>
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="tag in selectedTags" 
                                    :key="tag.id"
                                    class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-sm"
                                >
                                    {{ tag.icone }} {{ tag.nom }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Loi liée -->
                    <div v-if="currentStep === 5" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                📜 Rattacher à une loi (optionnel)
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                Liez votre idée à un projet ou une proposition de loi existante
                            </p>
                        </div>

                        <!-- Loi sélectionnée -->
                        <div v-if="selectedLoi" class="p-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-sky-700 dark:text-sky-400 mb-1">📜 Loi liée</p>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ selectedLoi.titre }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Code : {{ selectedLoi.code }}
                                        <span v-if="selectedLoi.etat" class="ml-2">· {{ selectedLoi.etat }}</span>
                                    </p>
                                </div>
                                <button 
                                    @click="removeLoi"
                                    class="px-3 py-1 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition text-sm"
                                >
                                    ✕ Retirer
                                </button>
                            </div>
                        </div>

                        <!-- Recherche de loi -->
                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                🔍 Rechercher une loi
                            </label>
                            <input
                                v-model="loiSearch"
                                type="text"
                                placeholder="Tapez le nom ou le sujet d'une loi (min. 3 caractères)..."
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-emerald-500 focus:border-emerald-500"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Ex: "retraites", "immigration", "logement"...
                            </p>

                            <!-- Résultats de recherche -->
                            <div v-if="loiSearchResults.length > 0" class="mt-4 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            📋 Résultats
                                        </span>
                                        <span class="text-xs text-gray-500">{{ loiSearchResults.length }} loi(s) trouvée(s)</span>
                                    </div>
                                </div>
                                
                                <div class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                    <button
                                        v-for="loi in loiSearchResults"
                                        :key="loi.code"
                                        @click="selectLoi(loi)"
                                        class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                                    >
                                        <span class="text-2xl flex-shrink-0">📜</span>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 dark:text-white line-clamp-2">
                                                {{ loi.titre }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                <span>{{ loi.code }}</span>
                                                <span v-if="loi.etat" :class="{
                                                    'text-emerald-600': loi.etat === 'promulgué',
                                                    'text-amber-600': loi.etat === 'en cours',
                                                    'text-gray-500': true
                                                }">· {{ loi.etat }}</span>
                                                <span v-if="loi.annee">· {{ loi.annee }}</span>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Loading -->
                            <div v-else-if="isSearchingLois" class="mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">⏳</div>
                                <p>Recherche en cours...</p>
                            </div>

                            <!-- Empty state -->
                            <div v-else-if="loiSearch.length >= 3" class="mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">🔍</div>
                                <p>Aucune loi trouvée pour "{{ loiSearch }}"</p>
                            </div>

                            <!-- Instructions -->
                            <div v-else class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    💡 <strong>Cette étape est optionnelle.</strong> Lier votre idée à une loi permet de contextualiser votre contribution 
                                    et de la relier aux débats parlementaires en cours.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 6: Élus -->
                    <div v-if="currentStep === 6" class="space-y-6">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                👤 Liez des élus (optionnel)
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                Mentionnez des élus concernés par votre idée
                            </p>
                        </div>

                        <!-- Interpellation checkbox -->
                        <label 
                            v-if="form.idea_type === 'interpellation'"
                            class="flex items-center gap-3 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700 rounded-xl cursor-pointer"
                        >
                            <input
                                v-model="form.is_interpellation"
                                type="checkbox"
                                class="w-5 h-5 rounded border-gray-300 text-rose-500 focus:ring-rose-500"
                            />
                            <div>
                                <p class="font-medium text-rose-700 dark:text-rose-300">📣 Interpellation directe</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Les élus sélectionnés seront notifiés et invités à répondre</p>
                            </div>
                        </label>

                        <!-- Search and filter -->
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🔍 Rechercher un élu
                                </label>
                                <input
                                    v-model="eluSearch"
                                    type="text"
                                    placeholder="Nom, prénom, commune..."
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-emerald-500 focus:border-emerald-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    📋 Type
                                </label>
                                <select
                                    v-model="eluSearchType"
                                    @change="searchElus"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="all">Tous les élus</option>
                                    <option value="depute">Députés</option>
                                    <option value="senateur">Sénateurs</option>
                                    <option value="maire">Maires</option>
                                </select>
                            </div>
                        </div>

                        <!-- Geo info -->
                        <div v-if="form.scope !== 'national'" class="p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-xl">
                            <p class="text-sm text-sky-700 dark:text-sky-400">
                                📍 Suggestions basées sur votre portée géographique : 
                                <strong>{{ scopes.find(s => s.value === form.scope)?.label }}</strong>
                                <span v-if="form.region_id"> - {{ regions.find(r => r.id === form.region_id)?.name }}</span>
                                <span v-if="form.department_id"> / {{ departments.find(d => d.id === form.department_id)?.name }}</span>
                            </p>
                        </div>

                        <!-- Selected elus -->
                        <div v-if="form.elus.length > 0" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-xl">
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400 mb-3">
                                ✅ Élus sélectionnés ({{ form.elus.length }})
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <div 
                                    v-for="elu in form.elus" 
                                    :key="`${elu.type}-${elu.id}`"
                                    class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-emerald-200 dark:border-emerald-700"
                                >
                                    <div 
                                        v-if="elu.photo_url"
                                        class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden flex-shrink-0"
                                    >
                                        <img :src="elu.photo_url" :alt="elu.nom_complet" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        👤
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ elu.nom_complet }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                            ({{ elu.type === 'depute' ? 'Député' : elu.type === 'senateur' ? 'Sénateur' : 'Maire' }})
                                        </span>
                                    </div>
                                    <button 
                                        @click="removeElu(elu.type, elu.id)"
                                        class="ml-2 text-gray-400 hover:text-rose-500 transition"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestions list -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        💡 Suggestions
                                    </span>
                                    <span v-if="isLoadingElus" class="text-xs text-gray-500">⏳ Chargement...</span>
                                    <span v-else class="text-xs text-gray-500">{{ allSuggestedElus.length }} résultat(s)</span>
                                </div>
                            </div>
                            
                            <div class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                <button
                                    v-for="elu in allSuggestedElus"
                                    :key="`${elu.type}-${elu.id}`"
                                    @click="selectElu(elu)"
                                    :disabled="form.elus.some(e => e.type === elu.type && e.id === elu.id)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-4 py-3 text-left transition',
                                        form.elus.some(e => e.type === elu.type && e.id === elu.id)
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 cursor-not-allowed'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                    ]"
                                >
                                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                                        <img 
                                            v-if="elu.photo_url" 
                                            :src="elu.photo_url" 
                                            :alt="elu.nom_complet"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-lg">
                                            👤
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white truncate">{{ elu.nom_complet }}</span>
                                            <span 
                                                class="px-2 py-0.5 text-xs rounded-full"
                                                :class="{
                                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': elu.type === 'depute',
                                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': elu.type === 'senateur',
                                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': elu.type === 'maire',
                                                }"
                                            >
                                                {{ elu.typeLabel }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            <span v-if="elu.groupe" class="mr-2">{{ elu.groupe }}</span>
                                            <span v-if="elu.circonscription">· {{ elu.circonscription }}</span>
                                            <span v-if="elu.commune">· {{ elu.commune }}</span>
                                        </div>
                                    </div>
                                    <div v-if="form.elus.some(e => e.type === elu.type && e.id === elu.id)" class="text-emerald-500">
                                        ✓
                                    </div>
                                </button>
                                
                                <div v-if="allSuggestedElus.length === 0 && !isLoadingElus" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="text-3xl mb-2">🔍</div>
                                    <p class="text-sm">
                                        <span v-if="form.scope === 'national' && eluSearch.length < 2">
                                            Commencez à taper pour rechercher un élu
                                        </span>
                                        <span v-else>
                                            Aucun élu trouvé. Essayez une autre recherche.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation buttons -->
                    <div class="flex justify-between mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            v-if="currentStep > 1"
                            @click="prevStep"
                            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-colors"
                        >
                            ← Précédent
                        </button>
                        <div v-else></div>

                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canProceed"
                            :class="[
                                'px-6 py-3 rounded-xl font-medium transition-all',
                                canProceed
                                    ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                            ]"
                        >
                            Suivant →
                        </button>

                        <button
                            v-else
                            @click="submit"
                            :disabled="form.processing || !canProceed"
                            class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl transition-all shadow-lg"
                        >
                            <span v-if="form.processing">⏳ Publication...</span>
                            <span v-else>🚀 Publier mon idée</span>
                        </button>
                    </div>
                </Card>

                <!-- Preview Card -->
                <Card class="mt-8">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">📋 Aperçu de votre contribution</h3>
                    <div class="flex items-start gap-4">
                        <div class="text-3xl">
                            {{ ideaTypes.find(t => t.value === form.idea_type)?.icon }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': form.idea_type === 'proposal',
                                        'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400': form.idea_type === 'question',
                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': form.idea_type === 'debate',
                                        'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400': form.idea_type === 'petition',
                                        'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': form.idea_type === 'interpellation',
                                    }"
                                >
                                    {{ ideaTypes.find(t => t.value === form.idea_type)?.label }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ scopes.find(s => s.value === form.scope)?.icon }}
                                    {{ scopes.find(s => s.value === form.scope)?.label }}
                                </span>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ form.title || 'Titre de votre idée...' }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                {{ form.description || 'Description de votre idée...' }}
                            </p>
                            <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-1 mt-2">
                                <span 
                                    v-for="tag in selectedTags" 
                                    :key="tag.id"
                                    class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded text-xs"
                                >
                                    {{ tag.nom }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
