<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    senateurs: Object,
    groupes: Array,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Sénateurs', current: true, icon: '🔴' },
];

const search = ref(props.filters?.search || '');
const groupe = ref(props.filters?.groupe || '');
const actifsOnly = ref(props.filters?.actifs_only === 'true');

const applyFilters = () => {
    router.get(route('admin.elus.senateurs.index'), {
        search: search.value || undefined,
        groupe: groupe.value || undefined,
        actifs_only: actifsOnly.value ? 'true' : undefined,
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
watch([groupe, actifsOnly], applyFilters);
</script>

<template>
    <Head title="Admin - Sénateurs" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-red-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <h1 class="text-3xl font-bold text-white">🔴 Sénateurs</h1>
                <p class="text-red-200 mt-2">{{ senateurs.total }} sénateurs</p>
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
                            placeholder="Rechercher un sénateur..."
                            class="flex-1 min-w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        />
                        <select
                            v-model="groupe"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        >
                            <option value="">Tous les groupes</option>
                            <option v-for="g in groupes" :key="g" :value="g">{{ g }}</option>
                        </select>
                        <label class="flex items-center gap-2">
                            <input v-model="actifsOnly" type="checkbox" class="rounded" />
                            <span class="text-sm">Actifs uniquement</span>
                        </label>
                    </div>
                </Card>

                <!-- Liste -->
                <div class="space-y-2">
                    <Card v-for="senateur in senateurs.data" :key="senateur.id" class="hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <img 
                                    v-if="senateur.photo_wikipedia_url"
                                    :src="senateur.photo_wikipedia_url"
                                    class="w-12 h-12 rounded-full object-cover"
                                />
                                <div v-else class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    👤
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                        {{ senateur.prenom }} {{ senateur.nom }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ senateur.circonscription }} • {{ senateur.groupe_politique_code || 'Sans groupe' }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="route('admin.elus.senateurs.edit', senateur.id)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                            >
                                ✏️ Modifier
                            </Link>
                        </div>
                    </Card>
                </div>

                <!-- Pagination -->
                <div v-if="senateurs.links" class="mt-6 flex justify-center gap-2">
                    <Link
                        v-for="link in senateurs.links"
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
