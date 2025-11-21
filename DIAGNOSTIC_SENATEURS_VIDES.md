# 🔍 Diagnostic : Amendements/Votes/Activités Sénateurs Vides

**Date** : 21 novembre 2025  
**Problème** : Les pages `/senateurs/{id}/amendements`, `/votes`, et `/activite` affichent 0 données

---

## 🚨 Problème Identifié

### Amendements Sénat
- **Vue SQL** : `amendements_senat` utilise `amdsen.senid::text AS senateur_matricule`
- **Modèle** : `AmendementSenat` cherche par `auteur_senateur_matricule`
- **Controller** : Cherche par `senateur_matricule`
- **❌ ERREUR** : `senid` est un ID numérique (ex: 123), pas le matricule (ex: "19565D")

### Votes Sénat
- **Vue SQL** : `senateurs_votes` utilise `votes.senmat AS senateur_matricule`
- **Modèle** : `VoteSenat` cherche par `senateur_matricule`
- **✅ OK** : Utilise bien le matricule

### Scrutins Sénat
- **Vue SQL** : `senateurs_scrutins` existe
- **✅ OK** : Pas de jointure nécessaire

---

## 🔍 Investigation Nécessaire

### 1. Vérifier la structure de `senat_ameli_amdsen`
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'senat_ameli_amdsen' 
ORDER BY ordinal_position;
```

### 2. Vérifier la structure de `senat_senateurs_sen`
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'senat_senateurs_sen' 
WHERE column_name LIKE '%senid%' OR column_name LIKE '%senmat%';
```

### 3. Trouver la correspondance senid ↔ senmat
```sql
-- Option A : senid existe dans senat_senateurs_sen ?
SELECT senid, senmat FROM senat_senateurs_sen LIMIT 5;

-- Option B : Il faut joindre via une autre table ?
-- Chercher dans toutes les tables senat_* qui ont senid et senmat
```

---

## 🛠️ Solutions Possibles

### Solution A : senid existe dans senat_senateurs_sen
Si `senat_senateurs_sen` a les deux colonnes (`senid` ET `senmat`), corriger la vue :

```sql
CREATE OR REPLACE VIEW amendements_senat AS
SELECT 
    amd.id AS id,
    sen.senmat AS senateur_matricule,  -- ✅ Via jointure avec senat_senateurs_sen
    amd.num AS numero,
    amd.typ AS type_amendement,
    amd.dis AS dispositif,
    amd.obj AS expose,
    amd.datdep::date AS date_depot,
    sor.lib AS sort_libelle,
    sor.cod AS sort_code,
    amdsen.nomuse AS auteur_nom,
    amdsen.prenomuse AS auteur_prenom,
    amdsen.grpid AS auteur_groupe_id,
    NOW() AS created_at,
    NOW() AS updated_at
    
FROM senat_ameli_amd amd
LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
LEFT JOIN senat_senateurs_sen sen ON amdsen.senid = sen.senid  -- ✅ Jointure correcte
LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
WHERE amdsen.senid IS NOT NULL
ORDER BY amd.datdep DESC NULLS LAST;
```

### Solution B : senid n'existe pas, utiliser un mapping différent
Si pas de `senid` dans `senat_senateurs_sen`, peut-être que `senid` correspond à l'ordre d'insertion ou à une autre colonne.

---

## 📋 Commandes de Test

### Tinker - Vérifier données amendements
```php
// Compter amendements avec senateur_matricule numérique
\App\Models\AmendementSenat::whereNotNull('senateur_matricule')->count();

// Voir un exemple
\App\Models\AmendementSenat::first();

// Compter sénateurs
\App\Models\Senateur::count();

// Voir un matricule sénateur
\App\Models\Senateur::first()->matricule; // "19565D"

// Chercher un amendement par matricule
\App\Models\AmendementSenat::where('senateur_matricule', '19565D')->count();
```

### Tinker - Vérifier données votes
```php
// Compter votes
\App\Models\VoteSenat::count();

// Voir un exemple
\App\Models\VoteSenat::first();

// Votes pour un sénateur spécifique
\App\Models\VoteSenat::where('senateur_matricule', '19565D')->count();
```

---

## ✅ Actions à Faire

1. **Exécuter les requêtes SQL** ci-dessus pour comprendre la structure
2. **Corriger la vue `amendements_senat`** avec la bonne jointure
3. **Re-tester** les pages amendements/votes/activités
4. **Vérifier le controller** pour s'assurer qu'il utilise la bonne colonne

---

## 📝 Notes
- Le matricule sénateur est au format "XXXXXL" (ex: "19565D", "01234A")
- C'est la clé primaire de `senateurs` et `senat_senateurs_sen.senmat`
- Ne PAS utiliser `senid` (ID numérique interne) pour les jointures avec Laravel

