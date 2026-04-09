<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import CommuneHeader from '@/Components/Commune/CommuneHeader.vue';
import GalerieSection from '@/Components/Commune/GalerieSection.vue';
import TimelineItem from '@/Components/Commune/TimelineItem.vue';
import AbonnementButton from '@/Components/Commune/AbonnementButton.vue';
import { computed } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    seo: Object,
    maire: Object,
    articles: Array,
    evenements: Array,
    timeline: Array,
    galerie: Array,
    deputes: Array,
    senateurs: Array,
    stats: Object,
    est_abonne: Boolean,
    est_admin: Boolean,
    role_admin: String,
});

const auth = computed(() => usePage().props.auth?.user);
const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';
</script>

<template>
    <CommuneLayout :ville="ville" :page="page">
        <!-- Hero -->
        <CommuneHeader :ville="ville" :page="page" :maire="maire" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-8">
            <!-- Action bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
                <AbonnementButton :code-insee="ville.code_insee" :est-abonne="est_abonne" />

                <div class="flex items-center gap-2">
                    <Link
                        v-if="est_admin"
                        :href="route('commune.admin.dashboard', ville.code_insee)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg text-sm font-medium hover:bg-slate-700 dark:hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Administrer
                    </Link>
                    <Link
                        v-else-if="!page?.est_reclamee && auth"
                        :href="route('commune.reclamer', ville.code_insee)"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        Reclamer cette commune
                    </Link>
                </div>
            </div>

            <!-- Mot du maire -->
            <div v-if="page?.mot_du_maire" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <div class="flex items-start gap-4">
                    <div v-if="maire?.photo_url" class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0 border-2 border-blue-200">
                        <img :src="maire.photo_url" :alt="maire.prenom + ' ' + maire.nom" class="w-full h-full object-cover" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <span class="w-1 h-5 bg-blue-600 rounded-full"></span>
                            Mot du maire
                        </h2>
                        <p v-if="maire" class="text-sm text-slate-500 dark:text-slate-400 mb-2">{{ maire.civilite }} {{ maire.prenom }} {{ maire.nom }}</p>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ page.mot_du_maire }}</p>
                    </div>
                </div>
            </div>

            <!-- Galerie -->
            <GalerieSection :images="galerie" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Timeline mixte articles + evenements -->
                    <section v-if="timeline?.length">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
                                Fil d'actualite
                            </h2>
                            <div class="flex gap-2 text-xs">
                                <Link
                                    v-if="page?.fonctionnalites?.actus"
                                    :href="route('commune.actualites', ville.code_insee)"
                                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium"
                                >
                                    Toutes les actus
                                </Link>
                                <span v-if="page?.fonctionnalites?.actus && page?.fonctionnalites?.evenements" class="text-slate-300 dark:text-slate-600">&middot;</span>
                                <Link
                                    v-if="page?.fonctionnalites?.evenements"
                                    :href="route('commune.evenements', ville.code_insee)"
                                    class="text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium"
                                >
                                    Tous les evenements
                                </Link>
                            </div>
                        </div>

                        <div>
                            <TimelineItem
                                v-for="item in timeline"
                                :key="`${item.type}-${item.id}`"
                                :item="item"
                                :code-insee="ville.code_insee"
                            />
                        </div>
                    </section>

                    <!-- Fallback si pas de timeline : sections separees -->
                    <template v-else>
                        <section v-if="articles?.length">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                                <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
                                Dernieres actualites
                            </h2>
                            <div class="space-y-3">
                                <TimelineItem
                                    v-for="a in articles.map(a => ({ ...a, type: 'article', date_formate: a.publie_at }))"
                                    :key="a.id"
                                    :item="a"
                                    :code-insee="ville.code_insee"
                                />
                            </div>
                        </section>
                    </template>

                    <!-- Elus -->
                    <section>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
                            Vos elus
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="maire" class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div v-if="maire.photo_url" class="w-12 h-12 rounded-full overflow-hidden border border-slate-200">
                                        <img :src="maire.photo_url" :alt="maire.nom" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-bold">
                                        {{ maire.prenom?.charAt(0) }}{{ maire.nom?.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Maire</div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ maire.prenom }} {{ maire.nom }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-for="depute in deputes" :key="depute.uid" class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                                <Link :href="route('representants.deputes.show', depute.uid)" class="flex items-center gap-3 group">
                                    <div v-if="depute.photo_url" class="w-12 h-12 rounded-full overflow-hidden border border-slate-200">
                                        <img :src="depute.photo_url" :alt="depute.nom" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 font-bold">
                                        {{ depute.prenom?.charAt(0) }}{{ depute.nom?.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-xs text-rose-600 dark:text-rose-400 font-medium">Depute(e) - {{ depute.circonscription }}e circo.</div>
                                        <div class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ depute.prenom }} {{ depute.nom }}</div>
                                    </div>
                                </Link>
                            </div>

                            <div v-for="senateur in senateurs" :key="senateur.matricule" class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                                <Link :href="route('representants.senateurs.show', senateur.matricule)" class="flex items-center gap-3 group">
                                    <div v-if="senateur.photo_url" class="w-12 h-12 rounded-full overflow-hidden border border-slate-200">
                                        <img :src="senateur.photo_url" :alt="senateur.nom" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-bold">
                                        {{ senateur.prenom?.charAt(0) }}{{ senateur.nom?.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Senateur(rice)</div>
                                        <div class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ senateur.prenom }} {{ senateur.nom }}</div>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <Link :href="route('commune.elus', ville.code_insee)" class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                            Voir tous les elus et l'historique
                        </Link>
                    </section>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Chiffres cles -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-1 h-5 bg-blue-600 rounded-full"></span>
                            Chiffres cles
                        </h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Population</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ stats?.population_formate }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Superficie</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ ville.superficie_formate }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Densite</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ ville.densite_formate }}</dd>
                            </div>
                            <div v-if="stats?.budget_total" class="flex justify-between pt-2 border-t border-slate-100 dark:border-slate-700">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Budget {{ stats.annee_budget }}</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatNumber(Math.round(stats.budget_total / 1000)) }}k&euro;</dd>
                            </div>
                            <div v-if="stats?.dette" class="flex justify-between">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Encours dette</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatNumber(Math.round(stats.dette / 1000)) }}k&euro;</dd>
                            </div>
                        </dl>

                        <Link :href="route('commune.budget', ville.code_insee)" class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                            Voir le budget complet
                        </Link>
                    </div>

                    <!-- Infos pratiques -->
                    <div v-if="page?.adresse_mairie || page?.telephone || page?.email_mairie" class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-1 h-5 bg-blue-600 rounded-full"></span>
                            Mairie
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div v-if="page.adresse_mairie" class="flex gap-2">
                                <span class="text-slate-400 flex-shrink-0">📍</span>
                                <span class="text-slate-600 dark:text-slate-300">{{ page.adresse_mairie }}</span>
                            </div>
                            <div v-if="page.telephone" class="flex gap-2">
                                <span class="text-slate-400 flex-shrink-0">📞</span>
                                <a :href="'tel:' + page.telephone" class="text-blue-600 dark:text-blue-400 hover:underline">{{ page.telephone }}</a>
                            </div>
                            <div v-if="page.email_mairie" class="flex gap-2">
                                <span class="text-slate-400 flex-shrink-0">✉️</span>
                                <a :href="'mailto:' + page.email_mairie" class="text-blue-600 dark:text-blue-400 hover:underline truncate">{{ page.email_mairie }}</a>
                            </div>
                            <div v-if="page.site_officiel" class="flex gap-2">
                                <span class="text-slate-400 flex-shrink-0">🌐</span>
                                <a :href="page.site_officiel" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline truncate">Site officiel</a>
                            </div>
                        </div>

                        <div v-if="Object.keys(page.reseaux_sociaux || {}).length" class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                            <a v-for="(url, reseau) in page.reseaux_sociaux" :key="reseau" :href="url" target="_blank" class="text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-sm font-medium">
                                {{ reseau === 'facebook' ? 'FB' : reseau === 'twitter' ? 'X' : reseau === 'instagram' ? 'IG' : reseau === 'youtube' ? 'YT' : 'LI' }}
                            </a>
                        </div>
                    </div>

                    <!-- Liens rapides -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <span class="w-1 h-5 bg-blue-600 rounded-full"></span>
                            Explorer
                        </h3>
                        <div class="space-y-1.5">
                            <Link :href="route('commune.elus', ville.code_insee)" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                🏛️ Elus et representants
                            </Link>
                            <Link :href="route('commune.budget', ville.code_insee)" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                💰 Budget municipal
                            </Link>
                            <Link :href="route('commune.elections', ville.code_insee)" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                🗳️ Resultats electoraux
                            </Link>
                            <Link v-if="page?.fonctionnalites?.forum" :href="route('commune.forum', ville.code_insee)" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                💬 Forum communal
                            </Link>
                            <Link :href="route('villes.show', ville.slug)" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                📊 Fiche complete de la ville
                            </Link>
                            <a v-if="ville.wikipedia_url" :href="ville.wikipedia_url" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                📖 Wikipedia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CommuneLayout>
</template>
