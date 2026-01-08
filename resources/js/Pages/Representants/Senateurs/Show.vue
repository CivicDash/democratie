<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import HatvpDeclarationCard from '@/Components/HatvpDeclarationCard.vue';
import EluFollowButton from '@/Components/EluFollowButton.vue';

const props = defineProps({
  senateur: Object,
});

// États pour les accordéons
const showAllMandats = ref(false);
const showAllCommissions = ref(false);
const showAllMandatsLocaux = ref(false);
const showAllHistoriqueGroupes = ref(false);

// Mandats groupés par type
const mandatsGroupes = computed(() => {
  if (!props.senateur.mandats) return {};
  const grouped = {};
  props.senateur.mandats.forEach(m => {
    const type = m.type || 'Mandat sénatorial';
    if (!grouped[type]) grouped[type] = [];
    grouped[type].push(m);
  });
  return grouped;
});

// Commissions actives vs historiques
const commissionsActives = computed(() => {
  if (!props.senateur.commissions) return [];
  return props.senateur.commissions.filter(c => c.actif);
});

const commissionsHistoriques = computed(() => {
  if (!props.senateur.commissions) return [];
  return props.senateur.commissions.filter(c => !c.actif);
});

// Formater un montant en euros
const formatMontant = (montant) => {
  if (!montant || montant === 0) return '-';
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(montant);
};

// Formater un montant compact (pour le graphique)
const formatMontantCompact = (montant) => {
  if (!montant || montant === 0) return '-';
  if (montant >= 1000000) {
    return (montant / 1000000).toFixed(1) + 'M€';
  }
  if (montant >= 1000) {
    return Math.round(montant / 1000) + 'k€';
  }
  return montant + '€';
};

// Calculer la hauteur de la barre en fonction du montant max
const getMaxRevenu = () => {
  if (!props.senateur.hatvp_summary?.revenus_par_annee) return 1;
  const revenus = Object.values(props.senateur.hatvp_summary.revenus_par_annee);
  return Math.max(...revenus.map(r => r.total || 0), 1);
};

const getBarHeight = (total) => {
  const maxRevenu = getMaxRevenu();
  const percentage = (total / maxRevenu) * 100;
  return `${Math.max(percentage, 5)}%`;
};

const getSegmentHeight = (value, total) => {
  if (!total || total === 0) return '0%';
  return `${(value / total) * 100}%`;
};
</script>

