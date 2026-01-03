<script setup>
import { ref, computed } from 'vue';
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
const showTemplates = ref(false);
const showTips = ref(true);

const responseForm = useForm({
    response_content: '',
});

const declineForm = useForm({
    reason: '',
});

// Templates de réponse
const responseTemplates = [
    {
        id: 'acknowledgment',
        name: 'Prise en compte',
        icon: '✅',
        template: `Madame, Monsieur,

Je vous remercie pour votre interpellation concernant ${props.topic.title}.

Votre préoccupation a bien été prise en compte. Je m'engage à examiner attentivement les points que vous soulevez et à y apporter une réponse dans les meilleurs délais.

Bien cordialement,`,
    },
    {
        id: 'detailed',
        name: 'Réponse détaillée',
        icon: '📝',
        template: `Madame, Monsieur,

Je vous remercie pour votre interpellation sur le sujet "${props.topic.title}".

[Contexte et analyse de la situation]

[Actions entreprises ou envisagées]

[Calendrier et prochaines étapes]

Je reste à votre disposition pour tout complément d'information.

Bien cordialement,`,
    },
    {
        id: 'redirect',
        name: 'Orientation vers un autre interlocuteur',
        icon: '🔄',
        template: `Madame, Monsieur,

Je vous remercie de m'avoir interpellé sur "${props.topic.title}".

Après examen, il me semble que cette question relève principalement de la compétence de [organisme/élu compétent]. Je vous invite donc à vous rapprocher de [contact].

Néanmoins, je reste attentif(ve) à ce dossier et me tiens disponible pour toute question complémentaire relevant de mes attributions.

Bien cordialement,`,
    },
    {
        id: 'legislative',
        name: 'Action législative',
        icon: '⚖️',
        template: `Madame, Monsieur,

Je vous remercie pour votre interpellation concernant "${props.topic.title}".

Cette question fait l'objet de mes préoccupations et [j'ai déposé un amendement/une proposition de loi / je soutiens activement le texte en cours d'examen] afin d'apporter des réponses concrètes.

[Détails de l'action législative]

Je vous tiendrai informé(e) de l'évolution de ce dossier.

Bien cordialement,`,
    },
];

// Conseils de rédaction
const writingTips = [
    { icon: '👋', text: 'Commencez par remercier le citoyen pour son interpellation' },
    { icon: '🎯', text: 'Répondez directement aux points soulevés dans la question' },
    { icon: '📊', text: 'Citez des chiffres ou des faits concrets si possible' },
    { icon: '🔗', text: 'Mentionnez les actions concrètes que vous avez entreprises ou comptez entreprendre' },
    { icon: '⏱️', text: 'Si vous ne pouvez pas répondre immédiatement, donnez un délai' },
    { icon: '🤝', text: 'Restez courtois et professionnel, même en cas de désaccord' },
];

function applyTemplate(template) {
    responseForm.response_content = template.template;
    showTemplates.value = false;
}

// Calcul du temps depuis la création
const daysSinceCreation = computed(() => {
    if (!props.interpellation.created_at) return 0;
    const created = new Date(props.interpellation.created_at);
    const now = new Date();
    return Math.floor((now - created) / (1000 * 60 * 60 * 24));
});

const urgencyLevel = computed(() => {
    if (daysSinceCreation.value >= 14) return { label: 'Urgente', color: 'rose', icon: '🚨' };
    if (daysSinceCreation.value >= 7) return { label: 'À traiter', color: 'amber', icon: '⚠️' };
    return { label: 'Récente', color: 'emerald', icon: '🟢' };
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
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                ✍️ Répondre à cette interpellation
                            </h2>
                            <!-- Indicateur d'urgence -->
                            <span 
                                class="px-3 py-1 rounded-full text-sm font-medium"
                                :class="{
                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': urgencyLevel.color === 'rose',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': urgencyLevel.color === 'amber',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': urgencyLevel.color === 'emerald',
                                }"
                            >
                                {{ urgencyLevel.icon }} {{ urgencyLevel.label }} · {{ daysSinceCreation }} jour(s)
                            </span>
                        </div>

                        <!-- Conseils de rédaction -->
                        <div v-if="showTips" class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-indigo-800 dark:text-indigo-300">
                                    💡 Conseils pour une réponse efficace
                                </h3>
                                <button 
                                    @click="showTips = false"
                                    class="text-indigo-400 hover:text-indigo-600"
                                >✕</button>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <li 
                                    v-for="tip in writingTips" 
                                    :key="tip.text"
                                    class="flex items-start gap-2 text-sm text-indigo-700 dark:text-indigo-400"
                                >
                                    <span class="shrink-0">{{ tip.icon }}</span>
                                    <span>{{ tip.text }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Modèles de réponse -->
                        <div class="mb-6">
                            <button
                                @click="showTemplates = !showTemplates"
                                class="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition"
                            >
                                📋 {{ showTemplates ? 'Masquer les modèles' : 'Utiliser un modèle de réponse' }}
                                <svg 
                                    class="w-4 h-4 transition-transform" 
                                    :class="{ 'rotate-180': showTemplates }"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="showTemplates" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <button
                                    v-for="template in responseTemplates"
                                    :key="template.id"
                                    @click="applyTemplate(template)"
                                    class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition text-left group"
                                >
                                    <span class="text-2xl group-hover:scale-110 transition-transform">{{ template.icon }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ template.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Cliquez pour appliquer ce modèle
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                        
                        <form @submit.prevent="submitResponse" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Votre réponse
                                </label>
                                <textarea
                                    v-model="responseForm.response_content"
                                    rows="12"
                                    placeholder="Rédigez votre réponse au citoyen... (minimum 50 caractères)"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 resize-y font-sans text-base leading-relaxed"
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

                            <!-- Prévisualisation -->
                            <div v-if="responseForm.response_content.length > 50" class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                    👁️ Aperçu de votre réponse :
                                </p>
                                <div class="prose dark:prose-invert prose-sm max-w-none">
                                    <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ responseForm.response_content }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="submit"
                                    :disabled="responseForm.processing || responseForm.response_content.length < 50"
                                    :class="[
                                        'px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2',
                                        responseForm.response_content.length >= 50
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/25 hover:shadow-xl hover:shadow-emerald-600/30'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]"
                                >
                                    <span v-if="responseForm.processing">⏳ Publication...</span>
                                    <span v-else>✅ Publier ma réponse</span>
                                </button>
                                
                                <button
                                    type="button"
                                    @click="showDeclineModal = true"
                                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition flex items-center gap-2"
                                >
                                    ❌ Décliner
                                </button>
                            </div>

                            <!-- Note informative -->
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                ⚠️ Votre réponse sera publique et visible par tous les citoyens. L'auteur sera notifié.
                            </p>
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
