<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    gouvernement: Object,
    ministresParType: Object,
    ministeres: Array,
});

const breadcrumbs = [
    { label: 'Admin', href: route('admin.dashboard'), icon: '⚙️' },
    { label: 'Gouvernements', href: route('admin.gouvernement.index'), icon: '🏛️' },
    { label: props.gouvernement.nom, current: true },
];

// Modal d'ajout de ministre
const showAddModal = ref(false);
const ministereForm = useForm({
    prenom: '',
    nom: '',
    fonction: '',
    type_fonction: 'ministre',
    ministere_id: null,
    parti_politique: '',
    photo_url: '',
    sexe: null,
});

const addMinistre = () => {
    ministereForm.post(route('admin.gouvernement.add-ministre', props.gouvernement.id), {
        onSuccess: () => {
            showAddModal.value = false;
            ministereForm.reset();
        },
    });
};

// Édition inline d'un ministre
const editingMinistre = ref(null);
const editForm = useForm({});

const startEdit = (ministre) => {
    editingMinistre.value = ministre.id;
    editForm.prenom = ministre.prenom;
    editForm.nom = ministre.nom;
    editForm.fonction = ministre.fonction;
    editForm.type_fonction = ministre.type_fonction;
    editForm.ministere_id = ministre.ministere_id;
    editForm.parti_politique = ministre.parti_politique;
    editForm.photo_url = ministre.photo_url;
    editForm.sexe = ministre.sexe;
    editForm.actif = ministre.actif;
};

const saveEdit = (ministre) => {
    editForm.put(route('admin.gouvernement.update-ministre', ministre.id), {
        onSuccess: () => {
            editingMinistre.value = null;
        },
    });
};

const deleteMinistre = (ministre) => {
    if (confirm(`Supprimer ${ministre.prenom} ${ministre.nom} ?`)) {
        router.delete(route('admin.gouvernement.delete-ministre', ministre.id));
    }
};

// Couleurs par type
const typeColors = {
    premier_ministre: 'bg-blue-600',
    ministre: 'bg-indigo-600',
    ministre_delegue: 'bg-purple-600',
    secretaire_etat: 'bg-pink-600',
};

const typeLabels = {
    premier_ministre: 'Premier ministre',
    ministre: 'Ministres',
    ministre_delegue: 'Ministres délégués',
    secretaire_etat: 'Secrétaires d\'État',
};

// Partis politiques courants
const partis = [
    'Renaissance', 'LR', 'PS', 'MoDem', 'Horizons', 'EELV', 'PCF', 'RN', 'LFI', 'UDI', 'Sans étiquette'
];
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
                                🏛️ {{ gouvernement.nom }}
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
                            • Depuis le {{ gouvernement.date_debut }}
                        </p>
                    </div>
                    <div class="flex gap-3">
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
                            ➕ Ajouter un ministre
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Par type de fonction -->
                <div v-for="(ministres, type) in ministresParType" :key="type" class="space-y-4">
                    <h2 
                        v-if="ministres.length > 0"
                        class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3"
                    >
                        <span :class="[typeColors[type], 'w-4 h-4 rounded-full']"></span>
                        {{ typeLabels[type] }}
                        <span class="text-sm font-normal text-gray-500">({{ ministres.length }})</span>
                    </h2>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card 
                            v-for="ministre in ministres" 
                            :key="ministre.id"
                            :class="[
                                'transition',
                                !ministre.actif ? 'opacity-50' : ''
                            ]"
                        >
                            <!-- Mode affichage -->
                            <div v-if="editingMinistre !== ministre.id">
                                <div class="flex items-start gap-4">
                                    <img 
                                        v-if="ministre.photo_url"
                                        :src="ministre.photo_url" 
                                        :alt="ministre.prenom + ' ' + ministre.nom"
                                        class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                                    />
                                    <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl">
                                        {{ ministre.sexe === 'F' ? '👩' : '👨' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ ministre.prenom }} {{ ministre.nom }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ ministre.fonction }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span 
                                                v-if="ministre.parti_politique"
                                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded"
                                            >
                                                {{ ministre.parti_politique }}
                                            </span>
                                            <span 
                                                v-if="!ministre.actif"
                                                class="px-2 py-0.5 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 text-xs rounded"
                                            >
                                                Inactif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        @click="startEdit(ministre)"
                                        class="px-3 py-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded text-sm"
                                    >
                                        ✏️ Modifier
                                    </button>
                                    <button
                                        @click="deleteMinistre(ministre)"
                                        class="px-3 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-sm"
                                    >
                                        🗑️ Supprimer
                                    </button>
                                </div>
                            </div>

                            <!-- Mode édition -->
                            <div v-else class="space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="editForm.prenom"
                                        type="text"
                                        placeholder="Prénom"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    />
                                    <input
                                        v-model="editForm.nom"
                                        type="text"
                                        placeholder="Nom"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                    />
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
                                    <option value="ministre">Ministre</option>
                                    <option value="ministre_delegue">Ministre délégué(e)</option>
                                    <option value="secretaire_etat">Secrétaire d'État</option>
                                </select>
                                <select
                                    v-model="editForm.parti_politique"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                >
                                    <option value="">Sans parti</option>
                                    <option v-for="parti in partis" :key="parti" :value="parti">{{ parti }}</option>
                                </select>
                                <input
                                    v-model="editForm.photo_url"
                                    type="url"
                                    placeholder="URL de la photo"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
                                />
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="editForm.actif"
                                        type="checkbox"
                                        id="edit-actif"
                                        class="rounded"
                                    />
                                    <label for="edit-actif" class="text-sm">Actif</label>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button
                                        @click="editingMinistre = null"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-sm"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        @click="saveEdit(ministre)"
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

                <!-- Message si aucun ministre -->
                <Card v-if="Object.values(ministresParType).every(m => m.length === 0)" class="text-center py-12">
                    <div class="text-6xl mb-4">👔</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Aucun ministre
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Ajoutez les membres du gouvernement
                    </p>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                    >
                        ➕ Ajouter un ministre
                    </button>
                </Card>
            </div>
        </div>

        <!-- Modal d'ajout -->
        <div 
            v-if="showAddModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showAddModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        ➕ Ajouter un ministre
                    </h2>
                </div>
                <form @submit.prevent="addMinistre" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom *</label>
                            <input
                                v-model="ministereForm.prenom"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="ministereForm.nom"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fonction *</label>
                        <input
                            v-model="ministereForm.fonction"
                            type="text"
                            placeholder="Ex: Ministre de l'Économie"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de fonction *</label>
                        <select
                            v-model="ministereForm.type_fonction"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            required
                        >
                            <option value="premier_ministre">Premier ministre</option>
                            <option value="ministre">Ministre</option>
                            <option value="ministre_delegue">Ministre délégué(e)</option>
                            <option value="secretaire_etat">Secrétaire d'État</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parti politique</label>
                            <select
                                v-model="ministereForm.parti_politique"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Sans parti</option>
                                <option v-for="parti in partis" :key="parti" :value="parti">{{ parti }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexe</label>
                            <select
                                v-model="ministereForm.sexe"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option :value="null">Non précisé</option>
                                <option value="M">Homme</option>
                                <option value="F">Femme</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL de la photo</label>
                        <input
                            v-model="ministereForm.photo_url"
                            type="url"
                            placeholder="https://..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
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
                            :disabled="ministereForm.processing"
                            class="px-6 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