<template>
  <Head :title="`${senateur.nom_complet} - Sénateur`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <Breadcrumb :items="[
          { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
          { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏰' },
          { label: senateur.nom, icon: '👤' }
        ]" />

        <!-- Header avec photo + Wikipedia -->
        <Card>
          <div class="grid md:grid-cols-4 gap-8">
            <!-- Photo -->
            <div class="md:col-span-1">
              <div class="w-48 h-48 mx-auto rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-700 shadow-lg">
                <img
                  v-if="senateur.photo_url"
                  :src="senateur.photo_url"
                  :alt="senateur.nom_complet"
                  class="w-full h-full object-cover"
                  @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'"
                />
                <div class="w-full h-full items-center justify-center text-6xl hidden">
                  👤
                </div>
              </div>
              
              <!-- Liens externes -->
              <div class="mt-4 space-y-2">
                <a
                  v-if="senateur.url_profil"
                  :href="senateur.url_profil"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition text-sm border border-red-200 dark:border-red-800"
                >
                  🏛️ Profil Sénat
                </a>
                <a
                  v-if="senateur.wikipedia?.url"
                  :href="senateur.wikipedia.url"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  📖 Wikipedia
                </a>
              </div>
            </div>

            <!-- Infos principales -->
            <div class="md:col-span-3">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    {{ senateur.nom_complet }}
                  </h1>
                  <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ senateur.profession || 'Profession non renseignée' }}
                  </p>
                  <p v-if="senateur.age" class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    {{ senateur.age }} ans
                    <span v-if="senateur.lieu_naissance"> • Né(e) à {{ senateur.lieu_naissance }}</span>
                  </p>
                </div>
                <div class="flex items-center gap-3">
                  <Badge
                    v-if="senateur.etat"
                    :class="[
                      senateur.etat === 'ACTIF' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
                    ]"
                    class="text-sm px-3 py-1"
                  >
                    {{ senateur.etat }}
                  </Badge>
                  <EluFollowButton
                    elu-type="senateur"
                    :elu-id="senateur.matricule"
                    :elu-name="senateur.nom_complet"
                    :initial-following="senateur.is_followed"
                  />
                </div>
              </div>

              <!-- Wikipedia Extract -->
              <div v-if="senateur.wikipedia?.extract" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 border-l-4 border-blue-500">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {{ senateur.wikipedia.extract }}
                </p>
                <a
                  v-if="senateur.wikipedia.url"
                  :href="senateur.wikipedia.url"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-700 text-xs mt-2 inline-block"
                >
                  Lire la suite sur Wikipedia →
                </a>
              </div>

              <div class="flex flex-wrap gap-3 mb-6">
                <Badge
                  v-if="senateur.groupe"
                  :style="{ backgroundColor: senateur.groupe.couleur, color: '#fff' }"
                  class="text-base px-4 py-2"
                >
                  {{ senateur.groupe.nom }}
                </Badge>
                <Badge v-if="senateur.circonscription" class="text-base px-4 py-2">
                  📍 {{ senateur.circonscription }}
                </Badge>
                <Badge v-if="senateur.commission" class="text-base px-4 py-2">
                  🏛️ {{ senateur.commission }}
                </Badge>
              </div>

              <!-- Contacts - Fiche contact complète -->
              <div class="mb-6 p-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800/50 dark:to-slate-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                  📬 Contact
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                  <!-- Email & Téléphone -->
                  <div class="space-y-2">
                    <a
                      v-if="senateur.email"
                      :href="`mailto:${senateur.email}`"
                      class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition border border-gray-200 dark:border-gray-700"
                    >
                      <span class="text-blue-500">📧</span>
                      <span class="text-sm text-gray-900 dark:text-gray-100 break-all">{{ senateur.email }}</span>
                    </a>
                    <a
                      v-if="senateur.telephone"
                      :href="`tel:${senateur.telephone.replace(/\s/g, '')}`"
                      class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition border border-gray-200 dark:border-gray-700"
                    >
                      <span class="text-green-500">📞</span>
                      <div>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ senateur.telephone }}</span>
                        <span class="text-xs text-gray-500 ml-1">(standard Sénat)</span>
                      </div>
                    </a>
                  </div>
                  <!-- Adresse postale -->
                  <div v-if="senateur.adresse_postale" class="px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start gap-2">
                      <span class="text-amber-500">📮</span>
                      <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                        {{ senateur.adresse_postale }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Statistiques rapides - 4 indicateurs clés -->
              <div v-if="senateur.statistiques" class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6 mb-4">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600">{{ senateur.statistiques.votes_total }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">Votes</div>
                </div>
                <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                  <div class="text-2xl font-bold text-emerald-600">{{ senateur.statistiques.taux_presence }}%</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">Présence</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <div class="text-2xl font-bold text-green-600">{{ senateur.statistiques.amendements_total }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">Amendements</div>
                </div>
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                  <div class="text-2xl font-bold text-purple-600">{{ senateur.statistiques.taux_adoption_amendements }}%</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">Taux adoption</div>
                </div>
              </div>

              <!-- Navigation vers pages détaillées -->
              <div v-if="senateur?.matricule" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link
                  :href="route('representants.senateurs.votes', senateur.matricule)"
                  class="text-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  🗳️ Votes
                </Link>
                <Link
                  :href="route('representants.senateurs.amendements', senateur.matricule)"
                  class="text-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  📝 Amendements
                </Link>
                <Link
                  :href="route('debats.senat.senateur', senateur.matricule)"
                  class="text-center px-4 py-3 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition"
                >
                  🎤 Interventions
                </Link>
                <Link
                  :href="route('representants.senateurs.activite', senateur.matricule)"
                  class="text-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                >
                  📊 Activité
                </Link>
              </div>
            </div>
          </div>
        </Card>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Mandats - Version compacte avec tableau -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📜</span>
              <span>Mandats</span>
              <Badge v-if="senateur.mandats?.length" class="ml-auto bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
                {{ senateur.mandats.length }}
              </Badge>
            </h2>
            
            <div v-if="senateur.mandats && senateur.mandats.length > 0">
              <!-- Tableau compact -->
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                      <th class="py-2 pr-2">Type</th>
                      <th class="py-2 px-2">Période</th>
                      <th class="py-2 pl-2 text-right">Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(mandats, type) in mandatsGroupes" :key="type">
                      <tr 
                        v-for="(mandat, index) in (showAllMandats ? mandats : mandats.slice(0, 2))"
                        :key="`${type}-${index}`"
                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                      >
                        <td class="py-2 pr-2">
                          <div class="font-medium text-gray-900 dark:text-gray-100">{{ type }}</div>
                          <div v-if="mandat.circonscription" class="text-xs text-gray-500 dark:text-gray-400">
                            {{ mandat.circonscription }}
                          </div>
                        </td>
                        <td class="py-2 px-2 text-gray-600 dark:text-gray-400">
                          {{ mandat.date_debut }}
                          <span v-if="mandat.date_fin"> → {{ mandat.date_fin }}</span>
                        </td>
                        <td class="py-2 pl-2 text-right">
                          <Badge 
                            :class="mandat.actif 
                              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
                              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                            class="text-xs"
                          >
                            {{ mandat.actif ? 'En cours' : 'Terminé' }}
                          </Badge>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              
              <!-- Bouton voir plus -->
              <button 
                v-if="senateur.mandats.length > 4"
                @click="showAllMandats = !showAllMandats"
                class="mt-3 w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition flex items-center justify-center gap-1"
              >
                <span v-if="showAllMandats">▲ Réduire</span>
                <span v-else>▼ Voir tous les mandats ({{ senateur.mandats.length }})</span>
              </button>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-6">
              Aucun mandat enregistré
            </div>
          </Card>

          <!-- Commissions - Version compacte avec tableau -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🏛️</span>
              <span>Commissions</span>
              <Badge v-if="senateur.commissions?.length" class="ml-auto bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
                {{ senateur.commissions.length }}
              </Badge>
            </h2>
            
            <div v-if="senateur.commissions && senateur.commissions.length > 0">
              <!-- Commissions actives en premier -->
              <div v-if="commissionsActives.length > 0" class="mb-4">
                <h3 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide mb-2">
                  Actuelles ({{ commissionsActives.length }})
                </h3>
                <div class="space-y-2">
                  <div 
                    v-for="(commission, index) in commissionsActives" 
                    :key="`active-${index}`"
                    class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
                  >
                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                      {{ commission.commission }}
                    </div>
                    <div class="flex items-center justify-between mt-1">
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        Depuis {{ commission.date_debut }}
                      </span>
                      <Badge v-if="commission.fonction" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-xs">
                        {{ commission.fonction }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Commissions historiques (déroulant) -->
              <div v-if="commissionsHistoriques.length > 0">
                <button 
                  @click="showAllCommissions = !showAllCommissions"
                  class="w-full flex items-center justify-between py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hover:text-gray-700 dark:hover:text-gray-300 transition"
                >
                  <span>Historique ({{ commissionsHistoriques.length }})</span>
                  <span class="text-lg">{{ showAllCommissions ? '▲' : '▼' }}</span>
                </button>
                
                <div v-show="showAllCommissions" class="overflow-x-auto mt-2">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-2">Commission</th>
                        <th class="py-2 px-2">Fonction</th>
                        <th class="py-2 pl-2 text-right">Période</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="(commission, index) in commissionsHistoriques"
                        :key="`hist-${index}`"
                        class="border-b border-gray-100 dark:border-gray-800"
                      >
                        <td class="py-2 pr-2 text-gray-700 dark:text-gray-300">
                          {{ commission.commission }}
                        </td>
                        <td class="py-2 px-2 text-gray-500 dark:text-gray-400">
                          {{ commission.fonction || '-' }}
                        </td>
                        <td class="py-2 pl-2 text-right text-xs text-gray-500 dark:text-gray-400">
                          {{ commission.date_debut }} → {{ commission.date_fin }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-6">
              Aucune commission
            </div>
          </Card>
        </div>

        <!-- Derniers Votes - Widget principal -->
        <Card v-if="senateur?.matricule && senateur.derniers_votes && senateur.derniers_votes.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>🗳️</span>
              <span>Derniers votes</span>
            </h2>
            <Link
              :href="route('representants.senateurs.votes', senateur.matricule)"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
            >
              Voir tous les votes →
            </Link>
          </div>
          
          <div class="space-y-3">
            <div
              v-for="vote in senateur.derniers_votes"
              :key="vote.id"
              class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-red-400 dark:hover:border-red-600 transition"
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
                  {{ vote.intitule }}
                </div>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                  <span>{{ vote.date }}</span>
                  <span v-if="vote.scrutin" class="text-xs">•</span>
                  <span v-if="vote.scrutin" :class="vote.scrutin.resultat?.includes('Adopté') ? 'text-green-600' : 'text-red-600'">
                    {{ vote.scrutin.resultat }}
                  </span>
                </div>
              </div>
              
              <!-- Résultats du scrutin -->
              <div v-if="vote.scrutin" class="hidden md:flex items-center gap-4 text-sm">
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
                {{ vote.position?.toUpperCase() || 'N/A' }}
              </Badge>
            </div>
          </div>
        </Card>

        <!-- Déclarations HATVP - Composant standardisé -->
        <HatvpDeclarationCard 
          v-if="senateur.hatvp_summary" 
          :summary="senateur.hatvp_summary"
          parlementaire-type="senateur"
        />

        <!-- Historique des déclarations HATVP -->
        <Card v-if="senateur.declarations_hatvp && senateur.declarations_hatvp.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📜</span>
            <span>Historique des déclarations</span>
            <Badge class="ml-2 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 text-xs">
              {{ senateur.declarations_hatvp.length }}
            </Badge>
          </h2>

          <!-- Liste des déclarations -->
          <div class="space-y-3">
            <a
              v-for="(declaration, index) in senateur.declarations_hatvp"
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

        <!-- Historique des groupes - Version compacte -->
        <Card v-if="senateur.historique_groupes && senateur.historique_groupes.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>🎨</span>
              <span>Groupes parlementaires</span>
            </h2>
            <Badge class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
              {{ senateur.historique_groupes.length }}
            </Badge>
          </div>
          
          <!-- Tableau compact -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                  <th class="py-2 pr-2">Groupe</th>
                  <th class="py-2 px-2">Début</th>
                  <th class="py-2 pl-2 text-right">Fin</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(groupe, index) in (showAllHistoriqueGroupes ? senateur.historique_groupes : senateur.historique_groupes.slice(0, 3))"
                  :key="index"
                  class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                >
                  <td class="py-2 pr-2 font-medium text-gray-900 dark:text-gray-100">
                    {{ groupe.groupe }}
                  </td>
                  <td class="py-2 px-2 text-gray-600 dark:text-gray-400">
                    {{ groupe.date_debut }}
                  </td>
                  <td class="py-2 pl-2 text-right">
                    <span v-if="groupe.date_fin" class="text-gray-500 dark:text-gray-400">{{ groupe.date_fin }}</span>
                    <Badge v-else class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs">
                      En cours
                    </Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Bouton voir plus -->
          <button 
            v-if="senateur.historique_groupes.length > 3"
            @click="showAllHistoriqueGroupes = !showAllHistoriqueGroupes"
            class="mt-3 w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition flex items-center justify-center gap-1"
          >
            <span v-if="showAllHistoriqueGroupes">▲ Réduire</span>
            <span v-else>▼ Voir tout l'historique ({{ senateur.historique_groupes.length }})</span>
          </button>
        </Card>

        <!-- Mandats locaux - Version compacte avec tableau -->
        <Card v-if="senateur.mandats_locaux && senateur.mandats_locaux.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <span>🏛️</span>
              <span>Mandats locaux et autres fonctions</span>
            </h2>
            <Badge class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
              {{ senateur.mandats_locaux.length }}
            </Badge>
          </div>
          
          <!-- Tableau compact -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                  <th class="py-2 pr-2">Type</th>
                  <th class="py-2 px-2">Fonction</th>
                  <th class="py-2 px-2">Collectivité</th>
                  <th class="py-2 pl-2 text-right">Période</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(mandat, index) in (showAllMandatsLocaux ? senateur.mandats_locaux : senateur.mandats_locaux.slice(0, 4))"
                  :key="index"
                  class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                >
                  <td class="py-2 pr-2">
                    <Badge 
                      :class="{
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': mandat.type_mandat === 'MUNICIPAL',
                        'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': mandat.type_mandat === 'DEPARTEMENTAL' || mandat.type_mandat === 'REGIONAL',
                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400': mandat.type_mandat === 'DEPUTE',
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': mandat.type_mandat === 'EUROPEEN',
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': !['MUNICIPAL', 'DEPARTEMENTAL', 'REGIONAL', 'DEPUTE', 'EUROPEEN'].includes(mandat.type_mandat)
                      }"
                      class="text-xs whitespace-nowrap"
                    >
                      {{ mandat.type_mandat === 'MUNICIPAL' ? '🏘️' : mandat.type_mandat === 'DEPARTEMENTAL' || mandat.type_mandat === 'REGIONAL' ? '🗺️' : mandat.type_mandat === 'DEPUTE' ? '🏛️' : mandat.type_mandat === 'EUROPEEN' ? '🇪🇺' : '📋' }}
                      {{ mandat.type_mandat }}
                    </Badge>
                  </td>
                  <td class="py-2 px-2 font-medium text-gray-900 dark:text-gray-100">
                    {{ mandat.fonction }}
                  </td>
                  <td class="py-2 px-2 text-gray-600 dark:text-gray-400">
                    {{ mandat.collectivite }}
                  </td>
                  <td class="py-2 pl-2 text-right">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ mandat.periode }}</span>
                    <Badge v-if="mandat.en_cours" class="ml-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs">
                      ✓
                    </Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Bouton voir plus -->
          <button 
            v-if="senateur.mandats_locaux.length > 4"
            @click="showAllMandatsLocaux = !showAllMandatsLocaux"
            class="mt-3 w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition flex items-center justify-center gap-1"
          >
            <span v-if="showAllMandatsLocaux">▲ Réduire</span>
            <span v-else>▼ Voir tous les mandats ({{ senateur.mandats_locaux.length }})</span>
          </button>
        </Card>

        <!-- Formation et études -->
        <Card v-if="senateur.etudes && senateur.etudes.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>🎓</span>
            <span>Formation et études</span>
          </h2>
          <div class="space-y-3">
            <div
              v-for="(etude, index) in senateur.etudes"
              :key="index"
              class="p-4 rounded-lg bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    {{ etude.diplome || 'Diplôme non précisé' }}
                  </div>
                  <div v-if="etude.domaine" class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ etude.domaine }}
                  </div>
                  <div v-if="etude.etablissement" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ etude.etablissement }}
                  </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                  <Badge v-if="etude.niveau" class="bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                    {{ etude.niveau }}
                  </Badge>
                  <span v-if="etude.annee" class="text-xs text-gray-500 dark:text-gray-500">
                    {{ etude.annee }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Adresse postale -->
        <Card v-if="senateur.adresse_postale">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>📮</span>
            <span>Adresse postale</span>
          </h2>
          <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ senateur.adresse_postale }}
            </p>
          </div>
        </Card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
