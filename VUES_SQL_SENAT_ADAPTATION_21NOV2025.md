# ✅ VUES SQL SÉNAT - Adaptation aux données réelles

**Date** : 21 novembre 2025, 12:30  
**Status** : ✅ PRÊT À DÉPLOYER

---

## 🎯 STRATÉGIE

Au lieu de créer de nouvelles vues, on **transforme les tables Laravel existantes en vues SQL** qui pointent vers les données brutes importées.

### Avantages
- ✅ **Aucun changement côté code** : Les modèles `Senateur`, `SenateurMandat`, etc. continuent de fonctionner
- ✅ **Données SQL brutes exploitées** : Les ~600k lignes importées sont utilisées
- ✅ **Flexibilité** : Les anciennes tables sont renommées en `_backup_old` (rollback facile)
- ✅ **Pérennité** : Données brutes préservées, vues adaptables

---

## 📋 MIGRATIONS CRÉÉES

| Migration | Table/Vue | Source | Status |
|-----------|-----------|--------|--------|
| `030000` | `senateurs` | `senat_senateurs_sen` + `senat_senateurs_qua` | ✅ |
| `030100` | `senateurs_mandats` | `senat_senateurs_elusen` + `senat_senateurs_dpt` | ✅ |
| `030200` | `senateurs_commissions` | `senat_senateurs_memcom` + `senat_senateurs_com` | ✅ |
| `030300` | `senateurs_historique_groupes` | `senat_senateurs_memgrpsen` + `senat_senateurs_grpsen` | ✅ |
| `030400` | `senateurs_mandats_locaux` | `senat_senateurs_eluvil/eludep/elureg/elumet` | ✅ |
| `030400` | `senateurs_etudes` | `senat_senateurs_eta` | ✅ |
| `030500` | `senateurs_questions` | `senat_questions_tam_questions` + `tam_reponses` | ✅ |

---

## 🔧 MAPPING DES COLONNES

### Vue `senateurs`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `matricule` | `sen.senmat` | Direct |
| `civilite` | `qua.qualib` | `'Monsieur' → 'M.'`, `'Madame' → 'Mme'` |
| `nom_usuel` | `sen.sennomuse` | Direct |
| `prenom_usuel` | `sen.senprenomuse` | Direct |
| `etat` | `sen.etasencod` | `'AC' → 'ACTIF'`, autres → `'ANCIEN'` |
| `date_naissance` | `sen.sendatnai` | Cast en `date` |
| `date_deces` | `sen.sendatdec` | Cast en `date` |
| `groupe_politique` | `sen.sengrppolliccou` | Direct (colonne dénormalisée) |
| `groupe_politique_code` | `sen.sengrppolcodcou` | Direct |
| `commission_permanente` | `sen.sencomliccou` | Direct (colonne dénormalisée) |
| `departement_code` | `sen.sencirnumcou` | `LPAD(..., 2, '0')` |
| `circonscription` | `sen.sencircou` | Direct |
| `email` | `sen.senema` | Direct |
| `pcs_insee` | `sen.pcscod` | Direct |
| `description_profession` | `sen.sendespro` | Direct |

### Vue `senateurs_mandats`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `elusen.senid` | Direct |
| `date_debut` | `elusen.elusendatent` | Cast en `date` |
| `date_fin` | `elusen.eludatfin` | Cast en `date` |
| `actif` | `elusen.eludatfin IS NULL` | Boolean |
| `departement_code` | `dpt.dptcod` | Direct |
| `departement_nom` | `dpt.dptlib` | Direct |
| `type_mandat` | `typman.typmanlib` | Direct |

### Vue `senateurs_commissions`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `mc.senid` | Direct |
| `commission_nom` | `com.comlib` | Direct |
| `commission_code` | `com.comcod` | Direct |
| `type_organe` | `typorg.typorglib` | Direct |
| `date_debut` | `mc.memcomdatdeb` | Cast en `date` |
| `date_fin` | `mc.memcomdatfin` | Cast en `date` |
| `actif` | `mc.memcomdatfin IS NULL` | Boolean |
| `fonction` | `fonmemcom.fonmemcomlib` | Direct |

