<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import HatvpDeclarationCard from '@/Components/HatvpDeclarationCard.vue';
import EluFollowButton from '@/Components/EluFollowButton.vue';

const props = defineProps({
  depute: Object,
});

// Formater la date pour les questions
const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const hasReseauxSociaux = computed(() => {
  return props.depute.reseaux_sociaux?.twitter ||
         props.depute.reseaux_sociaux?.facebook ||
         props.depute.reseaux_sociaux?.linkedin ||
         props.depute.reseaux_sociaux?.instagram;
});

// Extraire les identifiants des URLs
const extractTwitterHandle = (url) => {
  if (!url) return null;
  const match = url.match(/twitter\.com\/([^/?]+)/i) || url.match(/x\.com\/([^/?]+)/i);
  return match ? '@' + match[1] : null;
};

const extractFacebookHandle = (url) => {
  if (!url) return null;
  const match = url.match(/facebook\.com\/([^/?]+)/i);
  return match ? match[1] : null;
};

const extractLinkedInHandle = (url) => {
  if (!url) return null;
  const match = url.match(/linkedin\.com\/in\/([^/?]+)/i);
  return match ? match[1] : null;
};

const extractInstagramHandle = (url) => {
  if (!url) return null;
  const match = url.match(/instagram\.com\/([^/?]+)/i);
  return match ? '@' + match[1] : null;
};

// Format montant avec séparateur de milliers
const formatMontant = (montant) => {
  if (!montant && montant !== 0) return '-';
  const num = typeof montant === 'string' ? parseFloat(montant.replace(/\s/g, '').replace(',', '.')) : montant;
  if (isNaN(num)) return montant;
  return new Intl.NumberFormat('fr-FR').format(Math.round(num));
};


</script>

