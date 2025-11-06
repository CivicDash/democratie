<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
    topic: Object,
    posts: Object, // ✅ Maintenant un objet avec data, links, meta (pagination)
    can: Object,
    ballot: Object, // ✅ Scrutin associé au débat
});

const replyForm = useForm({
    content: '',
    parent_id: null,
});

const replyingTo = ref(null); // ✅ Pour savoir à quel post on répond
const loadingMore = ref(false);

// ✅ Posts locaux pour optimistic UI
const localPosts = ref([...props.posts.data]);

const submitReply = () => {
    replyForm.post(route('topics.posts.store', props.topic.id), {
        onSuccess: () => {
            replyForm.reset();
            replyingTo.value = null;
        },
    });
};

// ✅ Répondre à un post spécifique
const replyToPost = (post) => {
    replyingTo.value = post;
    replyForm.parent_id = post.id;
    // Scroll vers le formulaire
    document.getElementById('reply-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// ✅ Annuler la réponse
const cancelReply = () => {
    replyingTo.value = null;
    replyForm.parent_id = null;
};

// ✅ OPTIMISTIC UI - Vote instantané
const votePost = (postId, voteType) => {
    const postIndex = localPosts.value.findIndex(p => p.id === postId);
    if (postIndex === -1) return;
    
    const post = localPosts.value[postIndex];
    const previousVote = post.user_vote;
    const previousScore = post.vote_score;
    
    // Update UI immédiatement (optimistic)
    if (previousVote === voteType) {
        // Annuler vote
        post.user_vote = null;
        post.vote_score = previousScore + (voteType === 'up' ? -1 : 1);
    } else {
        // Nouveau vote
        post.user_vote = voteType;
        const delta = voteType === 'up' ? 1 : -1;
        const adjustment = previousVote ? (previousVote === 'up' ? -1 : 1) : 0;
        post.vote_score = previousScore + delta + adjustment;
    }
    
    // Requête serveur en arrière-plan
    router.post(
        route('posts.vote', postId),
        { vote_type: voteType },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['posts'],
            onError: (errors) => {
                // Rollback si erreur
                post.user_vote = previousVote;
                post.vote_score = previousScore;
                alert('Erreur lors du vote');
            },
            onSuccess: () => {
                // Synchro avec données serveur
                if (props.posts.data[postIndex]) {
                    localPosts.value[postIndex] = { ...props.posts.data[postIndex] };
                }
            },
        }
    );
};

// ✅ INFINITE SCROLL - Chargement automatique
const loadMorePosts = () => {
    if (loadingMore.value || !props.posts.next_page_url) return;
    
    loadingMore.value = true;
    
    router.get(
        props.posts.next_page_url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['posts'],
            onSuccess: () => {
                // Ajouter nouveaux posts aux existants
                localPosts.value = [...localPosts.value, ...props.posts.data];
                loadingMore.value = false;
            },
            onError: () => {
                loadingMore.value = false;
            },
        }
    );
};

