<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Les Élections en France', current: true, icon: '🗳️' },
];

const activeElection = ref(null);

const elections = [
    {
        id: 'presidentielle',
        icon: '🇫🇷',
        title: 'Présidentielle',
        scrutin: 'Uninominal majoritaire à 2 tours',
        mandat: '5 ans (quinquennat)',
        elus: '1 Président',
        ageMin: 18,
        prochaine: '2027',
        color: 'blue',
        description: 'Le Président de la République est élu au suffrage universel direct. Au 1er tour, si aucun candidat n\'obtient la majorité absolue, les 2 premiers s\'affrontent au 2nd tour.',
        route: null,
    },
    {
        id: 'legislatives',
        icon: '🏛️',
        title: 'Législatives',
        scrutin: 'Uninominal majoritaire à 2 tours',
        mandat: '5 ans',
        elus: '577 députés',
        ageMin: 18,
        prochaine: '2027',
        color: 'indigo',
        description: 'Chaque député est élu dans une circonscription. Au 1er tour, il faut la majorité absolue et au moins 25% des inscrits. Sinon, les candidats ayant obtenu 12,5% des inscrits passent au 2nd tour.',
        route: 'elections.legislatives',
    },
    {
        id: 'senatoriales',
        icon: '🏛️',
        title: 'Sénatoriales',
        scrutin: 'Indirect (grands électeurs)',
        mandat: '6 ans (renouvellement par moitié tous les 3 ans)',
        elus: '348 sénateurs',
        ageMin: 24,
        prochaine: '2026',
        color: 'rose',
        description: 'Les sénateurs sont élus par un collège de grands électeurs (élus locaux et parlementaires). Scrutin proportionnel dans les départements élisant 3+ sénateurs, majoritaire sinon.',
        route: 'elections.senatoriales',
    },
    {
        id: 'europeennes',
        icon: '🇪🇺',
        title: 'Européennes',
        scrutin: 'Proportionnel de liste à un tour',
        mandat: '5 ans',
        elus: '81 eurodéputés français',
        ageMin: 18,
        prochaine: '2029',
        color: 'amber',
        description: 'Les eurodéputés sont élus au scrutin de liste proportionnel dans une circonscription unique nationale. Les listes doivent obtenir au moins 5% pour avoir des élus.',
        route: null,
    },
    {
        id: 'regionales',
        icon: '🗺️',
        title: 'Régionales',
        scrutin: 'Proportionnel de liste à 2 tours avec prime majoritaire',
        mandat: '6 ans',
        elus: '~1 750 conseillers régionaux',
        ageMin: 18,
        prochaine: '2028',
        color: 'emerald',
        description: 'La liste arrivée en tête obtient 25% des sièges en prime, le reste est réparti proportionnellement entre les listes ayant obtenu au moins 5%.',
        route: null,
    },
    {
        id: 'departementales',
        icon: '📍',
        title: 'Départementales',
        scrutin: 'Binominal majoritaire à 2 tours',
        mandat: '6 ans',
        elus: '~4 000 conseillers départementaux',
        ageMin: 18,
        prochaine: '2028',
        color: 'violet',
        description: 'Particularité unique en France : chaque canton élit un binôme paritaire (un homme et une femme). C\'est le seul scrutin binominal au monde.',
        route: null,
    },
    {
        id: 'municipales',
        icon: '🏘️',
        title: 'Municipales',
        scrutin: 'Scrutin de liste (proportionnel ou majoritaire selon la taille)',
        mandat: '6 ans',
        elus: '~500 000 conseillers municipaux',
        ageMin: 18,
        prochaine: '2026',
        color: 'teal',
        description: 'Dans les communes de 1 000+ habitants : scrutin de liste proportionnel avec prime majoritaire. En dessous : scrutin plurinominal majoritaire. Les citoyens européens peuvent voter.',
        route: 'elections.municipales.index',
    },
];

const timeline = [
    { year: '2026', events: ['Sénatoriales (sept.)', 'Municipales (mars)'] },
    { year: '2027', events: ['Présidentielle (avril-mai)', 'Législatives (juin)'] },
    { year: '2028', events: ['Régionales', 'Départementales'] },
    { year: '2029', events: ['Européennes (juin)'] },
    { year: '2030', events: ['Sénatoriales (sept.)'] },
    { year: '2032', events: ['Présidentielle', 'Législatives', 'Municipales'] },
];

const simAge = ref(null);
const simNationality = ref('francaise');

