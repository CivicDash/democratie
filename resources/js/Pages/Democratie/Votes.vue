<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    dernierScrutin: { type: Object, default: null },
    groupes: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Comment Votent-ils ?', current: true, icon: '🗳️' },
];

const typesScrutin = [
    {
        icon: '🖐️',
        title: 'Vote à main levée',
        description: 'Le mode le plus courant. Le président de séance apprécie la majorité "à vue". Rapide mais imprécis : aucun décompte nominal n\'est enregistré.',
        frequency: 'Majorité des votes (~80%)',
    },
    {
        icon: '📊',
        title: 'Scrutin public ordinaire',
        description: 'Vote électronique individuel depuis l\'hémicycle. Chaque député appuie sur un bouton (Pour, Contre, Abstention). Le résultat est nominatif et publié.',
        frequency: 'Demandé par un président de groupe ou le Gouvernement',
    },
    {
        icon: '⚖️',
        title: 'Scrutin public solennel',
        description: 'Le vote le plus formel. Utilisé pour les textes importants. Chaque député vote individuellement à la tribune, appelé par ordre alphabétique. Le résultat est toujours publié.',
        frequency: 'Lois de finances, révisions constitutionnelles, textes majeurs',
    },
];

const totalVotes = computed(() => {
    if (!props.dernierScrutin) return 0;
    return props.dernierScrutin.pour + props.dernierScrutin.contre + props.dernierScrutin.abstentions;
});

const pourPct = computed(() => totalVotes.value ? Math.round((props.dernierScrutin.pour / totalVotes.value) * 100) : 0);
const contrePct = computed(() => totalVotes.value ? Math.round((props.dernierScrutin.contre / totalVotes.value) * 100) : 0);
const abstPct = computed(() => totalVotes.value ? 100 - pourPct.value - contrePct.value : 0);

const ventilationAvecNom = computed(() => {
    if (!props.dernierScrutin?.ventilation_groupes) return [];
    return props.dernierScrutin.ventilation_groupes
        .map(v => ({
            ...v,
            total: v.pour + v.contre + v.abstentions,
        }))
        .filter(v => v.total > 0)
        .sort((a, b) => b.total - a.total);
});
</script>

<template>
    <Head title="Comment Votent-ils ? - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🗳️ Comment Votent-ils ?</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Scrutins publics, scrutins solennels, discipline de groupe : décryptez les votes à l'Assemblée nationale.
                    </p>
                </div>

                <!-- Types de scrutin -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📋 Les 3 types de vote</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            v-for="type in typesScrutin"
                            :key="type.title"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5"
                        >
                            <div class="text-3xl mb-3">{{ type.icon }}</div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ type.title }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ type.description }}</p>
                            <div class="mt-3 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                                {{ type.frequency }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exemple interactif -->
                <div v-if="dernierScrutin" class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">📊 Exemple réel : dernier scrutin solennel</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Voici un vrai scrutin récent à l'Assemblée nationale, avec le détail par groupe politique.
                    </p>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 mb-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white leading-snug">{{ dernierScrutin.titre }}</h3>
                                <div class="flex items-center gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ dernierScrutin.date }}</span>
                                    <span>·</span>
                                    <span>{{ dernierScrutin.type }}</span>
                                    <span>·</span>
                                    <span>N°{{ dernierScrutin.numero }}</span>
                                </div>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-sm font-semibold"
                                :class="dernierScrutin.est_adopte
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'"
                            >
                                {{ dernierScrutin.resultat }}
                            </span>
                        </div>
                    </div>

                    <!-- Vote bar -->
                    <div class="mb-6">
                        <div class="flex rounded-full overflow-hidden h-8">
                            <div
                                class="bg-emerald-500 flex items-center justify-center text-white text-xs font-bold"
                                :style="{ width: pourPct + '%' }"
                            >
                                <span v-if="pourPct > 10">{{ dernierScrutin.pour }}</span>
                            </div>
                            <div
                                class="bg-gray-400 flex items-center justify-center text-white text-xs font-bold"
                                :style="{ width: abstPct + '%' }"
                            >
                                <span v-if="abstPct > 10">{{ dernierScrutin.abstentions }}</span>
                            </div>
                            <div
                                class="bg-red-500 flex items-center justify-center text-white text-xs font-bold"
                                :style="{ width: contrePct + '%' }"
                            >
                                <span v-if="contrePct > 10">{{ dernierScrutin.contre }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>✅ Pour : {{ dernierScrutin.pour }} ({{ pourPct }}%)</span>
                            <span>⬜ Abstentions : {{ dernierScrutin.abstentions }} ({{ abstPct }}%)</span>
                            <span>❌ Contre : {{ dernierScrutin.contre }} ({{ contrePct }}%)</span>
                        </div>
                    </div>

                    <!-- Ventilation par groupe -->
                    <div v-if="ventilationAvecNom.length > 0">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Détail par groupe politique</h4>
                        <div class="space-y-3">
                            <div v-for="g in ventilationAvecNom" :key="g.organe_ref">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: g.couleur }"></span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ g.sigle }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">{{ g.total }} votants</span>
                                </div>
                                <div class="flex rounded-full overflow-hidden h-3">
                                    <div class="bg-emerald-400" :style="{ width: (g.total ? (g.pour / g.total * 100) : 0) + '%' }"></div>
                                    <div class="bg-gray-300 dark:bg-gray-600" :style="{ width: (g.total ? (g.abstentions / g.total * 100) : 0) + '%' }"></div>
                                    <div class="bg-red-400" :style="{ width: (g.total ? (g.contre / g.total * 100) : 0) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <Link
                            :href="route('legislation.scrutins.show', { uid: dernierScrutin.uid })"
                            class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                        >
                            Voir le détail complet de ce scrutin
                            <svg class="ml-1 w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- Discipline de groupe -->
                <div class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">🎯 La Discipline de Groupe</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Les groupes parlementaires donnent des consignes de vote à leurs membres. La "discipline de groupe" mesure 
                        à quel point les membres suivent ces consignes.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Comment ça fonctionne ?</h3>
                            <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                                <div class="flex gap-3">
                                    <span class="text-lg">1️⃣</span>
                                    <p>Avant chaque scrutin, le groupe se réunit et décide d'une <strong>position commune</strong> (pour, contre, abstention).</p>
                                </div>
                                <div class="flex gap-3">
                                    <span class="text-lg">2️⃣</span>
                                    <p>Les membres sont invités à voter selon cette consigne. C'est la <strong>discipline de groupe</strong>.</p>
                                </div>
                                <div class="flex gap-3">
                                    <span class="text-lg">3️⃣</span>
                                    <p>Un député peut voter différemment : c'est un <strong>"vote dissident"</strong>. En France, le mandat est libre (pas de sanction juridique).</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Taux de cohésion typiques</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 95%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-24">~95% Majorité</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" style="width: 88%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-24">~88% Opposition</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: 70%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-24">~70% Sujets sociétaux</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                La cohésion baisse sur les votes de conscience (bioéthique, fin de vie, etc.) où les groupes laissent la "liberté de vote".
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Links -->
                <div class="mt-8 flex flex-wrap gap-3 justify-center pb-8">
                    <Link :href="route('legislation.scrutins.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        📊 Tous les scrutins
                    </Link>
                    <Link :href="route('legislation.groupes.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Groupes politiques
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
