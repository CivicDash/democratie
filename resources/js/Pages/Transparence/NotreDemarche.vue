<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Notre démarche — Transparence CivicDash" />

    <AuthenticatedLayout>
        <div class="bg-gradient-to-b from-slate-900 to-slate-800 py-12 mb-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-3xl font-bold text-white mb-3">Notre démarche</h1>
                <p class="text-slate-300 max-w-2xl mx-auto">
                    CivicDash n'est PAS un copier-coller de Wikipedia.
                    Voici comment nous travaillons.
                </p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 space-y-10">
            <div class="text-center bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-6">
                <p class="text-lg font-semibold text-indigo-900 dark:text-indigo-100">
                    L'algorithme détecte, l'humain vérifie, le citoyen consulte.
                </p>
                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-2">
                    Rien ne s'affiche sans qu'un modérateur ait personnellement vérifié chaque information.
                </p>
            </div>

            <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ stats.total_validees }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Affaires vérifiées</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ stats.total_rejetees }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Faux positifs rejetés</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.taux_rejet_pct }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Taux de rejet (qualité algo)</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.total_sources }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sources vérifiées</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="relative">
                    <div class="absolute left-6 top-12 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    <div class="relative pl-16 pb-8">
                        <div class="absolute left-3 top-1 w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/50 border-2 border-blue-500 flex items-center justify-center text-xs font-bold text-blue-700 dark:text-blue-300">1</div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Détection automatique</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            Nos algorithmes analysent des sources publiques structurées (Wikidata, HATVP, CNCCFP) pour identifier d'éventuelles affaires judiciaires impliquant des élus.
                        </p>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-2 text-xs text-red-700 dark:text-red-300 font-medium">
                            Rien n'est publié à cette étape.
                        </div>
                    </div>

                    <div class="relative pl-16 pb-8">
                        <div class="absolute left-3 top-1 w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 border-2 border-indigo-500 flex items-center justify-center text-xs font-bold text-indigo-700 dark:text-indigo-300">2</div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Vérification humaine</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            Un modérateur CivicDash examine chaque détection. Il doit :
                        </p>
                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                            <li>Vérifier au moins 1 source fiable (presse nationale, décision de justice, rapport officiel)</li>
                            <li>Qualifier précisément le statut judiciaire (mis en examen, condamné, relaxé...)</li>
                            <li>Renseigner les dates et la juridiction</li>
                        </ul>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            Si l'info est incorrecte ou non vérifiable, elle est rejetée. Elle ne sera <strong>jamais</strong> publiée.
                        </p>
                    </div>

                    <div class="relative pl-16 pb-8">
                        <div class="absolute left-3 top-1 w-7 h-7 rounded-full bg-green-100 dark:bg-green-900/50 border-2 border-green-500 flex items-center justify-center text-xs font-bold text-green-700 dark:text-green-300">3</div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Publication sourcée</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            L'information apparaît sur la fiche de l'élu avec :
                        </p>
                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                            <li>Un badge "Vérifié par CivicDash"</li>
                            <li>La liste des sources vérifiables (URL cliquables)</li>
                            <li>Le statut judiciaire précis avec code couleur</li>
                            <li>La mention de présomption d'innocence</li>
                        </ul>
                    </div>

                    <div class="relative pl-16">
                        <div class="absolute left-3 top-1 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/50 border-2 border-amber-500 flex items-center justify-center text-xs font-bold text-amber-700 dark:text-amber-300">4</div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Suivi continu</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            Les affaires sont mises à jour quand la situation évolue : relaxe, appel, condamnation définitive.
                            Tout citoyen ou élu peut contester une information. Un modérateur traite chaque contestation sous 72h.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Ce que nous ne faisons PAS</h2>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Publier des données brutes sans vérification</li>
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Copier-coller Wikipedia ou Wikidata aveuglément</li>
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Afficher des totaux bruts par parti sans ratio normalisé</li>
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Mélanger mis en examen et condamnés</li>
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Inclure des infractions sans lien avec la fonction publique</li>
                    <li class="flex items-start gap-2"><span class="text-red-500 shrink-0 mt-0.5">&#10005;</span> Ignorer les relaxes et acquittements</li>
                </ul>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-6">
                <h2 class="text-lg font-bold text-amber-800 dark:text-amber-200 mb-3">Nos biais connus</h2>
                <ul class="space-y-2 text-sm text-amber-700 dark:text-amber-300">
                    <li><strong>Biais temporel :</strong> les partis anciens cumulent mécaniquement plus d'affaires.</li>
                    <li><strong>Biais de couverture :</strong> les élus nationaux sont mieux documentés que les élus locaux.</li>
                    <li><strong>Biais médiatique :</strong> les élus médiatisés génèrent plus d'articles, donc plus de détections.</li>
                </ul>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-3">
                    C'est pourquoi nous normalisons toujours nos stats par le ratio condamnations / nombre d'élus.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Droit de réponse et contestation</h2>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                    Tout élu ou citoyen peut contester une information en écrivant à
                    <a href="mailto:contact@civicdash.fr" class="text-indigo-600 dark:text-indigo-400 hover:underline">contact@civicdash.fr</a>.
                </p>
                <ol class="text-sm text-gray-700 dark:text-gray-300 space-y-1.5 list-decimal list-inside">
                    <li>L'affaire passe en statut "Contesté"</li>
                    <li>Un modérateur examine la contestation sous 72h</li>
                    <li>Trois issues : maintien (sources confirmées), correction (mise à jour), ou retrait (info fausse)</li>
                </ol>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    Chaque contestation et sa résolution sont tracées dans les logs de modération.
                </p>
            </div>

            <div class="text-center text-sm text-gray-500 dark:text-gray-400 space-y-1">
                <p>Code source : <a href="https://github.com/CivicDash/democratie" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">github.com/CivicDash/democratie</a> (AGPL-3.0)</p>
                <p>Signaler une erreur : <a href="mailto:contact@civicdash.fr" class="text-indigo-600 dark:text-indigo-400 hover:underline">contact@civicdash.fr</a></p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
