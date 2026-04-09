<script setup>
import { Link, router } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    evenements: Array,
    mois_actuel: String,
});

const joursSemaine = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
const moisNoms = ['Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'];

const currentDate = computed(() => {
    const [y, m] = props.mois_actuel.split('-');
    return new Date(parseInt(y), parseInt(m) - 1, 1);
});

const moisLabel = computed(() => `${moisNoms[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`);

const prevMonth = computed(() => {
    const d = new Date(currentDate.value);
    d.setMonth(d.getMonth() - 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

const nextMonth = computed(() => {
    const d = new Date(currentDate.value);
    d.setMonth(d.getMonth() + 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

const naviguer = (mois) => {
    router.get(route('commune.evenements.calendrier', props.ville.code_insee), { mois }, { preserveState: true });
};

const joursCalendrier = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const premierJour = new Date(year, month, 1);
    const dernierJour = new Date(year, month + 1, 0);

    let startDay = premierJour.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const jours = [];

    for (let i = startDay - 1; i >= 0; i--) {
        const d = new Date(year, month, -i);
        jours.push({ date: d, moisCourant: false, evenements: [] });
    }

    for (let day = 1; day <= dernierJour.getDate(); day++) {
        const d = new Date(year, month, day);
        jours.push({ date: d, moisCourant: true, evenements: [] });
    }

    const rest = 42 - jours.length;
    for (let i = 1; i <= rest; i++) {
        const d = new Date(year, month + 1, i);
        jours.push({ date: d, moisCourant: false, evenements: [] });
    }

    props.evenements.forEach(evt => {
        const debut = new Date(evt.date_debut);
        const fin = evt.date_fin ? new Date(evt.date_fin) : debut;

        jours.forEach(jour => {
            const jourDate = jour.date;
            const jourDebut = new Date(jourDate.getFullYear(), jourDate.getMonth(), jourDate.getDate());
            const jourFin = new Date(jourDate.getFullYear(), jourDate.getMonth(), jourDate.getDate(), 23, 59, 59);

            if (debut <= jourFin && fin >= jourDebut) {
                jour.evenements.push(evt);
            }
        });
    });

    return jours;
});

const isToday = (date) => {
    const today = new Date();
    return date.getDate() === today.getDate() && date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear();
};

const selectedDay = ref(null);
const selectedEvents = computed(() => {
    if (!selectedDay.value) return [];
    return selectedDay.value.evenements;
});

const selectDay = (jour) => {
    if (jour.evenements.length) {
        selectedDay.value = selectedDay.value === jour ? null : jour;
    }
};

const categorieColors = {
    ceremonie: 'bg-amber-500',
    culture: 'bg-purple-500',
    sport: 'bg-green-500',
    marche: 'bg-teal-500',
    reunion: 'bg-indigo-500',
    atelier: 'bg-cyan-500',
    fete: 'bg-rose-500',
    environnement: 'bg-emerald-500',
    solidarite: 'bg-pink-500',
    autre: 'bg-slate-500',
};

const formatHeure = (iso) => {
    const d = new Date(iso);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" titre="Calendrier">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                <span class="w-1 h-7 bg-blue-600 rounded-full"></span>
                Calendrier municipal
            </h1>

            <!-- Navigation mois -->
            <div class="flex items-center justify-between mb-6">
                <button
                    @click="naviguer(prevMonth)"
                    class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <h2 class="text-xl font-bold text-slate-900 dark:text-white capitalize">{{ moisLabel }}</h2>

                <button
                    @click="naviguer(nextMonth)"
                    class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Grille calendrier -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Entete jours -->
                <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
                    <div v-for="jour in joursSemaine" :key="jour" class="text-center py-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        {{ jour }}
                    </div>
                </div>

                <!-- Jours -->
                <div class="grid grid-cols-7">
                    <button
                        v-for="(jour, idx) in joursCalendrier"
                        :key="idx"
                        @click="selectDay(jour)"
                        class="min-h-[80px] sm:min-h-[100px] p-1.5 border-b border-r border-slate-100 dark:border-slate-700/50 text-left transition-colors relative"
                        :class="{
                            'bg-white dark:bg-slate-800': jour.moisCourant,
                            'bg-slate-50 dark:bg-slate-900/30': !jour.moisCourant,
                            'ring-2 ring-blue-500 ring-inset z-10': selectedDay === jour,
                            'cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/10': jour.evenements.length,
                            'cursor-default': !jour.evenements.length,
                        }"
                    >
                        <span
                            class="text-sm font-medium inline-flex items-center justify-center w-7 h-7 rounded-full"
                            :class="{
                                'text-slate-900 dark:text-white': jour.moisCourant,
                                'text-slate-400 dark:text-slate-600': !jour.moisCourant,
                                'bg-blue-600 text-white': isToday(jour.date),
                            }"
                        >
                            {{ jour.date.getDate() }}
                        </span>

                        <!-- Pastilles evenements -->
                        <div class="mt-0.5 space-y-0.5">
                            <div
                                v-for="evt in jour.evenements.slice(0, 2)"
                                :key="evt.id"
                                class="text-xs px-1.5 py-0.5 rounded truncate font-medium"
                                :class="[
                                    categorieColors[evt.categorie] || 'bg-slate-500',
                                    'text-white',
                                    evt.annule ? 'line-through opacity-50' : '',
                                ]"
                            >
                                {{ evt.titre }}
                            </div>
                            <div v-if="jour.evenements.length > 2" class="text-xs text-slate-400 dark:text-slate-500 px-1">
                                +{{ jour.evenements.length - 2 }} autres
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Detail jour selectionne -->
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedDay && selectedEvents.length" class="mt-6 space-y-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ selectedDay.date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </h3>

                    <Link
                        v-for="evt in selectedEvents"
                        :key="evt.id"
                        :href="route('commune.evenements.show', [ville.code_insee, evt.slug])"
                        class="block bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 group"
                    >
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" :class="categorieColors[evt.categorie] || 'bg-slate-500'" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" :class="{ 'line-through opacity-50': evt.annule }">
                                        {{ evt.titre }}
                                    </span>
                                    <span v-if="evt.annule" class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Annule</span>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 flex flex-wrap gap-3">
                                    <span>{{ evt.journee_entiere ? 'Journee entiere' : formatHeure(evt.date_debut) }}</span>
                                    <span v-if="evt.lieu_nom">📍 {{ evt.lieu_nom }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">{{ evt.categorie_label }}</span>
                                    <span v-if="evt.inscription_requise && evt.est_complet" class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Complet</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </Transition>

            <!-- Lien retour -->
            <div class="mt-8 text-center">
                <Link :href="route('commune.evenements', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                    Voir tous les evenements en liste
                </Link>
            </div>
        </div>
    </CommuneLayout>
</template>
