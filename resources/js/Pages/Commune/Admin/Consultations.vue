<script setup>
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({ ville: Object, consultations: Object });

const fermer = (slug, codeInsee) => {
    if (!confirm('Fermer cette consultation ?')) return;
    router.post(route('commune.admin.consultations.fermer', [codeInsee, slug]), {}, { preserveScroll: true });
};

const supprimer = (slug, codeInsee) => {
    if (!confirm('Supprimer cette consultation ?')) return;
    router.delete(route('commune.admin.consultations.destroy', [codeInsee, slug]), { preserveScroll: true });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Consultations citoyennes</h2>
            <Link :href="route('commune.admin.consultations.create', ville.code_insee)" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                Nouvelle consultation
            </Link>
        </div>

        <div class="space-y-3">
            <div v-for="c in consultations.data" :key="c.id" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ c.titre }}</h3>
                            <span v-if="c.publie && !c.fermee" class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-2 py-0.5 rounded-full">Active</span>
                            <span v-else-if="c.fermee" class="text-xs bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-full">Fermee</span>
                            <span v-else class="text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-2 py-0.5 rounded-full">Brouillon</span>
                        </div>
                        <div class="text-sm text-slate-500">{{ c.votes_count }} votes - {{ c.options_count }} options</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="!c.fermee && c.publie" @click="fermer(c.slug, ville.code_insee)" class="px-3 py-1.5 text-xs border border-amber-300 text-amber-700 rounded-lg hover:bg-amber-50 transition-colors">
                            Fermer
                        </button>
                        <button @click="supprimer(c.slug, ville.code_insee)" class="px-3 py-1.5 text-xs border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!consultations.data?.length" class="text-center py-12 text-slate-400">
            Aucune consultation. Creez-en une pour recueillir l'avis des citoyens.
        </div>
        </div>
    </AuthenticatedLayout>
</template>
