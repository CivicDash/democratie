<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    sectors: Array,
    userAllocations: [Object, Array],
    averages: [Object, Array],
    stats: Object,
    govAllocations: Object,
    totalBudget: Number,
    budgetYear: Number,
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const sectorIcons = {
    EDU: '🎓', HEALTH: '🏥', ECO: '🌱', DEFENSE: '🛡️', SOCIAL: '🤝',
    CULTURE: '🎭', INFRA: '🚇', JUSTICE: '⚖️', RESEARCH: '🔬', AGRI: '🌾',
};

const allocations = ref({});
const activeSlider = ref(null);

props.sectors.forEach(sector => {
    let existing = null;
    if (props.userAllocations) {
        if (Array.isArray(props.userAllocations)) {
            const match = props.userAllocations.find(a => a.sector_id === sector.id);
            existing = match ? parseFloat(match.percent) : null;
        } else if (props.userAllocations.allocations) {
            existing = props.userAllocations.allocations[sector.id]
                ? parseFloat(props.userAllocations.allocations[sector.id])
                : null;
        }
    }
    allocations.value[sector.id] = existing ?? 0;
});

const totalAllocated = computed(() =>
    Object.values(allocations.value).reduce((s, v) => s + parseFloat(v || 0), 0)
);
const remainingBudget = computed(() => Math.max(0, 100 - totalAllocated.value));
const isValid = computed(() => Math.abs(totalAllocated.value - 100) < 0.1);
const isOverBudget = computed(() => totalAllocated.value > 100.1);

const totalBudgetMd = computed(() => (props.totalBudget / 1e9).toFixed(0));

function sectorBudgetMd(sectorId) {
    const pct = parseFloat(allocations.value[sectorId] || 0);
    return ((pct / 100) * props.totalBudget / 1e9).toFixed(1);
}

function govBudgetMd(sectorId) {
    const gov = props.govAllocations?.[sectorId];
    return gov?.amount ? (gov.amount / 1e9).toFixed(1) : null;
}

function govPercent(sectorId) {
    return props.govAllocations?.[sectorId]?.percent ?? null;
}

function avgPercent(sectorId) {
    if (!props.averages) return null;
    if (Array.isArray(props.averages)) {
        const match = props.averages.find(a => a.sector_id === sectorId);
        return match?.average_percent ?? null;
    }
    return props.averages[sectorId] ? parseFloat(props.averages[sectorId]) : null;
}

function sectorIcon(sector) {
    return sectorIcons[sector.code] || '📊';
}

function onSliderInput(sectorId, event) {
    allocations.value[sectorId] = parseFloat(event.target.value);
}

function onNumberInput(sectorId, event) {
    let val = parseFloat(event.target.value);
    if (isNaN(val)) val = 0;
    val = Math.max(0, Math.min(100, val));
    allocations.value[sectorId] = Math.round(val * 10) / 10;
}

function distributeEqually() {
    const perSector = Math.round((100 / props.sectors.length) * 10) / 10;
    const remainder = Math.round((100 - perSector * props.sectors.length) * 10) / 10;
    props.sectors.forEach((sector, i) => {
        allocations.value[sector.id] = i === 0 ? perSector + remainder : perSector;
    });
}

function useAverages() {
    props.sectors.forEach(sector => {
        const avg = avgPercent(sector.id);
        allocations.value[sector.id] = avg ?? 0;
    });
}

function useGovAllocations() {
    props.sectors.forEach(sector => {
        const gov = govPercent(sector.id);
        allocations.value[sector.id] = gov ?? 0;
    });
}

function resetAll() {
    props.sectors.forEach(sector => {
        allocations.value[sector.id] = 0;
    });
}

const form = useForm({});

function submit() {
    const payload = {};
    for (const [k, v] of Object.entries(allocations.value)) {
        payload[k] = parseFloat(v);
    }
    form.transform(() => ({ allocations: payload })).post(route('budget.bulk-allocate'));
}

function doReset() {
    if (!confirm('Réinitialiser votre allocation ?')) return;
    form.delete(route('budget.reset'), {
        onSuccess: () => resetAll(),
    });
}

const progressColor = computed(() => {
    if (isOverBudget.value) return 'bg-red-500';
    if (isValid.value) return 'bg-emerald-500';
    if (totalAllocated.value > 80) return 'bg-amber-500';
    return 'bg-blue-500';
});

