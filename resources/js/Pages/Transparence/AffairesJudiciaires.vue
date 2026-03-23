<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AffaireBadge from '@/Components/AffairesJudiciaires/AffaireBadge.vue';
import StatsParParti from '@/Components/AffairesJudiciaires/StatsParParti.vue';
import PresomptionInnocence from '@/Components/AffairesJudiciaires/PresomptionInnocence.vue';

const props = defineProps({
    stats_global: Object,
    stats_par_parti: Object,
    stats_par_mandat: Object,
    dernieres_validees: Array,
});

const typeLabels = {
    corruption: 'Corruption', detournement_fonds: 'Détournement de fonds', fraude_fiscale: 'Fraude fiscale',
    abus_biens_sociaux: 'Abus de biens sociaux', prise_illegale_interet: 'Prise illégale d\'intérêts',
    favoritisme: 'Favoritisme', trafic_influence: 'Trafic d\'influence', emploi_fictif: 'Emploi fictif',
    conflit_interets: 'Conflit d\'intérêts', manquement_probite: 'Manquement probité',
};
</script>

<template>
    <Head title="Transparence — Affaires judiciaires des élus" />

    <AuthenticatedLayout>
        <div class="bg-gradient-to-b from-slate-900 to-slate-800 py-12 mb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-3xl font-bold text-white mb-3">Affaires judiciaires des élus</h1>
                <p class="text-slate-300 max-w-2xl mx-auto">
                    Toutes les données présentées ici sont vérifiées par un modérateur humain à partir de sources publiques.
                </p>
                <Link :href="route('transparence.demarche')" class="inline-flex items-center gap-1.5 mt-4 text-sm text-indigo-300 hover:text-indigo-200 transition-colors">
                    Découvrir notre démarche &rarr;
                </Link>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 space-y-10">
            <div v-if="!stats_global && !dernieres_validees?.length" class="text-center py-16">
                <div class="text-6xl mb-4">📊</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Données en cours de constitution</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Les affaires judiciaires sont en cours de détection et de vérification.
                    Revenez bientôt pour consulter les statistiques complètes.
                </p>
            </div>

            <template v-else>
                <section v-if="stats_global">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-5">Vue d'ensemble</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Affaires référencées</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats_global.totaux?.validees || 0 }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ stats_global.totaux?.personnes || 0 }} personne(s) concernée(s)</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Condamnations définitives</p>
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats_global.totaux?.condamnations_definitives || 0 }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Par type de mandat</p>
                            <div v-if="stats_par_mandat" class="mt-2 space-y-1 text-sm">
                                <div v-for="(data, type) in stats_par_mandat" :key="type" class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400 capitalize">{{ type }}s</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ data.total_affaires || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="stats_par_parti && Object.keys(stats_par_parti).length">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <StatsParParti :stats-par-parti="stats_par_parti" />
                    </div>
                </section>

                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-5">
                    <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">Biais connus (honnêteté intellectuelle)</h3>
                    <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1.5">
                        <li><strong>Biais temporel :</strong> un parti historique accumule mécaniquement plus d'affaires qu'un parti récent.</li>
                        <li><strong>Biais de couverture :</strong> les élus nationaux (députés, sénateurs) sont mieux documentés que les élus locaux.</li>
                        <li><strong>Biais médiatique :</strong> les élus très médiatisés font l'objet de plus d'articles, ce qui augmente la probabilité de détection.</li>
                    </ul>
                    <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">
                        C'est pourquoi nous normalisons toujours nos statistiques par le ratio condamnations / nombre d'élus.
                    </p>
                </div>

                <section v-if="dernieres_validees?.length">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-5">Dernières affaires vérifiées</h2>
                    <div class="space-y-3">
                        <div
                            v-for="a in dernieres_validees"
                            :key="a.id"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ a.prenom }} {{ a.nom }}</span>
                                        <span v-if="a.parti_politique" class="text-xs text-gray-500 dark:text-gray-400">({{ a.parti_politique }})</span>
                                        <AffaireBadge :statut="a.statut_judiciaire" size="xs" />
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ a.titre }}</p>
                                    <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ typeLabels[a.type_affaire] || a.type_affaire_libelle || a.type_affaire }}</span>
                                        <span v-if="a.peine_resume">Peine : {{ a.peine_resume }}</span>
                                    </div>
                                </div>
                                <span v-if="a.valide_at" class="text-xs text-gray-400 dark:text-gray-500 shrink-0">
                                    Vérifié le {{ a.valide_at }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            <PresomptionInnocence />
        </div>
    </AuthenticatedLayout>
</template>
