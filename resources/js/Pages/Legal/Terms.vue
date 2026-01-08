<template>
    <GuestLayout>
        <Head title="Conditions d'Utilisation" />

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header avec Logo -->
            <div class="mb-8 text-center">
                <img src="/images/logo.png" alt="CivicDash" class="h-16 mx-auto mb-4" />
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Conditions Générales d'Utilisation
                </h1>
                <p v-if="policy" class="text-sm text-gray-600 dark:text-gray-400">
                    Version {{ policy.version }} • En vigueur depuis le {{ policy.effective_at }}
                </p>
            </div>

            <!-- Éditeur légal -->
            <div class="mb-8 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Éditeur de la plateforme</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    <strong>Association Civis-Consilium</strong><br>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Association Française Loi 1901</span><br>
                    Site web : <a href="https://civis-consilium.eu" target="_blank" class="text-indigo-600 hover:text-indigo-800">civis-consilium.eu</a><br>
                    Plateforme : <a href="https://objectif2027.fr" target="_blank" class="text-indigo-600 hover:text-indigo-800">objectif2027.fr</a> (CivicDash)
                </p>
            </div>

            <!-- Contenu -->
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Objet de la plateforme
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        <strong>CivicDash</strong> (accessible via objectif2027.fr) est une plateforme citoyenne de démocratie participative 
                        développée par l'Association Civis-Consilium, permettant aux citoyens français de :
                    </p>
                    <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Participer à des débats publics sur des sujets de société</li>
                        <li>Voter sur des propositions citoyennes via scrutins anonymes</li>
                        <li>Suivre l'activité législative (Assemblée nationale & Sénat)</li>
                        <li>Consulter les profils et votes des élus de la République</li>
                        <li>Interpeller leurs représentants élus</li>
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
                            <li><strong>Ne pas partager d'images externes</strong> : Les images et liens externes non autorisés sont filtrés</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        5. Modération
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p>
                            Les modérateurs bénévoles et l'équipe de l'Association Civis-Consilium peuvent masquer, supprimer ou sanctionner 
                            tout contenu ou utilisateur ne respectant pas les présentes conditions.
                        </p>
                        <p class="font-semibold">Sanctions possibles :</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Avertissement</strong> : Message privé</li>
                            <li><strong>Mute temporaire</strong> : Interdiction de poster (7-30 jours)</li>
                            <li><strong>Bannissement</strong> : Exclusion définitive pour violations graves</li>
                        </ul>
                        <p class="mt-4">
                            En cas de désaccord avec une sanction, vous pouvez contacter : 
                            <a href="mailto:moderation@civis-consilium.eu" class="text-indigo-600 hover:text-indigo-800">moderation@civis-consilium.eu</a>
                        </p>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Propriété intellectuelle
                    </h2>
                    <div class="text-gray-700 dark:text-gray-300 space-y-3">
                        <p>
                            Le contenu que vous publiez vous appartient. En publiant sur la plateforme, vous accordez à l'Association Civis-Consilium
                            une licence non-exclusive pour afficher, reproduire et distribuer votre contenu dans le cadre du service.
                        </p>
                        <p>
                            Le code source de CivicDash est <strong>open source</strong> et disponible sur 
                            <a href="https://github.com/CivicDash/democratie" target="_blank" class="text-indigo-600 hover:text-indigo-800">GitHub</a>.
                        </p>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        7. Responsabilités
                    </h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p>
                            <strong>L'Association Civis-Consilium est un facilitateur</strong> de débat démocratique. La plateforme :
                        </p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>N'est pas responsable du contenu publié par les utilisateurs</li>
                            <li>Ne garantit pas l'exactitude des informations législatives (sources officielles recommandées)</li>
                            <li>Ne s'engage pas sur la disponibilité 24/7 du service (maintenance possible)</li>
                            <li>Agrège des données publiques de l'Assemblée nationale, du Sénat et du Gouvernement</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        8. Hébergement
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        La plateforme est hébergée sur des serveurs situés en Union Européenne, 
                        conformément aux exigences du RGPD.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        9. Modification des CGU
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Nous nous réservons le droit de modifier ces conditions à tout moment. Vous serez notifié
                        par email en cas de changement majeur. L'utilisation continue de la plateforme après modification
                        vaut acceptation des nouvelles conditions.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        10. Loi applicable
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Les présentes conditions sont régies par le droit français. Tout litige sera soumis aux tribunaux
                        français compétents.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        11. Contact
                    </h2>
                    <div class="text-gray-700 dark:text-gray-300 bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
                        <p class="mb-2">Pour toute question sur ces conditions :</p>
                        <ul class="space-y-1">
                            <li>
                                <strong>Questions générales :</strong> 
                                <a href="mailto:contact@civis-consilium.eu" class="text-indigo-600 hover:text-indigo-800">contact@civis-consilium.eu</a>
                            </li>
                            <li>
                                <strong>Protection des données :</strong> 
                                <a href="mailto:dpo@civis-consilium.eu" class="text-indigo-600 hover:text-indigo-800">dpo@civis-consilium.eu</a>
                            </li>
                            <li>
                                <strong>Modération :</strong> 
                                <a href="mailto:moderation@civis-consilium.eu" class="text-indigo-600 hover:text-indigo-800">moderation@civis-consilium.eu</a>
                            </li>
                        </ul>
                    </div>
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
                
                <!-- Association info -->
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