const progressTextColor = computed(() => {
    if (isOverBudget.value) return 'text-red-600 dark:text-red-400';
    if (isValid.value) return 'text-emerald-600 dark:text-emerald-400';
    return 'text-gray-900 dark:text-gray-100';
});

const isDark = ref(false);
if (typeof window !== 'undefined') {
    isDark.value = document.documentElement.classList.contains('dark');
    const obs = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

function sliderGradient(sector) {
    const pct = (allocations.value[sector.id] / Math.min(parseFloat(sector.max_percent), 100)) * 100;
    const color = sector.color || '#6366F1';
    const track = isDark.value ? 'rgb(55 65 81)' : 'rgb(229 231 235)';
    return {
        background: `linear-gradient(to right, ${color} 0%, ${color} ${pct}%, ${track} ${pct}%, ${track} 100%)`
    };
}

function diffLabel(sectorId) {
    const user = parseFloat(allocations.value[sectorId] || 0);
    const gov = govPercent(sectorId);
    if (gov === null || user === 0) return null;
    const diff = user - gov;
    if (Math.abs(diff) < 0.5) return { text: '= État', class: 'text-gray-500' };
    if (diff > 0) return { text: `+${diff.toFixed(1)}%`, class: 'text-emerald-600 dark:text-emerald-400' };
    return { text: `${diff.toFixed(1)}%`, class: 'text-red-500 dark:text-red-400' };
}
</script>

<template>
    <Head title="Budget Participatif" />
    <MainLayout title="Budget Participatif">
        <div class="py-6 sm:py-10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Hero -->
                <div class="mb-8 text-center">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        Budget Participatif Citoyen
                    </h1>
                    <p class="mt-3 text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                        Si vous pouviez décider de l'allocation du budget de l'État
                        <span class="font-semibold text-gray-900 dark:text-white">({{ totalBudgetMd }} Md€)</span>,
                        comment le répartiriez-vous ?
                    </p>
                    <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                        Budget {{ budgetYear }} &middot; {{ stats?.total_citizens || 0 }} citoyens inscrits &middot; {{ stats?.participating_citizens || 0 }} ont voté
                    </p>
                </div>

                <!-- Sticky progress bar -->
                <div class="sticky top-16 z-30 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg px-5 py-3 mb-8 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Budget alloué</span>
                            <span class="text-xl font-bold tabular-nums" :class="progressTextColor">
                                {{ totalAllocated.toFixed(1) }}%
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span v-if="!isValid && !isOverBudget" class="text-gray-500 dark:text-gray-400">
                                Reste <span class="font-semibold text-gray-900 dark:text-white">{{ remainingBudget.toFixed(1) }}%</span>
                                soit <span class="font-semibold text-gray-900 dark:text-white">{{ (remainingBudget / 100 * props.totalBudget / 1e9).toFixed(1) }} Md€</span>
                            </span>
                            <span v-else-if="isOverBudget" class="text-red-600 dark:text-red-400 font-semibold">
                                Dépassement de {{ (totalAllocated - 100).toFixed(1) }}%
                            </span>
                            <span v-else class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Budget équilibré
                            </span>
                        </div>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden relative">
                        <div
                            class="h-full rounded-full transition-all duration-300 ease-out"
                            :class="progressColor"
                            :style="{ width: Math.min(totalAllocated, 100) + '%' }"
                        />
                        <!-- 100% mark -->
                        <div class="absolute top-0 right-0 h-full w-px bg-gray-400 dark:bg-gray-500" />
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="flex flex-wrap gap-2 mb-6 justify-center">
                    <button @click="distributeEqually" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        Répartir également
                    </button>
                    <button v-if="averages && (Array.isArray(averages) ? averages.length : Object.keys(averages).length)" @click="useAverages" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Copier la moyenne citoyenne
                    </button>
                    <button @click="useGovAllocations" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Copier l'allocation État
                    </button>
                    <button @click="resetAll" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Remettre à zéro
                    </button>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1 mb-6 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded-sm bg-indigo-500/80" /> Votre allocation
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded-sm bg-amber-400/80" /> Budget État {{ budgetYear }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded-sm bg-gray-400/40 border border-dashed border-gray-400" /> Moyenne citoyenne
                    </span>
                </div>

                <!-- Sectors grid -->
                <div class="space-y-3">
                    <div
                        v-for="sector in sectors"
                        :key="sector.id"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5 transition-shadow"
                        :class="{ 'ring-2 ring-indigo-400/50 shadow-md': activeSlider === sector.id }"
                    >
                        <!-- Sector header -->
                        <div class="flex items-start sm:items-center justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-2xl flex-shrink-0">{{ sectorIcon(sector) }}</span>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base truncate">
                                        {{ sector.name }}
                                    </h3>
                                    <p v-if="sector.description" class="text-xs text-gray-500 dark:text-gray-400 truncate hidden sm:block">
                                        {{ sector.description }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Diff badge -->
                                <span v-if="diffLabel(sector.id)" class="text-xs font-medium hidden sm:inline" :class="diffLabel(sector.id).class">
                                    {{ diffLabel(sector.id).text }} vs État
                                </span>
                                <!-- Amount in Md€ -->
                                <span class="text-sm font-bold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">
                                    {{ sectorBudgetMd(sector.id) }}
                                    <span class="text-xs font-normal text-gray-500">Md€</span>
                                </span>
                            </div>
                        </div>

                        <!-- Slider + input row -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex-1 relative">
                                <input
                                    type="range"
                                    :min="0"
                                    :max="Math.min(parseFloat(sector.max_percent), 100)"
                                    step="0.5"
                                    :value="allocations[sector.id]"
                                    @input="onSliderInput(sector.id, $event)"
                                    @focus="activeSlider = sector.id"
                                    @blur="activeSlider = null"
                                    class="w-full h-2 rounded-full appearance-none cursor-pointer
                                           bg-gray-200 dark:bg-gray-700
                                           [&::-webkit-slider-thumb]:appearance-none
                                           [&::-webkit-slider-thumb]:w-5
                                           [&::-webkit-slider-thumb]:h-5
                                           [&::-webkit-slider-thumb]:rounded-full
                                           [&::-webkit-slider-thumb]:border-2
                                           [&::-webkit-slider-thumb]:border-white
                                           [&::-webkit-slider-thumb]:shadow-md
                                           [&::-webkit-slider-thumb]:cursor-grab
                                           [&::-webkit-slider-thumb]:active:cursor-grabbing
                                           [&::-moz-range-thumb]:w-5
                                           [&::-moz-range-thumb]:h-5
                                           [&::-moz-range-thumb]:rounded-full
                                           [&::-moz-range-thumb]:border-2
                                           [&::-moz-range-thumb]:border-white
                                           [&::-moz-range-thumb]:shadow-md
                                           [&::-moz-range-thumb]:cursor-grab
                                           [&::-moz-range-thumb]:active:cursor-grabbing
                                           accent-indigo-500"
                                    :style="sliderGradient(sector)"
                                />
                                <!-- Min/Max labels -->
                                <div class="flex justify-between text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 px-0.5">
                                    <span>{{ sector.min_percent }}% min</span>
                                    <span>{{ sector.max_percent }}% max</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <input
                                    type="number"
                                    :value="allocations[sector.id]"
                                    @change="onNumberInput(sector.id, $event)"
                                    @focus="activeSlider = sector.id"
                                    @blur="activeSlider = null"
                                    step="0.5"
                                    min="0"
                                    :max="sector.max_percent"
                                    class="w-16 sm:w-20 text-right text-sm font-semibold tabular-nums
                                           border border-gray-300 dark:border-gray-600
                                           bg-white dark:bg-gray-900
                                           text-gray-900 dark:text-white
                                           rounded-lg px-2 py-1.5
                                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                           transition"
                                />
                                <span class="text-sm font-medium text-gray-400">%</span>
                            </div>
                        </div>

                        <!-- Comparison bars -->
                        <div class="space-y-1.5">
                            <!-- User bar -->
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 w-10 text-right flex-shrink-0">Vous</span>
                                <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-700/50 rounded-full overflow-hidden relative">
                                    <div
                                        class="h-full rounded-full transition-all duration-200 ease-out flex items-center justify-end pr-1.5"
                                        :style="{
                                            width: Math.min(allocations[sector.id], 100) + '%',
                                            backgroundColor: sector.color || '#6366F1',
                                            minWidth: allocations[sector.id] > 0 ? '2rem' : '0'
                                        }"
                                    >
                                        <span v-if="allocations[sector.id] >= 3" class="text-[10px] font-bold text-white drop-shadow-sm">
                                            {{ parseFloat(allocations[sector.id]).toFixed(1) }}%
                                        </span>
                                    </div>
                                    <!-- Average marker -->
                                    <div
                                        v-if="avgPercent(sector.id) !== null && avgPercent(sector.id) > 0"
                                        class="absolute top-0 h-full w-0.5 border-l-2 border-dashed border-gray-400 dark:border-gray-500 opacity-60"
                                        :style="{ left: avgPercent(sector.id) + '%' }"
                                        :title="'Moyenne citoyenne: ' + avgPercent(sector.id) + '%'"
                                    />
                                </div>
                            </div>
                            <!-- Gov bar -->
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 w-10 text-right flex-shrink-0">État</span>
                                <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-700/50 rounded-full overflow-hidden">
                                    <div
                                        v-if="govPercent(sector.id) !== null"
                                        class="h-full bg-amber-400/80 dark:bg-amber-500/70 rounded-full transition-all duration-200 flex items-center justify-end pr-1.5"
                                        :style="{
                                            width: govPercent(sector.id) + '%',
                                            minWidth: govPercent(sector.id) > 0 ? '2rem' : '0'
                                        }"
                                    >
                                        <span v-if="govPercent(sector.id) >= 3" class="text-[10px] font-bold text-amber-900 dark:text-amber-100">
                                            {{ govPercent(sector.id) }}%
                                        </span>
                                    </div>
                                    <span v-else class="text-[10px] text-gray-400 dark:text-gray-500 ml-2 leading-4">
                                        Pas de données
                                    </span>
                                </div>
                                <span v-if="govBudgetMd(sector.id)" class="text-[10px] text-gray-400 dark:text-gray-500 flex-shrink-0 tabular-nums w-14 text-right">
                                    {{ govBudgetMd(sector.id) }} Md€
                                </span>
                                <span v-else class="w-14 flex-shrink-0" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit section -->
                <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-center sm:text-left">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <template v-if="isValid">
                                    Votre budget de <strong class="text-gray-900 dark:text-white">{{ totalBudgetMd }} Md€</strong> est prêt à être soumis.
                                </template>
                                <template v-else-if="isOverBudget">
                                    Vous avez dépassé les 100%. Réduisez certains secteurs.
                                </template>
                                <template v-else>
                                    Allouez encore <strong class="text-gray-900 dark:text-white">{{ remainingBudget.toFixed(1) }}%</strong>
                                    ({{ (remainingBudget / 100 * props.totalBudget / 1e9).toFixed(1) }} Md€) pour valider.
                                </template>
                            </p>
                            <p v-if="!isAuthenticated" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                <Link :href="route('login')" class="underline hover:no-underline">Connectez-vous</Link> pour enregistrer votre allocation.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                v-if="isAuthenticated && userAllocations && (Array.isArray(userAllocations) ? userAllocations.length : true)"
                                @click="doReset"
                                :disabled="form.processing"
                                class="px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50"
                            >
                                Réinitialiser
                            </button>
                            <button
                                v-if="isAuthenticated"
                                @click="submit"
                                :disabled="form.processing || !isValid"
                                class="px-6 py-2.5 text-sm font-semibold rounded-lg text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="isValid
                                    ? 'bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/25'
                                    : 'bg-gray-400 dark:bg-gray-600'"
                            >
                                <span v-if="form.processing">Enregistrement...</span>
                                <span v-else>Valider mon budget</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info cards -->
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ totalBudgetMd }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Milliards d'euros</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">Budget total {{ budgetYear }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats?.participating_citizens || 0 }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Citoyens ont voté</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ stats?.participation_rate || 0 }}% de participation
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ sectors.length }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Secteurs budgétaires</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            <Link :href="route('budget.stats')" class="text-indigo-600 dark:text-indigo-400 hover:underline">Voir les statistiques</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 3px solid #6366f1;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}
input[type="range"]:active::-webkit-slider-thumb {
    cursor: grabbing;
    transform: scale(1.2);
}
input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 3px solid #6366f1;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    cursor: grab;
}
input[type="range"]::-moz-range-track {
    height: 8px;
    border-radius: 9999px;
}

.dark input[type="range"]::-webkit-slider-thumb,
:is(.dark input[type="range"])::-webkit-slider-thumb {
    background: #1f2937;
    border-color: #818cf8;
}
.dark input[type="range"]::-moz-range-thumb,
:is(.dark input[type="range"])::-moz-range-thumb {
    background: #1f2937;
    border-color: #818cf8;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}
</style>
