<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    gouvernement: { type: Object, default: null },
    postesParType: { type: Object, default: () => ({}) },
    stats: { type: Object, default: null },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Le Gouvernement', current: true, icon: '🏛️' },
];

const activePower = ref(null);

const pouvoirs = [
    { id: 'executif', icon: '🏛️', title: 'Pouvoir Exécutif', color: 'blue', institutions: ['Président de la République', 'Premier Ministre', 'Gouvernement'], role: 'Appliquer les lois et conduire la politique de la Nation. Le Président nomme le Premier Ministre, préside le Conseil des ministres, est chef des armées.' },
    { id: 'legislatif', icon: '📜', title: 'Pouvoir Législatif', color: 'indigo', institutions: ['Assemblée nationale', 'Sénat'], role: 'Voter les lois et contrôler l\'action du Gouvernement. Les deux chambres forment le Parlement. L\'Assemblée a le "dernier mot" en cas de désaccord.' },
    { id: 'judiciaire', icon: '⚖️', title: 'Pouvoir Judiciaire', color: 'amber', institutions: ['Tribunaux', 'Cours d\'appel', 'Cour de cassation', 'Conseil d\'État'], role: 'Juger les litiges et sanctionner les infractions. Indépendant des deux autres pouvoirs. Le Conseil constitutionnel vérifie la conformité des lois.' },
];

const pouvoirsPresident = [
    {
        icon: '👤',
        title: 'Nomination du Premier Ministre',
        description: 'Le Président nomme le Premier Ministre et, sur proposition de celui-ci, les autres membres du Gouvernement. Il n\'a pas besoin de l\'accord du Parlement, mais le Gouvernement doit avoir sa confiance.',
    },
    {
        icon: '💣',
        title: 'Dissolution de l\'Assemblée',
        description: 'Le Président peut dissoudre l\'Assemblée nationale (art. 12), déclenchant de nouvelles élections législatives. Il ne peut pas dissoudre le Sénat. Délai minimum de 1 an entre deux dissolutions.',
    },
    {
        icon: '⚡',
        title: 'Article 49.3',
        description: 'Le Premier Ministre (pas le Président) peut engager la responsabilité du Gouvernement sur un texte (art. 49 al. 3). Le texte est adopté sans vote, sauf si une motion de censure est votée dans les 24h. Limité à 1 utilisation par session (sauf lois de finances).',
    },
    {
        icon: '🗳️',
        title: 'Référendum',
        description: 'Le Président peut soumettre un projet de loi au référendum (art. 11) sur proposition du Gouvernement ou du Parlement. Porte sur l\'organisation des pouvoirs publics, les réformes économiques/sociales, ou la ratification de traités.',
    },
    {
        icon: '🌍',
        title: 'Chef des armées',
        description: 'Le Président est le chef des armées (art. 15). Il préside les conseils de défense et décide de l\'engagement des forces nucléaires. L\'envoi de troupes à l\'étranger doit être autorisé par le Parlement au-delà de 4 mois.',
    },
    {
        icon: '🛡️',
        title: 'Pouvoirs exceptionnels (art. 16)',
        description: 'En cas de menace grave et immédiate, le Président peut prendre les "pleins pouvoirs" temporairement. Utilisé une seule fois (1961, guerre d\'Algérie). Le Conseil constitutionnel est consulté et peut vérifier si les conditions sont toujours réunies après 30 jours.',
    },
];

const typeLabels = {
    premier_ministre: { label: 'Premier Ministre', icon: '👔' },
    ministre: { label: 'Ministres', icon: '🏛️' },
    ministre_delegue: { label: 'Ministres délégués', icon: '📋' },
    secretaire_etat: { label: 'Secrétaires d\'État', icon: '📎' },
};

const colorClasses = {
    blue: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    indigo: 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800',
    amber: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
};
</script>

