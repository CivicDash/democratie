<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    idea: { type: Object, required: true },
    comments: { type: Object, required: true },
    userVote: { type: Number, default: null },
    similar: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// ============================================================================
// VOTING
// ============================================================================
const isVoting = ref(false);
const localVote = ref(props.userVote);
const localStats = ref({
    votes_pour: props.idea.votes_pour,
    votes_contre: props.idea.votes_contre,
    score: props.idea.score,
});

async function vote(value) {
    if (isVoting.value) return;
    isVoting.value = true;

    try {
        // Si même vote, on retire
        if (localVote.value === value) {
            const response = await fetch(route('participation.ideas.unvote', props.idea.id), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
            const data = await response.json();
            localVote.value = null;
            localStats.value = data.stats;
        } else {
            const response = await fetch(route('participation.ideas.vote', props.idea.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ vote: value }),
            });
            const data = await response.json();
            localVote.value = value;
            localStats.value = data.stats;
        }
    } catch (error) {
        console.error('Erreur vote:', error);
    } finally {
        isVoting.value = false;
    }
}

// ============================================================================
// COMMENTS
// ============================================================================
const newComment = ref('');
const isSubmitting = ref(false);
const localComments = ref([...props.comments.data]);

async function submitComment() {
    if (!newComment.value.trim() || isSubmitting.value) return;
    if (newComment.value.length < 10) {
        alert('Le commentaire doit faire au moins 10 caractères.');
        return;
    }
    
    isSubmitting.value = true;

    try {
        const response = await fetch(route('participation.ideas.comment', props.idea.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ content: newComment.value }),
        });
        const data = await response.json();
        
        if (data.success) {
            localComments.value.unshift(data.comment);
            newComment.value = '';
        }
    } catch (error) {
        console.error('Erreur commentaire:', error);
    } finally {
        isSubmitting.value = false;
    }
}

// ============================================================================
// HELPERS
// ============================================================================
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatRelativeDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) return "Aujourd'hui";
    if (days === 1) return 'Hier';
    if (days < 7) return `Il y a ${days} jours`;
    if (days < 30) return `Il y a ${Math.floor(days / 7)} semaine(s)`;
    return formatDate(dateStr);
}

function getResponseStatusInfo(status) {
    const statuses = {
        'pending': { label: 'En attente', color: 'amber', icon: '⏳' },
        'answered': { label: 'Répondu', color: 'emerald', icon: '✅' },
        'refused': { label: 'Refusé', color: 'rose', icon: '❌' },
    };
    return statuses[status] || statuses['pending'];
}

const pctPour = computed(() => {
    const total = localStats.value.votes_pour + localStats.value.votes_contre;
    return total > 0 ? Math.round((localStats.value.votes_pour / total) * 100) : 50;
});

const breadcrumbs = computed(() => [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Participation', href: route('participation.hub'), icon: '💬' },
    { label: 'Idées', href: route('participation.ideas.index'), icon: '💡' },
    { label: props.idea.title.substring(0, 30) + (props.idea.title.length > 30 ? '...' : ''), current: true },
]);
</script>

