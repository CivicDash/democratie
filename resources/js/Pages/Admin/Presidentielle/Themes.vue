<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import GrapheThemes from '@/Components/Admin/GrapheThemes.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    themes: Array,
    total: Number,
    total_en_file: Number,
    perimetre: String,
    candidat_id: Number,
    candidats: Array,
});

const PERIMETRES = [
    { clef: 'toutes', libelle: 'Toutes les mesures' },
    { clef: 'validees', libelle: 'Validées' },
    { clef: 'publiees', libelle: 'Publiées' },
];

function filtrer(champs) {
    router.get(route('admin.presidentielle.themes'), {
        perimetre: props.perimetre,
        candidat: props.candidat_id || undefined,
        ...champs,
    }, { preserveState: true, replace: true });
}

const tries = computed(() => [...props.themes].sort((a, b) => b.total - a.total));

const absents = computed(() => tries.value.filter((t) => t.total === 0));

// Un thème est « mono-porté » quand un seul candidat en fournit plus de la moitié. Le
// seuil est arbitraire et assumé : il ne sert pas à trancher, seulement à attirer l'œil
// sur les barres qu'il ne faut pas lire comme une mesure du débat.
const monoPortes = computed(() => tries.value.filter((t) => t.total >= 10 && t.concentration > 50));

const monoCandidat = computed(() => Boolean(props.candidat_id));

const nomCandidat = computed(
    () => props.candidats.find((c) => c.id === props.candidat_id)?.nom ?? null,
);
</script>

<template>
    <Head title="Répartition thématique — présidentielle 2027" />
    <AuthenticatedLayout>
        <PresidentielleNav />

        <div class="max-w-6xl mx-auto p-4 space-y-6">
            <div>
                <h1 class="text-xl font-bold">Répartition thématique de la campagne</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Nombre de mesures enregistrées par thème. Ce décompte mesure ce que contient
                    la base, qui dépend des prises de parole déjà dépouillées — ce n'est pas une
                    mesure directe de ce dont parle la campagne. Les deux colonnes
                    <em>candidats</em> et <em>concentration</em> servent à faire la différence.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-2">
                    <button v-for="p in PERIMETRES" :key="p.clef"
                            @click="filtrer({ perimetre: p.clef })"
                            :class="['px-3 py-1 text-sm rounded',
                                     perimetre === p.clef ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700']">
                        {{ p.libelle }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label for="filtre-candidat" class="text-sm text-gray-600 dark:text-gray-300">Candidat</label>
                    <select id="filtre-candidat"
                            :value="candidat_id || ''"
                            @change="filtrer({ candidat: $event.target.value || undefined })"
                            class="text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                        <option value="">Tous</option>
                        <option v-for="c in candidats" :key="c.id" :value="c.id">{{ c.nom }}</option>
                    </select>
                </div>
            </div>

            <EmptyState v-if="total === 0"
                        icon="📊"
                        title="Aucune mesure dans ce périmètre"
                        :description="perimetre === 'publiees'
                            ? 'Aucune mesure n\'est encore publiée. Le graphe se remplira à la première publication.'
                            : 'Rien à compter ici. Élargissez le périmètre ou changez de candidat.'" />

            <template v-else>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Mesures comptées</p>
                        <p class="text-2xl font-bold">{{ total.toLocaleString('fr-FR') }}</p>
                        <p v-if="nomCandidat" class="text-xs text-gray-500">{{ nomCandidat }}</p>
                    </div>
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Thème dominant</p>
                        <p class="text-lg font-bold leading-tight">{{ tries[0]?.nom }}</p>
                        <p class="text-xs text-gray-500">{{ tries[0]?.part }} % des mesures</p>
                    </div>
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Thèmes sans mesure</p>
                        <p class="text-2xl font-bold">{{ absents.length }}</p>
                        <p class="text-xs text-gray-500">sur {{ themes.length }}</p>
                    </div>
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">En file d'ingestion</p>
                        <p class="text-2xl font-bold text-amber-600">{{ total_en_file.toLocaleString('fr-FR') }}</p>
                        <p class="text-xs text-gray-500">propositions pas encore rattachées</p>
                    </div>
                </div>

                <div class="rounded border p-4 dark:border-gray-700">
                    <GrapheThemes :themes="themes" :mono-candidat="monoCandidat" />
                </div>

                <div v-if="!monoCandidat && monoPortes.length"
                     class="rounded border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4">
                    <p class="font-medium text-amber-800 dark:text-amber-200">
                        {{ monoPortes.length }} thème{{ monoPortes.length > 1 ? 's' : '' }}
                        à lire avec précaution
                    </p>
                    <p class="text-sm mt-1 text-amber-700 dark:text-amber-300">
                        Plus de la moitié des mesures y viennent d'un seul candidat. Le volume dit
                        alors surtout ce qui a été dépouillé, pas ce qui est débattu :
                        <span v-for="(t, i) in monoPortes" :key="t.id">
                            <strong>{{ t.nom }}</strong> ({{ t.concentration }} % — {{ t.tete_nom }}){{ i < monoPortes.length - 1 ? ', ' : '.' }}
                        </span>
                    </p>
                </div>

                <div class="rounded border dark:border-gray-700 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium">Thème</th>
                                <th class="px-3 py-2 font-medium text-right">Mesures</th>
                                <th class="px-3 py-2 font-medium text-right">Part</th>
                                <th class="px-3 py-2 font-medium text-right">Candidats</th>
                                <th class="px-3 py-2 font-medium">Plus représenté</th>
                                <th class="px-3 py-2 font-medium text-right">Concentration</th>
                                <th class="px-3 py-2 font-medium text-right">En file</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tries" :key="t.id"
                                class="border-t dark:border-gray-700"
                                :class="t.total === 0 ? 'text-gray-400 dark:text-gray-500' : ''">
                                <td class="px-3 py-2">{{ t.nom }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ t.total }}</td>
                                <td class="px-3 py-2 text-right">{{ t.part }} %</td>
                                <td class="px-3 py-2 text-right">{{ t.candidats }}</td>
                                <td class="px-3 py-2">{{ t.tete_nom ?? '—' }}</td>
                                <td class="px-3 py-2 text-right"
                                    :class="t.total >= 10 && t.concentration > 50 ? 'text-amber-600 font-medium' : ''">
                                    {{ t.total > 0 ? t.concentration + ' %' : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span :class="t.en_file > 0 ? 'text-amber-600' : 'text-gray-400'">
                                        {{ t.en_file }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    « Concentration » : part des mesures du thème revenant au candidat le plus
                    représenté. « En file » : propositions détectées, pas encore rattachées à une
                    mesure — elles ne sont comptées dans aucune barre.
                </p>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
