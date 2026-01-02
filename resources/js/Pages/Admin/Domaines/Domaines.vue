<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    domaines: Array,
});

// Modal création
const showCreateModal = ref(false);
const createForm = useForm({
    nom: '',
    sigle: '',
    couleur: '#3b82f6',
    icone: '',
});

// Modal édition
const showEditModal = ref(false);
const editingDomaine = ref(null);
const editForm = useForm({
    nom: '',
    sigle: '',
    couleur: '',
    icone: '',
    description: '',
});

function openEdit(domaine) {
    editingDomaine.value = domaine;
    editForm.nom = domaine.nom;
    editForm.sigle = domaine.sigle || '';
    editForm.couleur = domaine.couleur || '#3b82f6';
    editForm.icone = domaine.icone || '';
    editForm.description = domaine.description || '';
    showEditModal.value = true;
}

function submitCreate() {
    createForm.post(route('admin.domaines.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function submitEdit() {
    editForm.put(route('admin.domaines.update', editingDomaine.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingDomaine.value = null;
        },
    });
}

function deleteDomaine(domaine) {
    if (confirm(`Supprimer le domaine "${domaine.nom}" ? Les ${domaine.postes_count} postes associés seront détachés.`)) {
        router.delete(route('admin.domaines.destroy', domaine.id));
    }
}

// Couleurs prédéfinies
const colorPresets = [
    '#dc2626', '#ea580c', '#d97706', '#ca8a04', '#65a30d', '#16a34a',
    '#059669', '#0d9488', '#0891b2', '#0284c7', '#2563eb', '#4f46e5',
    '#7c3aed', '#9333ea', '#c026d3', '#db2777', '#e11d48', '#64748b',
];
</script>

<template>
    <Head title="Gestion des Domaines Ministériels" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                ⚙️ Gestion des Domaines Ministériels
                            </h1>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                Créez et modifiez les grandes catégories de ministères
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <Link 
                                :href="route('admin.domaines.index')"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg"
                            >
                                ← Retour
                            </Link>
                            <button 
                                @click="showCreateModal = true"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2"
                            >
                                ➕ Nouveau domaine
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des domaines -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="domaine in domaines" 
                        :key="domaine.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4"
                        :style="{ borderLeftColor: domaine.couleur || '#6b7280' }"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <div 
                                        class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold"
                                        :style="{ backgroundColor: domaine.couleur || '#6b7280' }"
                                    >
                                        {{ domaine.sigle || domaine.nom.charAt(0) }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white">
                                            {{ domaine.nom }}
                                        </h3>
                                        <span class="text-sm text-gray-500">
                                            {{ domaine.postes_count }} postes
                                        </span>
                                    </div>
                                </div>
                                <p v-if="domaine.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ domaine.description }}
                                </p>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button 
                                    @click="openEdit(domaine)"
                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg"
                                    title="Modifier"
                                >
                                    ✏️
                                </button>
                                <button 
                                    @click="deleteDomaine(domaine)"
                                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                                    title="Supprimer"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Modal Création -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 mx-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Nouveau Domaine Ministériel
                    </h2>
                    
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom *
                            </label>
                            <input 
                                v-model="createForm.nom"
                                type="text"
                                required
                                placeholder="Ex: Transports"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sigle
                            </label>
                            <input 
                                v-model="createForm.sigle"
                                type="text"
                                maxlength="10"
                                placeholder="Ex: MT"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Couleur
                            </label>
                            <div class="flex items-center gap-3">
                                <input 
                                    v-model="createForm.couleur"
                                    type="color"
                                    class="w-12 h-10 rounded cursor-pointer"
                                />
                                <div class="flex flex-wrap gap-1">
                                    <button 
                                        v-for="color in colorPresets.slice(0, 9)" 
                                        :key="color"
                                        type="button"
                                        @click="createForm.couleur = color"
                                        class="w-6 h-6 rounded border-2 border-white shadow"
                                        :style="{ backgroundColor: color }"
                                    ></button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4">
                            <button 
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button 
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50"
                            >
                                Créer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
        
        <!-- Modal Édition -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 mx-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Modifier le Domaine
                    </h2>
                    
                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom *
                            </label>
                            <input 
                                v-model="editForm.nom"
                                type="text"
                                required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sigle
                            </label>
                            <input 
                                v-model="editForm.sigle"
                                type="text"
                                maxlength="10"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Couleur
                            </label>
                            <div class="flex items-center gap-3">
                                <input 
                                    v-model="editForm.couleur"
                                    type="color"
                                    class="w-12 h-10 rounded cursor-pointer"
                                />
                                <div class="flex flex-wrap gap-1">
                                    <button 
                                        v-for="color in colorPresets.slice(0, 9)" 
                                        :key="color"
                                        type="button"
                                        @click="editForm.couleur = color"
                                        class="w-6 h-6 rounded border-2 border-white shadow"
                                        :style="{ backgroundColor: color }"
                                    ></button>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea 
                                v-model="editForm.description"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4">
                            <button 
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button 
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
