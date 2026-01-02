<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const page = usePage();
const errors = computed(() => page.props.errors || {});

const props = defineProps({
    gouvernement: Object,
    postesParType: Object,
    ministeres: Array,
    personnes: Array,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Gouvernements', href: route('admin.gouvernement.index'), icon: '🏛️' },
    { label: props.gouvernement.nom, current: true },
];

// Modal d'ajout de poste
const showAddModal = ref(false);
const createNewPerson = ref(false);

const posteForm = useForm({
    personne_id: null,
    nouvelle_personne: {
        prenom: '',
        nom: '',
        civilite: null,
        parti_politique: '',
        photo_url: '',
    },
    fonction: '',
    type_fonction: 'ministre',
    ministere_id: null,
    ordre: 0,
    date_debut: null,
    date_fin: null,
});

const addPoste = () => {
    // Préparer les données à envoyer
    const data = {
        fonction: posteForm.fonction,
        type_fonction: posteForm.type_fonction,
        ministere_id: posteForm.ministere_id,
        ordre: posteForm.ordre,
        date_debut: posteForm.date_debut,
        date_fin: posteForm.date_fin,
    };

    // Ajouter soit personne_id, soit nouvelle_personne (pas les deux)
    if (createNewPerson.value) {
        data.nouvelle_personne = posteForm.nouvelle_personne;
    } else {
        data.personne_id = posteForm.personne_id;
    }

    // Utiliser router.post pour plus de contrôle
    router.post(route('admin.gouvernement.add-poste', props.gouvernement.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            posteForm.reset();
            createNewPerson.value = false;
            searchPersonne.value = '';
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);
        },
    });
};

// Édition inline d'un poste
const editingPoste = ref(null);
const editForm = useForm({});

const startEditPoste = (poste) => {
    editingPoste.value = poste.id;
    editForm.fonction = poste.fonction;
    editForm.type_fonction = poste.type_fonction;
    editForm.ministere_id = poste.ministere_id;
    editForm.ordre = poste.ordre;
    editForm.date_debut = poste.date_debut?.split('T')[0];
    editForm.date_fin = poste.date_fin?.split('T')[0];
    editForm.actif = poste.actif;
};

const savePoste = (poste) => {
    editForm.put(route('admin.gouvernement.update-poste', poste.id), {
        onSuccess: () => {
            editingPoste.value = null;
        },
    });
};

const deletePoste = (poste) => {
    const nom = poste.personne?.nom_complet || 'ce poste';
    if (confirm(`Supprimer ${nom} de ce gouvernement ?`)) {
        router.delete(route('admin.gouvernement.delete-poste', poste.id));
    }
};

const endPoste = (poste) => {
    if (confirm(`Terminer le poste de ${poste.personne?.nom_complet} ?`)) {
        router.post(route('admin.gouvernement.end-poste', poste.id));
    }
};

// Modal ministère
const showMinistereModal = ref(false);
const ministereForm = useForm({
    nom: '',
    sigle: '',
    site_web: '',
    couleur: '#3B82F6',
});

const addMinistere = () => {
    ministereForm.post(route('admin.gouvernement.store-ministere'), {
        onSuccess: () => {
            showMinistereModal.value = false;
            ministereForm.reset();
        },
    });
};

// Couleurs par type
const typeColors = {
    premier_ministre: 'bg-blue-600',
    ministre_etat: 'bg-indigo-600',
    ministre: 'bg-violet-600',
    ministre_delegue: 'bg-purple-600',
    secretaire_etat: 'bg-pink-600',
};

const typeLabels = {
    premier_ministre: 'Premier ministre',
    ministre_etat: 'Ministres d\'État',
    ministre: 'Ministres',
    ministre_delegue: 'Ministres délégués',
    secretaire_etat: 'Secrétaires d\'État',
};

// Partis politiques courants
const partis = [
    'Renaissance', 'LR', 'PS', 'MoDem', 'Horizons', 'EELV', 'PCF', 'RN', 'LFI', 'UDI', 'DVD', 'DVG', 'Sans étiquette'
];

// Filtrer les personnes pour l'autocomplete
const searchPersonne = ref('');
const filteredPersonnes = computed(() => {
    if (!searchPersonne.value) return props.personnes.slice(0, 20);
    const search = searchPersonne.value.toLowerCase();
    return props.personnes.filter(p => 
        p.nom.toLowerCase().includes(search) || 
        p.prenom.toLowerCase().includes(search)
    ).slice(0, 10);
});

