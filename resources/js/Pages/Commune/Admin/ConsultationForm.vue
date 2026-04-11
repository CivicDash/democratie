<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    code_insee: String,
    consultation: Object,
});

const form = useForm({
    titre: props.consultation?.titre ?? '',
    description: props.consultation?.description ?? '',
    options: props.consultation?.options ?? [
        { key: 'option_1', label: '' },
        { key: 'option_2', label: '' },
    ],
    multiple: props.consultation?.multiple ?? false,
    publie: props.consultation?.publie ?? false,
    ferme_at: props.consultation?.ferme_at ?? '',
});

const addOption = () => {
    const key = `option_${form.options.length + 1}`;
    form.options.push({ key, label: '' });
};

const removeOption = (index) => {
    if (form.options.length <= 2) return;
    form.options.splice(index, 1);
};

const submit = () => {
    form.post(route('commune.admin.consultations.store', props.code_insee));
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nouvelle consultation</h1>
                <Link :href="route('commune.admin.consultations', code_insee)" class="text-sm text-slate-500 hover:text-blue-600">Retour</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre</label>
                    <input v-model="form.titre" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Ex: Faut-il amenager la place du marche ?" />
                    <p v-if="form.errors.titre" class="text-red-500 text-xs mt-1">{{ form.errors.titre }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Contexte et details de la consultation..." />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Options de vote</label>
                    <div class="space-y-2">
                        <div v-for="(opt, i) in form.options" :key="i" class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 font-mono w-6">{{ i + 1 }}.</span>
                            <input
                                v-model="opt.label"
                                type="text"
                                class="flex-1 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm"
                                :placeholder="`Option ${i + 1}`"
                            />
                            <button v-if="form.options.length > 2" type="button" @click="removeOption(i)" class="text-red-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" @click="addOption" class="mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium">+ Ajouter une option</button>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.multiple" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Choix multiples autorises</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.publie" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Publier immediatement</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de fin (optionnel)</label>
                    <input v-model="form.ferme_at" type="datetime-local" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm" />
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Creation...' : 'Creer la consultation' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
