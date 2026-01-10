<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    liste: Object,
    autres_listes: Array,
});

const getSocialIcon = (reseau) => {
    const icons = {
        facebook: '📘',
        twitter: '🐦',
        instagram: '📷',
        youtube: '📺',
        tiktok: '🎵',
    };
    return icons[reseau] || '🔗';
};
</script>

<template>
    <Head :title="liste.nom_liste + ' - Municipales 2026'" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div
            class="relative overflow-hidden"
            :style="{ background: `linear-gradient(135deg, ${liste.couleur}dd, ${liste.couleur}99)` }"
        >
            <!-- Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="pattern-liste" width="8" height="8" patternUnits="userSpaceOnUse">
                            <circle cx="4" cy="4" r="1.5" fill="white"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#pattern-liste)" />
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Link
                    :href="route('elections.municipales.recherche', { q: liste.commune_nom })"
                    class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-6 transition"
                >
                    ← Retour à la recherche
                </Link>

                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Logo -->
                    <div
                        v-if="liste.logo_url"
                        class="w-24 h-24 md:w-32 md:h-32 rounded-2xl overflow-hidden bg-white shadow-xl flex-shrink-0"
                    >
                        <img :src="liste.logo_url" :alt="liste.nom_liste" class="w-full h-full object-cover" />
                    </div>
                    <div
                        v-else
                        class="w-24 h-24 md:w-32 md:h-32 rounded-2xl bg-white/20 shadow-xl flex-shrink-0 flex items-center justify-center text-5xl"
                    >
                        🏛️
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h1 class="text-3xl md:text-4xl font-bold text-white">
                                {{ liste.nom_liste }}
                            </h1>
                            <Badge
                                v-if="liste.nuance_politique"
                                class="bg-white/20 text-white border border-white/30"
                            >
                                {{ liste.nuance_politique }}
                            </Badge>
                        </div>

                        <p class="text-xl text-white/90 mb-4">
                            📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                        </p>

                        <p v-if="liste.slogan" class="text-lg text-white/80 italic mb-4">
                            "{{ liste.slogan }}"
                        </p>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3">
                            <a
                                v-if="liste.programme_pdf_url"
                                :href="liste.programme_pdf_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition"
                            >
                                📄 Télécharger le programme
                            </a>
                            <a
                                v-if="liste.site_web"
                                :href="liste.site_web"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-white/20 text-white font-semibold rounded-lg hover:bg-white/30 transition border border-white/30"
                            >
                                🌐 Site web
                            </a>
                            <a
                                v-for="(url, reseau) in liste.reseaux_sociaux"
                                :key="reseau"
                                :href="url"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition border border-white/30"
                            >
                                {{ getSocialIcon(reseau) }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Description -->
                    <section v-if="liste.description" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📝 Présentation</h2>
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                            {{ liste.description }}
                        </p>
                    </section>

                    <!-- Résumé programme -->
                    <section v-if="liste.resume_programme" class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-2xl border border-indigo-200 dark:border-indigo-700 p-6">
                        <h2 class="text-xl font-bold text-indigo-900 dark:text-indigo-200 mb-4">📋 Points clés du programme</h2>
                        <p class="text-indigo-800 dark:text-indigo-300 whitespace-pre-line">
                            {{ liste.resume_programme }}
                        </p>
                    </section>

                    <!-- Candidats -->
                    <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            👥 Les candidats ({{ liste.candidats.length }})
                        </h2>

                        <div class="space-y-4">
                            <!-- Tête de liste en premier -->
                            <div
                                v-for="candidat in liste.candidats"
                                :key="candidat.uuid"
                                class="flex items-center gap-4 p-4 rounded-xl transition"
                                :class="candidat.est_tete_de_liste
                                    ? 'bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30 border-2 border-amber-300 dark:border-amber-600'
                                    : 'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700'
                                "
                            >
                                <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400 font-mono text-sm w-8">
                                    #{{ candidat.position }}
                                </div>

                                <div
                                    v-if="candidat.photo_url"
                                    class="w-14 h-14 rounded-full overflow-hidden flex-shrink-0 shadow-md"
                                >
                                    <img :src="candidat.photo_url" :alt="candidat.nom_complet" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-14 h-14 rounded-full flex-shrink-0 flex items-center justify-center font-bold shadow-md"
                                    :class="candidat.est_tete_de_liste
                                        ? 'bg-amber-200 text-amber-800'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                    "
                                >
                                    {{ candidat.initiales }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ candidat.nom_complet }}
                                        </span>
                                        <Badge v-if="candidat.est_tete_de_liste" class="bg-amber-100 text-amber-700 text-xs">
                                            👑 Tête de liste
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ candidat.fonction_visee }}
                                    </p>
                                    <p v-if="candidat.profession" class="text-sm text-gray-500 dark:text-gray-500">
                                        💼 {{ candidat.profession }}
                                    </p>
                                </div>

                                <!-- Réseaux sociaux du candidat -->
                                <div v-if="Object.keys(candidat.reseaux_sociaux).length > 0" class="flex gap-2">
                                    <a
                                        v-for="(url, reseau) in candidat.reseaux_sociaux"
                                        :key="reseau"
                                        :href="url"
                                        target="_blank"
                                        class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 transition"
                                    >
                                        {{ getSocialIcon(reseau) }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contact -->
                    <section v-if="liste.email_contact || liste.site_web" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">📧 Contact</h3>
                        <div class="space-y-3 text-sm">
                            <a v-if="liste.email_contact" :href="'mailto:' + liste.email_contact" class="flex items-center gap-3 text-gray-600 dark:text-gray-400 hover:text-indigo-600 transition">
                                <span>📧</span>
                                <span class="truncate">{{ liste.email_contact }}</span>
                            </a>
                            <a v-if="liste.site_web" :href="liste.site_web" target="_blank" class="flex items-center gap-3 text-gray-600 dark:text-gray-400 hover:text-indigo-600 transition">
                                <span>🌐</span>
                                <span class="truncate">{{ liste.site_web }}</span>
                            </a>
                        </div>
                    </section>

                    <!-- Autres listes dans la commune -->
                    <section v-if="autres_listes.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                            🏛️ Autres listes à {{ liste.commune_nom }}
                        </h3>
                        <div class="space-y-3">
                            <Link
                                v-for="autre in autres_listes"
                                :key="autre.uuid"
                                :href="route('elections.municipales.liste', autre.uuid)"
                                class="block p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 transition group"
                            >
                                <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 transition">
                                    {{ autre.nom_liste }}
                                </h4>
                                <p v-if="autre.tete_de_liste" class="text-sm text-gray-500">
                                    👤 {{ autre.tete_de_liste }}
                                </p>
                            </Link>
                        </div>
                    </section>

                    <!-- Infos élection -->
                    <section class="bg-gradient-to-br from-fuchsia-50 to-purple-50 dark:from-fuchsia-900/30 dark:to-purple-900/30 rounded-2xl border border-fuchsia-200 dark:border-fuchsia-700 p-6">
                        <h3 class="font-bold text-fuchsia-900 dark:text-fuchsia-200 mb-4">📅 Dates du scrutin</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-fuchsia-700 dark:text-fuchsia-300">1er tour</span>
                                <span class="font-bold text-fuchsia-900 dark:text-fuchsia-200">15 mars 2026</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-fuchsia-700 dark:text-fuchsia-300">2nd tour</span>
                                <span class="font-bold text-fuchsia-900 dark:text-fuchsia-200">22 mars 2026</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
