<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    interpellation: { type: Object, required: true },
    topic: { type: Object, required: true },
    comments: { type: Array, default: () => [] },
});

const showDeclineModal = ref(false);

const responseForm = useForm({
    response_content: '',
});

const declineForm = useForm({
    reason: '',
});

function submitResponse() {
    responseForm.post(route('elu.interpellations.respond', props.interpellation.id), {
        preserveScroll: true,
    });
}

function submitDecline() {
    declineForm.post(route('elu.interpellations.decline', props.interpellation.id), {
        onSuccess: () => {
            showDeclineModal.value = false;
        },
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getStatusInfo(status) {
    const statuses = {
        'pending': { label: 'En attente', color: 'amber', icon: '⏳' },
        'answered': { label: 'Répondu', color: 'emerald', icon: '✅' },
        'declined': { label: 'Décliné', color: 'gray', icon: '❌' },
    };
    return statuses[status] || statuses['pending'];
}

const statusInfo = getStatusInfo(props.interpellation.response_status);

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Espace Élu', href: route('elu.dashboard'), icon: '🏛️' },
    { label: 'Interpellations', href: route('elu.interpellations'), icon: '📬' },
    { label: 'Détail', current: true },
];
</script>

<template>
    <Head :title="`Interpellation - ${topic.title}`" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-800 to-violet-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span 
                        class="px-3 py-1 text-sm font-medium rounded-full"
                        :class="{
                            'bg-amber-500/20 text-amber-300': interpellation.response_status === 'pending',
                            'bg-emerald-500/20 text-emerald-300': interpellation.response_status === 'answered',
                            'bg-gray-500/20 text-gray-300': interpellation.response_status === 'declined',
                        }"
                    >
                        {{ statusInfo.icon }} {{ statusInfo.label }}
                    </span>
                    <span class="text-white/70 text-sm">
                        {{ topic.idea_type_info?.icon }} {{ topic.idea_type_info?.label }}
                    </span>
                    <span class="text-white/70 text-sm">
                        {{ topic.scope_info?.icon }} {{ topic.scope_info?.label }}
                    </span>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-white mb-4">
                    📣 {{ topic.title }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-white/70 text-sm">
                    <span>👤 {{ topic.author?.name || 'Anonyme' }}</span>
                    <span>📅 {{ formatDate(topic.published_at) }}</span>
                    <span>👍 {{ topic.votes_pour }} / 👎 {{ topic.votes_contre }}</span>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="space-y-6">
                    
                    <!-- Contenu de l'interpellation -->
                    <Card>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            📝 Message du citoyen
                        </h2>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ topic.description }}</p>
                        </div>

                        <!-- Tags -->
                        <div v-if="topic.tags?.length > 0" class="flex flex-wrap gap-2 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <span 
                                v-for="tag in topic.tags" 
                                :key="tag.id"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm"
                            >
                                {{ tag.icone }} {{ tag.nom }}
                            </span>
                        </div>

                        <!-- Lien vers la page publique -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link 
                                :href="topic.url"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                🔗 Voir sur la page publique →
                            </Link>
                        </div>
                    </Card>

                    <!-- Réponse existante -->
                    <Card v-if="interpellation.response_status === 'answered'" class="bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-2xl">✅</span>
                            <h2 class="text-lg font-semibold text-emerald-800 dark:text-emerald-300">
                                Votre réponse
                            </h2>
                            <span class="text-sm text-emerald-600 dark:text-emerald-400 ml-auto">
                                {{ formatDate(interpellation.answered_at) }}
                            </span>
                        </div>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ interpellation.response_content }}</p>
                        </div>
                    </Card>

                    <!-- Décliné -->
                    <Card v-else-if="interpellation.response_status === 'declined'" class="bg-gray-100 dark:bg-gray-800">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-2xl">❌</span>
                            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                Interpellation déclinée
                            </h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">{{ interpellation.response_content }}</p>
                    </Card>

                    <!-- Formulaire de réponse -->
                    <Card v-else>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            ✍️ Répondre à cette interpellation
                        </h2>
                        
                        <form @submit.prevent="submitResponse" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Votre réponse
                                </label>
                                <textarea
                                    v-model="responseForm.response_content"
                                    rows="8"
                                    placeholder="Rédigez votre réponse au citoyen... (minimum 50 caractères)"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                                    :class="{ 'border-red-500': responseForm.errors.response_content }"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2">
                                    <span 
                                        class="text-xs"
                                        :class="responseForm.response_content.length >= 50 ? 'text-emerald-600' : 'text-gray-500'"
                                    >
                                        {{ responseForm.response_content.length }} / 10000 caractères (min. 50)
                                    </span>
                                    <span v-if="responseForm.errors.response_content" class="text-xs text-red-500">
                                        {{ responseForm.errors.response_content }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="submit"
                                    :disabled="responseForm.processing || responseForm.response_content.length < 50"
                                    :class="[
                                        'px-6 py-3 rounded-xl font-medium transition-all',
                                        responseForm.response_content.length >= 50
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]"
                                >
                                    <span v-if="responseForm.processing">⏳ Publication...</span>
                                    <span v-else>✅ Publier ma réponse</span>
                                </button>
                                
                                <button
                                    type="button"
                                    @click="showDeclineModal = true"
                                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition"
                                >
                                    ❌ Décliner
                                </button>
                            </div>
                        </form>
                    </Card>

                    <!-- Commentaires existants -->
                    <Card v-if="comments.length > 0">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            💬 Discussion ({{ comments.length }})
                        </h2>
                        <div class="space-y-4">
                            <div 
                                v-for="comment in comments" 
                                :key="comment.id"
                                class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ comment.user?.name || 'Anonyme' }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(comment.created_at) }}
                                    </span>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.content }}</p>
                            </div>
                        </div>
                    </Card>

                    <!-- Retour -->
                    <div class="flex justify-between">
                        <Link
                            :href="route('elu.interpellations')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition"
                        >
                            ← Retour aux interpellations
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Décliner -->
        <div 
            v-if="showDeclineModal" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="showDeclineModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    ❌ Décliner cette interpellation ?
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Le citoyen sera informé que vous avez choisi de ne pas répondre. 
                    Vous pouvez optionnellement expliquer pourquoi.
                </p>
                <form @submit.prevent="submitDecline">
                    <textarea
                        v-model="declineForm.reason"
                        rows="3"
                        placeholder="Raison (optionnel)..."
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-4"
                    ></textarea>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showDeclineModal = false"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="declineForm.processing"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl transition"
                        >
                            Confirmer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
