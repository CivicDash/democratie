<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import NuanceBadge from '@/Components/Municipales/NuanceBadge.vue';
import ResultatsTable from '@/Components/Municipales/ResultatsTable.vue';
import AffairesSection from '@/Components/AffairesJudiciaires/AffairesSection.vue';
import AffairesHeroBadges from '@/Components/AffairesJudiciaires/AffairesHeroBadges.vue';
import PresomptionInnocence from '@/Components/AffairesJudiciaires/PresomptionInnocence.vue';
import HatvpSection from '@/Components/Hatvp/HatvpSection.vue';
import EluFollowButton from '@/Components/EluFollowButton.vue';

const props = defineProps({
    maire: Object,
    mandats_historiques: { type: Array, default: () => [] },
    resultats_election: { type: Array, default: () => [] },
    affaires_judiciaires: { type: Array, default: () => [] },
    declarations_hatvp: { type: Array, default: () => [] },
    hatvp_summary: { type: Object, default: null },
    elus_rattaches: { type: Array, default: () => [] },
    budget_commune: { type: Array, default: () => [] },
});

const activeTab = ref('profil');
const tabs = [
    { key: 'profil', label: 'Profil', icon: '👤' },
    { key: 'mandat', label: 'Mandat', icon: '📋' },
    { key: 'elections', label: 'Elections', icon: '🗳️' },
    { key: 'transparence', label: 'Transparence', icon: '⚖️' },
];

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Maires', href: route('elus.maires.index'), icon: '🏛️' },
    { label: props.maire.nom_complet, current: true, icon: '👤' },
];

const formatPopulation = (pop) => {
    if (!pop) return '—';
    return pop.toLocaleString('fr-FR');
};

const formatMontant = (montant) => {
    if (!montant) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(montant);
};

const deputes = computed(() => props.elus_rattaches.filter(e => e.type === 'depute'));
const senateurs = computed(() => props.elus_rattaches.filter(e => e.type === 'senateur'));
</script>

