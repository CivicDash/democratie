<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import GrapheAudience from '@/Components/Admin/GrapheAudience.vue';

const props = defineProps({
    par_jour: Array, par_page: Array, jours: Number, totaux: Object, actif: Boolean,
});

function periode(n) {
    router.get(route('admin.presidentielle.audience'), { jours: n }, { preserveState: true, replace: true });
}

const part = (v, t) => (t > 0 ? Math.round((v / t) * 100) : 0);

const dateLongue = (jour) => (jour
    ? new Date(jour + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })
    : '—');
</script>

<template>
    <Head title="Audience — objectif2027.fr" />
    <AuthenticatedLayout>
        <PresidentielleNav />

        <div class="max-w-6xl mx-auto p-4 space-y-6">
            <div>
                <h1 class="text-xl font-bold">Audience d'objectif2027.fr</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Comptage à partir du journal du serveur. Aucun cookie, aucun script chez le
                    visiteur, aucune adresse IP conservée — seuls des agrégats quotidiens sont stockés.
                </p>
            </div>

            <div v-if="!actif" class="rounded border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4">
                <p class="font-medium text-amber-800 dark:text-amber-200">Aucune donnée pour l'instant.</p>
                <p class="text-sm mt-1 text-amber-700 dark:text-amber-300">
                    La journalisation du vhost doit être activée, puis <code>php artisan audience:agreger</code>
                    exécuté une première fois. Marche à suivre : <code>docs/audience.md</code>.
                </p>
            </div>

            <template v-else>
                <div class="flex gap-2">
                    <button v-for="n in [7, 30, 90]" :key="n" @click="periode(n)"
                            :class="['px-3 py-1 text-sm rounded', jours === n ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700']">
                        {{ n }} jours
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Vues humaines</p>
                        <p class="text-2xl font-bold">{{ totaux.humains.toLocaleString('fr-FR') }}</p>
                    </div>
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Vues de robots</p>
                        <p class="text-2xl font-bold text-gray-400">{{ totaux.bots.toLocaleString('fr-FR') }}</p>
                        <p class="text-xs text-gray-500">{{ part(totaux.bots, totaux.humains + totaux.bots) }} % du trafic</p>
                        <p class="text-xs text-gray-400">Un robot qui se déclare navigateur reste compté comme humain.</p>
                    </div>
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Meilleure journée</p>
                        <p class="text-2xl font-bold">
                            {{ Number(totaux.meilleur_jour?.humains ?? 0).toLocaleString('fr-FR') }}
                        </p>
                        <p class="text-xs text-gray-500">
                            le {{ dateLongue(totaux.meilleur_jour?.jour) }}
                        </p>
                    </div>
                </div>

                <section>
                    <h2 class="font-semibold mb-2">Par jour</h2>
                    <GrapheAudience :par-jour="par_jour" />
                </section>

                <section>
                    <h2 class="font-semibold mb-2">Pages les plus lues</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                        <thead class="text-left text-gray-500 border-b dark:border-gray-700">
                            <tr><th class="p-2">Page</th><th class="p-2 w-28">Humains</th><th class="p-2 w-28">Robots</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in par_page" :key="p.chemin" class="border-t border-gray-100 dark:border-gray-800">
                                <td class="p-2"><a :href="`https://objectif2027.fr${p.chemin}`" target="_blank" rel="noopener" class="hover:underline">{{ p.chemin }}</a></td>
                                <td class="p-2 font-medium">{{ Number(p.humains).toLocaleString('fr-FR') }}</td>
                                <td class="p-2 text-gray-400">{{ Number(p.bots).toLocaleString('fr-FR') }}</td>
                            </tr>
                            <tr v-if="!par_page.length"><td colspan="3" class="p-6 text-center text-gray-400">Aucune page mesurée.</td></tr>
                        </tbody>
                    </table>
                    </div>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
