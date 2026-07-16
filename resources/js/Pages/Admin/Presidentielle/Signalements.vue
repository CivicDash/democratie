<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';

const props = defineProps({
    signalements: Object,   // paginator
    types_incident: Object, // { slug: libellé }
    statut: String,
});

const filtres = ['nouveau', 'en_cours', 'resolu', 'rejete', 'tous'];
const libelleStatut = { nouveau: 'Nouveau', en_cours: 'En cours', resolu: 'Résolu', rejete: 'Rejeté' };

function filtrer(s) {
    router.get(route('admin.presidentielle.signalements'), { statut: s }, { preserveState: true, replace: true });
}

const notes = reactive({});
function agir(s, action) {
    if ((action === 'resoudre' || action === 'rejeter') && !notes[s.id]) {
        if (!confirm('Traiter ce signalement sans note de résolution ?')) return;
    }
    router.post(route('admin.presidentielle.signalements.action'),
        { id: s.id, action, note: notes[s.id] ?? null },
        { preserveScroll: true, onSuccess: () => { notes[s.id] = ''; } });
}
</script>

<template>
    <Head title="Signalements — présidentielle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Signalements citoyens</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-4">
            <div class="flex gap-2 flex-wrap">
                <button v-for="s in filtres" :key="s" @click="filtrer(s)" class="px-3 py-1 rounded-full text-sm border"
                    :class="statut === s ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">{{ s }}</button>
            </div>

            <div v-for="s in signalements.data" :key="s.id" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-2">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium">{{ s.type_libelle }}</span>
                        <span class="ml-2 text-xs text-gray-400">#{{ s.id }} · {{ s.created_at }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs"
                        :class="{
                            'bg-red-100 text-red-700': s.statut === 'nouveau',
                            'bg-amber-100 text-amber-700': s.statut === 'en_cours',
                            'bg-green-100 text-green-700': s.statut === 'resolu',
                            'bg-gray-100 text-gray-500': s.statut === 'rejete',
                        }">{{ libelleStatut[s.statut] ?? s.statut }}</span>
                </div>

                <p class="text-sm whitespace-pre-wrap">{{ s.description }}</p>

                <div class="text-xs text-gray-500 flex flex-wrap gap-x-4 gap-y-1">
                    <span v-if="s.candidat_slug">Candidat : <strong>{{ s.candidat_slug }}</strong></span>
                    <span v-if="s.theme_slug">Thème : <strong>{{ s.theme_slug }}</strong></span>
                    <a v-if="s.contexte_url" :href="s.contexte_url" target="_blank" rel="noopener" class="text-blue-600 hover:underline">page signalée ↗</a>
                    <span v-if="s.email">Contact : <a :href="`mailto:${s.email}`" class="text-blue-600 hover:underline">{{ s.email }}</a></span>
                    <span v-else class="italic">sans email</span>
                    <span v-if="s.moderator">Traité par : {{ s.moderator }}</span>
                </div>

                <p v-if="s.resolution_note" class="text-xs rounded bg-gray-50 dark:bg-gray-800 p-2">Note : {{ s.resolution_note }}</p>

                <div v-if="s.statut !== 'resolu' && s.statut !== 'rejete'" class="pt-1 flex items-center gap-2 flex-wrap">
                    <input v-model="notes[s.id]" placeholder="Note de traitement (optionnelle)"
                        class="flex-1 min-w-[12rem] rounded border-gray-300 dark:bg-gray-800 text-xs" />
                    <button v-if="s.statut === 'nouveau'" @click="agir(s, 'prendre_en_charge')" class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-700">Prendre en charge</button>
                    <button @click="agir(s, 'resoudre')" class="px-2 py-1 text-xs rounded bg-green-600 text-white">Résoudre</button>
                    <button @click="agir(s, 'rejeter')" class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">Rejeter</button>
                </div>
            </div>

            <p v-if="!signalements.data.length" class="p-6 text-center text-gray-400">Aucun signalement.</p>

            <div v-if="signalements.links" class="flex flex-wrap gap-1">
                <Link v-for="(l, i) in signalements.links" :key="i" :href="l.url ?? ''" v-html="l.label"
                    class="px-3 py-1 text-sm rounded border"
                    :class="[l.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300', !l.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
