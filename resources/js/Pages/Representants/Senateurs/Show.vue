<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

defineProps({
  senateur: Object,
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
</script>

<template>
  <Head :title="`${senateur.nom_complet} - Sénateur`" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6" style="max-width: 100%;">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <Link :href="route('representants.mes-representants')" class="hover:text-blue-600">
            Mes Représentants
          </Link>
          <span>/</span>
          <Link :href="route('representants.senateurs.index')" class="hover:text-red-600">
            Sénateurs
          </Link>
          <span>/</span>
          <span class="text-gray-900 dark:text-gray-100">{{ senateur.nom }}</span>
        </div>

        <!-- Header avec photo + Wikipedia -->
        <Card>
          <div class="grid md:grid-cols-4 gap-8">
            <!-- Photo -->
            <div class="md:col-span-1">
              <div class="w-48 h-48 mx-auto rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-700 shadow-lg">
                <img
                  v-if="senateur.wikipedia?.photo"
                  :src="senateur.wikipedia.photo"
                  :alt="senateur.nom_complet"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                  👤
                </div>
              </div>
              
              <!-- Liens externes -->
              <div class="mt-4 space-y-2">
                <a
                  v-if="senateur.wikipedia?.url"
                  :href="senateur.wikipedia.url"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  📖 Wikipedia
                </a>
                <a
                  v-if="senateur.url_profil"
                  :href="senateur.url_profil"
                  target="_blank"
                  class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm"
                >
                  🏛️ Profil Sénat
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

              <!-- Contacts -->
              <div v-if="senateur.email || senateur.telephone" class="flex flex-wrap gap-4 mb-6">
                <a
                  v-if="senateur.email"
                  :href="`mailto:${senateur.email}`"
                  class="flex items-center gap-2 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition"
                >
                  <span>📧</span>
                  <span class="text-sm text-gray-900 dark:text-gray-100">{{ senateur.email }}</span>
                </a>
                <a
                  v-if="senateur.telephone"
                  :href="`tel:${senateur.telephone}`"
                  class="flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition"
                >
                  <span>📞</span>
                  <span class="text-sm text-gray-900 dark:text-gray-100">{{ senateur.telephone }}</span>
                </a>
              </div>

              <!-- Statistiques rapides -->
              <div v-if="senateur.statistiques" class="grid grid-cols-3 gap-4 mt-6 mb-4">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600">{{ senateur.statistiques.votes_total }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">Votes</div>
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
              <div class="grid grid-cols-3 gap-3">
                <Link
                  :href="route('representants.senateurs.votes', senateur.matricule)"
                  class="text-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  🗳️ Voir les votes
                </Link>
                <Link
                  :href="route('representants.senateurs.amendements', senateur.matricule)"
                  class="text-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  📝 Amendements
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
          <!-- Mandats -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>📜</span>
              <span>Mandats</span>
            </h2>
            <div v-if="senateur.mandats && senateur.mandats.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(mandat, index) in senateur.mandats"
                :key="index"
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
                      {{ mandat.type || 'Mandat sénatorial' }}
                    </div>
                    <div v-if="mandat.circonscription" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ mandat.circonscription }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ mandat.date_debut }} 
                      <span v-if="mandat.date_fin">→ {{ mandat.date_fin }}</span>
                      <span v-else class="text-green-600 font-medium">→ En cours</span>
                    </div>
                  </div>
                  <Badge v-if="mandat.numero" class="text-xs">
                    N°{{ mandat.numero }}
                  </Badge>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
              Aucun mandat enregistré
            </div>
          </Card>

          <!-- Commissions -->
          <Card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
              <span>🏛️</span>
              <span>Commissions</span>
            </h2>
            <div v-if="senateur.commissions && senateur.commissions.length > 0" class="space-y-3">
              <div
                v-for="(commission, index) in senateur.commissions"
                :key="index"
                class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
              >
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ commission.commission }}
                </div>
                <div v-if="commission.fonction" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ commission.fonction }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                  {{ commission.date_debut }}
                  <span v-if="commission.date_fin"> → {{ commission.date_fin }}</span>
                  <span v-else class="text-green-600 font-medium"> → En cours</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
              Aucune commission
            </div>
          </Card>
        </div>

        <!-- Déclarations HATVP (Transparence) - Placé après mandats et commissions -->
        <Card v-if="senateur.declarations_hatvp && senateur.declarations_hatvp.length > 0">
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

          <!-- Résumé HATVP consolidé -->
          <div v-if="senateur.hatvp_summary" class="mb-6 p-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30 rounded-xl border border-amber-200 dark:border-amber-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div class="text-center p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                <div class="text-2xl font-bold text-amber-700 dark:text-amber-400">
                  {{ senateur.hatvp_summary.nombre_mandats || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Mandats cumulés</div>
              </div>
              <div class="text-center p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                  {{ senateur.hatvp_summary.nombre_emplois || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Fonctions/Emplois</div>
              </div>
              <div class="text-center p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                  {{ senateur.hatvp_summary.nombre_collaborateurs || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Collaborateurs</div>
              </div>
              <div class="text-center p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ senateur.hatvp_summary.declaration_date }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Dernière déclaration</div>
              </div>
            </div>

            <!-- Revenus par année -->
            <div v-if="senateur.hatvp_summary.revenus_par_annee && Object.keys(senateur.hatvp_summary.revenus_par_annee).length > 0" class="mt-4">
              <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-2">
                💰 Revenus déclarés par année
              </h4>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-400 border-b border-amber-200 dark:border-amber-700">
                      <th class="py-2 px-2">Année</th>
                      <th class="py-2 px-2 text-right">Mandats</th>
                      <th class="py-2 px-2 text-right">Activités pro.</th>
                      <th class="py-2 px-2 text-right">Consultant</th>
                      <th class="py-2 px-2 text-right">Dirigeant</th>
                      <th class="py-2 px-2 text-right font-bold">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr 
                      v-for="(revenus, annee) in senateur.hatvp_summary.revenus_par_annee" 
                      :key="annee"
                      class="border-b border-amber-100 dark:border-amber-800"
                    >
                      <td class="py-2 px-2 font-medium">{{ annee }}</td>
                      <td class="py-2 px-2 text-right">{{ formatMontant(revenus.mandats) }}</td>
                      <td class="py-2 px-2 text-right">{{ formatMontant(revenus.activites_pro) }}</td>
                      <td class="py-2 px-2 text-right">{{ formatMontant(revenus.consultant) }}</td>
                      <td class="py-2 px-2 text-right">{{ formatMontant(revenus.dirigeant) }}</td>
                      <td class="py-2 px-2 text-right font-bold text-amber-700 dark:text-amber-400">
                        {{ formatMontant(revenus.total) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Mandats électifs avec indemnités -->
            <div v-if="senateur.hatvp_summary.mandats_electifs && senateur.hatvp_summary.mandats_electifs.length > 0" class="mt-4">
              <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-2">
                🏛️ Mandats électifs déclarés
              </h4>
              <div class="space-y-2">
                <div 
                  v-for="(mandat, index) in senateur.hatvp_summary.mandats_electifs" 
                  :key="index"
                  class="p-3 bg-white/70 dark:bg-gray-800/70 rounded-lg"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="font-medium text-gray-900 dark:text-gray-100">{{ mandat.description }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ mandat.date_debut }} 
                        <span v-if="mandat.date_fin">→ {{ mandat.date_fin }}</span>
                        <span v-else class="text-green-600">→ En cours</span>
                      </div>
                    </div>
                    <div v-if="mandat.total_remunerations > 0" class="text-right">
                      <div class="text-sm font-bold text-amber-700 dark:text-amber-400">
                        {{ formatMontant(mandat.total_remunerations) }}
                      </div>
                      <div class="text-xs text-gray-500">Total déclaré</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Activités professionnelles parallèles -->
            <div v-if="senateur.hatvp_summary.activites_professionnelles && senateur.hatvp_summary.activites_professionnelles.length > 0" class="mt-4">
              <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-2">
                💼 Activités professionnelles parallèles
              </h4>
              <div class="space-y-2">
                <div 
                  v-for="(activite, index) in senateur.hatvp_summary.activites_professionnelles" 
                  :key="index"
                  class="p-3 bg-white/70 dark:bg-gray-800/70 rounded-lg"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="font-medium text-gray-900 dark:text-gray-100">{{ activite.employeur }}</div>
                      <div v-if="activite.description" class="text-sm text-gray-600 dark:text-gray-400">
                        {{ activite.description }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ activite.date_debut }} 
                        <span v-if="activite.date_fin">→ {{ activite.date_fin }}</span>
                        <span v-else-if="activite.actif" class="text-green-600">→ En cours</span>
                      </div>
                    </div>
                    <div v-if="activite.total_remunerations > 0" class="text-right">
                      <div class="text-sm font-bold text-blue-700 dark:text-blue-400">
                        {{ formatMontant(activite.total_remunerations) }}
                      </div>
                      <div class="text-xs text-gray-500">Total déclaré</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Collaborateurs parlementaires -->
            <div v-if="senateur.hatvp_summary.collaborateurs && senateur.hatvp_summary.collaborateurs.length > 0" class="mt-4">
              <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-2">
                👥 Collaborateurs parlementaires ({{ senateur.hatvp_summary.collaborateurs.length }})
              </h4>
              <div class="flex flex-wrap gap-2">
                <Badge 
                  v-for="(collab, index) in senateur.hatvp_summary.collaborateurs" 
                  :key="index"
                  class="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                  :title="collab.description"
                >
                  {{ collab.nom }}
                </Badge>
              </div>
            </div>
          </div>

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

        <!-- Historique des groupes -->
        <Card v-if="senateur.historique_groupes && senateur.historique_groupes.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>🎨</span>
            <span>Historique des groupes parlementaires</span>
          </h2>
          <div class="space-y-3">
            <div
              v-for="(groupe, index) in senateur.historique_groupes"
              :key="index"
              class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
            >
              <div class="flex items-center justify-between">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ groupe.groupe }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ groupe.date_debut }}
                  <span v-if="groupe.date_fin"> → {{ groupe.date_fin }}</span>
                  <span v-else class="text-green-600 font-medium"> → En cours</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Mandats locaux -->
        <Card v-if="senateur.mandats_locaux && senateur.mandats_locaux.length > 0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>🏛️</span>
            <span>Mandats locaux et autres fonctions</span>
          </h2>
          
          <div class="grid md:grid-cols-2 gap-4">
            <!-- Mandats municipaux -->
            <div v-if="senateur.mandats_locaux.filter(m => m.type_mandat === 'MUNICIPAL').length > 0">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                🏘️ Mandats municipaux
              </h3>
              <div class="space-y-2">
                <div
                  v-for="(mandat, index) in senateur.mandats_locaux.filter(m => m.type_mandat === 'MUNICIPAL')"
                  :key="index"
                  class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
                >
                  <div class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ mandat.fonction }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ mandat.collectivite }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ mandat.periode }}
                    <Badge v-if="mandat.en_cours" class="ml-2 bg-green-100 text-green-800 text-xs">
                      En cours
                    </Badge>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mandats départementaux/régionaux -->
            <div v-if="senateur.mandats_locaux.filter(m => m.type_mandat === 'DEPARTEMENTAL' || m.type_mandat === 'REGIONAL').length > 0">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                🗺️ Mandats départementaux/régionaux
              </h3>
              <div class="space-y-2">
                <div
                  v-for="(mandat, index) in senateur.mandats_locaux.filter(m => m.type_mandat === 'DEPARTEMENTAL' || m.type_mandat === 'REGIONAL')"
                  :key="index"
                  class="p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800"
                >
                  <div class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ mandat.fonction }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ mandat.collectivite }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ mandat.periode }}
                    <Badge v-if="mandat.en_cours" class="ml-2 bg-green-100 text-green-800 text-xs">
                      En cours
                    </Badge>
                  </div>
                </div>
              </div>
            </div>

            <!-- Anciens mandats de député -->
            <div v-if="senateur.mandats_locaux.filter(m => m.type_mandat === 'DEPUTE').length > 0">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                🏛️ Anciens mandats de député
              </h3>
              <div class="space-y-2">
                <div
                  v-for="(mandat, index) in senateur.mandats_locaux.filter(m => m.type_mandat === 'DEPUTE')"
                  :key="index"
                  class="p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800"
                >
                  <div class="font-semibold text-gray-900 dark:text-gray-100">
                    Député - {{ mandat.collectivite }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ mandat.periode }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Mandats européens -->
            <div v-if="senateur.mandats_locaux.filter(m => m.type_mandat === 'EUROPEEN').length > 0">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                🇪🇺 Mandats européens
              </h3>
              <div class="space-y-2">
                <div
                  v-for="(mandat, index) in senateur.mandats_locaux.filter(m => m.type_mandat === 'EUROPEEN')"
                  :key="index"
                  class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800"
                >
                  <div class="font-semibold text-gray-900 dark:text-gray-100">
                    Député européen
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ mandat.periode }}
                  </div>
                </div>
              </div>
            </div>
          </div>
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
