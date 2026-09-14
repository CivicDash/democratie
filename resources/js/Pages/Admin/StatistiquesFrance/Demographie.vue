<template>
    <FormulaireAnnuel
        slug="demographie"
        titre="Démographie"
        icone="👥"
        description="Population, naissances, dÃ©cÃ¨s, espÃ©rance de vie"
        :annee="annee"
        :annees-disponibles="anneesDisponibles"
        :champs="champs"
        :data="data"
        route-show="admin.stats-france.demographie"
        route-update="admin.stats-france.demographie.update" />
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
