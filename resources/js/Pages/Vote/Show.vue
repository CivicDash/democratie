<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Alert from '@/Components/Alert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
    topic: Object,
    ballotOptions: Array,
    hasVoted: Boolean,
    results: Object,
});

const tokenForm = useForm({});
const voteForm = useForm({
    token: '',
    ballot_choice: null,
});

const step = ref(props.hasVoted ? 3 : 1); // 1: request token, 2: vote, 3: results

const requestToken = () => {
    tokenForm.post(route('topics.vote.token', props.topic.id), {
        onSuccess: (page) => {
            if (page.props.flash?.token) {
                voteForm.token = page.props.flash.token;
                step.value = 2;
            }
        },
    });
};

const castVote = () => {
    voteForm.post(route('topics.vote.cast', props.topic.id), {
        onSuccess: () => {
            step.value = 3;
        },
    });
};

const isVotingOpen = computed(() => {
    if (!props.topic.ballot_ends_at) return true;
    return new Date(props.topic.ballot_ends_at) > new Date();
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getPercentage = (votes, total) => {
    if (total === 0) return 0;
    return ((votes / total) * 100).toFixed(1);
};
</script>

<template>
    <Head :title="`Vote - ${topic.title}`" />

    <MainLayout :title="`Vote - ${topic.title}`">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <Card class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        🗳️ {{ topic.title }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ topic.description }}
                    </p>
                    <div class="flex items-center gap-4 text-sm">
                        <Badge :variant="isVotingOpen ? 'green' : 'red'">
                            {{ isVotingOpen ? '✅ Vote ouvert' : '🔒 Vote fermé' }}
                        </Badge>
                        <span class="text-gray-500 dark:text-gray-400">
                            Type: {{ topic.ballot_type === 'binary' ? 'Oui/Non' : 'Choix multiple' }}
                        </span>
                        <span v-if="topic.ballot_ends_at" class="text-gray-500 dark:text-gray-400">
                            📅 Fin: {{ formatDate(topic.ballot_ends_at) }}
                        </span>
                    </div>
                </Card>

                <!-- Vote closed -->
                <Alert v-if="!isVotingOpen" type="warning" class="mb-6">
                    <strong>Vote terminé</strong><br>
                    Ce scrutin est maintenant fermé. Vous pouvez consulter les résultats ci-dessous.
                </Alert>

                <!-- Step 1: Request Token -->
                <Card v-if="step === 1 && isVotingOpen">
                    <div class="text-center py-8">
                        <div class="text-6xl mb-4">🎫</div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Vote Anonyme
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
                            Pour garantir l'anonymat de votre vote, vous allez recevoir un jeton unique.<br>
                            Ce jeton sera utilisé pour voter sans révéler votre identité.
                        </p>
                        
                        <Alert type="info" class="mb-6 text-left">
                            <strong>🔒 Garanties d'anonymat :</strong>
                            <ul class="mt-2 space-y-1 text-sm">
                                <li>• Votre identité n'est jamais liée à votre vote</li>
                                <li>• Le jeton est généré de manière cryptographique</li>
                                <li>• Impossible de retracer un vote à son auteur</li>
                                <li>• Un seul vote par citoyen autorisé</li>
                            </ul>
                        </Alert>

                        <PrimaryButton @click="requestToken" :disabled="tokenForm.processing">
                            {{ tokenForm.processing ? 'Génération...' : '🎫 Obtenir mon jeton de vote' }}
                        </PrimaryButton>
                    </div>
                </Card>

                <!-- Step 2: Cast Vote -->
                <Card v-if="step === 2 && isVotingOpen">
                    <div class="py-8">
                        <div class="text-center mb-8">
                            <div class="text-6xl mb-4">🗳️</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Votre Vote
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Jeton: <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ voteForm.token.substring(0, 16) }}...</code>
                            </p>
                        </div>

                        <form @submit.prevent="castVote" class="max-w-xl mx-auto">
                            <!-- Binary Vote -->
                            <div v-if="topic.ballot_type === 'binary'" class="space-y-4">
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors" 
                                    :class="voteForm.ballot_choice === 'yes' 
                                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20' 
                                        : 'border-gray-300 dark:border-gray-700 hover:border-green-300'">
                                    <input type="radio" v-model="voteForm.ballot_choice" value="yes" class="mr-3" required>
                                    <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">✅ Oui</span>
                                </label>
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
                                    :class="voteForm.ballot_choice === 'no' 
                                        ? 'border-red-500 bg-red-50 dark:bg-red-900/20' 
                                        : 'border-gray-300 dark:border-gray-700 hover:border-red-300'">
                                    <input type="radio" v-model="voteForm.ballot_choice" value="no" class="mr-3" required>
                                    <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">❌ Non</span>
                                </label>
                            </div>

                            <!-- Multiple Choice Vote -->
                            <div v-else class="space-y-3">
                                <label v-for="(option, index) in ballotOptions" :key="index"
                                    class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
                                    :class="voteForm.ballot_choice === option 
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' 
                                        : 'border-gray-300 dark:border-gray-700 hover:border-indigo-300'">
                                    <input type="radio" v-model="voteForm.ballot_choice" :value="option" class="mr-3" required>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ option }}</span>
                                </label>
                            </div>

                            <div class="mt-8 text-center">
                                <PrimaryButton type="submit" :disabled="voteForm.processing || !voteForm.ballot_choice">
                                    {{ voteForm.processing ? 'Envoi du vote...' : '📤 Valider mon vote' }}
                                </PrimaryButton>
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    🔒 Votre vote est anonyme et ne pourra pas être modifié
                                </p>
                            </div>
                        </form>
                    </div>
                </Card>

                <!-- Step 3: Results -->
                <Card v-if="step === 3 || !isVotingOpen">
                    <div class="py-8">
                        <div class="text-center mb-8">
                            <div class="text-6xl mb-4">📊</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Résultats du Vote
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Total: {{ results?.total_votes || 0 }} votes exprimés
                            </p>
                        </div>

                        <div v-if="results && results.total_votes > 0" class="max-w-2xl mx-auto space-y-4">
                            <!-- Binary Results -->
                            <div v-if="topic.ballot_type === 'binary'">
                                <div class="mb-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">✅ Oui</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ results.yes || 0 }} votes ({{ getPercentage(results.yes || 0, results.total_votes) }}%)
                                        </span>
                                    </div>
                                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 transition-all duration-500" 
                                            :style="{ width: `${getPercentage(results.yes || 0, results.total_votes)}%` }">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">❌ Non</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ results.no || 0 }} votes ({{ getPercentage(results.no || 0, results.total_votes) }}%)
                                        </span>
                                    </div>
                                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-red-500 transition-all duration-500" 
                                            :style="{ width: `${getPercentage(results.no || 0, results.total_votes)}%` }">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Multiple Choice Results -->
                            <div v-else class="space-y-4">
                                <div v-for="(votes, choice) in results.choices" :key="choice">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ choice }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ votes }} votes ({{ getPercentage(votes, results.total_votes) }}%)
                                        </span>
                                    </div>
                                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 transition-all duration-500" 
                                            :style="{ width: `${getPercentage(votes, results.total_votes)}%` }">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-gray-500 dark:text-gray-400">
                            Aucun vote enregistré pour le moment
                        </div>

                        <div v-if="hasVoted" class="mt-8 text-center">
                            <Alert type="success" class="max-w-xl mx-auto">
                                ✅ Vous avez déjà voté sur ce scrutin
                            </Alert>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </MainLayout>
</template>

