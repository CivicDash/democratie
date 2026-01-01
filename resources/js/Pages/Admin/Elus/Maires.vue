<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    maires: Object,
    departements: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Maires', current: true, icon: '🏛️' },
];

const search = ref(props.filters?.search || '');
const departement = ref(props.filters?.departement || '');

const applyFilters = () => {
    router.get(route('admin.elus.maires.index'), {
        search: search.value || undefined,
        departement: departement.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

let debounceTimer;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 500);
});
watch(departement, applyFilters);
</script>

<template>
    <Head title="Admin - Maires" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-green-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <h1 class="text-3xl font-bold text-white">🏛️ Maires</h1>
                <p class="text-green-200 mt-2">{{ maires.total }} maires</p>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Filtres -->
                <Card class="mb-6">
                    <div class="flex flex-wrap gap-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher un maire ou une commune..."
                            class="flex-1 min-w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        />
                        <select
                            v-model="departement"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        >
                            <option value="">Tous les départements</option>
                            <option v-for="(nom, code) in departements" :key="code" :value="code">
                                {{ code }} - {{ nom }}
                            </option>
                        </select>
                    </div>
                </Card>

                <!-- Liste -->
                <div class="space-y-2">
                    <Card v-for="maire in maires.data" :key="maire.id" class="hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center text-xl">
                                    🏛️
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                        {{ maire.prenom }} {{ maire.nom }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ maire.nom_commune }} ({{ maire.code_departement }})
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="route('admin.elus.maires.edit', maire.id)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                            >
                                ✏️ Modifier
                            </Link>
                        </div>
                    </Card>
                </div>

                <!-- Pagination -->
                <div v-if="maires.links" class="mt-6 flex justify-center gap-2">
                    <Link
                        v-for="link in maires.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-1 rounded',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
