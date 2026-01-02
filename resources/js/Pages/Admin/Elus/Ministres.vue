<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    ministres: Object,
    partis: Array,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Élus', href: route('admin.elus.index'), icon: '👥' },
    { label: 'Ministres', current: true, icon: '👔' },
];

const search = ref(props.filters?.search || '');
const parti = ref(props.filters?.parti || '');
const actifOnly = ref(props.filters?.actif === 'true' || props.filters?.actif === true);

const applyFilters = () => {
    router.get(route('admin.elus.ministres.index'), {
        search: search.value || undefined,
        parti: parti.value || undefined,
        actif: actifOnly.value ? 'true' : undefined,
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
watch([parti, actifOnly], applyFilters);
</script>

<template>
    <Head title="Admin - Ministres" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                <h1 class="text-3xl font-bold text-white">👔 Ministres</h1>
                <p class="text-purple-200 mt-2">{{ ministres.total }} personnes politiques</p>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Filtres -->
                <Card class="mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher un ministre..."
                            class="flex-1 min-w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        />
                        <select
                            v-model="parti"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                        >
                            <option value="">Tous les partis</option>
                            <option v-for="p in partis" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="actifOnly"
                                type="checkbox"
                                class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">En fonction uniquement</span>
                        </label>
                    </div>
                </Card>

                <!-- Liste -->
                <div class="space-y-2">
                    <Card v-for="ministre in ministres.data" :key="ministre.id" class="hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <img 
                                    v-if="ministre.photo_url"
                                    :src="ministre.photo_url"
                                    :alt="ministre.nom_complet"
                                    class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                                />
                                <div v-else class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center text-xl">
                                    {{ ministre.civilite === 'Mme' ? '👩' : '👨' }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ ministre.nom_complet }}
                                        </h3>
                                        <span 
                                            v-if="ministre.actif"
                                            class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400 text-xs rounded-full"
                                        >
                                            En fonction
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        {{ ministre.dernier_poste || 'Aucun poste' }}
                                        <span v-if="ministre.parti_politique" class="ml-2 text-purple-600 dark:text-purple-400">
                                            • {{ ministre.parti_politique }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ ministre.nb_postes }} poste(s) au gouvernement
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="ministre.slug"
                                    :href="route('gouvernement.personne', ministre.slug)"
                                    class="px-3 py-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm"
                                    target="_blank"
                                >
                                    👁️ Voir
                                </Link>
                                <Link
                                    :href="route('admin.elus.ministres.edit', ministre.id)"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm"
                                >
                                    ✏️ Modifier
                                </Link>
                            </div>
                        </div>
                    </Card>

                    <div v-if="ministres.data.length === 0" class="text-center py-12 text-gray-500">
                        Aucun ministre trouvé avec ces critères
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="ministres.links && ministres.data.length > 0" class="mt-6 flex justify-center gap-2">
                    <Link
                        v-for="link in ministres.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-1 rounded',
                            link.active ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