### Vue `senateurs_historique_groupes`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `mg.senid` | Direct |
| `groupe_nom` | `grp.grppolglo` | Direct |
| `groupe_code` | `grp.grppolglocod` | Direct |
| `date_debut` | `mg.memgrpsendatent` | Cast en `date` |
| `date_fin` | `mg.memgrpsendatsor` | Cast en `date` |
| `type_appartenance` | `mg.typapp` | `'M' → 'Membre'`, `'R' → 'Rattaché'`, etc. |

### Vue `senateurs_mandats_locaux`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `senid` | Direct (UNION de 4 tables) |
| `type_mandat` | - | `'Municipal'`, `'Départemental'`, `'Régional'`, `'Métropolitain'` |
| `fonction` | `fonmemlib` | Direct |
| `collectivite` | `comnom/dptlib/reglib/metnom` | Dépend du type |

### Vue `senateurs_etudes`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `eta.senid` | Direct |
| `etablissement` | `eta.etablib` | Direct |
| `diplome` | `eta.diplib` | Direct |
| `niveau` | `eta.nivlib` | Direct |
| `domaine` | `eta.domlib` | Direct |
| `annee` | `eta.etaann` | Direct |
| `details` | `eta.etades` | Direct |

### Vue `senateurs_questions`

| Colonne Laravel | Colonne SQL brute | Transformation |
|-----------------|-------------------|----------------|
| `senateur_matricule` | `q.senid` | Direct |
| `type_question` | `natq.natquelib` | Direct |
| `numero_question` | `q.quenum` | Direct |
| `objet` | `q.queobj` | Direct |
| `texte_question` | `q.quetxtque` | Direct |
| `texte_reponse` | `r.reptxtrep` | Direct |
| `date_depot` | `q.quedatjodep` | Cast en `date` |
| `date_reponse` | `r.repdatjorep` | Cast en `date` |
| `delai_reponse_jours` | - | Calculé : `EXTRACT(DAY FROM ...)` |
| `theme` | `the.thelib` | Direct |

---

## 🚀 DÉPLOIEMENT

### Sur le serveur

```bash
cd /opt/civicdash

# Pull des nouvelles migrations
git pull

# Lancer les migrations (transforme les tables en vues)
./deploy.sh
```

### Ce qui va se passer

1. ✅ Anciennes tables renommées en `*_backup_old`
2. ✅ Nouvelles vues créées avec les mêmes noms
3. ✅ Application continue de fonctionner sans changement de code
4. ✅ Données SQL brutes maintenant exploitées

### Rollback (si problème)

```bash
docker compose exec app php artisan migrate:rollback --step=6
```

Cela supprimera les vues et restaurera les anciennes tables.

---

## 📊 DONNÉES DISPONIBLES

Après déploiement, les modèles Laravel auront accès à :

| Modèle | Données | Source |
|--------|---------|--------|
| `Senateur` | ~350 sénateurs actifs | `senat_senateurs_sen` (9 085 lignes historiques) |
| `SenateurMandat` | ~55 000 mandats | `senat_senateurs_elusen` (55 231 lignes) |
| `SenateurCommission` | ~60 000 appartenances | `senat_senateurs_memcom` (62 538 lignes) |
| `SenateurHistoriqueGroupe` | ~48 000 appartenances | `senat_senateurs_memgrpsen` (48 360 lignes) |
| `SenateurMandatLocal` | ~4 sources | `eluvil`, `eludep`, `elureg`, `elumet` |
| `SenateurEtude` | Formations | `senat_senateurs_eta` |
| `SenateurQuestion` | Questions | `senat_questions_tam_questions` (si importé) |

---

## ✅ AVANTAGES

1. **Aucun changement de code** : Les controllers, modèles et vues continuent de fonctionner
2. **Données exhaustives** : ~600k lignes SQL exploitées
3. **Performance** : Requêtes SQL directes (pas de PHP)
4. **Pérennité** : Données brutes conservées
5. **Flexibilité** : Vues adaptables à tout moment
6. **Rollback facile** : Tables `_backup_old` préservées

---

**Document créé le** : 21 novembre 2025, 12:35  
**Status** : ✅ PRÊT À DÉPLOYER  
**Impact** : 🚀 DONNÉES SÉNAT 100% EXPLOITÉES

