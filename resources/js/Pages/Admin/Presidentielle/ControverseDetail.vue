<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PresidentielleNav from '@/Components/PresidentielleNav.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import FormErrors from '@/Components/Admin/FormErrors.vue';

/**
 * Lecture complète d'une controverse avant décision.
 *
 * Le sens appartient à la liaison, pas au fait : les faits ne sont donc ni groupés ni
 * filtrés par sens. « étaye » / « contredit » n'apparaît qu'accolé au titre de la mesure
 * visée, à l'intérieur du fait.
 */
const props = defineProps({
    controverse: Object,
    faits: Array,
});

const FIABILITE = {
    haute: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    moyenne: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    faible: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
};

function agir(type, id, action) {
    router.post(route('admin.presidentielle.moderation.action'), { type, id, action }, { preserveScroll: true });
}

const liaisons = computed(() => props.faits.flatMap((f) => f.liaisons));

const aDoubleValider = computed(() => liaisons.value.filter((l) => l.attend_double_validation));

const bloqueesParMoi = computed(() => aDoubleValider.value.filter((l) => l.double_validation_par_moi_interdite));
</script>

<template>
    <Head :title="`Controverse — ${controverse.titre}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-3">
                <h2 class="text-xl font-semibold">Controverse</h2>
                <PresidentielleNav />
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <FormErrors :errors="$page.props.errors" />

            <!-- En-tête -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <Link :href="route('admin.presidentielle.controverses')" class="text-xs text-blue-600 hover:underline">
                    ← Toutes les controverses
                </Link>
                <div class="flex items-start justify-between gap-3 flex-wrap mt-1">
                    <div>
                        <h3 class="font-semibold text-lg">{{ controverse.titre }}</h3>
                        <p v-if="controverse.theme" class="text-sm text-gray-500">{{ controverse.theme }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :statut="controverse.statut_validation" />
                        <StatusBadge :publie="controverse.affiche_publiquement" />
                    </div>
                </div>
                <p v-if="controverse.note_methodologique"
                   class="text-sm mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ controverse.note_methodologique }}
                </p>

                <div v-if="controverse.raisons_non_publiable.length"
                     class="mt-3 rounded border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Pas encore publiable
                    </p>
                    <ul class="text-sm mt-1 list-disc list-inside text-amber-700 dark:text-amber-300">
                        <li v-for="r in controverse.raisons_non_publiable" :key="r">{{ r }}</li>
                    </ul>
                </div>

                <p class="text-xs text-gray-500 mt-3">
                    Ordre de publication : les faits d'abord, puis les liaisons, puis les mesures.
                    Publier une liaison dont le fait n'est pas publié est refusé.
                </p>
            </div>

            <!-- Ce qui attend une seconde lecture -->
            <div v-if="aDoubleValider.length"
                 class="rounded-xl border border-violet-300 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-4">
                <p class="font-medium text-violet-900 dark:text-violet-200">
                    {{ aDoubleValider.length }} liaison{{ aDoubleValider.length > 1 ? 's' : '' }}
                    « contredit » en attente d'une seconde validation
                </p>
                <p class="text-sm mt-1 text-violet-800 dark:text-violet-300">
                    Elle doit venir d'un modérateur différent du premier, et c'est une seconde
                    <em>lecture</em> : le fait, ses sources et la note contextuelle sont dépliés
                    ci-dessous, à lire avant de confirmer.
                </p>
                <p v-if="bloqueesParMoi.length" class="text-sm mt-2 text-violet-800 dark:text-violet-300">
                    {{ bloqueesParMoi.length === aDoubleValider.length ? 'Toutes' : bloqueesParMoi.length }}
                    portent votre propre première validation : un autre modérateur doit s'en charger.
                </p>
            </div>

            <!-- Les faits -->
            <div v-for="fait in faits" :key="fait.id"
                 class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <span class="text-xs uppercase tracking-wide text-gray-500">{{ fait.type_libelle }}</span>
                            <h4 class="font-semibold mt-0.5">{{ fait.titre }}</h4>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <StatusBadge :statut="fait.statut_validation" />
                            <StatusBadge :publie="fait.affiche_publiquement" />
                        </div>
                    </div>

                    <p class="text-sm mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ fait.contenu }}</p>

                    <div class="flex items-center gap-2 mt-3 flex-wrap">
                        <ActionButton v-if="fait.statut_validation !== 'valide'"
                                      verbe="valider" @action="agir('argument', fait.id, 'valider')" />
                        <ActionButton v-if="fait.statut_validation === 'valide' && !fait.affiche_publiquement"
                                      verbe="publier"
                                      :disabled="fait.raisons_non_publiable.length > 0"
                                      :titre="fait.raisons_non_publiable.join(' · ') || null"
                                      :confirmation="`Le fait « ${fait.titre} » et ses sources partiront sur objectif2027.fr.`"
                                      titre-confirmation="Publier ce fait ?"
                                      @action="agir('argument', fait.id, 'publier')" />
                        <ActionButton v-if="fait.affiche_publiquement"
                                      verbe="depublier" @action="agir('argument', fait.id, 'depublier')" />
                        <span v-if="fait.raisons_non_publiable.length" class="text-xs text-amber-700 dark:text-amber-400">
                            {{ fait.raisons_non_publiable.join(' · ') }}
                        </span>
                    </div>
                </div>

                <!-- Sources du fait -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">
                        Sources ({{ fait.sources.length }})
                    </p>
                    <p v-if="!fait.sources.length" class="text-sm text-red-600 dark:text-red-400">
                        Aucune source. Un fait sans source fiable ne peut pas être publié.
                    </p>
                    <ul v-else class="space-y-2">
                        <li v-for="s in fait.sources" :key="s.id" class="text-sm">
                            <div class="flex items-start gap-2 flex-wrap">
                                <span :class="['px-1.5 py-0.5 rounded text-xs font-medium', FIABILITE[s.fiabilite] || 'bg-gray-100 text-gray-700']">
                                    {{ s.fiabilite }}
                                </span>
                                <a v-if="s.url" :href="s.url" target="_blank" rel="noopener noreferrer"
                                   class="text-blue-600 hover:underline break-all">{{ s.titre }}</a>
                                <span v-else>{{ s.titre }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span v-if="s.media">{{ s.media }}</span>
                                <span v-if="s.auteur"> · {{ s.auteur }}</span>
                                <span v-if="s.date_publication"> · {{ s.date_publication }}</span>
                                <a v-if="s.archive_url" :href="s.archive_url" target="_blank" rel="noopener noreferrer"
                                   class="text-blue-600 hover:underline"> · archive</a>
                            </p>
                            <p v-if="s.extrait" class="text-xs text-gray-600 dark:text-gray-400 mt-1 italic">
                                « {{ s.extrait }} »
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Emploi du fait : une entrée par mesure visée -->
                <div class="p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">
                        Emploi de ce fait ({{ fait.liaisons.length }})
                    </p>
                    <p v-if="!fait.liaisons.length" class="text-sm text-gray-500">
                        Ce fait n'est rattaché à aucune mesure. Il ne sera visible nulle part.
                    </p>

                    <div v-for="l in fait.liaisons" :key="l.id"
                         class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 mb-2 last:mb-0">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <p class="text-sm min-w-0">
                                <span :class="['font-medium', l.sens === 'contre' ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300']">
                                    {{ l.sens_libelle }}
                                </span>
                                <span v-if="l.mesure">
                                    « {{ l.mesure.titre }} »
                                    <span class="text-gray-500">— {{ l.mesure.candidat }}</span>
                                </span>
                                <span v-else class="text-amber-700 dark:text-amber-400">
                                    liaison non reliée à une mesure (à résoudre)
                                </span>
                            </p>
                            <div class="flex items-center gap-2 shrink-0">
                                <StatusBadge :statut="l.statut_validation" />
                                <StatusBadge :publie="l.affiche_publiquement" />
                            </div>
                        </div>

                        <p v-if="l.note_contextuelle" class="text-sm mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-line">
                            {{ l.note_contextuelle }}
                        </p>
                        <p v-else class="text-sm mt-2 text-amber-700 dark:text-amber-400">
                            Note contextuelle manquante : c'est elle qui explique en quoi le fait
                            porte sur cette mesure. Obligatoire avant publication.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            <span v-if="l.valide_par_nom">1<sup>re</sup> validation : {{ l.valide_par_nom }} le {{ l.valide_at }}</span>
                            <span v-else>Pas encore validée</span>
                            <span v-if="l.double_valide_par_nom"> · 2<sup>e</sup> : {{ l.double_valide_par_nom }} le {{ l.double_valide_at }}</span>
                        </p>

                        <div class="flex items-center gap-2 mt-3 flex-wrap">
                            <ActionButton v-if="l.statut_validation !== 'valide'"
                                          verbe="valider" @action="agir('argument_lien', l.id, 'valider')" />

                            <ActionButton v-if="l.attend_double_validation"
                                          verbe="double_valider"
                                          :disabled="l.double_validation_par_moi_interdite"
                                          :titre="l.double_validation_par_moi_interdite
                                              ? `Vous avez fait la première validation le ${l.valide_at} : la seconde revient à un autre modérateur.`
                                              : null"
                                          @action="agir('argument_lien', l.id, 'double_valider')" />

                            <ActionButton v-if="l.statut_validation === 'valide' && !l.affiche_publiquement"
                                          verbe="publier"
                                          :disabled="l.raisons_non_publiable.length > 0"
                                          :titre="l.raisons_non_publiable.join(' · ') || null"
                                          :confirmation="`Cet emploi du fait « ${fait.titre} » contre ou pour la mesure visée partira sur objectif2027.fr.`"
                                          titre-confirmation="Publier cette liaison ?"
                                          @action="agir('argument_lien', l.id, 'publier')" />

                            <ActionButton v-if="l.affiche_publiquement"
                                          verbe="depublier" @action="agir('argument_lien', l.id, 'depublier')" />

                            <Link v-if="l.mesure"
                                  :href="route('admin.presidentielle.mesures.arguments', l.mesure.id)"
                                  class="text-xs text-blue-600 hover:underline">
                                Voir l'argumentaire complet de la mesure →
                            </Link>
                        </div>

                        <p v-if="l.double_validation_par_moi_interdite"
                           class="text-xs mt-2 text-violet-700 dark:text-violet-300">
                            Vous avez signé la première validation : la seconde revient à un autre
                            modérateur. C'est la règle du contrôle à quatre yeux, pas une panne.
                        </p>
                        <p v-else-if="l.raisons_non_publiable.length" class="text-xs mt-2 text-amber-700 dark:text-amber-400">
                            {{ l.raisons_non_publiable.join(' · ') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
