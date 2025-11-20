# 🏛️ ARCHITECTURE LÉGISLATION - Vision complète

## 🎯 Objectif
Créer une expérience fluide pour comprendre le processus législatif et comparer vote parlementaire vs vote citoyen.

---

## 📊 STRUCTURE DES DONNÉES

### Hiérarchie logique :

```
📜 DOSSIER LÉGISLATIF
  └─ 📄 TEXTE LÉGISLATIF
      ├─ 📝 AMENDEMENTS
      │   └─ 🗳️ Scrutins sur amendements
      └─ 🗳️ SCRUTIN FINAL
          ├─ 👥 Votes individuels députés
          └─ 💬 Débat citoyen (Topic)
              └─ 🗳️ Vote citoyen (Ballot)
```

---

## 🎨 PAGES CRÉÉES/AMÉLIORÉES

### ✅ **Pages existantes améliorées**

1. **`/legislation/scrutins`** - Liste des scrutins
   - Recherche par texte
   - Filtre par législature
   - Stats (total, adoptés, rejetés)
   - Pagination

2. **`/legislation/scrutins/{uid}`** - Détail scrutin
   - ✅ Pourcentages ajoutés (pour/contre/abstention/participation)
   - Votes par groupe
   - Liste députés ayant voté
   - Lien vers débat citoyen

3. **`/legislation/dossiers/{uid}`** - Détail dossier
   - Timeline chronologique
   - Textes successifs
   - Scrutins associés
   - Amendements principaux
   - Stats complètes
   - Lien création débat citoyen

---

## 🔗 PARCOURS UTILISATEUR

### **Parcours 1 : Par député**

```
👤 Fiche député
  ├─ 🗳️ Ses votes (liste)
  │   └─ Détail scrutin
  │       └─ Dossier législatif
  ├─ 📝 Ses amendements
  │   └─ Détail amendement
  │       └─ Texte législatif
  │           └─ Dossier
  └─ 📊 Son activité
```

### **Parcours 2 : Par dossier**

```
📜 Dossier législatif
  ├─ 📄 Textes (chronologie)
  │   ├─ 📝 Amendements
  │   └─ 🗳️ Scrutins
  │       └─ 👥 Votes députés
  └─ 💬 Débat citoyen
      └─ 🗳️ Vote citoyen
          └─ 📊 Comparaison AN vs Citoyens
```

### **Parcours 3 : Par scrutin**

```
🗳️ Liste scrutins (recherche)
  └─ Détail scrutin
      ├─ 📜 Dossier lié
      ├─ 👥 Votes députés
      │   └─ Fiche député
      └─ 💬 Créer débat citoyen
```

---

## 🎨 COMPOSANTS RÉUTILISABLES

### **1. Timeline législative**
```vue
<LegislativeTimeline :etapes="etapes" />
```
- Affiche chronologie dossier
- Icônes par type (dépôt, texte, scrutin)
- Liens cliquables

### **2. Carte scrutin**
```vue
<ScrutinCard :scrutin="scrutin" />
```
- Résumé scrutin
- Résultats visuels (pour/contre/abst)
- Badge résultat
- Lien détail

### **3. Comparateur vote**
```vue
<VoteComparator :scrutin="scrutin" :ballot="ballot" />
```
- Vote AN vs Vote citoyen
- Graphiques côte à côte
- Analyse écarts

### **4. Fil d'Ariane intelligent**
```vue
<LegislativeBreadcrumb :dossier="..." :texte="..." :scrutin="..." />
```
- Génère automatiquement le fil
- Adapté au contexte

---

## 📱 WIDGETS/SECTIONS

### **Widget "Débat citoyen"**
À afficher sur :
- Page détail scrutin
- Page détail dossier
- Page détail amendement

```vue
<CitizenDebateWidget 
  :entity="dossier|scrutin|amendement"
  :existing-topics="topics"
/>
```

Fonctions :
- Affiche débats existants
- Bouton "Créer un débat"
- Stats participation

---

## 🔍 RECHERCHE GLOBALE

### **Barre de recherche unifiée**

```
🔍 Rechercher... [                    ] 🔎
    └─ Suggestions :
        📜 Dossiers
        🗳️ Scrutins
        👥 Députés
        📝 Amendements
```

Implémentation :
- Recherche full-text PostgreSQL
- Résultats groupés par type
- Filtres rapides

---

## 📊 DONNÉES À ENRICHIR

### **1. Liens manquants dans la BDD**

```sql
-- Ajouter colonne scrutin_ref dans amendements_an
ALTER TABLE amendements_an 
ADD COLUMN scrutin_ref VARCHAR(30) REFERENCES scrutins_an(uid);

-- Ajouter colonne texte_ref dans scrutins_an (si pas déjà là)
ALTER TABLE scrutins_an 
ADD COLUMN texte_ref VARCHAR(30) REFERENCES textes_legislatifs_an(uid);
```

### **2. Métadonnées à ajouter**

- **Dossiers** : Tags/thèmes (écologie, social, économie...)
- **Scrutins** : Importance (majeur/mineur)
- **Textes** : Résumé citoyen (vulgarisé)

---

## 🎯 PROCHAINES ÉTAPES

### **Phase 1 : Navigation fluide** ✅
- [x] Liste scrutins
- [x] Détail scrutins avec %
- [x] Détail dossier amélioré
- [x] Liens entre entités

### **Phase 2 : Comparaison AN vs Citoyens** 🔄
- [ ] Widget débat citoyen
- [ ] Page comparaison votes
- [ ] Graphiques côte à côte
- [ ] Analyse écarts

### **Phase 3 : Recherche & Découverte** ⏳
- [ ] Recherche globale
- [ ] Filtres avancés
- [ ] Tags/thèmes
- [ ] Suggestions intelligentes

### **Phase 4 : Engagement citoyen** ⏳
- [ ] Notifications scrutins importants
- [ ] Création débat depuis scrutin
- [ ] Partage social
- [ ] Synthèses hebdomadaires

---

## 💡 IDÉES BONUS

### **1. "Mon député a voté comme moi ?"**
- Comparer vote député vs vote utilisateur
- Score de concordance
- Historique comparaisons

### **2. "Scrutins à venir"**
- Calendrier prévisionnel
- Alertes personnalisées
- Possibilité voter en avance (citoyen)

### **3. "Dossiers tendances"**
- Most viewed
- Most debated
- Most voted (citoyens)

### **4. "Explique-moi ce scrutin"**
- Résumé en langage simple
- Enjeux principaux
- Positions des groupes
- Impact citoyen

---

## 🎨 DESIGN SYSTEM

### **Couleurs par entité**
- 📜 Dossier : Bleu (#3B82F6)
- 📄 Texte : Violet (#8B5CF6)
- 🗳️ Scrutin : Vert (#10B981)
- 📝 Amendement : Orange (#F59E0B)
- 👥 Député : Indigo (#6366F1)
- 💬 Débat : Rose (#EC4899)

### **Icônes cohérentes**
- ✅ Adopté
- ❌ Rejeté
- ⏸️ En cours
- 📥 Déposé
- 📊 Statistiques
- 🔗 Lien

---

**Date :** 20 novembre 2025  
**Status :** Architecture définie, Phase 1 complétée

