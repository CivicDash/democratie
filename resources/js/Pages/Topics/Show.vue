<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    topic: Object,
    posts: Array,
    can: Object,
});

const replyForm = useForm({
    content: '',
    parent_id: null,
});

const voteForm = useForm({});

const showReplyForm = ref(false);

const submitReply = () => {
    replyForm.post(route('topics.posts.store', props.topic.id), {
        onSuccess: () => {
            replyForm.reset();
            showReplyForm.value = false;
        },
    });
};

const votePost = (postId, voteType) => {
    voteForm.post(route('posts.vote', postId), {
        vote_type: voteType,
    }, {
        preserveScroll: true,
    });
};

const getScopeLabel = (scope) => {
    const labels = {
        national: '🇫🇷 National',
        regional: '🗺️ Régional',
        departmental: '📍 Départemental',
    };
    return labels[scope] || scope;
};

const getTypeLabel = (type) => {
    const labels = {
        debate: '💬 Débat',
        proposal: '💡 Proposition',
        question: '❓ Question',
        announcement: '📢 Annonce',
    };
    return labels[type] || type;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="topic.title" />

    <MainLayout :title="topic.title">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Topic Header -->
                <Card class="mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <Badge :variant="topic.scope === 'national' ? 'blue' : 'indigo'" size="sm">
                                    {{ getScopeLabel(topic.scope) }}
                                </Badge>
                                <Badge variant="gray" size="sm">
                                    {{ getTypeLabel(topic.type) }}
                                </Badge>
                                <Badge v-if="topic.ballot_type" variant="indigo">
                                    🗳️ Vote {{ topic.ballot_type }}
                                </Badge>
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                {{ topic.title }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ topic.description }}
                            </p>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span>👤 {{ topic.author?.name || 'Anonyme' }}</span>
                                <span>📅 {{ formatDate(topic.created_at) }}</span>
                                <span>💬 {{ posts.length }} réponses</span>
                                <span v-if="topic.ballots_count">🗳️ {{ topic.ballots_count }} votes</span>
                            </div>
                        </div>
                        <div v-if="can.update || can.delete" class="flex gap-2">
                            <Link v-if="can.update" :href="route('topics.edit', topic.id)">
                                <SecondaryButton>✏️ Modifier</SecondaryButton>
                            </Link>
                        </div>
                    </div>

                    <!-- Ballot Info -->
                    <div v-if="topic.ballot_type" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                    🗳️ Scrutin {{ topic.ballot_type === 'binary' ? 'Oui/Non' : 'Choix multiple' }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Se termine le {{ formatDate(topic.ballot_ends_at) }}
                                </p>
                            </div>
                            <Link :href="route('topics.vote', topic.id)">
                                <PrimaryButton>
                                    🗳️ Voter
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </Card>

                <!-- Posts List -->
                <div class="space-y-4 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        💬 Réponses ({{ posts.length }})
                    </h2>

                    <div v-if="posts.length > 0" class="space-y-4">
                        <Card v-for="post in posts" :key="post.id" padding="p-6">
                            <div class="flex gap-4">
                                <!-- Vote buttons -->
                                <div class="flex flex-col items-center gap-2">
                                    <button 
                                        @click="votePost(post.id, 'up')"
                                        class="text-gray-400 hover:text-green-600 transition-colors"
                                        :class="{ 'text-green-600': post.user_vote === 'up' }"
                                    >
                                        ▲
                                    </button>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ post.vote_score || 0 }}
                                    </span>
                                    <button 
                                        @click="votePost(post.id, 'down')"
                                        class="text-gray-400 hover:text-red-600 transition-colors"
                                        :class="{ 'text-red-600': post.user_vote === 'down' }"
                                    >
                                        ▼
                                    </button>
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ post.author?.name || 'Anonyme' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(post.created_at) }}
                                        </span>
                                        <Badge v-if="post.is_pinned" variant="yellow" size="sm">
                                            📌 Épinglé
                                        </Badge>
                                        <Badge v-if="post.is_solution" variant="green" size="sm">
                                            ✅ Solution
                                        </Badge>
                                    </div>
                                    <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                        {{ post.content }}
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <Card v-else>
                        <EmptyState
                            icon="💬"
                            title="Aucune réponse"
                            description="Soyez le premier à participer à cette discussion !"
                        />
                    </Card>
                </div>

                <!-- Reply Form -->
                <Card v-if="$page.props.auth.user && can.reply">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        ✍️ Ajouter une réponse
                    </h3>
                    <form @submit.prevent="submitReply">
                        <textarea
                            v-model="replyForm.content"
                            rows="4"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            placeholder="Écrivez votre réponse..."
                            required
                        ></textarea>
                        <div class="mt-4 flex gap-3">
                            <PrimaryButton :disabled="replyForm.processing">
                                {{ replyForm.processing ? 'Envoi...' : '📤 Envoyer' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </Card>

                <Card v-else-if="!$page.props.auth.user">
                    <EmptyState
                        icon="🔒"
                        title="Connectez-vous pour participer"
                        description="Vous devez être connecté pour répondre à ce sujet."
                    >
                        <Link :href="route('login')">
                            <PrimaryButton>Se connecter</PrimaryButton>
                        </Link>
                    </EmptyState>
                </Card>
            </div>
        </div>
    </MainLayout>
</template>

