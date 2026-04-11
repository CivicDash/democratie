<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    commentaires: { type: Array, default: () => [] },
    codeInsee: { type: String, required: true },
    commentableType: { type: String, required: true },
    commentableId: { type: String, required: true },
});

const auth = computed(() => usePage().props.auth?.user);
const newComment = ref('');
const replyingTo = ref(null);
const replyContent = ref('');
const submitting = ref(false);

const submitComment = () => {
    if (!newComment.value.trim()) return;
    submitting.value = true;
    router.post(
        route('commune.commentaires.store', [props.codeInsee, props.commentableType, props.commentableId]),
        { contenu: newComment.value },
        {
            preserveScroll: true,
            onSuccess: () => { newComment.value = ''; },
            onFinish: () => { submitting.value = false; },
        }
    );
};

const submitReply = (parentId) => {
    if (!replyContent.value.trim()) return;
    submitting.value = true;
    router.post(
        route('commune.commentaires.store', [props.codeInsee, props.commentableType, props.commentableId]),
        { contenu: replyContent.value, parent_id: parentId },
        {
            preserveScroll: true,
            onSuccess: () => { replyContent.value = ''; replyingTo.value = null; },
            onFinish: () => { submitting.value = false; },
        }
    );
};

const deleteComment = (id) => {
    if (!confirm('Supprimer ce commentaire ?')) return;
    router.delete(route('commune.commentaires.destroy', [props.codeInsee, id]), { preserveScroll: true });
};

const signaler = (id) => {
    router.post(route('commune.commentaires.signaler', [props.codeInsee, id]), {}, { preserveScroll: true });
};

const timeAgo = (dateStr) => {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return "A l'instant";
    if (mins < 60) return `Il y a ${mins}min`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `Il y a ${hours}h`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `Il y a ${days}j`;
    return new Date(dateStr).toLocaleDateString('fr-FR');
};
</script>

<template>
    <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Commentaires ({{ commentaires.length }})
        </h3>

        <!-- Formulaire nouveau commentaire -->
        <div v-if="auth" class="mb-6">
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-sm font-bold flex-shrink-0">
                    {{ auth.name?.charAt(0) }}
                </div>
                <div class="flex-1">
                    <textarea
                        v-model="newComment"
                        placeholder="Ecrire un commentaire..."
                        rows="2"
                        class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        @keydown.meta.enter="submitComment"
                    />
                    <div class="flex justify-end mt-2">
                        <button
                            @click="submitComment"
                            :disabled="!newComment.trim() || submitting"
                            class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Publier
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="mb-6 text-center py-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <a :href="route('login')" class="text-blue-600 hover:underline font-medium">Connectez-vous</a> pour laisser un commentaire
            </p>
        </div>

        <!-- Liste des commentaires -->
        <div class="space-y-4">
            <div v-for="c in commentaires" :key="c.id">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 text-sm font-bold flex-shrink-0">
                        {{ c.user?.name?.charAt(0) || '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ c.user?.name }}</span>
                            <span class="text-xs text-slate-400">{{ timeAgo(c.created_at) }}</span>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ c.contenu }}</p>
                        <div class="flex items-center gap-3 mt-1.5">
                            <button v-if="auth" @click="replyingTo = replyingTo === c.id ? null : c.id" class="text-xs text-slate-400 hover:text-blue-600 font-medium">
                                Repondre
                            </button>
                            <button v-if="auth && auth.id !== c.user_id" @click="signaler(c.id)" class="text-xs text-slate-400 hover:text-red-500">
                                Signaler
                            </button>
                            <button v-if="auth && (auth.id === c.user_id)" @click="deleteComment(c.id)" class="text-xs text-slate-400 hover:text-red-500">
                                Supprimer
                            </button>
                        </div>

                        <!-- Formulaire reponse -->
                        <div v-if="replyingTo === c.id" class="mt-3">
                            <textarea
                                v-model="replyContent"
                                :placeholder="`Repondre a ${c.user?.name}...`"
                                rows="2"
                                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            />
                            <div class="flex justify-end gap-2 mt-1.5">
                                <button @click="replyingTo = null" class="px-3 py-1 text-xs text-slate-500 hover:text-slate-700">Annuler</button>
                                <button
                                    @click="submitReply(c.id)"
                                    :disabled="!replyContent.trim() || submitting"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded-md font-medium hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Repondre
                                </button>
                            </div>
                        </div>

                        <!-- Reponses -->
                        <div v-if="c.reponses?.length" class="mt-3 ml-4 pl-4 border-l-2 border-slate-100 dark:border-slate-700 space-y-3">
                            <div v-for="r in c.reponses" :key="r.id" class="flex gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                                    {{ r.user?.name?.charAt(0) || '?' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ r.user?.name }}</span>
                                        <span class="text-xs text-slate-400">{{ timeAgo(r.created_at) }}</span>
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ r.contenu }}</p>
                                    <button v-if="auth && auth.id === r.user_id" @click="deleteComment(r.id)" class="text-xs text-slate-400 hover:text-red-500 mt-1">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!commentaires.length" class="text-center py-6 text-sm text-slate-400 dark:text-slate-500">
                Aucun commentaire pour le moment. Soyez le premier !
            </div>
        </div>
    </div>
</template>
