<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    exemples: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Parcours d\'une Loi', current: true, icon: '📜' },
];

const activeStep = ref(null);
const showQuiz = ref(false);
const quizAnswers = ref({});
const quizSubmitted = ref(false);

const steps = [
    {
        id: 'initiative',
        icon: '💡',
        title: 'Initiative',
        short: 'Qui peut proposer une loi ?',
        detail: 'L\'initiative peut venir du Gouvernement (projet de loi) ou d\'un parlementaire (proposition de loi). Le Gouvernement dépose ses projets devant l\'une ou l\'autre des assemblées, sauf pour les lois de finances (AN en priorité).',
        acteurs: ['Premier Ministre', 'Députés', 'Sénateurs'],
    },
    {
        id: 'depot',
        icon: '📥',
        title: 'Dépôt & Enregistrement',
        short: 'Le texte est enregistré officiellement',
        detail: 'Le texte est déposé au bureau de l\'Assemblée nationale ou du Sénat. Il est enregistré, numéroté et imprimé. Il est ensuite renvoyé à la commission permanente compétente.',
        acteurs: ['Bureau de l\'assemblée'],
    },
    {
        id: 'commission',
        icon: '🔍',
        title: 'Examen en Commission',
        short: 'Les experts étudient le texte article par article',
        detail: 'La commission permanente compétente (ou une commission spéciale) examine le texte article par article. Les commissaires peuvent déposer des amendements. Le rapporteur rédige un rapport. Depuis 2008, c\'est le texte de la commission (et non le texte initial) qui est discuté en séance.',
        acteurs: ['Commission permanente', 'Rapporteur'],
    },
    {
        id: 'seance',
        icon: '🎙️',
        title: 'Discussion en Séance Publique',
        short: 'Le texte est débattu par tous les parlementaires',
        detail: 'Le texte est inscrit à l\'ordre du jour. Discussion générale, puis article par article. Chaque député ou sénateur peut défendre des amendements. Le texte modifié est mis aux voix (vote par scrutin public ou à main levée).',
        acteurs: ['Tous les députés ou sénateurs'],
    },
    {
        id: 'navette',
        icon: '🔄',
        title: 'Navette Parlementaire',
        short: 'L\'autre chambre examine le texte à son tour',
        detail: 'Le texte voté est transmis à l\'autre chambre (AN → Sénat ou Sénat → AN) qui l\'examine selon le même processus. S\'il est modifié, le texte revient à la première chambre : c\'est la "navette". Les allers-retours continuent jusqu\'à accord sur un texte identique, ou jusqu\'à l\'intervention du Gouvernement.',
        acteurs: ['Assemblée nationale', 'Sénat'],
    },
    {
        id: 'cmp',
        icon: '🤝',
        title: 'Commission Mixte Paritaire',
        short: 'En cas de désaccord : 7 députés + 7 sénateurs négocient',
        detail: 'Si le désaccord persiste après 2 lectures (ou 1 en procédure accélérée), le Premier Ministre peut convoquer une Commission Mixte Paritaire (CMP) de 7 députés et 7 sénateurs. En cas d\'échec de la CMP, le Gouvernement peut donner le "dernier mot" à l\'Assemblée nationale.',
        acteurs: ['7 députés', '7 sénateurs', 'Premier Ministre'],
    },
    {
        id: 'vote-final',
        icon: '✅',
        title: 'Vote Final',
        short: 'Adoption définitive par les deux chambres',
        detail: 'Le texte de compromis (ou le texte avec dernier mot de l\'AN) est soumis au vote final. Si adopté, la loi est transmise au Président de la République. Le Conseil constitutionnel peut être saisi avant la promulgation (par 60 députés, 60 sénateurs, le Président, le PM ou les présidents des assemblées).',
        acteurs: ['Assemblée nationale', 'Sénat', 'Conseil constitutionnel'],
    },
    {
        id: 'promulgation',
        icon: '🏛️',
        title: 'Promulgation & Publication',
        short: 'Le Président signe, la loi entre en vigueur',
        detail: 'Le Président de la République dispose de 15 jours pour promulguer la loi (la signer). Il peut demander une nouvelle délibération (rare). La loi est ensuite publiée au Journal Officiel (JO) et entre en vigueur le lendemain (ou à une date spécifiée dans le texte).',
        acteurs: ['Président de la République', 'Journal Officiel'],
    },
];

