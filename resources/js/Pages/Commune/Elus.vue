<script setup>
import { Link } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';

const props = defineProps({
    ville: Object,
    page: Object,
    maire: Object,
    historique_maires: Array,
    deputes: Array,
    senateurs: Array,
});
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Elus - ${ville.nom}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-8">Vos elus</h1>

            <!-- Maire actuel -->
            <section v-if="maire" class="mb-10">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Maire en exercice</h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-6">
                        <div v-if="maire.photo_url" class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-200 dark:border-blue-800 flex-shrink-0">
                            <img :src="maire.photo_url" :alt="maire.nom" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-24 h-24 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-2xl font-bold flex-shrink-0">
                            {{ maire.prenom?.charAt(0) }}{{ maire.nom?.charAt(0) }}
                        </div>
                        <div>
                            <div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Maire de {{ ville.nom }}</div>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ maire.civilite }} {{ maire.prenom }} {{ maire.nom }}</h3>
                            <p v-if="maire.nuance_politique" class="text-sm text-slate-500 dark:text-slate-400 mt-1">Nuance : {{ maire.nuance_politique }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Deputes -->
            <section v-if="deputes?.length" class="mb-10">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Depute(s) de la circonscription</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link
                        v-for="d in deputes"
                        :key="d.uid"
                        :href="route('representants.deputes.show', d.uid)"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                        <div class="flex items-center gap-4">
                            <div v-if="d.photo_url" class="w-16 h-16 rounded-full overflow-hidden border border-slate-200 flex-shrink-0">
                                <img :src="d.photo_url" :alt="d.nom" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 font-bold flex-shrink-0">
                                {{ d.prenom?.charAt(0) }}{{ d.nom?.charAt(0) }}
                            </div>
                            <div>
                                <div class="text-xs text-rose-600 dark:text-rose-400 font-medium">Depute(e) - {{ d.circonscription }}e circonscription</div>
                                <div class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ d.prenom }} {{ d.nom }}</div>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Senateurs -->
            <section v-if="senateurs?.length" class="mb-10">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Senateur(s) du departement</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link
                        v-for="s in senateurs"
                        :key="s.matricule"
                        :href="route('representants.senateurs.show', s.matricule)"
                        class="group bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                        <div class="flex items-center gap-4">
                            <div v-if="s.photo_url" class="w-16 h-16 rounded-full overflow-hidden border border-slate-200 flex-shrink-0">
                                <img :src="s.photo_url" :alt="s.nom" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-bold flex-shrink-0">
                                {{ s.prenom?.charAt(0) }}{{ s.nom?.charAt(0) }}
                            </div>
                            <div>
                                <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Senateur(rice)</div>
                                <div class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ s.prenom }} {{ s.nom }}</div>
                                <div v-if="s.groupe" class="text-sm text-slate-500 dark:text-slate-400">{{ s.groupe }}</div>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Historique maires -->
            <section v-if="historique_maires?.length">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Historique des maires</h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50">
                                <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Periode</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Maire</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 hidden sm:table-cell">Nuance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="m in historique_maires"
                                :key="m.id"
                                class="border-t border-slate-100 dark:border-slate-700"
                                :class="m.est_actuel ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''"
                            >
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ m.date_debut }} - {{ m.date_fin || 'present' }}
                                    <span v-if="m.est_actuel" class="ml-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-1.5 py-0.5 rounded">actuel</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ m.civilite }} {{ m.prenom }} {{ m.nom }}</td>
                                <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ m.nuance || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </CommuneLayout>
</template>
