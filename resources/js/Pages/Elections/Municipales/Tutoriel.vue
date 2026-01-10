<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { ref } from 'vue';

const props = defineProps({
    etapes: Array,
    documents_requis: Array,
    conditions_eligibilite: Array,
    dates_cles: Object,
});

const etapeActive = ref(1);
const showEligibilityCheck = ref(false);

// Formulaire auto-évaluation éligibilité
const eligibilite = ref({
    nationalite: null,
    age: null,
    inscription: null,
    droits: null,
    tutelle: null,
});

const checkEligibilite = () => {
    showEligibilityCheck.value = true;
};

const estEligible = () => {
    return eligibilite.value.nationalite === true
        && eligibilite.value.age === true
        && eligibilite.value.inscription === true
        && eligibilite.value.droits === true
        && eligibilite.value.tutelle === true;
};

const toutesCasesRemplies = () => {
    return Object.values(eligibilite.value).every(v => v !== null);
};
</script>

<template>
    <Head title="Guide de candidature - Élections Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="relative bg-gradient-to-br from-indigo-900 via-purple-800 to-fuchsia-900 overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="dots-tuto" width="5" height="5" patternUnits="userSpaceOnUse">
                            <circle cx="2.5" cy="2.5" r="1" fill="currentColor"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#dots-tuto)" />
                </svg>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Link
                    :href="route('elections.municipales.index')"
                    class="inline-flex items-center gap-2 text-indigo-200 hover:text-white mb-6 transition"
                >
                    ← Retour aux municipales
                </Link>
                
                <div class="flex items-center gap-4 mb-4">
                    <span class="text-5xl">📋</span>
                    <Badge class="bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/30">
                        Guide officiel
                    </Badge>
                </div>
                
                <h1 class="text-3xl md:text-5xl font-bold text-white tracking-tight mb-4">
                    Comment déposer sa candidature
                </h1>
                
                <p class="text-xl text-indigo-200 max-w-3xl">
                    Suivez ce guide étape par étape pour vous présenter aux élections municipales 2026.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar gauche : Navigation étapes -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24">
                        <!-- Rappel dates -->
                        <div class="bg-amber-50 dark:bg-amber-900/30 rounded-xl p-5 border border-amber-200 dark:border-amber-700 mb-6">
                            <h3 class="font-bold text-amber-900 dark:text-amber-200 mb-3 flex items-center gap-2">
                                ⏰ Dates importantes
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex justify-between text-amber-800 dark:text-amber-300">
                                    <span>Limite dépôt</span>
                                    <span class="font-semibold">{{ dates_cles.limite_depot }}</span>
                                </li>
                                <li class="flex justify-between text-amber-800 dark:text-amber-300">
                                    <span>1er tour</span>
                                    <span class="font-semibold">{{ dates_cles.premier_tour }}</span>
                                </li>
                                <li class="flex justify-between text-amber-800 dark:text-amber-300">
                                    <span>2nd tour</span>
                                    <span class="font-semibold">{{ dates_cles.second_tour }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Navigation étapes -->
                        <nav class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Les étapes</h3>
                            <ul class="space-y-2">
                                <li v-for="etape in etapes" :key="etape.numero">
                                    <button
                                        @click="etapeActive = etape.numero"
                                        :class="[
                                            'w-full text-left px-3 py-2 rounded-lg transition flex items-center gap-3',
                                            etapeActive === etape.numero
                                                ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                                : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'
                                        ]"
                                    >
                                        <span class="text-lg">{{ etape.icone }}</span>
                                        <span class="text-sm font-medium">{{ etape.titre }}</span>
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Test éligibilité -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 md:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            ✅ Êtes-vous éligible ?
                        </h2>
                        
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Avant de vous lancer, vérifiez que vous remplissez les conditions d'éligibilité.
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="(condition, index) in conditions_eligibilite"
                                :key="index"
                                class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                            >
                                <div class="flex gap-2 flex-shrink-0">
                                    <button
                                        @click="eligibilite[['nationalite', 'age', 'inscription', 'droits', 'tutelle'][index]] = true"
                                        :class="[
                                            'w-8 h-8 rounded-full border-2 flex items-center justify-center transition',
                                            eligibilite[['nationalite', 'age', 'inscription', 'droits', 'tutelle'][index]] === true
                                                ? 'bg-green-500 border-green-500 text-white'
                                                : 'border-gray-300 dark:border-gray-600 hover:border-green-400'
                                        ]"
                                    >
                                        ✓
                                    </button>
                                    <button
                                        @click="eligibilite[['nationalite', 'age', 'inscription', 'droits', 'tutelle'][index]] = false"
                                        :class="[
                                            'w-8 h-8 rounded-full border-2 flex items-center justify-center transition',
                                            eligibilite[['nationalite', 'age', 'inscription', 'droits', 'tutelle'][index]] === false
                                                ? 'bg-red-500 border-red-500 text-white'
                                                : 'border-gray-300 dark:border-gray-600 hover:border-red-400'
                                        ]"
                                    >
                                        ✕
                                    </button>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300">{{ condition }}</p>
                            </div>
                        </div>

                        <div v-if="toutesCasesRemplies()" class="mt-6">
                            <div
                                v-if="estEligible()"
                                class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl p-6 text-center"
                            >
                                <span class="text-4xl">🎉</span>
                                <h3 class="text-xl font-bold text-green-700 dark:text-green-300 mt-2">
                                    Félicitations, vous êtes éligible !
                                </h3>
                                <p class="text-green-600 dark:text-green-400 mt-2">
                                    Vous pouvez vous présenter aux élections municipales.
                                </p>
                                <Link
                                    :href="route('elections.municipales.espace-candidat.index')"
                                    class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-500 transition"
                                >
                                    🚀 Créer ma liste
                                </Link>
                            </div>
                            <div
                                v-else
                                class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-6 text-center"
                            >
                                <span class="text-4xl">😔</span>
                                <h3 class="text-xl font-bold text-red-700 dark:text-red-300 mt-2">
                                    Vous n'êtes malheureusement pas éligible
                                </h3>
                                <p class="text-red-600 dark:text-red-400 mt-2">
                                    D'après vos réponses, vous ne remplissez pas toutes les conditions requises.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Étapes détaillées -->
                    <section
                        v-for="etape in etapes"
                        :key="etape.numero"
                        :id="`etape-${etape.numero}`"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 md:p-8"
                        :class="{ 'ring-2 ring-indigo-500': etapeActive === etape.numero }"
                    >
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-2xl flex-shrink-0">
                                {{ etape.icone }}
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <Badge class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                                        Étape {{ etape.numero }}
                                    </Badge>
                                    <span v-if="etape.duree" class="text-sm text-gray-500 dark:text-gray-400">
                                        ⏱️ {{ etape.duree }}
                                    </span>
                                    <span v-if="etape.date_limite" class="text-sm text-red-600 dark:text-red-400 font-medium">
                                        ⚠️ Avant le {{ etape.date_limite }}
                                    </span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ etape.titre }}
                                </h3>
                            </div>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ etape.description }}
                        </p>

                        <a
                            v-if="etape.lien"
                            :href="etape.lien"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 mt-4 text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                        >
                            📎 Télécharger le formulaire officiel ↗
                        </a>

                        <div v-if="etape.periode" class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                            <span class="text-indigo-700 dark:text-indigo-300 font-medium">
                                📅 Période : {{ etape.periode }}
                            </span>
                        </div>
                    </section>

                    <!-- Documents requis -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 md:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                            📁 Documents à préparer
                        </h2>

                        <div class="space-y-4">
                            <div
                                v-for="doc in documents_requis"
                                :key="doc.nom"
                                class="flex items-start gap-4 p-4 rounded-lg"
                                :class="doc.obligatoire ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-900/50'"
                            >
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl flex-shrink-0"
                                    :class="doc.obligatoire ? 'bg-red-100 dark:bg-red-900/50' : 'bg-gray-200 dark:bg-gray-700'"
                                >
                                    {{ doc.obligatoire ? '📋' : '📄' }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ doc.nom }}
                                        </h4>
                                        <Badge v-if="doc.obligatoire" class="bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 text-xs">
                                            Obligatoire
                                        </Badge>
                                        <Badge v-if="doc.pour_civicdash" class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-xs">
                                            Requis sur CivicDash
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ doc.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- CTA final -->
                    <section class="bg-gradient-to-br from-indigo-600 to-fuchsia-600 rounded-2xl p-8 text-center text-white">
                        <h2 class="text-2xl font-bold mb-4">
                            Prêt(e) à vous lancer ? 🚀
                        </h2>
                        <p class="text-indigo-100 mb-6">
                            Créez votre liste sur CivicDash et gagnez en visibilité auprès des électeurs de votre commune.
                        </p>
                        <Link
                            :href="route('elections.municipales.espace-candidat.index')"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl hover:bg-indigo-50 transition shadow-lg"
                        >
                            📝 Créer mon profil candidat
                        </Link>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
