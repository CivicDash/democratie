<template>
    <FormulaireAnnuel
        slug="sante"
        titre="Santé"
        icone="⚕️"
        description="DÃ©mographie mÃ©dicale, dÃ©penses, prÃ©vention"
        :annee="annee"
        :annees-disponibles="anneesDisponibles"
        :champs="champs"
        :data="data"
        route-show="admin.stats-france.sante"
        route-update="admin.stats-france.sante.update" />
</template>

<script setup>
import FormulaireAnnuel from '@/Pages/Admin/StatistiquesFrance/Partials/FormulaireAnnuel.vue';

// Les champs viennent du serveur, qui les tire des colonnes de la table : c'est ce qui
// empêche le formulaire de dériver à nouveau du schéma.
defineProps({
    annee: [Number, String],
    anneesDisponibles: Array,
    champs: Array,
    data: Object,
});
</script>
