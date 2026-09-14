<template>
    <GuestLayout>
        <Head title="Politique des cookies" />

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header avec Logo -->
            <div class="mb-8 text-center">
                <img src="/images/logo.png" alt="CivicDash" class="h-16 mx-auto mb-4" />
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Politique des cookies
                </h1>
                <p v-if="policy" class="text-sm text-gray-600 dark:text-gray-400">
                    Version {{ policy.version }} • En vigueur depuis le {{ policy.effective_at }}
                </p>
                <p class="mt-2 text-gray-700 dark:text-gray-300">
                    Conforme à l'article 82 de la loi Informatique et Libertés et aux
                    <a href="https://www.cnil.fr/fr/cookies-et-autres-traceurs/regles/cookies-solutions-pour-les-outils-de-mesure-daudience"
                       target="_blank"
                       class="text-indigo-600 hover:text-indigo-800 underline">
                        recommandations de la CNIL
                    </a>
                </p>
            </div>

            <!-- L'essentiel, en une phrase -->
            <div class="mb-8 rounded-lg border-2 border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-6">
                <h2 class="text-xl font-semibold text-green-900 dark:text-green-200 mb-2">
                    En résumé
                </h2>
                <p class="text-green-900 dark:text-green-100">
                    <strong>Nous ne déposons aucun cookie publicitaire, aucun cookie de mesure d'audience
                    et aucun cookie de réseau social.</strong> Les seuls cookies déposés sont ceux qui
                    permettent techniquement de vous connecter et de protéger votre session. C'est pourquoi
                    aucune bannière de consentement ne vous est présentée : la loi n'en exige pas pour ces
                    cookies-là, et nous n'en utilisons pas d'autres.
                </p>
            </div>

            <div class="prose prose-lg dark:prose-invert max-w-none">
                <!-- 1. Ce qu'est un cookie -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Ce qu'est un cookie
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Un cookie est un petit fichier texte déposé sur votre appareil par un site que vous
                        visitez. Il permet notamment de vous reconnaître d'une page à l'autre — sans quoi
                        vous devriez vous authentifier à chaque clic.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-3">
                        La réglementation distingue deux catégories. Les cookies
                        <strong>strictement nécessaires</strong> au fonctionnement d'un service demandé par
                        l'utilisateur sont dispensés de consentement. Tous les autres — publicité, mesure
                        d'audience non exemptée, partage social — exigent un consentement préalable.
                        <strong>CivicDash n'utilise que la première catégorie.</strong>
                    </p>
                </section>

                <!-- 2. Cookies déposés -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        2. Les cookies que nous déposons
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        La liste est exhaustive. Vous pouvez la vérifier vous-même dans les outils de
                        développement de votre navigateur (onglet « Application » ou « Stockage »).
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Nom</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Rôle</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Durée</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Consentement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <td class="px-4 py-3"><code>civicdash_session</code></td>
                                    <td class="px-4 py-3">
                                        Identifie votre session de navigation. Sans lui, la connexion est
                                        impossible. Il ne contient qu'un identifiant chiffré : les données de
                                        session sont stockées sur notre serveur, pas dans le cookie.
                                    </td>
                                    <td class="px-4 py-3">2 heures</td>
                                    <td class="px-4 py-3">Non requis (strictement nécessaire)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code>XSRF-TOKEN</code></td>
                                    <td class="px-4 py-3">
                                        Protège contre les attaques par falsification de requête (CSRF) :
                                        il garantit qu'un formulaire soumis en votre nom vient bien de votre
                                        navigateur et non d'un site tiers.
                                    </td>
                                    <td class="px-4 py-3">2 heures</td>
                                    <td class="px-4 py-3">Non requis (sécurité)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code>remember_web_*</code></td>
                                    <td class="px-4 py-3">
                                        Déposé <strong>uniquement</strong> si vous cochez « Se souvenir de
                                        moi » lors de la connexion. Il évite d'avoir à se reconnecter à
                                        chaque visite. Ne pas cocher la case suffit à l'éviter ; la
                                        déconnexion le supprime.
                                    </td>
                                    <td class="px-4 py-3">5 ans, ou jusqu'à déconnexion</td>
                                    <td class="px-4 py-3">Non requis (action demandée par vous)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                        Ces trois cookies sont transmis en <code>Secure</code> (HTTPS uniquement) et
                        <code>SameSite=Lax</code> (non envoyés lors d'une navigation depuis un site tiers).
                        Les deux premiers sont en outre <code>HttpOnly</code> lorsque c'est techniquement
                        possible, c'est-à-dire inaccessibles aux scripts.
                    </p>
                </section>

                <!-- 3. Ce que nous ne déposons pas -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        3. Ce que nous ne déposons pas
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Aucun cookie publicitaire</strong> ni de reciblage. Le service n'affiche pas de publicité.</li>
                        <li><strong>Aucun Google Analytics</strong>, Matomo, Hotjar ou équivalent.</li>
                        <li><strong>Aucun bouton de partage</strong> Facebook, X ou LinkedIn susceptible de déposer un traceur tiers.</li>
                        <li><strong>Aucune police ni bibliothèque chargée depuis un CDN tiers</strong> qui exposerait votre adresse IP à un autre acteur.</li>
                        <li><strong>Aucune revente ni transmission de données</strong> à un partenaire commercial.</li>
                    </ul>
                </section>

                <!-- 4. Mesure d'audience -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        4. Comment nous mesurons l'audience sans vous suivre
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Nous avons besoin de savoir si notre travail est lu. Nous le mesurons
                        <strong>sans cookie et sans identifiant</strong>, en agrégeant les journaux du
                        serveur web : seuls sont conservés un décompte quotidien de pages vues et une
                        estimation du nombre de visiteurs distincts, après séparation des robots
                        d'indexation.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-3">
                        Aucune adresse IP n'est conservée dans ces agrégats, aucun profil n'est constitué,
                        et il n'existe aucun moyen de relier une consultation à une personne. Ce dispositif
                        relève de l'exemption de consentement prévue par la CNIL pour les mesures d'audience
                        strictement limitées à la statistique du site.
                    </p>
                </section>

                <!-- 5. Stockage local -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        5. Stockage local (ce n'est pas un cookie, mais autant le dire)
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        L'interface mémorise quelques préférences dans le stockage local de votre
                        navigateur. Ces données <strong>ne sont jamais transmises au serveur</strong> :
                        elles restent sur votre appareil et disparaissent si vous effacez les données du
                        site.
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><code>theme</code> — votre choix d'affichage clair ou sombre.</li>
                        <li><code>advancedFilters</code> — les filtres de recherche que vous aviez laissés ouverts.</li>
                        <li><code>civicdash_completed_tours</code>, <code>civicdash_pending_tour</code>, <code>welcomeCompleted</code>, <code>democratie_visited</code> — pour ne pas vous reproposer une visite guidée déjà suivie.</li>
                        <li><code>civicdash_hide_discover</code> — si vous avez masqué le bandeau de découverte.</li>
                    </ul>
                </section>

                <!-- 6. Maîtrise -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Comment reprendre la main
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Vous pouvez à tout moment supprimer les cookies de ce site et bloquer leur dépôt
                        depuis les réglages de votre navigateur
                        (<a href="https://support.mozilla.org/fr/kb/protection-renforcee-contre-pistage-firefox-ordinateur" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Firefox</a>,
                        <a href="https://support.google.com/chrome/answer/95647?hl=fr" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Chrome</a>,
                        <a href="https://support.apple.com/fr-fr/guide/safari/sfri11471/mac" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Safari</a>,
                        <a href="https://support.microsoft.com/fr-fr/microsoft-edge" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Edge</a>).
                    </p>
                    <div class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                        <p class="text-amber-900 dark:text-amber-100 text-sm">
                            <strong>À savoir :</strong> refuser <code>civicdash_session</code> et
                            <code>XSRF-TOKEN</code> rend la connexion impossible. La consultation des
                            contenus publics, elle, reste accessible sans aucun cookie.
                        </p>
                    </div>
                </section>

                <!-- 7. Évolutions et contact -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        7. Évolutions de cette politique
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Si nous devions un jour déposer un cookie non exempté de consentement, cette page
                        serait mise à jour <strong>avant</strong> le dépôt et un recueil de consentement
                        serait mis en place. Toute modification est versionnée et datée en tête de page.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-3">
                        Question ou contestation :
                        <a href="mailto:dpo@civis-consilium.eu" class="text-indigo-600 hover:text-indigo-800 underline">dpo@civis-consilium.eu</a>.
                        Vous disposez également du droit d'introduire une réclamation auprès de la
                        <a href="https://www.cnil.fr/fr/plaintes" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">CNIL</a>.
                    </p>
                </section>

                <!-- Navigation -->
                <div class="mt-10 flex flex-wrap gap-4 justify-center border-t border-gray-200 dark:border-gray-700 pt-8">
                    <Link :href="route('privacy')"
                          class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                        Politique de confidentialité
                    </Link>
                    <Link :href="route('terms')"
                          class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Conditions d'utilisation
                    </Link>
                </div>

                <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        © {{ new Date().getFullYear() }} Association Civis-Consilium •
                        <a href="https://civis-consilium.eu" target="_blank" class="hover:text-indigo-600">civis-consilium.eu</a>
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    policy: {
        type: Object,
        default: null,
    },
});
</script>
