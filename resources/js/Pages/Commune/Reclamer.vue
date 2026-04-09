<script setup>
import { router, useForm } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    email_masque: String,
    a_email_officiel: Boolean,
    niveaux_disponibles: Array,
});

const step = ref('choix');
const selectedNiveau = ref(null);
const codeForm = useForm({ code: '' });
const docForm = useForm({ document: null, type_document: 'arrete_nomination' });

const choisirNiveau = (niveau) => {
    selectedNiveau.value = niveau;
    router.post(route('commune.reclamer.initier', props.ville.code_insee), {
        niveau: niveau.id,
    }, {
        onSuccess: () => {
            if (niveau.id === 'email_officiel') step.value = 'code';
            else if (niveau.id === 'manuelle') step.value = 'document';
            else step.value = 'attente';
        },
    });
};

const verifierCode = () => {
    codeForm.post(route('commune.reclamer.verifier', props.ville.code_insee));
};

const soumettreDocument = () => {
    docForm.post(route('commune.reclamer.document', props.ville.code_insee), {
        forceFormData: true,
    });
};
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :titre="`Reclamer - ${ville.nom}`">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Reclamer cette commune</h1>
            <p class="text-slate-500 dark:text-slate-400 mb-8">
                En tant que maire de {{ ville.nom }}, reclamez votre page commune pour publier des actualites, creer des evenements et personnaliser votre espace.
            </p>

            <!-- Choix du niveau -->
            <div v-if="step === 'choix'" class="space-y-4">
                <div
                    v-for="niveau in niveaux_disponibles"
                    :key="niveau.id"
                    @click="choisirNiveau(niveau)"
                    class="bg-white dark:bg-slate-800 rounded-xl p-5 border-2 cursor-pointer transition-all hover:shadow-lg"
                    :class="niveau.recommande ? 'border-blue-300 dark:border-blue-600 hover:border-blue-500' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 dark:text-white">{{ niveau.label }}</h3>
                                <span v-if="niveau.recommande" class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full font-medium">
                                    Recommande
                                </span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ niveau.description }}</p>
                            <p v-if="niveau.id === 'email_officiel' && email_masque" class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                Email : {{ email_masque }}
                            </p>
                        </div>
                        <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded flex-shrink-0">
                            {{ niveau.delai }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Saisie du code -->
            <div v-else-if="step === 'code'" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Saisissez le code de verification</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Un code a 6 chiffres a ete envoye a {{ email_masque }}. Il est valable 24 heures.</p>

                <form @submit.prevent="verifierCode">
                    <input
                        v-model="codeForm.code"
                        type="text"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="w-full text-center text-3xl tracking-[0.5em] font-mono py-4 border border-slate-300 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        placeholder="000000"
                        autofocus
                    />
                    <p v-if="codeForm.errors.code" class="text-sm text-red-500 mt-2">{{ codeForm.errors.code }}</p>

                    <button
                        type="submit"
                        :disabled="codeForm.processing || codeForm.code.length !== 6"
                        class="w-full mt-4 bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ codeForm.processing ? 'Verification...' : 'Verifier le code' }}
                    </button>
                </form>
            </div>

            <!-- Upload document -->
            <div v-else-if="step === 'document'" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Soumettre un justificatif</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    Merci de fournir un document officiel pour verifier votre identite. Notre equipe traitera votre demande sous 24 a 72 heures.
                </p>

                <form @submit.prevent="soumettreDocument" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type de document</label>
                        <select v-model="docForm.type_document" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="arrete_nomination">Arrete de nomination</option>
                            <option value="pv_installation">PV d'installation du conseil</option>
                            <option value="piece_identite">Piece d'identite</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Document (PDF, JPG, PNG, max 5 Mo)</label>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            @change="docForm.document = $event.target.files[0]"
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="docForm.processing || !docForm.document"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ docForm.processing ? 'Envoi...' : 'Soumettre le document' }}
                    </button>
                </form>
            </div>

            <!-- Attente -->
            <div v-else-if="step === 'attente'" class="text-center py-12">
                <div class="text-5xl mb-4">⏳</div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Demande en cours de verification</h2>
                <p class="text-slate-500 dark:text-slate-400">Notre equipe verifiera votre demande dans les meilleurs delais.</p>
            </div>
        </div>
    </CommuneLayout>
</template>