const selectPersonne = (personne) => {
    posteForm.personne_id = personne.id;
    searchPersonne.value = `${personne.prenom} ${personne.nom}`;
};

// Stats
const totalPostes = computed(() => {
    return Object.values(props.postesParType).reduce((sum, arr) => sum + arr.length, 0);
});

// Modal d'édition du gouvernement
const showEditGouvernementModal = ref(false);
const gouvernementForm = useForm({
    nom: props.gouvernement.nom,
    numero: props.gouvernement.numero || '',
    suffixe: props.gouvernement.suffixe || '',
    premier_ministre: props.gouvernement.premier_ministre,
    president: props.gouvernement.president,
    date_debut: props.gouvernement.date_debut?.split('T')[0] || '',
    date_fin: props.gouvernement.date_fin?.split('T')[0] || '',
    actif: props.gouvernement.actif,
});

const updateGouvernement = () => {
    gouvernementForm.put(route('admin.gouvernement.update', props.gouvernement.id), {
        onSuccess: () => {
            showEditGouvernementModal.value = false;
        },
    });
};

const deleteGouvernement = () => {
    if (confirm(`Supprimer le gouvernement "${props.gouvernement.nom}" et tous ses postes associés ?`)) {
        router.delete(route('admin.gouvernement.destroy', props.gouvernement.id));
    }
};
</script>

