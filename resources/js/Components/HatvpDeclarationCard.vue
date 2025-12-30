<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true
    },
    parlementaireType: {
        type: String,
        default: 'depute' // 'depute' ou 'senateur'
    }
});

// Helpers
const formatMontant = (montant) => {
    if (!montant && montant !== 0) return '-';
    const num = typeof montant === 'number' ? montant : parseFloat(String(montant).replace(/\s/g, '').replace(',', '.'));
    if (isNaN(num)) return montant;
    return new Intl.NumberFormat('fr-FR').format(Math.round(num));
};

const formatMontantCompact = (montant) => {
    if (!montant || montant === 0) return '0€';
    if (montant >= 1000000) return (montant / 1000000).toFixed(1) + 'M€';
    if (montant >= 1000) return Math.round(montant / 1000) + 'k€';
    return Math.round(montant) + '€';
};

// Calculs pour le graphique
const maxRevenu = computed(() => {
    if (!props.summary?.revenus_par_annee) return 1;
    const revenus = Object.values(props.summary.revenus_par_annee);
    return Math.max(...revenus.map(r => r?.total || 0), 1);
});

const getBarHeight = (total) => {
    if (!total) return '5%';
    const percentage = (total / maxRevenu.value) * 100;
    return `${Math.max(percentage, 5)}%`;
};

const getSegmentHeight = (value, total) => {
    if (!total || total === 0 || !value) return '0%';
    const percentage = (value / total) * 100;
    return `${percentage}%`;
};

// Extraire le total d'un objet de revenus
const getTotal = (revenus) => {
    if (typeof revenus === 'number') return revenus;
    return revenus?.total || 0;
};

