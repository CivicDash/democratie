<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    city_name: props.profile?.city_name || '',
    postal_code: props.profile?.postal_code || '',
    circonscription: props.profile?.circonscription || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                📍 Ma Localisation
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Renseignez votre localisation pour découvrir vos représentants politiques (député et sénateurs).
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Ville -->
                <div>
                    <InputLabel for="city_name" value="Ville / Commune" />

                    <TextInput
                        id="city_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.city_name"
                        placeholder="Ex: Paris, Lyon, Marseille..."
                    />

                    <InputError class="mt-2" :message="form.errors.city_name" />
                </div>

                <!-- Code postal -->
                <div>
                    <InputLabel for="postal_code" value="Code Postal" />

                    <TextInput
                        id="postal_code"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.postal_code"
                        placeholder="Ex: 75001"
                        maxlength="5"
                    />

                    <InputError class="mt-2" :message="form.errors.postal_code" />
                </div>
            </div>

            <!-- Circonscription -->
            <div>
                <InputLabel for="circonscription" value="Circonscription Législative" />

                <TextInput
                    id="circonscription"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.circonscription"
                    placeholder="Ex: 75-01 (Département-Numéro)"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    💡 Format : <strong>Code département</strong> suivi de <strong>-</strong> et du <strong>numéro de circonscription</strong>
                    <br>
                    Exemples : <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">75-01</code>, 
                    <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">13-03</code>, 
                    <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">59-10</code>
                </p>

                <InputError class="mt-2" :message="form.errors.circonscription" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    💾 Enregistrer
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        ✅ Localisation enregistrée
                    </p>
                </Transition>
            </div>

            <!-- Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-600 p-4 rounded">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">ℹ️</span>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-semibold mb-1">Comment trouver ma circonscription ?</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Consultez le site <a href="https://www.nosdeputes.fr/" target="_blank" class="text-blue-600 hover:underline">NosDéputés.fr</a></li>
                            <li>Ou rendez-vous sur <a href="https://www.assemblee-nationale.fr/" target="_blank" class="text-blue-600 hover:underline">Assemblée Nationale</a></li>
                            <li>Entrez votre code postal pour connaître votre circonscription</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>

