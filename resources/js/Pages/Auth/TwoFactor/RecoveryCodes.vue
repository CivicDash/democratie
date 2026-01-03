<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    recoveryCodes: Array,
    justEnabled: Boolean,
});

const copied = ref(false);

const copyAllCodes = () => {
    const text = props.recoveryCodes.join('\n');
    navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const downloadCodes = () => {
    const text = `Codes de récupération CivicDash\n${'='.repeat(30)}\n\nConservez ces codes en lieu sûr.\nChaque code ne peut être utilisé qu'une seule fois.\n\n${props.recoveryCodes.join('\n')}\n\nGénérés le ${new Date().toLocaleDateString('fr-FR')}`;
    
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'civicdash-recovery-codes.txt';
    a.click();
    URL.revokeObjectURL(url);
};

const regenerateCodes = () => {
    if (confirm('Êtes-vous sûr ? Les anciens codes seront invalidés.')) {
        router.post(route('two-factor.regenerate-recovery-codes'));
    }
};
</script>

<template>
    <Head title="Codes de récupération" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                📋 Codes de Récupération
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <!-- Message de succès -->
                <div v-if="justEnabled" class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-4 dark:bg-emerald-900/20 dark:border-emerald-800">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">
                                Double authentification activée !
                            </h3>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                Votre compte est maintenant protégé. Conservez ces codes de récupération en lieu sûr.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <!-- Avertissement -->
                        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg dark:bg-amber-900/20 dark:border-amber-800">
                            <div class="flex items-start gap-3">
                                <span class="text-xl">⚠️</span>
                                <div class="text-sm text-amber-700 dark:text-amber-300">
                                    <p class="font-semibold mb-1">Important !</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Ces codes vous permettent d'accéder à votre compte si vous perdez votre téléphone</li>
                                        <li>Chaque code ne peut être utilisé qu'<strong>une seule fois</strong></li>
                                        <li>Conservez-les dans un endroit sûr (gestionnaire de mots de passe, coffre-fort...)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des codes -->
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Vos {{ recoveryCodes.length }} codes de récupération :
                            </h3>
                            <div class="grid grid-cols-2 gap-2 p-4 bg-gray-100 rounded-lg font-mono text-sm dark:bg-gray-700">
                                <div
                                    v-for="(code, index) in recoveryCodes"
                                    :key="index"
                                    class="p-2 bg-white rounded border dark:bg-gray-800 dark:border-gray-600 select-all"
                                >
                                    {{ code }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                @click="copyAllCodes"
                            >
                                {{ copied ? '✅ Copié !' : '📋 Copier tout' }}
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                @click="downloadCodes"
                            >
                                📥 Télécharger (.txt)
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                @click="regenerateCodes"
                            >
                                🔄 Régénérer
                            </button>
                        </div>

                        <div class="mt-6 pt-6 border-t dark:border-gray-700">
                            <Link
                                :href="route('two-factor.show')"
                                class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                ← Retour aux paramètres 2FA
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