const getValue = (revenus, key) => {
    if (typeof revenus !== 'object') return 0;
    return revenus?.[key] || 0;
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="p-5 border-b border-slate-200 dark:border-gray-700 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span>📋</span>
                    <span>Déclarations HATVP</span>
                </h2>
                <Badge v-if="summary.declaration_date" class="bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ summary.declaration_date }}
                </Badge>
            </div>
            
            <!-- Stats résumé -->
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-3 bg-white/70 dark:bg-gray-800/50 rounded-lg">
                    <div class="text-2xl font-bold text-amber-700 dark:text-amber-400">
                        {{ summary.nombre_mandats || 0 }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Mandats électifs</div>
                </div>
                <div class="text-center p-3 bg-white/70 dark:bg-gray-800/50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                        {{ summary.nombre_emplois || 0 }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Activités pro</div>
                </div>
                <div class="text-center p-3 bg-white/70 dark:bg-gray-800/50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                        {{ summary.nombre_collaborateurs || 0 }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Collaborateurs</div>
                </div>
            </div>
        </div>

        <!-- Graphique des revenus -->
        <div v-if="summary.revenus_par_annee && Object.keys(summary.revenus_par_annee).length > 0" class="p-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                📊 Revenus déclarés par année
            </h3>
            
            <!-- Graphique en barres verticales -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-gray-900/50 rounded-xl">
                <div class="flex items-end justify-around gap-2 h-48">
                    <div 
                        v-for="(revenus, annee) in summary.revenus_par_annee" 
                        :key="annee"
                        class="flex flex-col items-center flex-1 max-w-20"
                    >
                        <!-- Barre empilée -->
                        <div 
                            class="w-full flex flex-col-reverse rounded-t-lg overflow-hidden transition-all duration-500"
                            :style="{ height: getBarHeight(getTotal(revenus)) }"
                        >
                            <div 
                                v-if="getValue(revenus, 'mandats') > 0"
                                class="bg-amber-500"
                                :style="{ height: getSegmentHeight(getValue(revenus, 'mandats'), getTotal(revenus)) }"
                                :title="`Mandats: ${formatMontant(getValue(revenus, 'mandats'))}€`"
                            />
                            <div 
                                v-if="getValue(revenus, 'activites_pro') > 0"
                                class="bg-blue-500"
                                :style="{ height: getSegmentHeight(getValue(revenus, 'activites_pro'), getTotal(revenus)) }"
                                :title="`Activités pro: ${formatMontant(getValue(revenus, 'activites_pro'))}€`"
                            />
                            <div 
                                v-if="getValue(revenus, 'consultant') > 0"
                                class="bg-purple-500"
                                :style="{ height: getSegmentHeight(getValue(revenus, 'consultant'), getTotal(revenus)) }"
                                :title="`Consultant: ${formatMontant(getValue(revenus, 'consultant'))}€`"
                            />
                            <div 
                                v-if="getValue(revenus, 'dirigeant') > 0"
                                class="bg-emerald-500"
                                :style="{ height: getSegmentHeight(getValue(revenus, 'dirigeant'), getTotal(revenus)) }"
                                :title="`Dirigeant: ${formatMontant(getValue(revenus, 'dirigeant'))}€`"
                            />
                        </div>
                        <!-- Montant total -->
                        <div class="text-xs font-bold text-amber-700 dark:text-amber-400 mt-1 text-center whitespace-nowrap">
                            {{ formatMontantCompact(getTotal(revenus)) }}
                        </div>
                        <!-- Année -->
                        <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                            {{ annee }}
                        </div>
                    </div>
                </div>
                
                <!-- Légende -->
                <div class="flex flex-wrap justify-center gap-4 mt-4 text-xs">
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-amber-500 rounded"></span>
                        Mandats
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-blue-500 rounded"></span>
                        Activités pro
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-purple-500 rounded"></span>
                        Consultant
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-emerald-500 rounded"></span>
                        Dirigeant
                    </span>
                </div>
            </div>

            <!-- Tableau détaillé -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-amber-50 dark:bg-amber-900/20">
                        <tr class="text-xs text-amber-800 dark:text-amber-300">
                            <th class="py-2 px-2 text-left">Année</th>
                            <th class="py-2 px-2 text-right">Mandats</th>
                            <th class="py-2 px-2 text-right">Activités</th>
                            <th class="py-2 px-2 text-right">Consultant</th>
                            <th class="py-2 px-2 text-right">Dirigeant</th>
                            <th class="py-2 px-2 text-right font-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr 
                            v-for="(revenus, annee) in summary.revenus_par_annee" 
                            :key="annee"
                            class="border-b border-amber-100 dark:border-amber-800/30"
                        >
                            <td class="py-2 px-2 font-medium">{{ annee }}</td>
                            <td class="py-2 px-2 text-right">{{ formatMontant(getValue(revenus, 'mandats')) }}</td>
                            <td class="py-2 px-2 text-right">{{ formatMontant(getValue(revenus, 'activites_pro')) }}</td>
                            <td class="py-2 px-2 text-right">{{ formatMontant(getValue(revenus, 'consultant')) }}</td>
                            <td class="py-2 px-2 text-right">{{ formatMontant(getValue(revenus, 'dirigeant')) }}</td>
                            <td class="py-2 px-2 text-right font-bold text-amber-700 dark:text-amber-400">
                                {{ formatMontant(getTotal(revenus)) }}€
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mandats électifs -->
        <div v-if="summary.mandats_electifs?.length > 0" class="p-5 border-t border-slate-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                🏛️ Mandats électifs
            </h3>
            <div class="space-y-3">
                <div 
                    v-for="(mandat, idx) in summary.mandats_electifs" 
                    :key="idx"
                    class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ mandat.description || 'Mandat électif' }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                📅 {{ mandat.date_debut || 'Non précisé' }} → 
                                <span :class="mandat.date_fin ? '' : 'text-green-600 dark:text-green-400 font-medium'">
                                    {{ mandat.date_fin || 'En cours' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <Badge v-if="mandat.conserve || mandat.actif" class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 text-xs">
                                ✓ {{ mandat.conserve ? 'Conservé' : 'Actif' }}
                            </Badge>
                            <span v-if="mandat.total_remunerations > 0" class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                {{ formatMontant(mandat.total_remunerations) }}€
                            </span>
                        </div>
                    </div>
                    <!-- Rémunérations par année -->
                    <div v-if="mandat.remunerations?.length > 0" class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="rem in mandat.remunerations.slice(0, 6)" 
                                :key="rem.annee"
                                class="text-xs px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
                            >
                                {{ rem.annee }}: <strong>{{ formatMontant(rem.montant) }}€</strong>
                                <span class="opacity-60" v-if="rem.brut_net">({{ rem.brut_net }})</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activités professionnelles -->
        <div v-if="summary.activites_professionnelles?.length > 0" class="p-5 border-t border-slate-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                💼 Activités professionnelles
            </h3>
            <div class="space-y-3">
                <div 
                    v-for="(activite, idx) in summary.activites_professionnelles" 
                    :key="idx"
                    class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ activite.description || activite.employeur || 'Activité professionnelle' }}
                            </div>
                            <div v-if="activite.employeur && activite.description" class="text-sm text-gray-600 dark:text-gray-400">
                                🏢 {{ activite.employeur }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                📅 {{ activite.date_debut || 'Non précisé' }} → {{ activite.date_fin || 'En cours' }}
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <Badge v-if="activite.conservee" class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 text-xs">
                                ✓ Conservée
                            </Badge>
                            <span v-if="activite.total_remunerations > 0" class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ formatMontant(activite.total_remunerations) }}€
                            </span>
                        </div>
                    </div>
                    <!-- Rémunérations -->
                    <div v-if="activite.remunerations?.length > 0" class="mt-3 pt-3 border-t border-purple-200 dark:border-purple-700">
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="rem in activite.remunerations.slice(0, 6)" 
                                :key="rem.annee"
                                class="text-xs px-2 py-1 rounded bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300"
                            >
                                {{ rem.annee }}: <strong>{{ formatMontant(rem.montant) }}€</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Participations dirigeantes -->
        <div v-if="summary.participations_dirigeantes?.length > 0" class="p-5 border-t border-slate-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                🏢 Participations dans des sociétés
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div 
                    v-for="(participation, idx) in summary.participations_dirigeantes" 
                    :key="idx"
                    class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800"
                >
                    <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                        {{ participation.societe || 'Société' }}
                    </div>
                    <div v-if="participation.activite" class="text-xs text-gray-600 dark:text-gray-400">
                        {{ participation.activite }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        {{ participation.date_debut || '?' }} → {{ participation.date_fin || 'En cours' }}
                    </div>
                    <div v-if="participation.total_remunerations > 0" class="mt-2 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                        {{ formatMontant(participation.total_remunerations) }}€
                    </div>
                </div>
            </div>
        </div>

        <!-- Fonctions bénévoles -->
        <div v-if="summary.fonctions_benevoles?.length > 0" class="p-5 border-t border-slate-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                🤝 Fonctions bénévoles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div 
                    v-for="(fonction, idx) in summary.fonctions_benevoles" 
                    :key="idx"
                    class="p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg"
                >
                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                        {{ fonction.organisme || fonction.description }}
                    </div>
                    <div v-if="fonction.description && fonction.organisme" class="text-xs text-gray-600 dark:text-gray-400">
                        {{ fonction.description }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