function toggleStep(id) {
    activeStep.value = activeStep.value === id ? null : id;
}

const quizQuestions = [
    {
        id: 1,
        question: 'Qui peut être à l\'initiative d\'une loi ?',
        options: ['Uniquement le Président', 'Le Gouvernement et les parlementaires', 'Uniquement les députés', 'Les citoyens directement'],
        correct: 1,
    },
    {
        id: 2,
        question: 'Que se passe-t-il en cas de désaccord entre l\'AN et le Sénat ?',
        options: ['La loi est abandonnée', 'Le Président tranche', 'Une CMP de 14 parlementaires est convoquée', 'On refait un référendum'],
        correct: 2,
    },
    {
        id: 3,
        question: 'Combien de lectures maximum pour un texte en procédure accélérée avant la CMP ?',
        options: ['3 lectures', '2 lectures', '1 lecture par chambre', '4 lectures'],
        correct: 2,
    },
    {
        id: 4,
        question: 'Qui promulgue la loi ?',
        options: ['Le Premier Ministre', 'Le Président du Sénat', 'Le Président de la République', 'Le Conseil constitutionnel'],
        correct: 2,
    },
    {
        id: 5,
        question: 'Où la loi est-elle publiée pour entrer en vigueur ?',
        options: ['Sur le site de l\'Élysée', 'Au Journal Officiel', 'Dans la presse nationale', 'Au bulletin municipal'],
        correct: 1,
    },
];

function selectAnswer(qId, optIndex) {
    if (quizSubmitted.value) return;
    quizAnswers.value[qId] = optIndex;
}

function submitQuiz() {
    quizSubmitted.value = true;
}

const quizScore = computed(() => {
    if (!quizSubmitted.value) return 0;
    return quizQuestions.filter(q => quizAnswers.value[q.id] === q.correct).length;
});

