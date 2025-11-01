<template>
    <GuestLayout>
        <Head title="Politique de Confidentialité" />

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Politique de Confidentialité
                </h1>
                <p v-if="policy" class="text-sm text-gray-600 dark:text-gray-400">
                    Version {{ policy.version }} • En vigueur depuis le {{ policy.effective_at }}
                </p>
                <p class="mt-2 text-gray-700 dark:text-gray-300">
                    Conforme au Règlement Général sur la Protection des Données (RGPD) - 
                    <a href="https://www.cnil.fr/fr/reglement-europeen-protection-donnees" 
                       target="_blank" 
                       class="text-indigo-600 hover:text-indigo-800 underline">
                        Règlement (UE) 2016/679
                    </a>
                </p>
            </div>

            <!-- Contenu -->
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <!-- Section 1 : Responsable du traitement -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Responsable du traitement des données
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        <strong>Démocratos</strong><br>
                        Plateforme citoyenne de démocratie participative<br>
                        Email : <a href="mailto:contact@demoscratos.fr" class="text-indigo-600 hover:text-indigo-800">contact@demoscratos.fr</a><br>
                        DPO (Délégué à la Protection des Données) : <a href="mailto:dpo@demoscratos.fr" class="text-indigo-600 hover:text-indigo-800">dpo@demoscratos.fr</a>
                    </p>
                </section>

                <!-- Section 2 : Données collectées -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        2. Données personnelles collectées
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Données d'identification (FranceConnect+)</h3>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Identifiant unique FranceConnect+</strong> (sub) : Stocké de manière sécurisée</li>
                            <li><strong>Email</strong> : Pour la communication et la récupération de compte</li>
                            <li><strong>Nom et prénom</strong> : Chiffrés et non exposés publiquement (anonymat par défaut)</li>
                            <li><strong>Date de naissance, sexe, lieu de naissance</strong> : Chiffrés, utilisés uniquement pour vérification d'identité</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-6">Données de profil</h3>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Pseudonyme aléatoire</strong> (ex: Citoyen1234) : Affiché publiquement pour garantir l'anonymat</li>
                            <li><strong>Scope territorial</strong> : National, régional ou départemental</li>
                            <li><strong>Vérification FranceConnect+</strong> : Badge de vérification d'identité</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-6">Données d'activité</h3>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Posts, commentaires, votes sur sujets de débat</li>
                            <li>Participations aux scrutins citoyens (anonymisées via jetons cryptographiques)</li>
                            <li>Statistiques de gamification (XP, niveaux, badges)</li>
                        </ul>
                    </div>
                </section>

                <!-- Section 3 : Finalités -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        3. Finalités du traitement (RGPD Art. 5)
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Authentification sécurisée</strong> via FranceConnect+</li>
                        <li><strong>Participation démocratique</strong> : Débats, votes, propositions citoyennes</li>
                        <li><strong>Prévention de la fraude</strong> : Hash cryptographique pour éviter doublons</li>
                        <li><strong>Statistiques anonymes</strong> : Analyse d'engagement sans données personnelles</li>
                        <li><strong>Communication</strong> : Notifications liées à vos activités (désactivables)</li>
                    </ul>
                </section>

                <!-- Section 4 : Base légale -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        4. Base légale du traitement (RGPD Art. 6)
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Consentement</strong> (Art. 6.1.a) : Acceptation lors de la connexion FranceConnect+</li>
                        <li><strong>Exécution du contrat</strong> (Art. 6.1.b) : Fourniture des services de la plateforme</li>
                        <li><strong>Intérêt légitime</strong> (Art. 6.1.f) : Lutte contre la fraude et sécurité</li>
                    </ul>
                </section>

                <!-- Section 5 : Anonymat et transparence -->
                <section class="mb-8 bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h2 class="text-2xl font-semibold text-blue-900 dark:text-blue-100 mb-4">
                        🔒 5. Anonymat des citoyens (Principe clé)
                    </h2>
                    <div class="space-y-4 text-blue-800 dark:text-blue-200">
                        <p>
                            <strong>Par défaut, votre identité reste anonyme</strong> sur la plateforme :
                        </p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Votre <strong>pseudonyme aléatoire</strong> (ex: Citoyen1234) est affiché publiquement</li>
                            <li>Votre <strong>nom réel est chiffré</strong> et n'apparaît jamais dans les échanges publics</li>
                            <li>Seul votre <strong>ID utilisateur unique</strong> est utilisé en interne</li>
                        </ul>
                        <p class="mt-4">
                            <strong>Exceptions (comptes publics transparents) :</strong>
                        </p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Journalistes vérifiés</li>
                            <li>Élus et législateurs</li>
                            <li>Représentants d'organisations</li>
                            <li>Personnalités publiques (opt-in volontaire)</li>
                        </ul>
                    </div>
                </section>

                <!-- Section 6 : Sécurité -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Sécurité des données (RGPD Art. 32)
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Chiffrement</strong> : AES-256 pour toutes les données sensibles</li>
                        <li><strong>Hash cryptographique</strong> : SHA-256 + PEPPER pour références citoyennes</li>
                        <li><strong>Connexion sécurisée</strong> : HTTPS/TLS obligatoire</li>
                        <li><strong>Authentification forte</strong> : FranceConnect+ (niveau eIDAS substantiel)</li>
                        <li><strong>Surveillance</strong> : Logs d'accès admin tracés et audités</li>
                    </ul>
                </section>

                <!-- Section 7 : Vos droits -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        7. Vos droits (RGPD Art. 15-22)
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Droit d'accès</strong> (Art. 15) : Consulter vos données via "Mon Compte"</li>
                            <li><strong>Droit de rectification</strong> (Art. 16) : Modifier vos informations</li>
                            <li><strong>Droit à l'effacement</strong> (Art. 17) : Supprimer votre compte (anonymisation complète)</li>
                            <li><strong>Droit à la portabilité</strong> (Art. 20) : Exporter vos données (format JSON)</li>
                            <li><strong>Droit d'opposition</strong> (Art. 21) : Refuser certains traitements</li>
                            <li><strong>Droit de retrait du consentement</strong> (Art. 7.3) : À tout moment</li>
                        </ul>
                        <p class="mt-4 font-semibold">
                            Pour exercer vos droits : 
                            <a href="mailto:dpo@demoscratos.fr" class="text-indigo-600 hover:text-indigo-800 underline">
                                dpo@demoscratos.fr
                            </a>
                        </p>
                        <p class="text-sm">
                            Délai de réponse : 30 jours maximum (RGPD Art. 12.3)
                        </p>
                    </div>
                </section>

                <!-- Section 8 : Conservation -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        8. Durée de conservation
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Compte actif</strong> : Tant que le compte existe</li>
                        <li><strong>Après suppression</strong> : Anonymisation immédiate + conservation logs 1 an (obligation légale)</li>
                        <li><strong>Données d'audit</strong> : 3 ans (prévention fraude)</li>
                    </ul>
                </section>

                <!-- Section 9 : Contact CNIL -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        9. Réclamation auprès de la CNIL
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Si vous estimez que vos droits ne sont pas respectés, vous pouvez introduire une réclamation auprès de la 
                        <a href="https://www.cnil.fr/fr/plaintes" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                            Commission Nationale de l'Informatique et des Libertés (CNIL)
                        </a>.
                    </p>
                </section>

                <!-- Section 10 : Modifications -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        10. Modifications de la politique
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Nous nous réservons le droit de modifier cette politique. Vous serez informé par email de tout changement majeur.
                        La version en vigueur est toujours disponible sur cette page.
                    </p>
                </section>
            </div>

            <!-- Footer CTA -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
                    <Link :href="route('home')" 
                          class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        ← Retour à l'accueil
                    </Link>
                    <div class="flex gap-4">
                        <Link :href="route('terms')" 
                              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 underline">
                            Conditions d'utilisation
                        </Link>
                        <Link :href="route('cookies')" 
                              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 underline">
                            Politique des cookies
                        </Link>
                    </div>
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

