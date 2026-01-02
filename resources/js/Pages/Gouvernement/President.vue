<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    president: Object,
    gouvernements: Array,
    gouvernementActuel: Object,
    presidents: Array,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: 'Président de la République', current: true, icon: '🏛️' },
];
</script>

<template>
    <Head title="Président de la République" />

    <AuthenticatedLayout>
        <!-- Hero Section avec le logo de la Présidence -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#28285a] via-[#1e1e4a] to-[#14143a]">
            <!-- Motif de fond -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                    <!-- Logo de la Présidence -->
                    <div class="flex items-center gap-8">
                        <div class="w-32 h-32 lg:w-40 lg:h-40 bg-white rounded-full p-3 shadow-2xl flex-shrink-0">
                            <img 
                                src="/images/Logo_de_la_présidence_de_la_République_(2018).svg"
                                alt="Présidence de la République française"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        
                        <div>
                            <p class="text-blue-300 text-sm font-medium mb-1">RÉPUBLIQUE FRANÇAISE</p>
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white tracking-tight">
                                Président de la République
                            </h1>
                            <p class="text-blue-200 text-lg mt-2">
                                Chef de l'État • Chef des armées
                            </p>
                        </div>
                    </div>

                    <!-- Lien vers le gouvernement -->
                    <Link 
                        :href="route('gouvernement.index')"
                        class="flex items-center gap-3 px-6 py-4 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-white transition group"
                    >
                        <span class="text-2xl">🏛️</span>
                        <div>
                            <div class="font-semibold">Voir le Gouvernement</div>
                            <div v-if="gouvernementActuel" class="text-sm text-blue-200">
                                {{ gouvernementActuel.premier_ministre }}
                            </div>
                        </div>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Fiche du Président -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card class="overflow-hidden">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Photo -->
                                <div class="md:w-48 flex-shrink-0">
                                    <img 
                                        v-if="president.photo"
                                        :src="president.photo" 
                                        :alt="president.nom"
                                        class="w-full md:w-48 h-60 object-cover rounded-xl shadow-lg"
                                    />
                                    <div v-else class="w-full md:w-48 h-60 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center text-6xl">
                                        👤
                                    </div>
                                </div>
                                
                                <!-- Infos -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 bg-[#28285a] text-white text-xs font-bold rounded-full">
                                            {{ president.mandat_numero }}e PRÉSIDENT
                                        </span>
                                        <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-full">
                                            EN FONCTION
                                        </span>
                                    </div>
                                    
                                    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                                        {{ president.nom }}
                                    </h2>
                                    
                                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Né le</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.date_naissance }} ({{ president.age }} ans)</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Lieu de naissance</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.lieu_naissance }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Profession</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.profession }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Parti politique</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.parti }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Début du mandat</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.debut_mandat }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Résidence officielle</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ president.residence }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <a 
                                            v-if="president.wikipedia"
                                            :href="president.wikipedia" 
                                            target="_blank"
                                            class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.09 13.119c-.936 1.932-2.217 4.548-2.853 5.728-.616 1.074-1.127.931-1.532.029-1.406-3.321-4.293-9.144-5.651-12.409-.251-.601-.441-.987-.619-1.139-.181-.15-.554-.24-1.122-.271C.103 5.033 0 4.982 0 4.898v-.455l.052-.045c.924-.005 5.401 0 5.401 0l.051.045v.434c0 .119-.075.176-.225.176l-.564.031c-.485.029-.727.164-.727.436 0 .135.053.33.166.601 1.082 2.646 4.818 10.521 4.818 10.521l2.681-5.476-2.322-5.168c-.256-.554-.477-.963-.619-1.083-.142-.117-.479-.192-1.009-.228-.158-.012-.237-.063-.237-.153v-.44l.05-.044h5.089l.05.044v.436c0 .119-.075.169-.225.169l-.468.017c-.54.021-.768.163-.768.443 0 .122.053.321.166.596 1.037 2.526 2.025 4.548 2.025 4.548l1.67-3.39-.787-1.661c-.279-.581-.519-.981-.661-1.101-.131-.119-.468-.192-.997-.228-.166-.012-.245-.063-.245-.153l.004-.44.049-.044h4.62l.049.044v.436c0 .119-.075.169-.225.169l-.46.017c-.548.021-.776.163-.776.443 0 .122.053.321.166.596l1.525 3.215 1.461-2.966c.064-.138.101-.296.101-.464 0-.303-.272-.467-.816-.496l-.553-.027c-.166-.012-.245-.063-.245-.153l.004-.44.049-.044h3.782l.049.044v.436c0 .119-.075.169-.225.169l-.347.017c-.706.036-1.154.446-1.691 1.426l-2.019 4.076 2.681 5.476s2.614-5.584 3.697-7.967c.113-.271.166-.467.166-.601 0-.272-.241-.407-.727-.436l-.563-.031c-.15 0-.225-.057-.225-.176v-.434l.051-.045s3.685-.005 4.609 0l.051.045v.455c0 .084-.103.135-.304.159-.568.031-.94.121-1.122.271-.179.152-.368.538-.619 1.139-1.358 3.265-4.245 9.088-5.651 12.409-.404.902-.916 1.045-1.532-.029-.636-1.18-1.917-3.796-2.853-5.728-.936 1.932-2.217 4.548-2.853 5.728-.616 1.074-1.127.931-1.532.029-.314-.742-.916-2.114-1.545-3.572l.186-.082 1.359 3.654z"/>
                                            </svg>
                                            Voir sur Wikipédia
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Liste des gouvernements -->
                        <Card>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-3">
                                <span class="text-2xl">🏛️</span>
                                Gouvernements sous cette présidence
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm rounded-full">
                                    {{ gouvernements.length }}
                                </span>
                            </h3>
                            
                            <div class="space-y-4">
                                <Link
                                    v-for="gouv in gouvernements"
                                    :key="gouv.id"
                                    :href="route('gouvernement.index', { gouvernement: gouv.id })"
                                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition group"
                                >
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <span v-if="gouv.actif" class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <h4 class="font-bold text-gray-900 dark:text-gray-100">
                                                {{ gouv.nom_complet || gouv.nom }}
                                            </h4>
                                            <span v-if="gouv.actif" class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs rounded-full">
                                                Actuel
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Premier ministre : <strong>{{ gouv.premier_ministre }}</strong>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ gouv.date_debut }}
                                            <span v-if="gouv.date_fin"> → {{ gouv.date_fin }}</span>
                                            • {{ gouv.duree }} • {{ gouv.nb_postes }} membres
                                        </p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Tous les présidents de la Ve République -->
                        <Card>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                📜 Présidents de la Ve République
                            </h3>
                            <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
                                <div 
                                    v-for="pres in presidents" 
                                    :key="pres.nom"
                                    :class="[
                                        'flex items-center gap-3 p-2.5 rounded-lg transition',
                                        pres.actuel 
                                            ? 'bg-[#28285a]/10 border-2 border-[#28285a]' 
                                            : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <img 
                                        :src="pres.photo" 
                                        :alt="pres.nom"
                                        class="w-11 h-11 rounded-full object-cover object-top shadow-sm"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm truncate">
                                            {{ pres.nom }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ pres.mandat }}</p>
                                    </div>
                                    <span v-if="pres.actuel" class="flex-shrink-0 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full">
                                        Actuel
                                    </span>
                                </div>
                            </div>
                        </Card>

                        <!-- Liens utiles -->
                        <Card>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                🔗 Liens officiels
                            </h3>
                            <div class="space-y-3">
                                <a 
                                    href="https://www.elysee.fr/" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <div class="w-10 h-10 bg-white rounded-lg p-1.5 shadow-sm">
                                        <img 
                                            src="/images/Logo_de_la_présidence_de_la_République_(2018).svg"
                                            alt="Élysée"
                                            class="w-full h-full object-contain"
                                        />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">Élysée</p>
                                        <p class="text-xs text-gray-500">Site officiel</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <a 
                                    href="https://www.info.gouv.fr/" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white text-lg">
                                        🏛️
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">Gouvernement</p>
                                        <p class="text-xs text-gray-500">info.gouv.fr</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </Card>

                        <!-- Rôle du Président -->
                        <Card>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                📖 Rôle du Président
                            </h3>
                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-3">
                                <p>
                                    Le Président de la République française est le chef de l'État. 
                                    Il est élu au suffrage universel direct pour un mandat de 5 ans.
                                </p>
                                <ul class="space-y-2">
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600">•</span>
                                        <span>Nomme le Premier ministre</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600">•</span>
                                        <span>Préside le Conseil des ministres</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600">•</span>
                                        <span>Chef des armées</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600">•</span>
                                        <span>Promulgue les lois</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600">•</span>
                                        <span>Peut dissoudre l'Assemblée nationale</span>
                                    </li>
                                </ul>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Source -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        🏛️ Source : 
                        <a href="https://www.elysee.fr" target="_blank" class="text-blue-600 hover:underline">
                            elysee.fr
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