const etatBadge = {
    '04': { label: 'Promulguée', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' },
    '01': { label: 'En cours', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' },
    '03': { label: 'Rejetée', class: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' },
};
</script>

<template>
    <Head title="Parcours d'une Loi - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        📜 Le Parcours d'une Loi
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        De l'initiative à la publication au Journal Officiel : découvrez les 8 étapes du processus législatif français.
                    </p>
                </div>

                <!-- Interactive Timeline -->
                <div class="mt-10 relative">
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    <div v-for="(step, index) in steps" :key="step.id" class="relative pl-16 pb-8">
                        <!-- Connector dot -->
                        <button
                            @click="toggleStep(step.id)"
                            class="absolute left-3 w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all cursor-pointer"
                            :class="activeStep === step.id
                                ? 'bg-indigo-600 border-indigo-600 text-white scale-110'
                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-indigo-400'"
                        >
                            {{ index + 1 }}
                        </button>

                        <!-- Step card -->
                        <div
                            @click="toggleStep(step.id)"
                            class="bg-white dark:bg-gray-800 border rounded-xl p-5 cursor-pointer transition-all"
                            :class="activeStep === step.id
                                ? 'border-indigo-300 dark:border-indigo-700 shadow-md'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ step.icon }}</span>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ step.title }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ step.short }}</p>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': activeStep === step.id }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <!-- Expanded detail -->
                            <div v-if="activeStep === step.id" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ step.detail }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="acteur in step.acteurs"
                                        :key="acteur"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300"
                                    >
                                        {{ acteur }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navette Schema -->
                <div class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔄 La Navette Parlementaire</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Le texte fait des allers-retours entre les deux chambres jusqu'à un accord ou l'intervention du Gouvernement.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-2">
                        <!-- AN -->
                        <div class="flex-shrink-0 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-center w-48">
                            <div class="text-3xl mb-2">🏛️</div>
                            <div class="font-semibold text-blue-900 dark:text-blue-200">Assemblée Nationale</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">577 députés</div>
                        </div>

                        <!-- Arrows -->
                        <div class="flex flex-col items-center gap-1">
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <span class="hidden sm:inline">1re lecture</span>
                                <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                            </div>
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <svg class="w-6 h-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" /></svg>
                                <span class="hidden sm:inline">2e lecture</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">ou CMP</div>
                        </div>

                        <!-- Sénat -->
                        <div class="flex-shrink-0 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4 text-center w-48">
                            <div class="text-3xl mb-2">🏛️</div>
                            <div class="font-semibold text-rose-900 dark:text-rose-200">Sénat</div>
                            <div class="text-xs text-rose-600 dark:text-rose-400 mt-1">348 sénateurs</div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <div class="inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-2 text-sm text-amber-800 dark:text-amber-200">
                            <span>🤝</span>
                            <span><strong>CMP :</strong> 7 députés + 7 sénateurs en cas de désaccord</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            En cas d'échec de la CMP, l'Assemblée nationale a le "dernier mot" si le Gouvernement le demande.
                        </p>
                    </div>
                </div>

                <!-- Real Examples -->
                <div v-if="exemples.length > 0" class="mt-12">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📚 Exemples Réels</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Découvrez le parcours concret de lois récentes.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            v-for="loi in exemples"
                            :key="loi.loicod"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5"
                        >
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="etatBadge[loi.etat_code]?.class || 'bg-gray-100 text-gray-800'"
                                >
                                    {{ etatBadge[loi.etat_code]?.label || loi.etat }}
                                </span>
                            </div>
                            <h3 class="font-medium text-gray-900 dark:text-white text-sm leading-snug line-clamp-3">
                                {{ loi.titre }}
                            </h3>

                            <!-- Mini progress -->
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Progression</span>
                                    <span>{{ loi.progression }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div
                                        class="h-1.5 rounded-full transition-all"
                                        :class="loi.etat_code === '04' ? 'bg-emerald-500' : loi.etat_code === '03' ? 'bg-red-500' : 'bg-blue-500'"
                                        :style="{ width: loi.progression + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <Link
                                :href="loi.url"
                                class="mt-3 inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Voir le parcours complet
                                <svg class="ml-1 w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quiz -->
                <div class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">🧠 Testez vos connaissances</h2>
                        <button
                            v-if="!showQuiz"
                            @click="showQuiz = true"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition"
                        >
                            Commencer le quiz
                        </button>
                    </div>

                    <div v-if="showQuiz">
                        <div v-for="q in quizQuestions" :key="q.id" class="mb-6">
                            <p class="font-medium text-gray-900 dark:text-white mb-3">
                                {{ q.id }}. {{ q.question }}
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button
                                    v-for="(opt, idx) in q.options"
                                    :key="idx"
                                    @click="selectAnswer(q.id, idx)"
                                    class="text-left px-4 py-2.5 rounded-lg border text-sm transition-all"
                                    :class="[
                                        quizSubmitted && idx === q.correct
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-400 text-emerald-800 dark:text-emerald-200'
                                            : quizSubmitted && quizAnswers[q.id] === idx && idx !== q.correct
                                                ? 'bg-red-50 dark:bg-red-900/20 border-red-400 text-red-800 dark:text-red-200'
                                                : quizAnswers[q.id] === idx
                                                    ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-400 text-indigo-800 dark:text-indigo-200'
                                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300',
                                    ]"
                                >
                                    {{ opt }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!quizSubmitted" class="text-center">
                            <button
                                @click="submitQuiz"
                                :disabled="Object.keys(quizAnswers).length < quizQuestions.length"
                                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Valider mes réponses
                            </button>
                        </div>

                        <div v-else class="text-center mt-4 p-4 rounded-lg" :class="quizScore >= 4 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20'">
                            <p class="text-lg font-semibold" :class="quizScore >= 4 ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'">
                                {{ quizScore >= 4 ? '🎉' : '💪' }} {{ quizScore }} / {{ quizQuestions.length }} bonnes réponses
                            </p>
                            <p class="text-sm mt-1" :class="quizScore >= 4 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                {{ quizScore >= 4 ? 'Excellent ! Vous maîtrisez le processus législatif.' : 'Pas mal ! Relisez les étapes ci-dessus pour progresser.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Links to existing pages -->
                <div class="mt-8 flex flex-wrap gap-3 justify-center pb-8">
                    <Link :href="route('lois.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        📜 Explorer les lois en cours
                    </Link>
                    <Link :href="route('legislation.scrutins.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🗳️ Voir les scrutins
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
