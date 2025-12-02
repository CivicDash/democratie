# 📋 TODO - Implémentations Restantes

**Date de mise à jour** : 2 décembre 2025

---

## ✅ Corrections Effectuées

### 1. Doublons Sénateurs
- [x] Migration `2025_12_02_200000_fix_senateurs_views_duplicates.php`
- [x] Utilisation de `DISTINCT ON` pour éliminer les doublons
- [x] `TRIM()` sur les matricules
- [x] Libellés des groupes parlementaires (au lieu des codes)

### 2. Bloc HATVP
- [x] Déplacé après les mandats et commissions dans `Show.vue`
- [x] Affichage des déclarations avec résumé consolidé

---

## 🔄 En Cours

### 3. Responsivité Mobile
- [ ] Vérifier `Senateurs/Index.vue` - table → cards sur mobile
- [ ] Vérifier `Senateurs/Show.vue` - grilles adaptatives
- [ ] Vérifier `Deputes/Index.vue` - table → cards sur mobile
- [ ] Vérifier `Deputes/Show.vue` - grilles adaptatives
- [ ] Ajouter bottom navigation mobile
- [ ] Touch targets ≥ 44px

---

## 📊 Données Disponibles Non Exploitées

### Assemblée Nationale

| Source | Modèle | Status | Vue |
|--------|--------|--------|-----|
| Questions au Gouvernement | `QuestionGouvernement` | ⚠️ Modèle existe, pas d'import | ❌ |
| Dossiers Législatifs | `DossierLegislatifAN` | ⚠️ Modèle existe, import partiel | ⚠️ |
| Réunions/Agenda | `ReunionAN` | ⚠️ Modèle existe, pas d'import | ❌ |
| Consultations Citoyennes | ❌ Pas de modèle | ❌ | ❌ |

### Sénat

| Source | Modèle | Status | Vue |
|--------|--------|--------|-----|
| Questions écrites | ❌ Pas de modèle | ❌ | ❌ |
| Textes Akoma Ntoso | ❌ Pas de modèle | ❌ | ❌ |
| Dossiers Législatifs | `DossierLegislatifSenat` | ⚠️ Modèle existe | ⚠️ |

### HATVP

| Source | Modèle | Status | Vue |
|--------|--------|--------|-----|
| Déclarations | `HatvpDeclaration` | ✅ Import basique | ✅ |
| Mandats électifs | `HatvpMandatElectif` | ⚠️ Pas de parsing détaillé | ⚠️ |
| Rémunérations | `HatvpRemuneration*` | ⚠️ Tables créées, pas d'import | ❌ |
| Patrimoine (immeubles, etc.) | `HatvpImmeuble`, etc. | ⚠️ Tables créées, pas d'import | ❌ |

---

## 🚀 Fonctionnalités à Implémenter

### Priorité Haute

1. **Import complet HATVP avec détails XML**
   - Parser les déclarations individuelles
   - Extraire mandats, activités, rémunérations
   - Lier aux sénateurs/députés par nom/prénom

2. **Proportion Hommes/Femmes**
   - Calculer depuis les données existantes
   - Afficher sur la page comparaison AN/Sénat
   - Ajouter graphique

3. **Questions au Gouvernement (AN)**
   - Activer l'import XML
   - Créer vue liste et détail
   - Lier aux députés

### Priorité Moyenne

4. **Questions Écrites (Sénat)**
   - Créer modèle `QuestionSenat`
   - Importer depuis base SQL
   - Créer vues

5. **Textes Akoma Ntoso (Sénat)**
   - Parser les flux XML
   - Créer modèle `TexteSenat`
   - Afficher sur les dossiers législatifs

6. **Consultations Citoyennes (AN)**
   - Créer modèle `ConsultationCitoyenne`
   - Parser JSON
   - Créer page dédiée

### Priorité Basse

7. **Agenda/Réunions (AN)**
   - Activer l'import
   - Créer calendrier interactif

8. **Débats (Sénat)**
   - Importer comptes rendus
   - Recherche full-text

---

## 📱 Améliorations UX/Mobile

### Vue Index Sénateurs/Députés

**Actuel** : Table avec colonnes
**Problème** : Illisible sur mobile

**Solution** : Cards empilées sur mobile

```vue
<!-- Desktop: table -->
<div class="hidden lg:block">
  <table>...</table>
</div>

<!-- Mobile: cards -->
<div class="lg:hidden space-y-4">
  <div v-for="item in items" class="p-4 bg-white rounded-lg shadow">
    <div class="flex items-center gap-3">
      <img class="w-12 h-12 rounded-full" />
      <div>
        <h3 class="font-bold">{{ item.nom }}</h3>
        <p class="text-sm text-gray-500">{{ item.groupe }}</p>
      </div>
    </div>
  </div>
</div>
```

### Vue Show Sénateur/Député

- [x] Grid responsive (md:grid-cols-2)
- [ ] Cards collapsibles sur mobile
- [ ] Sticky header avec nom
- [ ] Bottom sheet pour actions

---

## 🔧 Corrections Techniques

### Base de Données

1. **Index manquants**
   - `hatvp_declarations(nom, prenom)` pour recherche
   - `votes_senat(senateur_matricule)` pour performances

2. **Vues SQL à optimiser**
   - `senateurs_votes` - ajouter index
   - `amendements_senat` - vérifier jointures

### Import/Sync

1. **Import HATVP**
   - Actuellement : import basique (métadonnées seulement)
   - À faire : parser XML complet de chaque déclaration

2. **Import AN**
   - Vérifier extraction récursive des ZIP
   - Ajouter gestion des erreurs réseau

---

## 📈 Statistiques à Ajouter

### Page Comparaison AN/Sénat

- [ ] Proportion H/F par chambre
- [ ] Âge moyen par chambre
- [ ] Répartition par profession
- [ ] Taux de participation aux votes
- [ ] Nombre moyen d'amendements par parlementaire

### Fiches Parlementaires

- [ ] Graphique d'activité (votes/mois)
- [ ] Comparaison avec moyenne du groupe
- [ ] Évolution du taux d'adoption amendements

---

## 🗓️ Planning Suggéré

### Semaine 1
- [ ] Corriger responsivité mobile (Index + Show)
- [ ] Import complet HATVP

### Semaine 2
- [ ] Questions au Gouvernement (AN)
- [ ] Proportion H/F

### Semaine 3
- [ ] Questions Écrites (Sénat)
- [ ] Textes Akoma Ntoso

### Semaine 4
- [ ] Consultations Citoyennes
- [ ] Statistiques avancées

---

## 📝 Notes

### URLs Importantes

- **AN Open Data** : https://data.assemblee-nationale.fr/
- **Sénat Open Data** : https://data.senat.fr/
- **HATVP** : https://www.hatvp.fr/open-data/

### Commandes Utiles

```bash
# Synchronisation complète
./scripts/sync-docker.sh --verbose

# Import spécifique
docker compose exec app php artisan an:sync scrutins --legislature=17
docker compose exec app php artisan senat:sync senateurs
docker compose exec app php artisan hatvp:sync --import --parlementaires

# Vérifier les données
docker compose exec app php artisan tinker --execute="echo App\Models\Senateur::count();"
```

