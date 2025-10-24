<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    regions: Array,
    departments: Array,
});

const form = useForm({
    title: '',
    description: '',
    type: 'debate',
    scope: 'national',
    region_id: null,
    department_id: null,
});

const filteredDepartments = ref([]);

const updateDepartments = () => {
    if (form.region_id) {
        filteredDepartments.value = props.departments.filter(
            dept => dept.region_id === form.region_id
        );
    } else {
        filteredDepartments.value = [];
    }
    form.department_id = null;
};

const submit = () => {
    form.post(route('topics.store'), {
        onSuccess: () => {
            // Redirection automatique vers le topic créé
        },
    });
};
</script>

<template>
    <Head title="Créer un sujet" />

    <MainLayout title="Créer un sujet">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            ➕ Créer un nouveau sujet
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Lancez un débat, proposez une idée ou posez une question à la communauté
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <InputLabel for="title" value="Titre *" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                placeholder="Ex: Faut-il développer les transports en commun ?"
                            />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description *" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required
                                placeholder="Décrivez votre sujet en détail..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Type -->
                        <div>
                            <InputLabel for="type" value="Type de sujet *" />
                            <select 
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required
                            >
                                <option value="debate">💬 Débat - Discussion ouverte</option>
                                <option value="proposal">💡 Proposition - Idée concrète</option>
                                <option value="question">❓ Question - Demande d'information</option>
                                <option value="announcement">📢 Annonce - Information publique</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <!-- Scope -->
                        <div>
                            <InputLabel for="scope" value="Portée géographique *" />
                            <select 
                                id="scope"
                                v-model="form.scope"
                                @change="form.region_id = null; form.department_id = null; filteredDepartments = []"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required
                            >
                                <option value="national">🇫🇷 National - Toute la France</option>
                                <option value="regional">🗺️ Régional - Une région spécifique</option>
                                <option value="departmental">📍 Départemental - Un département spécifique</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.scope" />
                        </div>

                        <!-- Region (if regional or departmental) -->
                        <div v-if="form.scope === 'regional' || form.scope === 'departmental'">
                            <InputLabel for="region" value="Région *" />
                            <select 
                                id="region"
                                v-model="form.region_id"
                                @change="updateDepartments"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required
                            >
                                <option :value="null">Sélectionnez une région</option>
                                <option v-for="region in regions" :key="region.id" :value="region.id">
                                    {{ region.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.region_id" />
                        </div>

                        <!-- Department (if departmental) -->
                        <div v-if="form.scope === 'departmental'">
                            <InputLabel for="department" value="Département *" />
                            <select 
                                id="department"
                                v-model="form.department_id"
                                :disabled="!form.region_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50"
                                required
                            >
                                <option :value="null">Sélectionnez un département</option>
                                <option v-for="department in filteredDepartments" :key="department.id" :value="department.id">
                                    {{ department.code }} - {{ department.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.department_id" />
                            <p v-if="!form.region_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Sélectionnez d'abord une région
                            </p>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Création...' : '✅ Créer le sujet' }}
                            </PrimaryButton>
                            <SecondaryButton type="button" @click="$inertia.visit(route('topics.index'))">
                                Annuler
                            </SecondaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </MainLayout>
</template>