<template>
  <Head :title="`${depute.nom_complet} - Député`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb -->
        <Breadcrumb :items="[
          { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
          { label: 'Députés', href: route('representants.deputes.index'), icon: '👥' },
          { label: depute.nom, icon: '👤' }
        ]" />

        <!-- Header avec photo + Wikipedia -->
        <Card>
          <div class="grid md:grid-cols-4 gap-8">
            <!-- Photo -->
            <div class="md:col-span-1">
              <div class="w-48 h-48 mx-auto rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-700 shadow-lg">
                <img
                  v-if="depute.photo_url"
                  :src="depute.photo_url"
                  :alt="depute.nom_complet"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                  👤
                </div>
              </div>
              
              <!-- Liens externes -->
              <div class="mt-4 space-y-2">
                <a
                  v-if="depute.wikipedia.url"
                  :href="depute.wikipedia.url"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  📖 Wikipedia
                </a>
                <a
                  v-if="depute.url_hatvp"
                  :href="depute.url_hatvp"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  💰 HATVP
                </a>
              </div>
            </div>

            <!-- Infos principales -->
            <div class="md:col-span-3">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {{ depute.nom_complet }}
                  </h1>
                  <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ depute.profession || 'Profession non renseignée' }}
                  </p>
                  <p v-if="depute.age" class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    {{ depute.age }} ans
                    <span v-if="depute.lieu_naissance"> • Né(e) à {{ depute.lieu_naissance }}</span>
                  </p>
                </div>
                <div class="flex items-center gap-3">
                  <Badge
                    v-if="depute.trigramme"
                    class="text-lg px-4 py-2"
                  >
                    {{ depute.trigramme }}
                  </Badge>
                  <EluFollowButton
                    elu-type="depute"
                    :elu-id="depute.uid"
                    :elu-name="depute.nom_complet"
                    :initial-following="depute.is_followed"
                  />
                </div>
              </div>

              <!-- Wikipedia Extract -->
              <div v-if="depute.wikipedia.extract" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {{ depute.wikipedia.extract }}
                </p>
                <a
                  v-if="depute.wikipedia.url"
                  :href="depute.wikipedia.url"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-700 text-xs mt-2 inline-block"
                >
                  Lire la suite sur Wikipedia →
                </a>
              </div>

              <div class="flex flex-wrap gap-3 mb-6">
                <Badge
                  v-if="depute.groupe"
                  :style="{ backgroundColor: depute.groupe.couleur, color: '#fff' }"
                  class="text-base px-4 py-2"
                >
                  {{ depute.groupe.nom }}
                </Badge>
                <Badge v-if="depute.categorie_socio_pro" class="text-base px-4 py-2">
                  💼 {{ depute.categorie_socio_pro }}
                </Badge>
              </div>

              <!-- Stats rapides - 5 indicateurs clés -->
              <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ depute.statistiques.votes_total }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Votes</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3 text-center">
                  <div class="text-2xl font-bold text-emerald-600">{{ depute.statistiques.taux_presence }}%</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Présence</div>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 text-center">
                  <div class="text-2xl font-bold text-amber-600">{{ depute.statistiques.discipline_groupe }}%</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Discipline groupe</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                  <div class="text-2xl font-bold text-green-600">{{ depute.statistiques.amendements_total }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Amendements</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                  <div class="text-2xl font-bold text-purple-600">{{ depute.statistiques.taux_adoption_amendements }}%</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Taux adoption</div>
                </div>
              </div>

              <!-- Navigation vers pages détaillées -->
              <div v-if="depute?.uid" class="grid grid-cols-3 gap-3 mt-6">
                <Link
                  :href="route('representants.deputes.votes', depute.uid)"
                  class="text-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  🗳️ Voir les votes
                </Link>
                <Link
                  :href="route('representants.deputes.amendements', depute.uid)"
                  class="text-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  📝 Amendements
                </Link>
                <Link
                  :href="route('representants.deputes.activite', depute.uid)"
                  class="text-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                >
                  📊 Activité
                </Link>
              </div>
            </div>
          </div>
        </Card>

        <!-- Wikipedia -->
        <Card v-if="depute.wikipedia">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📖</span>
            <span>Wikipedia</span>
          </h2>
          <div class="grid md:grid-cols-3 gap-6">
            <div v-if="depute.wikipedia.photo" class="flex justify-center">
              <img
                :src="depute.wikipedia.photo"
                :alt="depute.nom_complet"
                class="rounded-lg shadow-lg max-h-64 object-cover"
              />
            </div>
            <div :class="depute.wikipedia.photo ? 'md:col-span-2' : 'md:col-span-3'">
              <p v-if="depute.wikipedia.extract" class="text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
                {{ depute.wikipedia.extract }}
              </p>
              <a
                v-if="depute.wikipedia.url"
                :href="depute.wikipedia.url"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                <span>🔗</span>
                <span>Voir la page Wikipedia</span>
              </a>
            </div>
          </div>
        </Card>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Mandats -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📜</span>
              <span>Mandats</span>
            </h2>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="mandat in depute.mandats"
                :key="mandat.uid"
                :class="[
                  'p-3 rounded-lg border',
                  mandat.actif 
                    ? 'border-green-300 bg-green-50 dark:bg-green-900/20' 
                    : 'border-gray-200 dark:border-gray-700'
                ]"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ mandat.organe?.nom || mandat.type }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ mandat.date_debut }} 
                      <span v-if="mandat.date_fin">→ {{ mandat.date_fin }}</span>
                      <span v-else class="text-green-600 font-medium">→ En cours</span>
                    </div>
                  </div>
                  <Badge v-if="mandat.organe?.sigle" class="text-xs">
                    {{ mandat.organe.sigle }}
                  </Badge>
                </div>
              </div>
            </div>
          </Card>

          <!-- Commissions -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🏛️</span>
              <span>Commissions actuelles</span>
            </h2>
            <div v-if="depute.commissions.length > 0" class="space-y-3">
              <div
                v-for="commission in depute.commissions"
                :key="commission.uid"
                class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
              >
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ commission.nom }}
                </div>
                <div v-if="commission.sigle" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ commission.sigle }}
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
              Aucune commission
            </div>
          </Card>
        </div>

        <!-- Derniers Votes - Widget principal -->
        <Card v-if="depute?.uid && depute.derniers_votes && depute.derniers_votes.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>🗳️</span>
              <span>Derniers votes</span>
            </h2>
            <Link
              :href="route('representants.deputes.votes', depute.uid)"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
            >
              Voir tous les votes →
            </Link>
          </div>
          
          <div class="space-y-3">
            <div
              v-for="vote in depute.derniers_votes"
              :key="vote.id"
              class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 transition"
            >
              <!-- Position du vote -->
              <div class="flex-shrink-0">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-12 h-12 rounded-full text-white font-bold text-sm',
                    vote.position === 'pour' ? 'bg-green-500' :
                    vote.position === 'contre' ? 'bg-red-500' :
                    vote.position === 'abstention' ? 'bg-yellow-500' : 'bg-gray-400'
                  ]"
                >
                  {{ vote.position === 'pour' ? '✓' : vote.position === 'contre' ? '✗' : '○' }}
                </span>
              </div>
              
              <!-- Infos scrutin -->
              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 dark:text-gray-100 line-clamp-2">
                  {{ vote.scrutin.titre }}
                </div>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                  <span>{{ vote.date }}</span>
                  <span class="text-xs">•</span>
                  <span :class="vote.scrutin.resultat?.includes('Adopté') ? 'text-green-600' : 'text-red-600'">
                    {{ vote.scrutin.resultat }}
                  </span>
                </div>
              </div>
              
              <!-- Résultats du scrutin -->
              <div class="hidden md:flex items-center gap-4 text-sm">
                <span class="text-green-600">✓ {{ vote.scrutin.pour }}</span>
                <span class="text-red-600">✗ {{ vote.scrutin.contre }}</span>
              </div>
              
              <!-- Badge position -->
              <Badge
                :class="[
                  vote.position === 'pour' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                  vote.position === 'contre' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                ]"
              >
                {{ vote.position.toUpperCase() }}
              </Badge>
            </div>
          </div>
        </Card>

        <!-- Questions au Gouvernement -->
        <Card v-if="depute?.uid && depute.dernieres_questions && depute.dernieres_questions.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>❓</span>
              <span>Questions au Gouvernement</span>
              <Badge v-if="depute.questions_stats?.total" class="ml-2 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 text-xs">
                {{ depute.questions_stats.total }} questions
              </Badge>
            </h2>
            <Link
              :href="route('questions.depute', depute.uid)"
              class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
            >
              Voir toutes les questions →
            </Link>
          </div>
          
          <!-- Stats rapides -->
          <div v-if="depute.questions_stats" class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ depute.questions_stats.total || 0 }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ depute.questions_stats.repondues || 0 }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Répondues</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ depute.questions_stats.en_attente || 0 }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">En attente</div>
            </div>
          </div>
          
          <!-- Liste des dernières questions -->
          <div class="space-y-3">
            <Link
              v-for="q in depute.dernieres_questions"
              :key="q.uid"
              :href="route('questions.show', q.uid)"
              class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition group"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded-full">
                      {{ q.type }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(q.date_question) }}
                    </span>
                  </div>
                  <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-2">
                    {{ q.analyse || q.rubrique || 'Question #' + q.numero }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <span v-if="q.ministere_sigle">🏛️ {{ q.ministere_sigle }}</span>
                  </div>
                </div>
                <div class="shrink-0">
                  <Badge
                    :class="q.date_reponse 
                      ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' 
                      : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'"
                  >
                    {{ q.date_reponse ? '✅ Répondue' : '⏳ En attente' }}
                  </Badge>
                </div>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Déclarations HATVP (Transparence) -->
        <Card v-if="depute.declarations_hatvp && depute.declarations_hatvp.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📋</span>
            <span>Déclarations d'intérêts et de patrimoine</span>
            <Badge class="ml-2 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 text-xs">
              HATVP
            </Badge>
          </h2>
          
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Déclarations publiques auprès de la Haute Autorité pour la Transparence de la Vie Publique
          </p>

          <div class="space-y-3">
            <a
              v-for="(declaration, index) in depute.declarations_hatvp"
              :key="index"
              :href="declaration.url"
              target="_blank"
              class="block p-4 rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 hover:shadow-md transition"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span v-if="declaration.type === 'DIA' || declaration.type === 'DIAI' || declaration.type === 'DIAC'">📝</span>
                    <span v-else>💰</span>
                    {{ declaration.type_label }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Déposée le {{ declaration.date_depot }}
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge 
                    :class="[
                      declaration.type.startsWith('D') && declaration.type.includes('I') 
                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                        : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                    ]"
                  >
                    {{ declaration.type }}
                  </Badge>
                  <span class="text-amber-600 dark:text-amber-400">→</span>
                </div>
              </div>
            </a>
          </div>

          <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400">
            <p>
              💡 <strong>DIA</strong> = Déclaration d'Intérêts et d'Activités • 
              <strong>DSP</strong> = Déclaration de Situation Patrimoniale
            </p>
            <a 
              href="https://www.hatvp.fr" 
              target="_blank"
              class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 mt-2 inline-block"
            >
              En savoir plus sur la HATVP →
            </a>
          </div>
        </Card>

        <!-- Déclarations HATVP - Composant standardisé -->
        <HatvpDeclarationCard 
          v-if="depute.hatvp_summary" 
          :summary="depute.hatvp_summary"
          parlementaire-type="depute"
        />

        <!-- Contacts -->
        <Card v-if="(depute.adresses && depute.adresses.length > 0) || hasReseauxSociaux">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📧</span>
            <span>Contacts</span>
          </h2>
          
          <!-- Réseaux sociaux -->
          <div v-if="hasReseauxSociaux" class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
              Réseaux sociaux
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Twitter / X -->
              <a
                v-if="depute.reseaux_sociaux.twitter"
                :href="depute.reseaux_sociaux.twitter"
                target="_blank"
                class="flex items-center gap-3 p-4 rounded-lg bg-black hover:bg-gray-800 text-white transition group"
              >
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 group-hover:bg-white/20 transition flex-shrink-0">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs text-white/60 uppercase font-medium">Twitter / X</div>
                  <div class="text-sm font-semibold truncate">
                    {{ extractTwitterHandle(depute.reseaux_sociaux.twitter) || 'Profil' }}
                  </div>
                </div>
              </a>

              <!-- Facebook -->
              <a
                v-if="depute.reseaux_sociaux.facebook"
                :href="depute.reseaux_sociaux.facebook"
                target="_blank"
                class="flex items-center gap-3 p-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition group"
              >
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 group-hover:bg-white/20 transition flex-shrink-0">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs text-white/60 uppercase font-medium">Facebook</div>
                  <div class="text-sm font-semibold truncate">
                    {{ extractFacebookHandle(depute.reseaux_sociaux.facebook) || 'Profil' }}
                  </div>
                </div>
              </a>

              <!-- LinkedIn -->
              <a
                v-if="depute.reseaux_sociaux.linkedin"
                :href="depute.reseaux_sociaux.linkedin"
                target="_blank"
                class="flex items-center gap-3 p-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white transition group"
              >
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 group-hover:bg-white/20 transition flex-shrink-0">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs text-white/60 uppercase font-medium">LinkedIn</div>
                  <div class="text-sm font-semibold truncate">
                    {{ extractLinkedInHandle(depute.reseaux_sociaux.linkedin) || 'Profil' }}
                  </div>
                </div>
              </a>

              <!-- Instagram -->
              <a
                v-if="depute.reseaux_sociaux.instagram"
                :href="depute.reseaux_sociaux.instagram"
                target="_blank"
                class="flex items-center gap-3 p-4 rounded-lg bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 hover:opacity-90 text-white transition group"
              >
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 group-hover:bg-white/20 transition flex-shrink-0">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs text-white/60 uppercase font-medium">Instagram</div>
                  <div class="text-sm font-semibold truncate">
                    {{ extractInstagramHandle(depute.reseaux_sociaux.instagram) || 'Profil' }}
                  </div>
                </div>
              </a>
            </div>
          </div>

          <!-- Adresses -->
          <div v-if="depute.adresses && depute.adresses.length > 0">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
              Coordonnées
            </h3>
            <div class="grid md:grid-cols-2 gap-4">
              <div
                v-for="(adresse, index) in depute.adresses"
                :key="index"
                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
              >
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                  {{ adresse.type }}
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  <div v-if="adresse.intitule">{{ adresse.intitule }}</div>
                  <div v-if="adresse.valeur">{{ adresse.valeur }}</div>
                  <div v-if="adresse.numero_rue || adresse.nom_rue">
                    {{ adresse.numero_rue }} {{ adresse.nom_rue }}
                  </div>
                  <div v-if="adresse.code_postal || adresse.ville">
                    {{ adresse.code_postal }} {{ adresse.ville }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