const simResult = computed(() => {
    if (!simAge.value || simAge.value < 16) return null;
    const age = Number(simAge.value);
    const isFrench = simNationality.value === 'francaise';
    const isEU = simNationality.value === 'europeenne';

    const results = [];
    if (age >= 18 && isFrench) {
        results.push(
            { name: 'Présidentielle', ok: true },
            { name: 'Législatives', ok: true },
            { name: 'Européennes', ok: true },
            { name: 'Régionales', ok: true },
            { name: 'Départementales', ok: true },
            { name: 'Municipales', ok: true },
        );
        if (age >= 24) results.push({ name: 'Sénatoriales (éligible)', ok: true });
        else results.push({ name: 'Sénatoriales', ok: false, reason: 'Éligible à partir de 24 ans' });
    } else if (age >= 18 && isEU) {
        results.push(
            { name: 'Présidentielle', ok: false, reason: 'Réservée aux citoyens français' },
            { name: 'Législatives', ok: false, reason: 'Réservée aux citoyens français' },
            { name: 'Européennes', ok: true },
            { name: 'Régionales', ok: false, reason: 'Réservée aux citoyens français' },
            { name: 'Départementales', ok: false, reason: 'Réservée aux citoyens français' },
            { name: 'Municipales', ok: true },
            { name: 'Sénatoriales', ok: false, reason: 'Réservée aux citoyens français' },
        );
    } else if (age >= 18) {
        results.push(
            { name: 'Aucune élection', ok: false, reason: 'Le droit de vote en France est réservé aux citoyens français et européens (municipales + européennes).' },
        );
    } else {
        results.push(
            { name: 'Aucune élection', ok: false, reason: `Le droit de vote est accessible à partir de 18 ans (il vous reste ${18 - age} an${18 - age > 1 ? 's' : ''}).` },
        );
    }
    return results;
});

const colorMap = {
    blue: 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20',
    indigo: 'border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20',
    rose: 'border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20',
    amber: 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20',
    emerald: 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20',
    violet: 'border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20',
    teal: 'border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20',
};
</script>

<template>
    <Head title="Les Élections en France - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🗳️ Les Élections en France</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        7 types de scrutin, des millions d'élus : le système électoral français décrypté.
                    </p>
                </div>

                <!-- Election cards -->
                <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="e in elections"
                        :key="e.id"
                        class="border rounded-xl p-5 transition-all cursor-pointer hover:shadow-md"
                        :class="[colorMap[e.color], activeElection === e.id ? 'ring-2 ring-indigo-500' : '']"
                        @click="activeElection = activeElection === e.id ? null : e.id"
                    >
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">{{ e.icon }}</span>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ e.title }}</h3>
                        </div>

                        <div class="space-y-1.5 text-sm">
                            <div class="flex items-start gap-2">
                                <span class="text-gray-400 shrink-0">📊</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ e.scrutin }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">⏱️</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ e.mandat }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">👥</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ e.elus }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">🎂</span>
                                <span class="text-gray-600 dark:text-gray-400">Dès {{ e.ageMin }} ans</span>
                            </div>
                        </div>

                        <div class="mt-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/60 dark:bg-black/20 text-gray-700 dark:text-gray-300">
                            Prochaine : {{ e.prochaine }}
                        </div>

                        <!-- Expanded detail -->
                        <div v-if="activeElection === e.id" class="mt-4 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ e.description }}</p>
                            <Link
                                v-if="e.route"
                                :href="route(e.route)"
                                class="mt-3 inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                            >
                                Explorer les données
                                <svg class="ml-1 w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📅 Calendrier Électoral 2026-2032</h2>
                    <div class="flex overflow-x-auto gap-4 pb-2">
                        <div
                            v-for="t in timeline"
                            :key="t.year"
                            class="flex-shrink-0 w-40"
                        >
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-bold text-sm mb-2">
                                    {{ t.year }}
                                </div>
                                <div class="space-y-1">
                                    <div
                                        v-for="event in t.events"
                                        :key="event"
                                        class="text-xs bg-gray-100 dark:bg-gray-700 rounded-lg px-2 py-1.5 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ event }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulator -->
                <div class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">🧮 Simulateur : Dans quelles élections puis-je voter ?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Indiquez votre âge et votre nationalité pour savoir à quelles élections vous pouvez participer.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Votre âge</label>
                            <input
                                v-model.number="simAge"
                                type="number"
                                min="16"
                                max="120"
                                placeholder="ex: 25"
                                class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nationalité</label>
                            <select
                                v-model="simNationality"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="francaise">Française</option>
                                <option value="europeenne">Européenne (non française)</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="simResult" class="space-y-2">
                        <div
                            v-for="r in simResult"
                            :key="r.name"
                            class="flex items-center gap-3 p-3 rounded-lg"
                            :class="r.ok ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-gray-50 dark:bg-gray-700/50'"
                        >
                            <span v-if="r.ok" class="text-emerald-600 dark:text-emerald-400 text-lg">✅</span>
                            <span v-else class="text-gray-400 text-lg">❌</span>
                            <div>
                                <span class="font-medium text-sm" :class="r.ok ? 'text-emerald-800 dark:text-emerald-200' : 'text-gray-600 dark:text-gray-400'">{{ r.name }}</span>
                                <p v-if="r.reason" class="text-xs text-gray-500 dark:text-gray-400">{{ r.reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Links -->
                <div class="mt-8 flex flex-wrap gap-3 justify-center pb-8">
                    <Link :href="route('elections.municipales.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏘️ Municipales 2026
                    </Link>
                    <Link :href="route('elections.legislatives')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Législatives
                    </Link>
                    <Link :href="route('elections.senatoriales')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Sénatoriales
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
