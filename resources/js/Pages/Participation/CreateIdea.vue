<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

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
const totalSteps = 5;

const steps = [
    { id: 1, title: 'Type', icon: '💡', description: 'Quel type de contribution ?' },
    { id: 2, title: 'Contenu', icon: '✍️', description: 'Rédigez votre idée' },
    { id: 3, title: 'Échelle', icon: '🗺️', description: 'Portée géographique' },
    { id: 4, title: 'Thèmes', icon: '🏷️', description: 'Catégorisez votre idée' },
    { id: 5, title: 'Élus', icon: '👤', description: 'Liez des élus (optionnel)' },
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
        case 4: return true;
        case 5: return true;
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

                    <!-- STEP 5: Élus -->
                    <div v-if="currentStep === 5" class="space-y-6">
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

                        <!-- Info message -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                                💡 La recherche d'élus sera disponible prochainement. 
                                Pour l'instant, vous pouvez soumettre votre idée sans élu lié.
                            </p>
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
