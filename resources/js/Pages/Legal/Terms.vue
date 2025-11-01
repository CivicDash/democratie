<template>
    <GuestLayout>
        <Head title="Conditions d'Utilisation" />

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Conditions Générales d'Utilisation
                </h1>
                <p v-if="policy" class="text-sm text-gray-600 dark:text-gray-400">
                    Version {{ policy.version }} • En vigueur depuis le {{ policy.effective_at }}
                </p>
            </div>

            <!-- Contenu -->
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Objet de la plateforme
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Démocratos est une plateforme citoyenne de démocratie participative permettant aux citoyens français de :
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Participer à des débats publics sur des sujets de société</li>
                        <li>Voter sur des propositions citoyennes via scrutins anonymes</li>
                        <li>Suivre l'activité législative (Assemblée nationale & Sénat)</li>
                        <li>Consulter le contexte juridique des propositions de loi</li>
                        <li>Allouer symboliquement un budget participatif</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        2. Acceptation des conditions
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        L'utilisation de la plateforme implique l'acceptation pleine et entière des présentes conditions.
                        Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser la plateforme.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        3. Inscription et compte utilisateur
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Authentification FranceConnect+</h3>
                        <p>
                            L'authentification se fait via <strong>FranceConnect+</strong>, le service d'identification de l'État français.
                            Seuls les citoyens français peuvent créer un compte.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-6">Anonymat par défaut</h3>
                        <p>
                            Votre identité est <strong>anonyme par défaut</strong>. Un pseudonyme aléatoire (ex: Citoyen1234) 
                            est généré automatiquement et affiché publiquement.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-6">Comptes publics</h3>
                        <p>
                            Les journalistes, élus, représentants d'organisations et personnalités publiques peuvent afficher
                            leur nom réel après vérification.
                        </p>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        4. Règles d'utilisation
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p>Vous vous engagez à :</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Respecter la loi</strong> : Pas de contenu illégal, diffamatoire, haineux ou violent</li>
                            <li><strong>Débattre de manière constructive</strong> : Arguments factuels, respect des opinions divergentes</li>
                            <li><strong>Ne pas usurper d'identité</strong> : Un seul compte par citoyen</li>
                            <li><strong>Ne pas spammer</strong> : Pas de publicité, contenu répétitif ou hors-sujet</li>
                            <li><strong>Respecter la vie privée</strong> : Pas de doxxing ou divulgation d'informations personnelles</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        5. Modération
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p>
                            Les modérateurs peuvent masquer, supprimer ou sanctionner tout contenu ou utilisateur
                            ne respectant pas les présentes conditions.
                        </p>
                        <p class="font-semibold">Sanctions possibles :</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Avertissement</strong> : Message privé</li>
                            <li><strong>Mute temporaire</strong> : Interdiction de poster (7-30 jours)</li>
                            <li><strong>Bannissement</strong> : Exclusion définitive pour violations graves</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Propriété intellectuelle
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Le contenu que vous publiez vous appartient. En publiant sur la plateforme, vous accordez à Démocratos
                        une licence non-exclusive pour afficher, reproduire et distribuer votre contenu dans le cadre du service.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        7. Responsabilités
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p>
                            <strong>Démocratos est un facilitateur</strong> de débat démocratique. La plateforme :
                        </p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>N'est pas responsable du contenu publié par les utilisateurs</li>
                            <li>Ne garantit pas l'exactitude des informations législatives (sources officielles recommandées)</li>
                            <li>Ne s'engage pas sur la disponibilité 24/7 du service (maintenance possible)</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        8. Modification des CGU
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Nous nous réservons le droit de modifier ces conditions à tout moment. Vous serez notifié
                        par email en cas de changement majeur. L'utilisation continue de la plateforme après modification
                        vaut acceptation des nouvelles conditions.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        9. Loi applicable
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Les présentes conditions sont régies par le droit français. Tout litige sera soumis aux tribunaux
                        français compétents.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        10. Contact
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Pour toute question sur ces conditions : 
                        <a href="mailto:legal@demoscratos.fr" class="text-indigo-600 hover:text-indigo-800 underline">
                            legal@demoscratos.fr
                        </a>
                    </p>
                </section>
            </div>

            <!-- Footer -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
                    <Link :href="route('home')" 
                          class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        ← Retour à l'accueil
                    </Link>
                    <div class="flex gap-4">
                        <Link :href="route('privacy')" 
                              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 underline">
                            Politique de confidentialité
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

