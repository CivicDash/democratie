<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evenements: { type: Array, default: () => [] },
    codeInsee: { type: String, required: true },
});

const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

const today = new Date();
const startOfWeek = new Date(today);
startOfWeek.setDate(today.getDate() - today.getDay() + 1);

const jours = computed(() => {
    const result = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(startOfWeek);
        d.setDate(startOfWeek.getDate() + i);
        const dateStr = d.toISOString().split('T')[0];

        const eventsForDay = props.evenements.filter(e => {
            if (!e.date_debut_iso) return false;
            return e.date_debut_iso.startsWith(dateStr);
        });

        result.push({
            date: d,
            jour: joursSemaine[d.getDay()],
            numero: d.getDate(),
            isToday: d.toDateString() === today.toDateString(),
            events: eventsForDay,
        });
    }
    return result;
});

const hasEvents = computed(() => props.evenements.length > 0);
</script>

<template>
    <div v-if="hasEvents" class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-5 bg-amber-500 rounded-full"></span>
            Cette semaine
        </h3>

        <div class="grid grid-cols-7 gap-1 mb-3">
            <div
                v-for="j in jours"
                :key="j.numero"
                class="text-center"
            >
                <div class="text-[10px] text-slate-400 mb-1">{{ j.jour }}</div>
                <div
                    class="w-8 h-8 mx-auto rounded-lg flex items-center justify-center text-xs font-medium transition-colors"
                    :class="{
                        'bg-blue-600 text-white': j.isToday,
                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300': !j.isToday && j.events.length > 0,
                        'text-slate-500 dark:text-slate-400': !j.isToday && j.events.length === 0,
                    }"
                >
                    {{ j.numero }}
                </div>
                <div v-if="j.events.length > 0" class="flex justify-center mt-1">
                    <span
                        v-for="n in Math.min(j.events.length, 3)"
                        :key="n"
                        class="w-1 h-1 rounded-full mx-0.5"
                        :class="j.isToday ? 'bg-blue-300' : 'bg-amber-400'"
                    />
                </div>
            </div>
        </div>

        <!-- Events list -->
        <div class="space-y-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
            <Link
                v-for="e in evenements.slice(0, 4)"
                :key="e.id"
                :href="route('commune.evenements.show', [codeInsee, e.slug])"
                class="flex items-center gap-2 py-1.5 text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors group"
            >
                <span class="text-xs text-slate-400 font-mono w-12 flex-shrink-0">{{ e.date_courte }}</span>
                <span class="text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">{{ e.titre }}</span>
            </Link>
        </div>

        <Link :href="route('commune.evenements.calendrier', codeInsee)" class="mt-3 block text-center text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
            Voir le calendrier complet
        </Link>
    </div>
</template>
