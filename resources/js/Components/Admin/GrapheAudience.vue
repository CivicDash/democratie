<template>
    <div>
        <div class="h-64">
            <Bar :data="donnees" :options="options" />
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Les jours sans trafic apparaissent à zéro plutôt que d'être omis : une coupure de
            journalisation doit se voir, pas se confondre avec une baisse de fréquentation.
        </p>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend,
    LineController, LineElement, PointElement);

/**
 * Fréquentation quotidienne d'objectif2027.fr.
 *
 * Barres empilées pour les vues — humaines et robots séparés, puisque c'est toute la
 * question : savoir si le site est lu par des gens. Une ligne superposée pour
 * l'estimation de visiteurs distincts, qui n'est pas de même nature qu'un décompte de
 * vues et ne doit donc pas partager le même type de tracé.
 */
const props = defineProps({
    parJour: { type: Array, required: true },
});

// Le thème est piloté par une classe sur <html> : les couleurs d'axes doivent suivre.
const sombre = ref(false);
let observateur = null;

onMounted(() => {
    const racine = document.documentElement;
    const lire = () => { sombre.value = racine.classList.contains('dark'); };
    lire();
    observateur = new MutationObserver(lire);
    observateur.observe(racine, { attributes: true, attributeFilter: ['class'] });
});

onBeforeUnmount(() => observateur?.disconnect());

const grille = computed(() => (sombre.value ? 'rgba(148,163,184,0.18)' : 'rgba(100,116,139,0.15)'));
const texte = computed(() => (sombre.value ? '#cbd5e1' : '#475569'));

const etiquettes = computed(() => props.parJour.map((j) => new Date(j.jour + 'T12:00:00')
    .toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })));

const donnees = computed(() => ({
    labels: etiquettes.value,
    datasets: [
        {
            type: 'bar',
            label: 'Vues humaines',
            data: props.parJour.map((j) => Number(j.humains)),
            backgroundColor: '#2563eb',
            stack: 'vues',
            borderRadius: 2,
            order: 2,
        },
        {
            type: 'bar',
            label: 'Vues de robots',
            data: props.parJour.map((j) => Number(j.bots)),
            backgroundColor: sombre.value ? '#475569' : '#cbd5e1',
            stack: 'vues',
            borderRadius: 2,
            order: 3,
        },
        {
            type: 'line',
            label: 'Visiteurs distincts (estimation)',
            data: props.parJour.map((j) => Number(j.visiteurs ?? 0)),
            borderColor: '#059669',
            backgroundColor: '#059669',
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            tension: 0.25,
            order: 1,
        },
    ],
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: texte.value, boxWidth: 12, usePointStyle: true, padding: 16 },
        },
        tooltip: {
            callbacks: {
                title: (items) => {
                    const jour = props.parJour[items[0].dataIndex]?.jour;
                    return jour
                        ? new Date(jour + 'T12:00:00').toLocaleDateString('fr-FR',
                            { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
                        : '';
                },
                label: (item) => `${item.dataset.label} : ${Number(item.raw).toLocaleString('fr-FR')}`,
            },
        },
    },
    scales: {
        x: {
            stacked: true,
            grid: { display: false },
            ticks: { color: texte.value, maxRotation: 0, autoSkipPadding: 16 },
        },
        y: {
            stacked: true,
            beginAtZero: true,
            grid: { color: grille.value },
            ticks: { color: texte.value, precision: 0 },
        },
    },
}));
</script>