<template>
    <Head :title="idea.title" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Titre et meta -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span 
                                class="px-3 py-1 text-sm font-medium rounded-full"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-300': idea.idea_type === 'proposal',
                                    'bg-sky-500/20 text-sky-300': idea.idea_type === 'question',
                                    'bg-amber-500/20 text-amber-300': idea.idea_type === 'debate',
                                    'bg-violet-500/20 text-violet-300': idea.idea_type === 'petition',
                                    'bg-rose-500/20 text-rose-300': idea.idea_type === 'interpellation',
                                }"
                            >
                                {{ idea.idea_type_info?.icon }} {{ idea.idea_type_info?.label }}
                            </span>
                            <span class="text-white/70 text-sm">
                                {{ idea.scope_info?.icon }} {{ idea.scope_info?.label }}
                            </span>
                            <span v-if="idea.region" class="text-white/70 text-sm">
                                · {{ idea.region.name }}
                            </span>
                            <span v-if="idea.department" class="text-white/70 text-sm">
                                · {{ idea.department.code }} - {{ idea.department.name }}
                            </span>
                        </div>

                        <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-4">
                            {{ idea.title }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-4 text-white/70 text-sm">
                            <span class="flex items-center gap-1">
                                <span>👤</span>
                                {{ idea.author?.name || 'Anonyme' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span>📅</span>
                                {{ formatDate(idea.published_at) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span>👁️</span>
                                {{ idea.views_count }} vues
                            </span>
                            <span class="flex items-center gap-1">
                                <span>💬</span>
                                {{ idea.posts_count }} commentaires
                            </span>
                        </div>
                    </div>

                    <!-- Vote Widget -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 min-w-[200px]">
                        <div class="text-center mb-4">
                            <div 
                                class="text-4xl font-bold"
                                :class="localStats.score > 0 ? 'text-emerald-400' : localStats.score < 0 ? 'text-rose-400' : 'text-white'"
                            >
                                {{ localStats.score > 0 ? '+' : '' }}{{ localStats.score }}
                            </div>
                            <div class="text-white/60 text-sm">score</div>
                        </div>

                        <!-- Barre de progression -->
                        <div class="h-3 bg-rose-500/50 rounded-full overflow-hidden mb-4">
                            <div 
                                class="h-full bg-emerald-500 transition-all duration-500"
                                :style="{ width: `${pctPour}%` }"
                            ></div>
                        </div>

                        <div class="flex justify-between text-sm text-white/70 mb-6">
                            <span>👍 {{ localStats.votes_pour }}</span>
                            <span>👎 {{ localStats.votes_contre }}</span>
                        </div>

                        <!-- Boutons de vote -->
                        <div class="flex gap-2">
                            <button
                                @click="vote(1)"
                                :disabled="isVoting"
                                :class="[
                                    'flex-1 py-3 rounded-xl font-medium transition-all',
                                    localVote === 1
                                        ? 'bg-emerald-500 text-white'
                                        : 'bg-white/20 text-white hover:bg-emerald-500/50'
                                ]"
                            >
                                👍 Pour
                            </button>
                            <button
                                @click="vote(-1)"
                                :disabled="isVoting"
                                :class="[
                                    'flex-1 py-3 rounded-xl font-medium transition-all',
                                    localVote === -1
                                        ? 'bg-rose-500 text-white'
                                        : 'bg-white/20 text-white hover:bg-rose-500/50'
                                ]"
                            >
                                👎 Contre
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Description -->
                        <Card>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                📝 Description
                            </h2>
                            <div class="prose dark:prose-invert max-w-none">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ idea.description }}</p>
                            </div>

                            <!-- Tags -->
                            <div v-if="idea.tags?.length > 0" class="flex flex-wrap gap-2 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <span 
                                    v-for="tag in idea.tags" 
                                    :key="tag.id"
                                    class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm"
                                >
                                    {{ tag.icone }} {{ tag.nom }}
                                </span>
                            </div>
                        </Card>

                        <!-- Loi liée -->
                        <Card v-if="idea.loi">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                📜 Loi liée
                            </h2>
                            <Link 
                                :href="route('lois.show', idea.loi.code)"
                                class="block p-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-xl hover:shadow-md transition"
                            >
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ idea.loi.titre }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Code : {{ idea.loi.code }}
                                </div>
                            </Link>
                        </Card>

                        <!-- Élus liés / Interpellations -->
                        <Card v-if="idea.elus?.length > 0">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                👤 Élus concernés
                            </h2>
                            <div class="space-y-4">
                                <div 
                                    v-for="elu in idea.elus" 
                                    :key="elu.id"
                                    class="p-4 rounded-xl border"
                                    :class="elu.is_interpellation 
                                        ? 'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-700' 
                                        : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                            <img 
                                                v-if="elu.elu_data?.photo_url"
                                                :src="elu.elu_data.photo_url"
                                                :alt="elu.elu_data?.nom_complet"
                                                class="w-full h-full object-cover"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-xl">
                                                👤
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ elu.elu_data?.nom_complet || 'Élu inconnu' }}
                                                </span>
                                                <span v-if="elu.is_interpellation" class="px-2 py-0.5 bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-400 text-xs rounded-full">
                                                    📣 Interpellation
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ elu.elu_type === 'depute' ? 'Député' : elu.elu_type === 'senateur' ? 'Sénateur' : 'Maire' }}
                                                <span v-if="elu.elu_data?.groupe"> · {{ elu.elu_data.groupe }}</span>
                                            </div>

                                            <!-- Statut réponse -->
                                            <div v-if="elu.is_interpellation" class="mt-2">
                                                <span 
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium"
                                                    :class="{
                                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': elu.response_status === 'pending',
                                                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': elu.response_status === 'answered',
                                                        'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': elu.response_status === 'refused',
                                                    }"
                                                >
                                                    {{ getResponseStatusInfo(elu.response_status).icon }}
                                                    {{ getResponseStatusInfo(elu.response_status).label }}
                                                </span>
                                            </div>

                                            <!-- Réponse de l'élu -->
                                            <div v-if="elu.response_content" class="mt-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-lg">
                                                <div class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">
                                                    Réponse le {{ formatDate(elu.response_date) }}
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ elu.response_content }}</p>
                                            </div>
                                        </div>
                                        <Link 
                                            v-if="elu.elu_data?.url"
                                            :href="elu.elu_data.url"
                                            class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline"
                                        >
                                            Voir la fiche →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Commentaires -->
                        <Card>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                                💬 Commentaires ({{ idea.posts_count }})
                            </h2>

                            <!-- Formulaire nouveau commentaire -->
                            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                                <textarea
                                    v-model="newComment"
                                    rows="3"
                                    placeholder="Partagez votre avis sur cette idée..."
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white resize-y"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ newComment.length }} / 5000 caractères (min. 10)
                                    </span>
                                    <button
                                        @click="submitComment"
                                        :disabled="isSubmitting || newComment.length < 10"
                                        :class="[
                                            'px-4 py-2 rounded-lg font-medium transition-colors',
                                            newComment.length >= 10
                                                ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                                : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                        ]"
                                    >
                                        {{ isSubmitting ? '⏳' : '📤' }} Publier
                                    </button>
                                </div>
                            </div>

                            <!-- Liste des commentaires -->
                            <div class="space-y-4">
                                <div 
                                    v-for="comment in localComments" 
                                    :key="comment.id"
                                    class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-lg flex-shrink-0">
                                            👤
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ comment.user?.name || 'Anonyme' }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ formatRelativeDate(comment.created_at) }}
                                                </span>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.content }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="localComments.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <div class="text-4xl mb-2">💬</div>
                                    <p>Aucun commentaire pour le moment. Soyez le premier à réagir !</p>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="comments.last_page > 1" class="flex justify-center gap-2 mt-6">
                                <Link
                                    v-for="link in comments.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active
                                            ? 'bg-emerald-600 text-white'
                                            : link.url
                                                ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <Card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Actions</h3>
                            <div class="space-y-2">
                                <button 
                                    class="w-full flex items-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-left transition"
                                    @click="navigator.share?.({ title: idea.title, url: window.location.href })"
                                >
                                    <span>📤</span>
                                    <span>Partager</span>
                                </button>
                                <button class="w-full flex items-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-left transition">
                                    <span>🔔</span>
                                    <span>Suivre</span>
                                </button>
                                <button class="w-full flex items-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-left transition text-rose-600 dark:text-rose-400">
                                    <span>🚩</span>
                                    <span>Signaler</span>
                                </button>
                            </div>
                        </Card>

                        <!-- Idées similaires -->
                        <Card v-if="similar.length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💡 Idées similaires</h3>
                            <div class="space-y-3">
                                <Link
                                    v-for="item in similar"
                                    :key="item.id"
                                    :href="route('participation.ideas.show', item.slug)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">{{ item.title }}</h4>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>👍 {{ item.votes_pour }}</span>
                                        <span>👎 {{ item.votes_contre }}</span>
                                    </div>
                                </Link>
                            </div>
                        </Card>

                        <!-- Retour à la liste -->
                        <Link
                            :href="route('participation.ideas.index')"
                            class="block w-full text-center px-4 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition"
                        >
                            ← Retour aux idées
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
