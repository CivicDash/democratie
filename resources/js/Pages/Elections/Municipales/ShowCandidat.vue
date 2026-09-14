<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    candidat: Object,
    liste: Object,
});

const reseaux = computed(() => Object.entries(props.candidat.reseaux_sociaux ?? {})
    .filter(([, url]) => Boolean(url))
    .map(([nom, url]) => ({ nom, url })));

const sections = computed(() => [
    { titre: 'Biographie', texte: props.candidat.biographie },
    { titre: 'Parcours', texte: props.candidat.parcours },
    { titre: 'Engagements', texte: props.candidat.engagements },
].filter((s) => Boolean(s.texte)));
</script>

<template>
    <Head :title="candidat.nom_complet" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 py-8">
            <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('elections.municipales.index')" class="hover:text-blue-600">Municipales</Link>
                <span aria-hidden="true">→</span>
                <Link :href="route('elections.municipales.liste', liste.uuid)" class="hover:text-blue-600">
                    {{ liste.nom_liste }}
                </Link>
                <span aria-hidden="true">→</span>
                <span class="text-gray-900 dark:text-white">{{ candidat.nom_complet }}</span>
            </nav>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <img v-if="candidat.photo_url"
                         :src="candidat.photo_url"
                         :alt="candidat.nom_complet"
                         class="h-28 w-28 rounded-lg object-cover shrink-0 bg-gray-100 dark:bg-gray-700" />
                    <div v-else
                         class="h-28 w-28 rounded-lg shrink-0 flex items-center justify-center text-3xl font-semibold text-white"
                         :style="{ backgroundColor: liste.couleur || '#64748b' }"
                         aria-hidden="true">
                        {{ candidat.initiales }}
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-medium" :style="{ color: liste.couleur || undefined }">
                            {{ liste.nom_liste }} — {{ liste.commune_nom }}
                        </p>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ candidat.nom_complet }}
                        </h1>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <span v-if="candidat.est_tete_de_liste"
                                  class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                Tête de liste
                            </span>
                            <span v-if="candidat.position"
                                  class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                Position {{ candidat.position }}
                            </span>
                            <span v-if="candidat.fonction_visee"
                                  class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ candidat.fonction_visee }}
                            </span>
                        </div>

                        <dl class="mt-3 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                            <div v-if="candidat.profession" class="flex gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">Profession</dt>
                                <dd>{{ candidat.profession }}</dd>
                            </div>
                            <div v-if="candidat.age" class="flex gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">Âge</dt>
                                <dd>{{ candidat.age }} ans</dd>
                            </div>
                        </dl>

                        <div v-if="reseaux.length" class="mt-3 flex flex-wrap gap-3 text-sm">
                            <a v-for="r in reseaux" :key="r.url" :href="r.url" target="_blank" rel="noopener noreferrer"
                               class="text-blue-600 dark:text-blue-400 hover:underline capitalize">
                                {{ r.nom }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div v-for="section in sections" :key="section.titre"
                 class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ section.titre }}</h2>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ section.texte }}</p>
            </div>

            <p v-if="!sections.length" class="mt-6 text-center text-gray-500 dark:text-gray-400">
                Ce candidat n'a pas encore renseigné de présentation.
            </p>

            <p class="mt-6 text-xs text-center text-gray-500 dark:text-gray-400">
                Informations déclarées par la liste elle-même, dans le cadre de sa candidature.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
