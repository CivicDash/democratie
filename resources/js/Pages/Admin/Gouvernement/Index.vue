<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    gouvernements: Array,
    stats: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Gouvernements', current: true, icon: '🏛️' },
];
</script>

<template>
    <Head title="Admin - Gouvernements" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                            🏛️ Gestion des Gouvernements
                        </h1>
                        <p class="text-blue-200 mt-2">
                            Gérez la composition des gouvernements et l'historique
                        </p>
                    </div>
                    <Link 
                        :href="route('admin.gouvernement.create')"
                        class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition flex items-center gap-2"
                    >
                        ➕ Nouveau gouvernement
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.total }}</div>
                        <div class="text-blue-200 text-sm">Gouvernements</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-emerald-400">{{ stats.actif }}</div>
                        <div class="text-blue-200 text-sm">Actif</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.total_ministres }}</div>
                        <div class="text-blue-200 text-sm">Ministres actuels</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid gap-4">
                    <Card 
                        v-for="gouv in gouvernements" 
                        :key="gouv.id"
                        :class="[
                            'hover:shadow-lg transition',
                            gouv.actif ? 'border-l-4 border-l-emerald-500' : ''
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center text-3xl">
                                    🏛️
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                            {{ gouv.nom }}
                                        </h3>
                                        <span 
                                            v-if="gouv.actif"
                                            class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-full"
                                        >
                                            ACTIF
                                        </span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Premier ministre : <strong>{{ gouv.premier_ministre }}</strong>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500">
                                        {{ gouv.date_debut }} → {{ gouv.date_fin || 'En cours' }} • {{ gouv.duree }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ gouv.ministres_count }}
                                    </div>
                                    <div class="text-xs text-gray-500">ministres</div>
                                </div>
                                <div class="flex gap-2">
                                    <Link
                                        :href="route('admin.gouvernement.show', gouv.id)"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                                    >
                                        👁️ Voir
                                    </Link>
                                    <a
                                        :href="route('admin.gouvernement.export', gouv.id)"
                                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm"
                                        download
                                    >
                                        📥 JSON
                                    </a>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card v-if="gouvernements.length === 0" class="text-center py-12">
                        <div class="text-6xl mb-4">📭</div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                            Aucun gouvernement
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Créez le premier gouvernement ou importez-le depuis un fichier JSON.
                        </p>
                        <Link
                            :href="route('admin.gouvernement.create')"
                            class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                        >
                            ➕ Créer un gouvernement
                        </Link>
                    </Card>
                </div>

                <!-- Instructions d'import -->
                <Card class="mt-8 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700">
                    <h3 class="font-bold text-amber-800 dark:text-amber-200 mb-3">
                        💡 Import depuis JSON
                    </h3>
                    <p class="text-amber-700 dark:text-amber-300 text-sm mb-2">
                        Vous pouvez aussi importer un gouvernement depuis un fichier JSON :
                    </p>
                    <code class="block bg-amber-100 dark:bg-amber-900 p-3 rounded text-xs text-amber-800 dark:text-amber-200">
                        php artisan import:gouvernement-json --file=/chemin/vers/gouvernement.json
                    </code>
                    <p class="text-amber-600 dark:text-amber-400 text-xs mt-2">
                        Source officielle : 
                        <a href="https://www.info.gouv.fr/composition-du-gouvernement" target="_blank" class="underline">
                            info.gouv.fr/composition-du-gouvernement
                        </a>
                    </p>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