<template>
    <Head :title="`${maire.nom_complet} — Maire de ${maire.commune.nom}`" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden bg-gradient-to-br from-amber-900 via-amber-800 to-orange-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>

            <div class="relative w-full px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />

                <div class="flex flex-col md:flex-row items-start gap-6">
                    <!-- Photo -->
                    <div class="w-28 h-28 md:w-36 md:h-36 rounded-2xl overflow-hidden bg-white/10 flex-shrink-0 ring-4 ring-white/20">
                        <img
                            v-if="maire.photo"
                            :src="maire.photo"
                            :alt="maire.nom_complet"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-5xl">
                            {{ maire.civilite === 'Mme' ? '👩' : '👨' }}
                        </div>
                    </div>

                    <div class="flex-1">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/30 text-amber-100 border border-amber-400/30">
                                Maire
                            </span>
                            <NuanceBadge v-if="maire.nuance" :nuance="maire.nuance.code" size="sm" />
                            <span v-if="maire.est_aussi_depute" class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/30 text-blue-200 border border-blue-400/30">
                                Aussi depute
                            </span>
                            <span v-if="maire.est_aussi_senateur" class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/30 text-rose-200 border border-rose-400/30">
                                Aussi senateur
                            </span>
                            <AffairesHeroBadges :affaires="affaires_judiciaires" variant="dark" />
                        </div>

                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-2 tracking-tight">
                            {{ maire.nom_complet }}
                        </h1>
                        <p class="text-amber-200 text-lg mb-1">
                            Maire de {{ maire.commune.nom }} ({{ maire.commune.code_departement }})
                        </p>
                        <p class="text-amber-300/70 text-sm">
                            <span v-if="maire.mandat.debut">Depuis {{ maire.mandat.debut }}</span>
                            <span v-if="maire.age"> · {{ maire.age }} ans</span>
                            <span v-if="maire.commune.population"> · {{ formatPopulation(maire.commune.population) }} habitants</span>
                        </p>

                        <div class="flex items-center gap-3 mt-4">
                            <Link
                                v-if="maire.commune.ville_slug"
                                :href="route('villes.show', maire.commune.ville_slug)"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition text-sm font-medium"
                            >
                                📍 Voir la fiche ville
                            </Link>
                            <EluFollowButton
                                elu-type="maire"
                                :elu-id="String(maire.id)"
                                :elu-name="maire.nom_complet"
                                :initial-following="maire.is_followed"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Onglets -->
        <div class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <nav class="flex gap-1 overflow-x-auto py-1 -mb-px">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-4 py-3 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap',
                            activeTab === tab.key
                                ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-b-2 border-amber-600'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        ]"
                    >
                        <span class="mr-1.5">{{ tab.icon }}</span>
                        {{ tab.label }}
                    </button>
                </nav>
            </div>
        </div>

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <!-- ═══ TAB PROFIL ═══ -->
            <div v-show="activeTab === 'profil'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Col principale -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Biographie -->
                        <Card>
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Biographie</h2>
                                <p v-if="maire.wikipedia?.extract" class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                    {{ maire.wikipedia.extract }}
                                </p>
                                <p v-else class="text-gray-500 dark:text-gray-400 italic">
                                    Aucune biographie disponible pour ce maire.
                                </p>
                                <a
                                    v-if="maire.wikipedia?.url"
                                    :href="maire.wikipedia.url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 mt-4 text-sm text-amber-600 dark:text-amber-400 hover:underline"
                                >
                                    Lire sur Wikipedia →
                                </a>
                            </div>
                        </Card>

                        <!-- Postes gouvernementaux (si applicable) -->
                        <Card v-if="maire.postes_gouvernement?.length">
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Fonctions gouvernementales</h2>
                                <ul class="space-y-2">
                                    <li v-for="(poste, i) in maire.postes_gouvernement" :key="i" class="flex items-center gap-2">
                                        <span class="text-amber-500">⚜️</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ poste }}</span>
                                    </li>
                                </ul>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Informations -->
                        <Card>
                            <div class="p-6">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Informations</h3>
                                <dl class="space-y-3 text-sm">
                                    <div v-if="maire.profession" class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Profession</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium text-right">{{ maire.profession }}</dd>
                                    </div>
                                    <div v-if="maire.formation" class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Formation</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium text-right">{{ maire.formation }}</dd>
                                    </div>
                                    <div v-if="maire.date_naissance" class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Date de naissance</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ maire.date_naissance }}</dd>
                                    </div>
                                    <div v-if="maire.lieu_naissance" class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Lieu de naissance</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium text-right">{{ maire.lieu_naissance }}</dd>
                                    </div>
                                    <div v-if="maire.nuance" class="flex justify-between items-center">
                                        <dt class="text-gray-500 dark:text-gray-400">Nuance politique</dt>
                                        <dd><NuanceBadge :nuance="maire.nuance.code" size="sm" /></dd>
                                    </div>
                                </dl>
                            </div>
                        </Card>

                        <!-- Contact -->
                        <Card v-if="Object.keys(maire.contact).length">
                            <div class="p-6">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Contact</h3>
                                <div class="space-y-3 text-sm">
                                    <a v-if="maire.contact.email" :href="`mailto:${maire.contact.email}`" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">
                                        <span>📧</span> {{ maire.contact.email }}
                                    </a>
                                    <a v-if="maire.contact.telephone" :href="`tel:${maire.contact.telephone}`" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">
                                        <span>📞</span> {{ maire.contact.telephone }}
                                    </a>
                                    <a v-if="maire.contact.site_web" :href="maire.contact.site_web" target="_blank" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">
                                        <span>🌐</span> Site web
                                    </a>
                                    <p v-if="maire.contact.adresse_mairie" class="flex items-start gap-2 text-gray-700 dark:text-gray-300">
                                        <span>📍</span> {{ maire.contact.adresse_mairie }}
                                    </p>
                                </div>
                            </div>
                        </Card>

                        <!-- Reseaux sociaux -->
                        <Card v-if="Object.keys(maire.reseaux_sociaux).length">
                            <div class="p-6">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Reseaux sociaux</h3>
                                <div class="flex flex-wrap gap-2">
                                    <a v-if="maire.reseaux_sociaux.twitter" :href="maire.reseaux_sociaux.twitter" target="_blank" class="px-3 py-1.5 bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 rounded-lg text-sm hover:bg-sky-100 dark:hover:bg-sky-900/40 transition">
                                        𝕏 Twitter
                                    </a>
                                    <a v-if="maire.reseaux_sociaux.facebook" :href="maire.reseaux_sociaux.facebook" target="_blank" class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                        Facebook
                                    </a>
                                    <a v-if="maire.reseaux_sociaux.instagram" :href="maire.reseaux_sociaux.instagram" target="_blank" class="px-3 py-1.5 bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 rounded-lg text-sm hover:bg-pink-100 dark:hover:bg-pink-900/40 transition">
                                        Instagram
                                    </a>
                                    <a v-if="maire.reseaux_sociaux.linkedin" :href="maire.reseaux_sociaux.linkedin" target="_blank" class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                        LinkedIn
                                    </a>
                                </div>
                            </div>
                        </Card>

                        <!-- Elus rattaches -->
                        <Card v-if="elus_rattaches.length">
                            <div class="p-6">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Vos autres elus</h3>
                                <div class="space-y-3">
                                    <Link
                                        v-for="(elu, i) in elus_rattaches"
                                        :key="i"
                                        :href="elu.url"
                                        class="flex items-center gap-3 group"
                                    >
                                        <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                            <img v-if="elu.photo" :src="elu.photo" :alt="elu.nom" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">
                                                {{ elu.type === 'depute' ? '🔵' : '🔴' }}
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">
                                                {{ elu.nom }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ elu.type === 'depute' ? 'Depute' : 'Senateur' }}
                                                <span v-if="elu.detail"> · {{ elu.detail }}</span>
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB MANDAT ═══ -->
            <div v-show="activeTab === 'mandat'" class="space-y-6">
                <!-- Mandat actuel -->
                <Card>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mandat actuel</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-amber-700 dark:text-amber-400">
                                    {{ maire.mandat.mandature || '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mandature</div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                                    {{ maire.mandat.debut || '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Debut</div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-green-700 dark:text-green-400">
                                    {{ maire.mandat.reelu ? 'Oui' : 'Non' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reelu(e)</div>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                                    {{ maire.mandat.score ? `${maire.mandat.score}%` : '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Score {{ maire.mandat.tour ? `T${maire.mandat.tour}` : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Historique mandats -->
                <Card v-if="mandats_historiques.length">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historique des mandats</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Periode</th>
                                        <th class="text-left py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Duree</th>
                                        <th class="text-left py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Nuance</th>
                                        <th class="text-left py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="mandat in mandats_historiques"
                                        :key="mandat.id"
                                        :class="mandat.est_actuel ? 'bg-amber-50/50 dark:bg-amber-900/10' : ''"
                                        class="border-b border-gray-100 dark:border-gray-800"
                                    >
                                        <td class="py-3 px-2 font-medium text-gray-900 dark:text-white">
                                            {{ mandat.periode }}
                                            <span v-if="mandat.est_actuel" class="ml-2 text-xs text-amber-600 dark:text-amber-400 font-semibold">EN COURS</span>
                                        </td>
                                        <td class="py-3 px-2 text-gray-600 dark:text-gray-400">{{ mandat.duree || '—' }}</td>
                                        <td class="py-3 px-2">
                                            <NuanceBadge v-if="mandat.nuance_politique" :nuance="mandat.nuance_politique" size="sm" />
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="py-3 px-2 text-gray-600 dark:text-gray-400">
                                            {{ mandat.score_election ? `${mandat.score_election}%` : '—' }}
                                            <span v-if="mandat.tour_election" class="text-xs text-gray-400"> T{{ mandat.tour_election }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

                <!-- Budget -->
                <Card v-if="budget_commune.length">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Budget communal</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Annee</th>
                                        <th class="text-right py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Recettes</th>
                                        <th class="text-right py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Depenses</th>
                                        <th class="text-right py-3 px-2 text-gray-500 dark:text-gray-400 font-medium">Encours dette</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in budget_commune" :key="b.annee" class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-3 px-2 font-medium text-gray-900 dark:text-white">{{ b.annee }}</td>
                                        <td class="py-3 px-2 text-right text-green-600 dark:text-green-400">{{ formatMontant(b.recettes) }}</td>
                                        <td class="py-3 px-2 text-right text-red-600 dark:text-red-400">{{ formatMontant(b.depenses) }}</td>
                                        <td class="py-3 px-2 text-right text-gray-600 dark:text-gray-400">{{ formatMontant(b.dette) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Link
                            v-if="maire.commune.ville_slug"
                            :href="route('villes.show', maire.commune.ville_slug)"
                            class="inline-flex items-center gap-1 mt-4 text-sm text-amber-600 dark:text-amber-400 hover:underline"
                        >
                            Voir le detail sur la fiche ville →
                        </Link>
                    </div>
                </Card>
            </div>

            <!-- ═══ TAB ELECTIONS ═══ -->
            <div v-show="activeTab === 'elections'" class="space-y-6">
                <template v-if="resultats_election.length">
                    <Card v-for="resultat in resultats_election" :key="resultat.tour">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Municipales 2026 — Tour {{ resultat.tour }}
                                </h2>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Participation : <span class="font-semibold text-gray-900 dark:text-white">{{ resultat.taux_participation?.toFixed(1) }}%</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mb-6">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ resultat.inscrits?.toLocaleString('fr-FR') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Inscrits</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ resultat.votants?.toLocaleString('fr-FR') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Votants</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ resultat.exprimes?.toLocaleString('fr-FR') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Exprimes</div>
                                </div>
                            </div>

                            <ResultatsTable :listes="resultat.listes" :tour="resultat.tour" />
                        </div>
                    </Card>
                </template>

                <div v-else class="text-center py-16">
                    <div class="text-5xl mb-4">🗳️</div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucun resultat electoral</h3>
                    <p class="text-gray-500 dark:text-gray-400">Les resultats electoraux de cette commune ne sont pas encore disponibles.</p>
                </div>
            </div>

            <!-- ═══ TAB TRANSPARENCE ═══ -->
            <div v-show="activeTab === 'transparence'" class="space-y-6">
                <!-- HATVP -->
                <Card v-if="declarations_hatvp?.length">
                    <div class="p-6">
                        <HatvpSection :declarations="declarations_hatvp" :summary="hatvp_summary" />
                    </div>
                </Card>
                <Card v-else-if="maire.est_soumis_hatvp">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Declarations HATVP</h2>
                        <p class="text-gray-500 dark:text-gray-400">
                            Ce maire est soumis aux obligations de declaration HATVP (commune de plus de 20 000 habitants) mais aucune declaration n'a ete retrouvee dans nos donnees.
                        </p>
                    </div>
                </Card>
                <Card v-else>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Declarations HATVP</h2>
                        <p class="text-gray-500 dark:text-gray-400">
                            Ce maire n'est pas soumis aux obligations de declaration HATVP (commune de moins de 20 000 habitants).
                        </p>
                    </div>
                </Card>

                <!-- Affaires judiciaires -->
                <Card v-if="affaires_judiciaires?.length" id="affaires-section">
                    <AffairesSection :affaires="affaires_judiciaires" />
                </Card>
                <Card v-else>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Affaires judiciaires</h2>
                        <p class="text-gray-500 dark:text-gray-400">Aucune affaire judiciaire referencee pour ce maire.</p>
                    </div>
                </Card>

                <PresomptionInnocence />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