// Intersection Observer pour infinite scroll
let observer;
onMounted(() => {
    const sentinel = document.querySelector('#scroll-sentinel');
    if (sentinel) {
        observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    loadMorePosts();
                }
            },
            { threshold: 0.5 }
        );
        observer.observe(sentinel);
    }
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

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

    <AuthenticatedLayout :title="topic.title">
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
                                <span>💬 {{ posts.total || localPosts.length }} réponses</span>
                                <span v-if="topic.ballots_count">🗳️ {{ topic.ballots_count }} votes</span>
                            </div>
                        </div>
                        <div v-if="can.update || can.delete" class="flex gap-2">
                            <Link v-if="can.update" :href="route('topics.edit', topic.id)">
                                <SecondaryButton>✏️ Modifier</SecondaryButton>
                            </Link>
                        </div>
                    </div>

                </Card>

                <!-- ✅ SCRUTIN ASSOCIÉ (si présent) -->
                <Card v-if="ballot" class="mb-6 bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border-2 border-indigo-200 dark:border-indigo-700">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🗳️</div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Scrutin : {{ ballot.title }}
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-4">
                                {{ ballot.description }}
                            </p>
                            <div class="flex flex-wrap items-center gap-4 text-sm mb-4">
                                <Badge :variant="ballot.status === 'open' ? 'green' : 'gray'">
                                    {{ ballot.status === 'open' ? '✅ En cours' : '🔒 Terminé' }}
                                </Badge>
                                <span class="text-gray-600 dark:text-gray-400">
                                    📅 {{ ballot.status === 'open' ? 'Se termine le' : 'Terminé le' }} {{ formatDate(ballot.ends_at) }}
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    👥 {{ ballot.votes_count || 0 }} votes
                                </span>
                            </div>
                            <div class="flex gap-3">
                                <Link v-if="ballot.status === 'open' && can.vote" :href="route('ballots.vote', ballot.id)">
                                    <PrimaryButton>
                                        🗳️ Voter maintenant
                                    </PrimaryButton>
                                </Link>
                                <Link :href="route('ballots.results', ballot.id)">
                                    <SecondaryButton>
                                        📊 Voir les résultats
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- ✅ FORMULAIRE DE RÉPONSE EN HAUT -->
                <Card v-if="$page.props.auth.user && can.reply" id="reply-form" class="mb-6">
                    <div v-if="replyingTo" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                                    💬 En réponse à {{ replyingTo.author?.name || 'Anonyme' }}
                                </p>
                                <p class="text-sm text-blue-700 dark:text-blue-300 line-clamp-2">
                                    {{ replyingTo.content }}
                                </p>
                            </div>
                            <button @click="cancelReply" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                ✕
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        ✍️ {{ replyingTo ? 'Répondre' : 'Ajouter une réponse' }}
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
                            <SecondaryButton v-if="replyingTo" type="button" @click="cancelReply">
                                Annuler
                            </SecondaryButton>
                        </div>
                    </form>
                </Card>

                <Card v-else-if="!$page.props.auth.user" class="mb-6">
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

                <!-- Posts List -->
                <div class="space-y-4 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        💬 Réponses ({{ posts.total || localPosts.length }})
                    </h2>

                    <div v-if="localPosts.length > 0" class="space-y-4">
                        <Card v-for="post in localPosts" :key="post.id" padding="p-6" :class="{ 'ml-12 border-l-4 border-indigo-200 dark:border-indigo-700': post.parent_id }">
                            <div class="flex gap-4">
                                <!-- Vote buttons -->
                                <div class="flex flex-col items-center gap-2">
                                    <button 
                                        @click="votePost(post.id, 'up')"
                                        class="text-gray-400 hover:text-green-600 transition-colors text-xl"
                                        :class="{ 'text-green-600': post.user_vote === 'up' }"
                                        title="Vote positif"
                                    >
                                        ▲
                                    </button>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 text-lg">
                                        {{ post.vote_score || 0 }}
                                    </span>
                                    <button 
                                        @click="votePost(post.id, 'down')"
                                        class="text-gray-400 hover:text-red-600 transition-colors text-xl"
                                        :class="{ 'text-red-600': post.user_vote === 'down' }"
                                        title="Vote négatif"
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
                                            {{ post.created_at }}
                                        </span>
                                        <Badge v-if="post.is_pinned" variant="yellow" size="sm">
                                            📌 Épinglé
                                        </Badge>
                                        <Badge v-if="post.is_solution" variant="green" size="sm">
                                            ✅ Solution
                                        </Badge>
                                        <Badge v-if="post.parent_id" variant="blue" size="sm">
                                            💬 Réponse
                                        </Badge>
                                    </div>
                                    
                                    <!-- Parent post preview (si c'est une réponse) -->
                                    <div v-if="post.parent" class="mb-3 p-2 bg-gray-50 dark:bg-gray-800 rounded border-l-2 border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            En réponse à <span class="font-semibold">{{ post.parent.author?.name || 'Anonyme' }}</span>
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ post.parent.content }}
                                        </p>
                                    </div>
                                    
                                    <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-3">
                                        {{ post.content }}
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center gap-4 text-sm">
                                        <button 
                                            v-if="$page.props.auth.user && can.reply"
                                            @click="replyToPost(post)"
                                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 font-medium flex items-center gap-1"
                                        >
                                            💬 Répondre
                                        </button>
                                        <span v-if="post.replies_count" class="text-gray-500 dark:text-gray-400">
                                            {{ post.replies_count }} {{ post.replies_count > 1 ? 'réponses' : 'réponse' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                        
                        <!-- ✅ INFINITE SCROLL SENTINEL -->
                        <div 
                            v-if="posts.next_page_url" 
                            id="scroll-sentinel" 
                            class="py-8 text-center"
                        >
                            <LoadingSpinner v-if="loadingMore" />
                            <p v-else class="text-sm text-gray-500">
                                Scroll pour charger plus...
                            </p>
                        </div>
                        
                        <!-- Pagination info -->
                        <div v-if="!posts.next_page_url" class="text-center text-sm text-gray-500 py-4">
                            ✅ Toutes les réponses ont été chargées
                        </div>
                    </div>

                    <Card v-else>
                        <EmptyState
                            icon="💬"
                            title="Aucune réponse"
                            description="Soyez le premier à participer à cette discussion !"
                        />
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

