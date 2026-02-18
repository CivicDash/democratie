<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Le Conseil Constitutionnel', current: true, icon: '⚖️' },
];

const activeSection = ref(null);

const missions = [
    {
        id: 'constitutionnalite',
        icon: '📜',
        title: 'Contrôle de constitutionnalité',
        color: 'indigo',
        summary: 'Vérifier que les lois respectent la Constitution avant ou après leur promulgation.',
        detail: 'Le Conseil peut être saisi avant la promulgation d\'une loi (contrôle a priori, art. 61) par le Président, le Premier Ministre, les présidents des assemblées, ou 60 députés/sénateurs. Depuis 2010, tout justiciable peut aussi saisir le Conseil via une QPC (Question Prioritaire de Constitutionnalité, art. 61-1).',
    },
    {
        id: 'elections',
        icon: '🗳️',
        title: 'Contrôle des élections',
        color: 'emerald',
        summary: 'Garantir la régularité des élections présidentielles, législatives et des référendums.',
        detail: 'Le Conseil constitutionnel proclame les résultats de l\'élection présidentielle, veille à la régularité des référendums, et peut être saisi pour contester l\'élection d\'un député ou d\'un sénateur. Il peut annuler une élection en cas d\'irrégularité.',
    },
    {
        id: 'droits',
        icon: '🛡️',
        title: 'Protection des droits fondamentaux',
        color: 'amber',
        summary: 'Garantir le respect des droits et libertés constitutionnels des citoyens.',
        detail: 'Le "bloc de constitutionnalité" comprend la Constitution de 1958, la Déclaration des droits de l\'homme de 1789, le préambule de 1946, la Charte de l\'environnement de 2004, et les principes fondamentaux reconnus par les lois de la République. Le Conseil veille au respect de l\'ensemble de ces textes.',
    },
];

const composition = [
    { role: 'Membres nommés', count: 9, detail: '3 nommés par le Président de la République, 3 par le président de l\'Assemblée nationale, 3 par le président du Sénat. Mandat de 9 ans, non renouvelable. Renouvellement par tiers tous les 3 ans.' },
    { role: 'Membres de droit', count: null, detail: 'Les anciens Présidents de la République sont membres de droit à vie. En pratique, depuis 2004, plus aucun ancien Président n\'y siège.' },
    { role: 'Président du Conseil', count: 1, detail: 'Désigné par le Président de la République parmi les membres. Voix prépondérante en cas d\'égalité.' },
];

const qpcSteps = [
    { step: 1, title: 'Le justiciable', description: 'Lors d\'un procès, un justiciable conteste la constitutionnalité d\'une disposition législative applicable à son litige.' },
    { step: 2, title: 'Le juge du fond', description: 'Le tribunal examine si la question est sérieuse, nouvelle, et n\'a pas déjà été tranchée. Si oui, il la transmet.' },
    { step: 3, title: 'Cour de cassation ou Conseil d\'État', description: 'La juridiction suprême filtre la QPC en 3 mois maximum. Elle vérifie le caractère sérieux et la transmet au Conseil constitutionnel.' },
    { step: 4, title: 'Le Conseil constitutionnel', description: 'Le Conseil statue en 3 mois. Il peut déclarer la disposition conforme, non conforme (abrogation), ou émettre une réserve d\'interprétation.' },
    { step: 5, title: 'Effet de la décision', description: 'Si la disposition est déclarée inconstitutionnelle, elle est abrogée. Le Conseil peut différer l\'abrogation pour laisser au législateur le temps d\'adapter la loi.' },
];

const chiffresCles = [
    { label: 'Membres', value: '9', sub: '+ anciens Présidents' },
    { label: 'Durée du mandat', value: '9 ans', sub: 'non renouvelable' },
    { label: 'Décisions QPC/an', value: '~80', sub: 'depuis 2010' },
    { label: 'Délais QPC', value: '3 mois', sub: 'pour statuer' },
];

const colorClasses = {
    indigo: 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800',
    emerald: 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800',
    amber: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
};
</script>

<template>
    <Head title="Le Conseil Constitutionnel - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⚖️ Le Conseil Constitutionnel</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Gardien de la Constitution et protecteur des droits fondamentaux des citoyens.
                        Institution clé de la Vème République depuis 1958.
                    </p>
                </div>

                <!-- Chiffres clés -->
                <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="c in chiffresCles"
                        :key="c.label"
                        class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center"
                    >
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ c.value }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ c.label }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ c.sub }}</p>
                    </div>
                </div>

                <!-- Missions -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🎯 Les Missions</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            v-for="m in missions"
                            :key="m.id"
                            class="border rounded-xl p-5 cursor-pointer transition-all hover:shadow-md"
                            :class="[colorClasses[m.color], activeSection === m.id ? 'ring-2 ring-indigo-500' : '']"
                            @click="activeSection = activeSection === m.id ? null : m.id"
                        >
                            <div class="text-3xl mb-3">{{ m.icon }}</div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ m.title }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ m.summary }}</p>

                            <div v-if="activeSection === m.id" class="mt-4 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ m.detail }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Composition -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">👥 La Composition</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Le Conseil constitutionnel est composé de 9 membres nommés, auxquels s'ajoutent les anciens Présidents de la République.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="c in composition"
                            :key="c.role"
                            class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5"
                        >
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ c.role }}</h3>
                                <span v-if="c.count" class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ c.count }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ c.detail }}</p>
                        </div>
                    </div>
                </div>

                <!-- QPC -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">📋 La QPC : le citoyen face à la loi</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Depuis la réforme constitutionnelle de 2008 (entrée en vigueur en 2010), tout justiciable peut contester
                        la constitutionnalité d'une loi qui lui est appliquée via la <strong>Question Prioritaire de Constitutionnalité</strong>.
                    </p>

                    <div class="relative">
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-indigo-200 dark:bg-indigo-800 hidden md:block" />

                        <div class="space-y-6">
                            <div
                                v-for="s in qpcSteps"
                                :key="s.step"
                                class="relative md:pl-16"
                            >
                                <div class="hidden md:flex absolute left-0 top-1 w-12 h-12 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-lg shadow-md z-10">
                                    {{ s.step }}
                                </div>
                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="md:hidden inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-sm">
                                            {{ s.step }}
                                        </span>
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ s.title }}</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ s.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lien externe -->
                <div class="mt-10 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 p-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">🔗 Pour aller plus loin</h3>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a
                            href="https://www.conseil-constitutionnel.fr/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                        >
                            🏛️ Site officiel du Conseil constitutionnel →
                        </a>
                        <a
                            href="https://www.conseil-constitutionnel.fr/les-decisions"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                        >
                            📜 Décisions récentes →
                        </a>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-between">
                    <Link
                        :href="route('democratie.gouvernement')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        ← Le Gouvernement
                    </Link>
                    <Link
                        :href="route('democratie.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700"
                    >
                        Retour au Hub Démocratie →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