<template>
    <Head title="Le Gouvernement - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🏛️ Le Gouvernement</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Président, Premier Ministre, ministères : comprendre la structure et les pouvoirs de l'exécutif français.
                    </p>
                </div>

                <!-- Séparation des pouvoirs -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">⚖️ La Séparation des Pouvoirs</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Depuis Montesquieu, la démocratie repose sur la séparation de trois pouvoirs indépendants, pour éviter la concentration du pouvoir.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            v-for="p in pouvoirs"
                            :key="p.id"
                            class="border rounded-xl p-5 cursor-pointer transition-all hover:shadow-md"
                            :class="[colorClasses[p.color], activePower === p.id ? 'ring-2 ring-indigo-500' : '']"
                            @click="activePower = activePower === p.id ? null : p.id"
                        >
                            <div class="text-3xl mb-3">{{ p.icon }}</div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ p.title }}</h3>

                            <div class="mt-3 flex flex-wrap gap-1">
                                <span
                                    v-for="inst in p.institutions"
                                    :key="inst"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white/60 dark:bg-black/20 text-gray-700 dark:text-gray-300"
                                >
                                    {{ inst }}
                                </span>
                            </div>

                            <div v-if="activePower === p.id" class="mt-4 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ p.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gouvernement actuel -->
                <div v-if="gouvernement" class="mt-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">🇫🇷 Gouvernement actuel</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ gouvernement.nom }} · Depuis le {{ gouvernement.date_debut }}
                            </p>
                        </div>
                        <div v-if="stats" class="text-right">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                            <div class="text-xs text-gray-500">membres</div>
                        </div>
                    </div>

                    <!-- Organigramme simplifié -->
                    <div class="space-y-6">
                        <!-- Président -->
                        <div class="text-center">
                            <div class="inline-flex flex-col items-center bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-6 py-4">
                                <span class="text-2xl">🇫🇷</span>
                                <span class="font-bold text-blue-900 dark:text-blue-200 mt-1">Président de la République</span>
                                <span class="text-sm text-blue-700 dark:text-blue-300">{{ gouvernement.president }}</span>
                            </div>
                            <div class="h-6 w-0.5 bg-gray-300 dark:bg-gray-600 mx-auto"></div>
                        </div>

                        <!-- Premier Ministre -->
                        <div class="text-center" v-if="postesParType.premier_ministre?.length">
                            <div class="inline-flex flex-col items-center bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl px-6 py-4">
                                <span class="text-2xl">👔</span>
                                <span class="font-bold text-indigo-900 dark:text-indigo-200 mt-1">Premier Ministre</span>
                                <span class="text-sm text-indigo-700 dark:text-indigo-300">{{ postesParType.premier_ministre[0].nom }}</span>
                            </div>
                            <div class="h-6 w-0.5 bg-gray-300 dark:bg-gray-600 mx-auto"></div>
                        </div>

                        <!-- Ministres par type -->
                        <div v-for="(type, key) in { ministre: 'ministre', ministre_delegue: 'ministre_delegue', secretaire_etat: 'secretaire_etat' }" :key="key">
                            <div v-if="postesParType[type]?.length" class="mb-4">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <span>{{ typeLabels[type]?.icon }}</span>
                                    {{ typeLabels[type]?.label }} ({{ postesParType[type].length }})
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    <div
                                        v-for="poste in postesParType[type]"
                                        :key="poste.id"
                                        class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2"
                                    >
                                        <img
                                            v-if="poste.photo_url"
                                            :src="poste.photo_url"
                                            :alt="poste.nom"
                                            class="w-8 h-8 rounded-full object-cover shrink-0"
                                        />
                                        <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs text-gray-500 shrink-0">
                                            {{ poste.nom?.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ poste.nom }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ poste.fonction }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-12 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">Données du gouvernement actuel non disponibles.</p>
                    <Link :href="route('gouvernement.index')" class="mt-3 inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        Voir la page Gouvernement
                    </Link>
                </div>

                <!-- Pouvoirs du Président -->
                <div class="mt-12">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">👑 Les Pouvoirs du Président</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div
                            v-for="pouvoir in pouvoirsPresident"
                            :key="pouvoir.title"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5"
                        >
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-2xl">{{ pouvoir.icon }}</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ pouvoir.title }}</h3>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ pouvoir.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Links -->
                <div class="mt-8 flex flex-wrap gap-3 justify-center pb-8">
                    <Link :href="route('gouvernement.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Composition du Gouvernement
                    </Link>
                    <Link :href="route('gouvernement.ministeres')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏢 Ministères
                    </Link>
                    <Link :href="route('gouvernement.statistiques')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        📊 Statistiques
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
