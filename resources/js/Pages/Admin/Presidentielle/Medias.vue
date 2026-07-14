<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ candidats: Array });

// copie éditable locale
const etat = reactive(Object.fromEntries(props.candidats.map((c) => [c.id, { ...c }])));

function enregistrer(id) {
    router.post(route('admin.presidentielle.medias.update'), etat[id], { preserveScroll: true });
}
</script>

<template>
    <Head title="Médias candidats — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Médias des candidats (portrait & bannière)</h2>
                <nav class="text-sm space-x-3">
                    <Link :href="route('admin.presidentielle.moderation')" class="text-blue-600 hover:underline">Tableau de bord</Link>
                </nav>
            </div>
        </template>

        <div class="max-w-4xl mx-auto p-6 space-y-6">
            <p class="text-sm text-gray-500">
                URL d'image (Wikimedia Commons CC-BY/CC-BY-SA, kit presse officiel avec autorisation, portrait officiel AN/Sénat).
                <strong>Crédit et licence obligatoires.</strong> Jamais de photo d'agence sans licence, jamais de captation TV.
            </p>

            <div v-for="c in candidats" :key="c.id" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <img v-if="etat[c.id].photo_url" :src="etat[c.id].photo_url" alt="" class="w-12 h-12 rounded-full object-cover" />
                    <span v-else class="w-12 h-12 rounded-full grid place-items-center text-white text-sm" :style="{ background: c.couleur_hex || '#64748b' }">{{ (c.nom||'').split(' ').map(w=>w[0]).slice(0,2).join('') }}</span>
                    <h3 class="font-semibold">{{ c.nom }}</h3>
                </div>

                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <fieldset class="space-y-2">
                        <legend class="font-medium">Portrait</legend>
                        <input v-model="etat[c.id].photo_url" type="url" placeholder="https://…" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                        <input v-model="etat[c.id].photo_credit" type="text" placeholder="Crédit (ex. Auteur / Wikimedia)" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                        <input v-model="etat[c.id].photo_licence" type="text" placeholder="Licence (ex. CC-BY-SA 4.0)" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                    </fieldset>
                    <fieldset class="space-y-2">
                        <legend class="font-medium">Bannière (hero)</legend>
                        <input v-model="etat[c.id].hero_banner_url" type="url" placeholder="https://…" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                        <input v-model="etat[c.id].hero_credit" type="text" placeholder="Crédit" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                        <input v-model="etat[c.id].hero_licence" type="text" placeholder="Licence" class="w-full rounded border-gray-300 dark:bg-gray-800 text-sm" />
                    </fieldset>
                </div>

                <div class="mt-3 text-right">
                    <button type="button" @click="enregistrer(c.id)" class="px-4 py-2 text-sm rounded bg-blue-600 text-white">Enregistrer</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
