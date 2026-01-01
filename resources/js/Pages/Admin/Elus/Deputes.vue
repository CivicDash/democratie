<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    deputes: Object,
    groupes: Array,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Députés', current: true, icon: '🔵' },
];

// Filtres
const search = ref(props.filters.search || '');
const groupe = ref(props.filters.groupe || '');
const enMandat = ref(props.filters.en_mandat || '');
const sansPhoto = ref(props.filters.sans_photo === 'true');

let debounceTimer = null;

const applyFilters = () => {
    router.get(route('admin.elus.deputes.index'), {
        search: search.value || undefined,
        groupe: groupe.value || undefined,
        en_mandat: enMandat.value || undefined,
        sans_photo: sansPhoto.value ? 'true' : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

watch([groupe, enMandat, sansPhoto], applyFilters);
</script>

<template>
    <Head title="Admin - Députés" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    🔵 Gestion des Députés
                </h1>
                <p class="text-blue-200 mt-2">
                    {{ deputes.total }} députés au total
                </p>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Filtres -->
                <Card class="mb-6">
                    <div class="grid md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Recherche
                            </label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Nom, prénom..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Groupe
                            </label>
                            <select
                                v-model="groupe"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Tous les groupes</option>
                                <option v-for="g in groupes" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Mandat
                            </label>
                            <select
                                v-model="enMandat"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Tous</option>
                                <option value="true">En mandat</option>
                                <option value="false">Anciens</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="sansPhoto"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Sans photo uniquement</span>
                            </label>
                        </div>
                    </div>
                </Card>

                <!-- Liste -->
                <Card>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Photo</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Député</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Groupe</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Circonscription</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Statut</th>
                                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="depute in deputes.data" 
                                    :key="depute.id"
                                    class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                                >
                                    <td class="py-3 px-4">
                                        <img 
                                            v-if="depute.photo_url"
                                            :src="depute.photo_url" 
                                            :alt="depute.nom"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div 
                                            v-else 
                                            class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center text-amber-600 dark:text-amber-400"
                                        >
                                            ?
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ depute.prenom }} {{ depute.nom }}
                                        </div>
                                        <div v-if="depute.profession" class="text-xs text-gray-500">
                                            {{ depute.profession }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs rounded">
                                            {{ depute.groupe_sigle || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ depute.circonscription || '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span 
                                            :class="[
                                                'px-2 py-1 text-xs rounded',
                                                depute.en_mandat 
                                                    ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                                            ]"
                                        >
                                            {{ depute.en_mandat ? 'En mandat' : 'Ancien' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <Link
                                            :href="route('admin.elus.deputes.edit', depute.id)"
                                            class="px-3 py-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded text-sm"
                                        >
                                            ✏️ Modifier
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination :links="deputes.links" />
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