<template>
    <Head :title="'Admin - ' + gouvernement.nom" />

    <AuthenticatedLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-white">
                                🏛️ {{ gouvernement.numero ? gouvernement.numero + 'ème - ' : '' }}{{ gouvernement.nom }}{{ gouvernement.suffixe ? ' ' + gouvernement.suffixe : '' }}
                            </h1>
                            <span 
                                v-if="gouvernement.actif"
                                class="px-3 py-1 bg-emerald-500 text-white text-sm font-bold rounded-full"
                            >
                                ACTIF
                            </span>
                        </div>
                        <p class="text-blue-200 mt-2">
                            Premier ministre : <strong class="text-white">{{ gouvernement.premier_ministre }}</strong>
                            • Depuis le {{ new Date(gouvernement.date_debut).toLocaleDateString('fr-FR') }}
                            • <strong class="text-white">{{ totalPostes }}</strong> membres
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="showEditGouvernementModal = true"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition"
                        >
                            ✏️ Modifier infos
                        </button>
                        <button
                            @click="showMinistereModal = true"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
                        >
                            🏢 Nouveau ministère
                        </button>
                        <a
                            :href="route('admin.gouvernement.export', gouvernement.id)"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
                            download
                        >
                            📥 Exporter JSON
                        </a>
                        <button
                            @click="showAddModal = true"
                            class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition"
                        >
                            ➕ Ajouter un membre
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Par type de fonction -->
                <div v-for="(postes, type) in postesParType" :key="type" class="space-y-4">
                    <h2 
                        v-if="postes.length > 0"
                        class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3"
                    >
                        <span :class="[typeColors[type], 'w-4 h-4 rounded-full']"></span>
                        {{ typeLabels[type] }}
                        <span class="text-sm font-normal text-gray-500">({{ postes.length }})</span>
                    </h2>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card 
                            v-for="poste in postes" 
                            :key="poste.id"
                            :class="[
                                'transition',
                                !poste.actif ? 'opacity-50' : ''
                            ]"
                        >
                            <!-- Mode affichage -->
                            <div v-if="editingPoste !== poste.id">
                                <div class="flex items-start gap-4">
                                    <img 
                                        v-if="poste.personne?.photo_url || poste.personne?.photo"
                                        :src="poste.personne?.photo_url || poste.personne?.photo" 
                                        :alt="poste.personne?.nom_complet"
                                        class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                                    />
                                    <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl">
                                        {{ poste.personne?.civilite === 'Mme' ? '👩' : '👨' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ poste.personne?.nom_complet || 'Non défini' }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ poste.fonction }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-2 mt-2">
                                            <span 
                                                v-if="poste.ministere"
                                                class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs rounded"
                                            >
                                                🏢 {{ poste.ministere.sigle || poste.ministere.nom }}
                                            </span>
                                            <span 
                                                v-if="poste.personne?.parti_politique"
                                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded"
                                            >
                                                {{ poste.personne.parti_politique }}
                                            </span>
                                            <span 
                                                v-if="!poste.actif"
                                                class="px-2 py-0.5 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 text-xs rounded"
                                            >
                                                Terminé
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            📅 {{ poste.duree_fonction }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        @click="startEditPoste(poste)"
                                        class="px-3 py-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded text-sm"
                                    >
                                        ✏️ Modifier
                                    </button>
                                    <button
                                        v-if="poste.actif"
                                        @click="endPoste(poste)"
                                        class="px-3 py-1 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded text-sm"
                                    >
                                        ⏹️ Terminer
                                    </button>
                                    <button
                                        @click="deletePoste(poste)"
                                        class="px-3 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-sm"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            <!-- Mode édition -->
                            <div v-else class="space-y-3">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ✏️ Modification du poste de {{ poste.personne?.nom_complet }}
                                </div>
                                <input
                                    v-model="editForm.fonction"
                                    type="text"
                                    placeholder="Fonction"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                />
                                <select
                                    v-model="editForm.type_fonction"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                >
                                    <option value="premier_ministre">Premier ministre</option>
                                    <option value="ministre_etat">Ministre d'État</option>
                                    <option value="ministre">Ministre</option>
                                    <option value="ministre_delegue">Ministre délégué(e)</option>
                                    <option value="secretaire_etat">Secrétaire d'État</option>
                                </select>
                                <select
                                    v-model="editForm.ministere_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                >
                                    <option :value="null">-- Aucun ministère --</option>
                                    <option v-for="m in ministeres" :key="m.id" :value="m.id">{{ m.nom }}</option>
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="editForm.date_debut"
                                        type="date"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    />
                                    <input
                                        v-model="editForm.date_fin"
                                        type="date"
                                        placeholder="Fin (vide si en cours)"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="editForm.actif"
                                        type="checkbox"
                                        id="edit-actif"
                                        class="rounded"
                                    />
                                    <label for="edit-actif" class="text-sm">En fonction</label>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button
                                        @click="editingPoste = null"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-sm"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        @click="savePoste(poste)"
                                        :disabled="editForm.processing"
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
                                    >
                                        Enregistrer
                                    </button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Message si aucun membre -->
                <Card v-if="totalPostes === 0" class="text-center py-12">
                    <div class="text-6xl mb-4">👔</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Aucun membre
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Ajoutez les membres du gouvernement
                    </p>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                    >
                        ➕ Ajouter un membre
                    </button>
                </Card>
            </div>
        </div>

        <!-- Modal d'ajout de poste -->
        <div 
            v-if="showAddModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showAddModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        ➕ Ajouter un membre au gouvernement
                    </h2>
                </div>
                <form @submit.prevent="addPoste" class="p-6 space-y-4">
                    
                    <!-- Affichage des erreurs -->
                    <div v-if="Object.keys(errors).length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-700 dark:text-red-300 mb-2">⚠️ Erreurs de validation :</h4>
                        <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">
                            <li v-for="(error, key) in errors" :key="key">• {{ error }}</li>
                        </ul>
                    </div>

                    <!-- Choix personne existante ou nouvelle -->
                    <div class="flex gap-4 mb-4">
                        <button
                            type="button"
                            @click="createNewPerson = false"
                            :class="[
                                'flex-1 py-2 px-4 rounded-lg border-2 transition',
                                !createNewPerson ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'
                            ]"
                        >
                            👤 Personne existante
                        </button>
                        <button
                            type="button"
                            @click="createNewPerson = true"
                            :class="[
                                'flex-1 py-2 px-4 rounded-lg border-2 transition',
                                createNewPerson ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'
                            ]"
                        >
                            ✨ Nouvelle personne
                        </button>
                    </div>

                    <!-- Sélection personne existante -->
                    <div v-if="!createNewPerson" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rechercher une personne</label>
                        <input
                            v-model="searchPersonne"
                            type="text"
                            placeholder="Tapez un nom..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            @focus="posteForm.personne_id = null"
                        />
                        <div v-if="filteredPersonnes.length && !posteForm.personne_id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                            <button
                                v-for="p in filteredPersonnes"
                                :key="p.id"
                                type="button"
                                @click="selectPersonne(p)"
                                class="w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3"
                            >
                                <img v-if="p.photo_url" :src="p.photo_url" class="w-8 h-8 rounded-full object-cover" />
                                <div v-else class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">👤</div>
                                <div>
                                    <div class="font-medium">{{ p.prenom }} {{ p.nom }}</div>
                                    <div class="text-xs text-gray-500">{{ p.parti_politique || 'Sans parti' }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Création nouvelle personne -->
                    <div v-else class="space-y-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Prénom *</label>
                                <input
                                    v-model="posteForm.nouvelle_personne.prenom"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Nom *</label>
                                <input
                                    v-model="posteForm.nouvelle_personne.nom"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    required
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Civilité</label>
                                <select
                                    v-model="posteForm.nouvelle_personne.civilite"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                >
                                    <option :value="null">--</option>
                                    <option value="M.">M.</option>
                                    <option value="Mme">Mme</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Parti politique</label>
                                <select
                                    v-model="posteForm.nouvelle_personne.parti_politique"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                >
                                    <option value="">Sans parti</option>
                                    <option v-for="parti in partis" :key="parti" :value="parti">{{ parti }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">URL photo</label>
                            <input
                                v-model="posteForm.nouvelle_personne.photo_url"
                                type="url"
                                placeholder="https://..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                            />
                        </div>
                    </div>

                    <hr class="my-4 border-gray-200 dark:border-gray-700" />

                    <!-- Infos du poste -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fonction *</label>
                        <input
                            v-model="posteForm.fonction"
                            type="text"
                            placeholder="Ex: Ministre de l'Économie"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                            <select
                                v-model="posteForm.type_fonction"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                                required
                            >
                                <option value="premier_ministre">Premier ministre</option>
                                <option value="ministre_etat">Ministre d'État</option>
                                <option value="ministre">Ministre</option>
                                <option value="ministre_delegue">Ministre délégué(e)</option>
                                <option value="secretaire_etat">Secrétaire d'État</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ministère</label>
                            <select
                                v-model="posteForm.ministere_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            >
                                <option :value="null">-- Aucun --</option>
                                <option v-for="m in ministeres" :key="m.id" :value="m.id">{{ m.nom }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="posteForm.processing"
                            class="px-6 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal création ministère -->
        <div 
            v-if="showMinistereModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showMinistereModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        🏢 Créer un ministère
                    </h2>
                </div>
                <form @submit.prevent="addMinistere" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                        <input
                            v-model="ministereForm.nom"
                            type="text"
                            placeholder="Ministère de..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            required
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sigle</label>
                            <input
                                v-model="ministereForm.sigle"
                                type="text"
                                placeholder="Ex: MINEFI"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Couleur</label>
                            <input
                                v-model="ministereForm.couleur"
                                type="color"
                                class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site web</label>
                        <input
                            v-model="ministereForm.site_web"
                            type="url"
                            placeholder="https://..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                        />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="showMinistereModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="ministereForm.processing"
                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal édition gouvernement -->
        <div 
            v-if="showEditGouvernementModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showEditGouvernementModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        ✏️ Modifier le gouvernement
                    </h2>
                </div>
                <form @submit.prevent="updateGouvernement" class="p-6 space-y-4">
                    <!-- Nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                        <input
                            v-model="gouvernementForm.nom"
                            type="text"
                            placeholder="Ex: Bayrou, Barnier..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Numéro -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numéro</label>
                            <input
                                v-model="gouvernementForm.numero"
                                type="number"
                                min="1"
                                max="100"
                                placeholder="48"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <!-- Suffixe -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suffixe</label>
                            <input
                                v-model="gouvernementForm.suffixe"
                                type="text"
                                placeholder="II, III..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <!-- Premier ministre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Premier ministre *</label>
                        <input
                            v-model="gouvernementForm.premier_ministre"
                            type="text"
                            placeholder="Prénom Nom"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required
                        />
                    </div>

                    <!-- Président -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Président de la République *</label>
                        <input
                            v-model="gouvernementForm.president"
                            type="text"
                            placeholder="Emmanuel Macron"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Date de début -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début *</label>
                            <input
                                v-model="gouvernementForm.date_debut"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                                required
                            />
                        </div>
                        <!-- Date de fin -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin</label>
                            <input
                                v-model="gouvernementForm.date_fin"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p class="text-xs text-gray-500 mt-1">Laisser vide si toujours en fonction</p>
                        </div>
                    </div>

                    <!-- Actif -->
                    <div class="flex items-center gap-3">
                        <input
                            v-model="gouvernementForm.actif"
                            type="checkbox"
                            id="gouvernement-actif"
                            class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                        />
                        <label for="gouvernement-actif" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Gouvernement actuellement en fonction
                        </label>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="deleteGouvernement"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            🗑️ Supprimer
                        </button>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="showEditGouvernementModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="gouvernementForm.processing"
                                class="px-6 py-2 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 disabled:opacity-50"
                            >
                                💾 Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
