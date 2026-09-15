<template>
    <div>
        <div :style="{ height: hauteur + 'px' }">
            <Bar :data="donnees" :options="options" />
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
            Chaque barre est empilée par candidat. Un thème dont la barre est faite d'un seul
            bloc n'est pas un thème débattu : c'est un thème porté par une seule personne, ou
            dépouillé plus finement que les autres.
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
    Tooltip,
} from 'chart.js';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

/**
 * Répartition thématique des mesures de la campagne.
 *
 * Barres horizontales : les libellés de thème sont longs (« Institutions & démocratie »)
 * et se liraient de travers sur un axe vertical. Empilement par candidat pour que la
 * composition d'un thème se voie en même temps que sa taille — c'est tout l'intérêt,
 * un total seul ne distingue pas un sujet consensuel d'un sujet mono-porté.
 */
const props = defineProps({
    themes: { type: Array, required: true },
    // Un seul candidat sélectionné : l'empilement n'a plus de sens, une seule série.
    monoCandidat: { type: Boolean, default: false },
});

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

// Trié par volume : la question posée est « quels sujets dominent », l'ordre du
// référentiel ne l'éclaire pas.
const tries = computed(() => [...props.themes].sort((a, b) => b.total - a.total));

const hauteur = computed(() => Math.max(320, tries.value.length * 34 + 60));

// Palette de repli quand un candidat n'a pas de couleur déclarée. Écrite en dur plutôt
// que générée : Tailwind ne purge pas ces valeurs (elles ne passent pas par une classe),
// mais une couleur choisie au hasard changerait à chaque rendu.
const REPLI = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#dc2626', '#0891b2',
    '#65a30d', '#db2777', '#4338ca', '#ea580c', '#0d9488', '#9333ea'];

// Un dataset par candidat, dans l'ordre de sa contribution totale : les gros
// contributeurs d'abord, pour que la légende se lise de haut en bas.
const candidats = computed(() => {
    const cumul = new Map();
    for (const t of props.themes) {
        for (const d of t.detail ?? []) {
            const e = cumul.get(d.candidat_id) ?? { id: d.candidat_id, nom: d.nom, couleur: d.couleur, n: 0 };
            e.n += d.n;
            cumul.set(d.candidat_id, e);
        }
    }

    return [...cumul.values()].sort((a, b) => b.n - a.n);
});

const donnees = computed(() => {
    const labels = tries.value.map((t) => t.nom);

    if (props.monoCandidat) {
        return {
            labels,
            datasets: [{
                label: 'Mesures',
                data: tries.value.map((t) => t.total),
                backgroundColor: '#2563eb',
                borderRadius: 2,
            }],
        };
    }

    return {
        labels,
        datasets: candidats.value.map((c, i) => ({
            label: c.nom,
            data: tries.value.map((t) => (t.detail ?? []).find((d) => d.candidat_id === c.id)?.n ?? 0),
            backgroundColor: c.couleur || REPLI[i % REPLI.length],
            stack: 'mesures',
            borderRadius: 2,
        })),
    };
});

const options = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            display: !props.monoCandidat,
            position: 'bottom',
            labels: { color: texte.value, boxWidth: 12, usePointStyle: true, padding: 12 },
        },
        tooltip: {
            // Un candidat sans mesure sur le thème survolé n'a rien à dire : sans ce
            // filtre, l'infobulle empilée liste douze lignes à zéro.
            filter: (item) => Number(item.raw) > 0,
            callbacks: {
                label: (item) => {
                    const t = tries.value[item.dataIndex];
                    const n = Number(item.raw);
                    const part = t?.total > 0 ? Math.round((n * 100) / t.total) : 0;

                    return props.monoCandidat
                        ? `${n} mesure${n > 1 ? 's' : ''}`
                        : `${item.dataset.label} : ${n} (${part} % du thème)`;
                },
                footer: (items) => {
                    const t = tries.value[items[0]?.dataIndex];

                    return t ? `Total : ${t.total} — ${t.candidats} candidat${t.candidats > 1 ? 's' : ''}` : '';
                },
            },
        },
    },
    scales: {
        x: {
            stacked: true,
            beginAtZero: true,
            grid: { color: grille.value },
            ticks: { color: texte.value, precision: 0 },
        },
        y: {
            stacked: true,
            grid: { display: false },
            ticks: { color: texte.value, autoSkip: false },
        },
    },
}));
</script>
