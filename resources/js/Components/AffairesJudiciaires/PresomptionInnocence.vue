<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-800 dark:text-blue-200">
        <p class="font-medium mb-1">Rappel juridique</p>
        <p>
            Conformément à l'article 9-1 du Code civil, toute personne a droit au respect
            de la présomption d'innocence. Une mise en examen ne constitue pas une condamnation.
            Seule une condamnation définitive établit la responsabilité pénale.
        </p>
        <p class="mt-1.5">
            Données vérifiées par l'équipe CivicDash à partir de sources publiques.
            Signaler une erreur :
            <a href="mailto:contact@civicdash.fr" class="underline hover:text-blue-900 dark:hover:text-blue-100">contact@civicdash.fr</a>
        </p>
        <p class="mt-1.5">
            <Link :href="route('transparence.demarche')" class="font-medium underline hover:text-blue-900 dark:hover:text-blue-100">
                Comment vérifions-nous ces informations ?
            </Link>
        </p>
    </div>
</template>
