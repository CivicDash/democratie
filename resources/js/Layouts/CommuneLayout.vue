<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    ville: Object,
    page: { type: Object, default: () => ({}) },
    titre: { type: String, default: '' },
    seo: { type: Object, default: () => ({}) },
});

const communePage = computed(() => usePage().props.communePage ?? props.page);
const villeData = computed(() => props.ville ?? {});

const pageSeo = computed(() => usePage().props.seo ?? props.seo ?? {});
const seoTitle = computed(() => pageSeo.value.title || `${props.titre || villeData.value.nom} - Hub Citoyen`);
const seoDesc = computed(() => pageSeo.value.description || null);
const seoImage = computed(() => pageSeo.value.image || null);
const seoUrl = computed(() => pageSeo.value.url || null);
const seoType = computed(() => pageSeo.value.type || 'website');

const navItems = computed(() => {
    const items = [
        { label: 'Accueil', route: route('commune.index', villeData.value.code_insee), icon: 'home' },
        { label: 'Elus', route: route('commune.elus', villeData.value.code_insee), icon: 'users' },
        { label: 'Budget', route: route('commune.budget', villeData.value.code_insee), icon: 'chart' },
        { label: 'Elections', route: route('commune.elections', villeData.value.code_insee), icon: 'vote' },
    ];

    const fonc = communePage.value?.fonctionnalites ?? props.page?.fonctionnalites ?? {};

    if (fonc.actus) {
        items.splice(1, 0, { label: 'Actualites', route: route('commune.actualites', villeData.value.code_insee), icon: 'news' });
    }
    if (fonc.evenements) {
        items.splice(2, 0, { label: 'Evenements', route: route('commune.evenements', villeData.value.code_insee), icon: 'calendar' });
        items.splice(3, 0, { label: 'Calendrier', route: route('commune.evenements.calendrier', villeData.value.code_insee), icon: 'calendar-month' });
    }
    items.push({ label: 'Consultations', route: route('commune.consultations', villeData.value.code_insee), icon: 'poll' });

    if (fonc.forum) {
        items.push({ label: 'Forum', route: route('commune.forum', villeData.value.code_insee), icon: 'chat' });
    }

    items.push({ label: 'FAQ', route: route('commune.faq', villeData.value.code_insee), icon: 'help' });

    return items;
});

const mobileMenuOpen = ref(false);
const currentUrl = computed(() => usePage().url);

const jsonLd = computed(() => {
    const s = pageSeo.value;
    const v = villeData.value;
    const schemas = [];

    schemas.push({
        '@context': 'https://schema.org',
        '@type': 'GovernmentOrganization',
        name: `Mairie de ${v.nom || ''}`,
        url: s.site_officiel || s.url,
        logo: s.logo || undefined,
        telephone: s.telephone || undefined,
        email: s.email || undefined,
        address: s.adresse ? {
            '@type': 'PostalAddress',
            streetAddress: s.adresse,
            addressLocality: v.nom,
            postalCode: s.code_postal || v.code_postal,
            addressCountry: 'FR',
        } : undefined,
        areaServed: {
            '@type': 'Place',
            name: v.nom,
            geo: (s.latitude && s.longitude) ? {
                '@type': 'GeoCoordinates',
                latitude: s.latitude,
                longitude: s.longitude,
            } : undefined,
        },
    });

    schemas.push({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'CivicDash', item: window.location.origin },
            { '@type': 'ListItem', position: 2, name: 'Communes', item: `${window.location.origin}/villes` },
            { '@type': 'ListItem', position: 3, name: v.nom || '', item: s.url },
        ],
    });

    return schemas;
});
</script>

<template>
    <Head :title="seoTitle">
        <meta v-if="seoDesc" name="description" :content="seoDesc" />
        <meta property="og:title" :content="seoTitle" />
        <meta v-if="seoDesc" property="og:description" :content="seoDesc" />
        <meta v-if="seoImage" property="og:image" :content="seoImage" />
        <meta v-if="seoUrl" property="og:url" :content="seoUrl" />
        <meta property="og:type" :content="seoType" />
        <meta property="og:locale" content="fr_FR" />
        <meta property="og:site_name" content="CivicDash" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoTitle" />
        <meta v-if="seoDesc" name="twitter:description" :content="seoDesc" />
        <meta v-if="seoImage" name="twitter:image" :content="seoImage" />
        <link v-if="seoUrl" rel="canonical" :href="seoUrl" />
    </Head>

    <component
        v-for="(schema, idx) in jsonLd"
        :key="idx"
        :is="'script'"
        type="application/ld+json"
        v-text="JSON.stringify(schema)"
    />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
        <!-- Header commune -->
        <header class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo + nom commune -->
                    <Link :href="route('commune.index', villeData.code_insee)" class="flex items-center gap-3 group">
                        <div
                            v-if="page?.logo_url || villeData.blason_url"
                            class="w-9 h-9 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600"
                        >
                            <img :src="page?.logo_url || villeData.blason_url" :alt="villeData.nom" class="w-full h-full object-contain" />
                        </div>
                        <div v-else class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-sm" :style="{ background: page?.couleur_primaire || '#1e40af' }">
                            {{ villeData.nom?.charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ villeData.nom }}
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400 -mt-0.5">{{ villeData.departement_nom }}</p>
                        </div>
                    </Link>

                    <!-- Nav desktop -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.label"
                            :href="item.route"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="currentUrl.startsWith(item.route)
                                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <Link :href="route('villes.show', villeData.slug)" class="text-xs text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 hidden sm:block">
                            Fiche complete
                        </Link>

                        <!-- Mobile menu button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile nav -->
                <nav v-if="mobileMenuOpen" class="md:hidden pb-3 border-t border-slate-200 dark:border-slate-700 pt-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="item.route"
                        class="block px-3 py-2 rounded-lg text-sm font-medium"
                        :class="currentUrl.startsWith(item.route)
                            ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Contenu principal -->
        <main>
            <slot />
        </main>

        <!-- Footer commune -->
        <footer class="bg-white/80 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span>Page commune propulsee par</span>
                        <Link href="/" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">CivicDash</Link>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span v-if="page?.vues_totales">{{ page.vues_totales.toLocaleString('fr-FR') }} vues</span>
                        <span v-if="page?.abonnes_count">{{ page.abonnes_count }} abonnes</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
