<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    stats: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Gestion des Élus', current: true, icon: '👥' },
];

const categories = [
    {
        key: 'deputes',
        icon: '🔵',
        title: 'Députés',
        description: 'Assemblée Nationale',
        route: 'admin.elus.deputes.index',
        color: 'blue',
    },
    {
        key: 'senateurs',
        icon: '🔴',
        title: 'Sénateurs',
        description: 'Sénat',
        route: 'admin.elus.senateurs.index',
        color: 'red',
    },
    {
        key: 'maires',
        icon: '🏛️',
        title: 'Maires',
        description: 'Communes',
        route: 'admin.elus.maires.index',
        color: 'green',
    },
    {
        key: 'ministres',
        icon: '👔',
        title: 'Ministres',
        description: 'Gouvernement',
        route: 'admin.elus.ministres.index',
        color: 'purple',
    },
];
</script>

<template>
    <Head title="Admin - Gestion des Élus" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    👥 Gestion des Élus
                </h1>
                <p class="text-indigo-200 mt-2">
                    Enrichissez et corrigez les fiches des représentants
                </p>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Catégories -->
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Link
                        v-for="cat in categories"
                        :key="cat.key"
                        :href="route(cat.route)"
                        class="block"
                    >
                        <Card class="hover:shadow-lg transition hover:-translate-y-1 h-full">
                            <div class="text-center">
                                <div class="text-5xl mb-4">{{ cat.icon }}</div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ cat.title }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    {{ cat.description }}
                                </p>
                                <div v-if="stats[cat.key]" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Total</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ stats[cat.key].total?.toLocaleString() }}
                                        </span>
                                    </div>
                                    <div v-if="stats[cat.key].actifs !== undefined" class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Actifs</span>
                                        <span class="font-bold text-emerald-600">
                                            {{ stats[cat.key].actifs?.toLocaleString() }}
                                        </span>
                                    </div>
                                    <div v-if="stats[cat.key].sans_photo !== undefined" class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Sans photo</span>
                                        <span class="font-bold text-amber-600">
                                            {{ stats[cat.key].sans_photo?.toLocaleString() }}
                                        </span>
                                    </div>
                                    <div v-if="stats[cat.key].sans_email !== undefined" class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Sans email</span>
                                        <span class="font-bold text-amber-600">
                                            {{ stats[cat.key].sans_email?.toLocaleString() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </Link>
                </div>

                <!-- Actions rapides -->
                <Card>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        ⚡ Actions rapides
                    </h2>
                    <div class="grid md:grid-cols-3 gap-4">
                        <Link
                            :href="route('admin.elus.deputes.index', { sans_photo: true })"
                            class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📷</span>
                                <div>
                                    <h3 class="font-semibold text-amber-800 dark:text-amber-200">
                                        Députés sans photo
                                    </h3>
                                    <p class="text-sm text-amber-600 dark:text-amber-400">
                                        {{ stats.deputes?.sans_photo }} fiches à compléter
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('admin.gouvernement.index')"
                            class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🏛️</span>
                                <div>
                                    <h3 class="font-semibold text-blue-800 dark:text-blue-200">
                                        Gouvernement
                                    </h3>
                                    <p class="text-sm text-blue-600 dark:text-blue-400">
                                        {{ stats.ministres?.actifs }} ministres actifs
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('admin.domaines.index')"
                            class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🏷️</span>
                                <div>
                                    <h3 class="font-semibold text-purple-800 dark:text-purple-200">
                                        Catégoriser les postes
                                    </h3>
                                    <p class="text-sm text-purple-600 dark:text-purple-400">
                                        Associer fonctions aux domaines
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <a
                            href="https://www.info.gouv.fr/composition-du-gouvernement"
                            target="_blank"
                            class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🔗</span>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                                        Source officielle
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        info.gouv.fr
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                </Card>

                <!-- Commandes d'import -->
                <Card class="mt-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        🔄 Commandes d'enrichissement
                    </h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <code class="text-blue-600 dark:text-blue-400">php artisan enrich:deputes</code>
                            <p class="text-gray-500 mt-1">Enrichit les fiches depuis NosDeputes.fr</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <code class="text-blue-600 dark:text-blue-400">php artisan enrich:senateurs</code>
                            <p class="text-gray-500 mt-1">Enrichit les fiches depuis NosSenateurs.fr</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <code class="text-blue-600 dark:text-blue-400">php artisan import:deputes-wikipedia</code>
                            <p class="text-gray-500 mt-1">Ajoute les biographies Wikipedia</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <code class="text-blue-600 dark:text-blue-400">php artisan import:gouvernement-json</code>
                            <p class="text-gray-500 mt-1">Importe le gouvernement depuis JSON</p>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
