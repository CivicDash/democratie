<template>
    <FormulaireAnnuel
        slug="environnement"
        titre="Environnement"
        icone="🌱"
        description="Ãmissions, Ã©nergies, air, dÃ©chets, biodiversitÃ©"
        :annee="annee"
        :annees-disponibles="anneesDisponibles"
        :champs="champs"
        :data="data"
        route-show="admin.stats-france.environnement"
        route-update="admin.stats-france.environnement.update" />
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
