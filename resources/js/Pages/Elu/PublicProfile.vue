<template>
    <MainLayout :title="elu.nom_complet">
        <Head :title="elu.nom_complet" />

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Identité -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                    <img v-if="elu.photo_url"
                         :src="elu.photo_url"
                         :alt="elu.nom_complet"
                         class="h-28 w-28 rounded-lg object-cover bg-gray-100 dark:bg-gray-700 shrink-0" />
                    <div v-else
                         class="h-28 w-28 rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0 flex items-center justify-center text-3xl text-gray-400"
                         aria-hidden="true">
                        {{ initiales }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ typeLabel }}
                        </p>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ elu.nom_complet }}
                        </h1>

                        <dl class="mt-3 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                            <div v-if="elu.groupe" class="flex gap-2">
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Groupe</dt>
                                <dd>{{ elu.groupe }}</dd>
                            </div>
                            <div v-if="elu.circonscription" class="flex gap-2">
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Circonscription</dt>
                                <dd>{{ elu.circonscription }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <Link v-if="ficheCompleteUrl"
                                  :href="ficheCompleteUrl"
                                  class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition">
                                Voir la fiche complète
                            </Link>
                            <EluFollowButton v-if="isAuthenticated"
                                             :elu-type="elu.type"
                                             :elu-id="String(elu.ref)"
                                             :elu-name="elu.nom_complet"
                                             size="md" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compte vérifié -->
            <div v-if="userAccount"
                 class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium">
                        <span aria-hidden="true">✓</span> Compte vérifié
                    </span>
                    <span v-if="userAccount.verified_at" class="text-xs text-gray-500 dark:text-gray-400">
                        depuis le {{ formatDate(userAccount.verified_at) }}
                    </span>
                </div>

                <p v-if="userAccount.bio" class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ userAccount.bio }}
                </p>
                <p v-else class="text-gray-500 dark:text-gray-400 italic text-sm">
                    Cet élu n'a pas encore rédigé de présentation.
                </p>

                <div v-if="liens.length" class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a v-for="l in liens" :key="l.url"
                       :href="l.url" target="_blank" rel="noopener noreferrer"
                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ l.label }}
                    </a>
                </div>
            </div>

            <div v-else
                 class="mt-6 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    Cet élu n'a pas de compte vérifié sur CivicDash.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    Les informations ci-dessus proviennent des données ouvertes officielles
                    ({{ elu.type === 'depute' ? 'Assemblée nationale' : 'Sénat' }}), pas d'une
                    déclaration de l'intéressé.
                </p>
            </div>

            <!-- Interpellations -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Interpellations citoyennes
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ stats.total_interpellations }} reçue{{ stats.total_interpellations > 1 ? 's' : '' }} •
                        <span :class="stats.answered > 0 ? 'text-green-700 dark:text-green-400 font-medium' : ''">
                            {{ stats.answered }} réponse{{ stats.answered > 1 ? 's' : '' }}
                        </span>
                    </p>
                </div>

                <ul v-if="interpellations.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="i in interpellations" :key="i.id" class="py-4 first:pt-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="statutClasse(i.response_status)">
                                {{ statutLabel(i.response_status) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(i.created_at) }}
                            </span>
                        </div>

                        <component :is="lienTopic(i) ? Link : 'p'"
                                   v-bind="lienTopic(i) ? { href: lienTopic(i) } : {}"
                                   class="block font-medium text-gray-900 dark:text-white"
                                   :class="lienTopic(i) ? 'hover:text-indigo-600 dark:hover:text-indigo-400' : ''">
                            {{ i.topic?.title ?? 'Sujet supprimé' }}
                        </component>

                        <p v-if="i.topic" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Proposé par {{ i.topic.author?.name ?? 'un citoyen' }} •
                            {{ i.topic.votes_pour }} pour / {{ i.topic.votes_contre }} contre
                        </p>
                    </li>
                </ul>

                <div v-else class="text-center py-8">
                    <p class="text-gray-600 dark:text-gray-400">
                        Aucune interpellation publique pour le moment.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                        Les interpellations naissent des idées citoyennes : une idée qui rassemble
                        suffisamment de soutiens est transmise à l'élu concerné.
                    </p>
                </div>
            </div>

            <p class="mt-6 text-xs text-center text-gray-500 dark:text-gray-400">
                Données publiques issues des sources officielles. Une erreur ?
                <a href="mailto:contact@civis-consilium.eu" class="underline hover:text-indigo-600">Signalez-la</a>.
            </p>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import EluFollowButton from '@/Components/EluFollowButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    elu: { type: Object, required: true },
    userAccount: { type: Object, default: null },
    interpellations: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_interpellations: 0, answered: 0 }) },
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const typeLabel = computed(() => ({
    depute: 'Députée ou député',
    senateur: 'Sénatrice ou sénateur',
    maire: 'Maire',
}[props.elu.type] ?? 'Élu'));

const initiales = computed(() => (props.elu.nom_complet ?? '?')
    .split(/\s+/).filter(Boolean).slice(0, 2).map((m) => m[0].toUpperCase()).join(''));

// La fiche détaillée (votes, amendements, activité) est réservée aux comptes connectés.
const ficheCompleteUrl = computed(() => {
    if (!isAuthenticated.value) return null;
    if (props.elu.type === 'depute') return route('representants.deputes.show', props.elu.ref);
    if (props.elu.type === 'senateur') return route('representants.senateurs.show', props.elu.ref);
    return null;
});

const liens = computed(() => {
    const a = props.userAccount;
    if (!a) return [];
    return [
        a.website ? { label: 'Site officiel', url: a.website } : null,
        a.twitter ? { label: '@' + String(a.twitter).replace(/^@/, ''), url: `https://x.com/${String(a.twitter).replace(/^@/, '')}` } : null,
        a.facebook ? { label: 'Facebook', url: a.facebook } : null,
    ].filter(Boolean);
});

function lienTopic(i) {
    if (!i.topic?.slug || !isAuthenticated.value) return null;
    return route('participation.ideas.show', i.topic.slug);
}

function statutLabel(s) {
    return { answered: 'Répondu', viewed: 'Lu', declined: 'Décliné' }[s] ?? 'En attente';
}

function statutClasse(s) {
    return {
        answered: 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        viewed: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        declined: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    }[s] ?? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}
</script>
