<template>
    <Head :title="`${titre} - Admin`" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <nav aria-label="Fil d'Ariane" class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <Link :href="route('admin.stats-france.index')" class="hover:text-blue-600">Statistiques France</Link>
                <span aria-hidden="true">→</span>
                <span class="text-gray-900 dark:text-white">{{ titre }}</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        <span aria-hidden="true">{{ icone }}</span> {{ titre }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ description }}</p>
                </div>
                <div>
                    <label :for="`annee-${slug}`" class="sr-only">Millésime</label>
                    <select :id="`annee-${slug}`"
                            v-model="anneeSelectionnee"
                            class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                        <option v-for="an in anneesDisponibles" :key="an" :value="an">{{ an }}</option>
                    </select>
                </div>
            </div>

            <Card>
                <form @submit.prevent="soumettre" class="space-y-6">
                    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div v-for="champ in champsNombre" :key="champ.nom">
                            <label :for="idChamp(champ)"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ champ.libelle }}
                                <span v-if="champ.suffixe" class="text-gray-400 font-normal">({{ champ.suffixe }})</span>
                            </label>
                            <input :id="idChamp(champ)"
                                   v-model="form[champ.nom]"
                                   type="number"
                                   :step="champ.pas"
                                   :aria-invalid="Boolean(form.errors[champ.nom])"
                                   :aria-describedby="form.errors[champ.nom] ? `${idChamp(champ)}-erreur` : undefined"
                                   class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                   :class="form.errors[champ.nom] ? 'border-red-400 dark:border-red-600' : ''" />
                            <p v-if="form.errors[champ.nom]"
                               :id="`${idChamp(champ)}-erreur`"
                               class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors[champ.nom] }}
                            </p>
                        </div>
                    </div>

                    <div v-for="champ in champsTexte" :key="champ.nom">
                        <label :for="idChamp(champ)"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ champ.libelle }}
                        </label>
                        <textarea :id="idChamp(champ)"
                                  v-model="form[champ.nom]"
                                  rows="3"
                                  class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"></textarea>
                        <p v-if="champ.nom === 'sources'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            D'où viennent ces chiffres — un chiffre sans provenance ne peut pas être défendu.
                        </p>
                    </div>

                    <FormErrors :errors="form.errors" />

                    <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                        <Link :href="route('admin.stats-france.index')" class="text-gray-600 hover:text-gray-800 dark:text-gray-300">
                            ← Retour
                        </Link>
                        <button type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

/**
 * Formulaire annuel de statistiques, entièrement piloté par le schéma.
 *
 * Les dix écrans de statistiques listaient leurs champs à la main, avec des noms qui
 * avaient dérivé de ceux du contrôleur, eux-mêmes dérivés des colonnes réelles : sur
 * soixante-quatre champs, dix-neuf seulement arrivaient en base, et quatre écrans
 * n'écrivaient rien en affichant « mises à jour ». La liste vient désormais du serveur,
 * qui la tire des colonnes — et les mêmes colonnes produisent les règles de validation.
 */
const props = defineProps({
    slug: { type: String, required: true },
    titre: { type: String, required: true },
    icone: { type: String, default: '' },
    description: { type: String, default: '' },
    annee: { type: [Number, String], required: true },
    anneesDisponibles: { type: Array, default: () => [] },
    champs: { type: Array, required: true },
    data: { type: Object, default: null },
    routeShow: { type: String, required: true },
    routeUpdate: { type: String, required: true },
});

const champsNombre = computed(() => props.champs.filter((c) => c.type === 'nombre'));
const champsTexte = computed(() => props.champs.filter((c) => c.type === 'texte'));

const idChamp = (champ) => `${props.slug}-${champ.nom}`;

const form = useForm(Object.fromEntries(
    props.champs.map((c) => [c.nom, props.data?.[c.nom] ?? '']),
));

const anneeSelectionnee = ref(props.annee);
watch(anneeSelectionnee, (an) => {
    if (String(an) !== String(props.annee)) {
        router.get(route(props.routeShow), { annee: an });
    }
});

function soumettre() {
    form.put(route(props.routeUpdate, anneeSelectionnee.value), { preserveScroll: true });
}
</script>
