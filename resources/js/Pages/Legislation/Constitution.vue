<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Législatif', href: route('lois.index'), icon: '⚖️' },
    { label: 'La Constitution', current: true, icon: '📜' },
];

const activeTitle = ref(null);

const titres = [
    {
        id: 'souverainete',
        numero: 'Titre I',
        nom: 'De la souveraineté',
        articles: 'Art. 1 à 4',
        resume: 'La France est une République indivisible, laïque, démocratique et sociale. La souveraineté nationale appartient au peuple, qui l\'exerce par ses représentants et par voie de référendum.',
        color: 'indigo',
    },
    {
        id: 'president',
        numero: 'Titre II',
        nom: 'Le Président de la République',
        articles: 'Art. 5 à 19',
        resume: 'Chef de l\'État, garant de l\'indépendance nationale et du respect de la Constitution. Élu au suffrage universel direct pour 5 ans. Nomme le Premier Ministre, peut dissoudre l\'Assemblée, soumettre un projet au référendum.',
        color: 'blue',
    },
    {
        id: 'gouvernement',
        numero: 'Titre III',
        nom: 'Le Gouvernement',
        articles: 'Art. 20 à 23',
        resume: 'Le Gouvernement détermine et conduit la politique de la Nation. Il est responsable devant le Parlement. Le Premier Ministre dirige l\'action du Gouvernement.',
        color: 'amber',
    },
    {
        id: 'parlement',
        numero: 'Titre IV',
        nom: 'Le Parlement',
        articles: 'Art. 24 à 33',
        resume: 'L\'Assemblée nationale et le Sénat votent la loi, contrôlent l\'action du Gouvernement et évaluent les politiques publiques. Les députés sont élus au suffrage universel direct, les sénateurs au suffrage indirect.',
        color: 'emerald',
    },
    {
        id: 'rapports',
        numero: 'Titre V',
        nom: 'Des rapports entre le Parlement et le Gouvernement',
        articles: 'Art. 34 à 51',
        resume: 'Définit le domaine de la loi (art. 34), la procédure législative, l\'article 49.3, la motion de censure, et les commissions d\'enquête. Cœur de l\'équilibre institutionnel de la Vème République.',
        color: 'rose',
    },
    {
        id: 'conseil-constitutionnel',
        numero: 'Titre VII',
        nom: 'Le Conseil Constitutionnel',
        articles: 'Art. 56 à 63',
        resume: '9 membres nommés pour 9 ans. Contrôle la constitutionnalité des lois, veille à la régularité des élections. Depuis 2010, les citoyens peuvent le saisir via la QPC.',
        color: 'purple',
    },
    {
        id: 'revision',
        numero: 'Titre XVI',
        nom: 'De la révision',
        articles: 'Art. 89',
        resume: 'L\'initiative de la révision appartient au Président (sur proposition du Premier Ministre) et aux parlementaires. Le projet doit être adopté par les deux assemblées en termes identiques, puis approuvé par référendum ou par le Congrès (à 3/5).',
        color: 'indigo',
    },
];

const reperes = [
    { date: '4 octobre 1958', event: 'Promulgation de la Constitution de la Vème République' },
    { date: '1962', event: 'Élection du Président au suffrage universel direct' },
    { date: '2000', event: 'Passage du septennat au quinquennat' },
    { date: '2008', event: 'Réforme constitutionnelle : création de la QPC, modernisation du Parlement' },
    { date: '2024', event: 'Liberté garantie de recourir à l\'IVG inscrite dans la Constitution' },
];

const colorMap = {
    indigo: 'border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20',
    blue: 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20',
    amber: 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20',
    emerald: 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20',
    rose: 'border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20',
    purple: 'border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20',
};
</script>

<template>
    <Head title="La Constitution - Législatif" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📜 La Constitution de la Vème République</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Adoptée le 4 octobre 1958, la Constitution est la norme suprême de l'ordre juridique français.
                        Elle organise les pouvoirs publics et garantit les droits fondamentaux des citoyens.
                    </p>
                </div>

                <!-- Chiffres -->
                <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">16</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Titres</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">89</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Articles</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">25</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Révisions</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">1958</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Promulguée</p>
                    </div>
                </div>

                <!-- Titres principaux -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🏛️ Les grands Titres</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        La Constitution est organisée en 16 titres. Voici les plus importants pour comprendre le fonctionnement de la République.
                    </p>

                    <div class="space-y-3">
                        <div
                            v-for="t in titres"
                            :key="t.id"
                            class="border rounded-xl p-5 cursor-pointer transition-all hover:shadow-md"
                            :class="[colorMap[t.color], activeTitle === t.id ? 'ring-2 ring-indigo-500' : '']"
                            @click="activeTitle = activeTitle === t.id ? null : t.id"
                        >
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 rounded-lg text-xs font-bold bg-white/60 dark:bg-black/20 text-gray-700 dark:text-gray-300">
                                    {{ t.numero }}
                                </span>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ t.nom }}</h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">{{ t.articles }}</span>
                            </div>

                            <div v-if="activeTitle === t.id" class="mt-4 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ t.resume }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Repères chronologiques -->
                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📅 Repères chronologiques</h2>

                    <div class="relative">
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-indigo-200 dark:bg-indigo-800 hidden md:block" />

                        <div class="space-y-4">
                            <div
                                v-for="(r, i) in reperes"
                                :key="i"
                                class="relative md:pl-16"
                            >
                                <div class="hidden md:flex absolute left-0 top-1 w-12 h-12 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-xs shadow-md z-10">
                                    {{ r.date.split(' ')[0] }}
                                </div>
                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ r.date }}</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ r.event }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc de constitutionnalité -->
                <div class="mt-10 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 p-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3">📚 Le bloc de constitutionnalité</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        La Constitution ne se limite pas au texte de 1958. Le Conseil constitutionnel veille au respect de l'ensemble du "bloc de constitutionnalité" :
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 mt-0.5">•</span>
                            <span>La Constitution du 4 octobre 1958</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 mt-0.5">•</span>
                            <span>La Déclaration des droits de l'homme et du citoyen de 1789</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 mt-0.5">•</span>
                            <span>Le Préambule de la Constitution de 1946</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 mt-0.5">•</span>
                            <span>La Charte de l'environnement de 2004</span>
                        </div>
                    </div>
                </div>

                <!-- Liens -->
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a
                        href="https://www.legifrance.gouv.fr/loda/id/JORFTEXT000000571356"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                    >
                        📜 Texte intégral sur Légifrance →
                    </a>
                    <Link
                        :href="route('democratie.conseil-constitutionnel')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                    >
                        ⚖️ Le Conseil Constitutionnel →
                    </Link>
                </div>

                <!-- Navigation -->
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-between">
                    <Link
                        :href="route('lois.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        ← Lois en cours
                    </Link>
                    <Link
                        :href="route('democratie.parcours-loi')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700"
                    >
                        Parcours d'une Loi →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
