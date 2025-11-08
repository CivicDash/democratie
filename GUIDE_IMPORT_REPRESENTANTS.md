# 🚀 GUIDE RAPIDE - Import Députés & Sénateurs

## 📊 Données disponibles

Trois fichiers CSV de **data.gouv.fr** sont disponibles dans `/public/data/` :

| Fichier | Lignes | Description |
|---------|--------|-------------|
| `elus-deputes-dep.csv` | 575 | Députés actuels (Assemblée Nationale) |
| `elus-senateurs-sen.csv` | 348 | Sénateurs actuels |
| `elus-maires-mai.csv` | 34,867 | Maires (non importé pour le moment) |

---

## 🏗️ Structure BDD : `deputes_senateurs`

```sql
CREATE TABLE deputes_senateurs (
    id BIGSERIAL PRIMARY KEY,
    source VARCHAR(20),           -- 'assemblee' ou 'senat'
    uid VARCHAR(50) UNIQUE,       -- Identifiant unique (ex: DEP-75-breton-xavier)
    nom VARCHAR(255),
    prenom VARCHAR(255),
    nom_complet VARCHAR(255),     -- "M. Xavier BRETON"
    civilite VARCHAR(10),          -- 'M.' ou 'Mme'
    groupe_politique VARCHAR(100), -- Nom du groupe (à compléter via API)
    groupe_sigle VARCHAR(20),      -- Sigle (à compléter via API)
    circonscription VARCHAR(100),  -- "75-01" pour députés, "75 - Paris" pour sénateurs
    numero_circonscription VARCHAR(10), -- "01", "02", etc.
    profession VARCHAR(150),
    date_naissance DATE,
    legislature INTEGER,           -- 17 pour députés, NULL pour sénateurs
    debut_mandat DATE,
    fin_mandat DATE,
    en_exercice BOOLEAN,           -- true par défaut
    photo_url VARCHAR(255),
    url_profil VARCHAR(255),
    fonctions JSON,                -- Fonctions (président, rapporteur, etc.)
    commissions JSON,              -- Commissions
    nb_propositions INTEGER,       -- Nombre de propositions
    nb_amendements INTEGER,        -- Nombre d'amendements
    taux_presence DECIMAL(5,2),   -- Taux de présence en %
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🛠️ Commandes disponibles

### 1. **Import automatique (recommandé)**

```bash
bash scripts/import_representants.sh
```

**Ce que fait le script :**
- ✅ Vérifie que les fichiers CSV existent
- ✅ Affiche l'état actuel de la BDD
- ✅ Demande confirmation (supprime les données de démo)
- ✅ Importe les 575 députés
- ✅ Importe les 348 sénateurs
- ✅ Affiche un résumé avec échantillons

### 2. **Import manuel (avancé)**

#### Députés uniquement :
```bash
docker compose exec app php artisan import:deputes --fresh
```

#### Sénateurs uniquement :
```bash
docker compose exec app php artisan import:senateurs --fresh
```

#### Sans l'option `--fresh` (mise à jour) :
```bash
docker compose exec app php artisan import:deputes
docker compose exec app php artisan import:senateurs
```

---

## 📋 Format des CSV

### Députés (`elus-deputes-dep.csv`)
```csv
Code du département;Libellé du département;Code de la collectivité à statut particulier;Libellé de la collectivité à statut particulier;Code de la circonscription législative;Libellé de la circonscription législative;Nom de l'élu;Prénom de l'élu;Code sexe;Date de naissance;Code de la catégorie socio-professionnelle;Libellé de la catégorie socio-professionnelle;Date de début du mandat
01;Ain;;;0101;1Ère Circonscription;BRETON;Xavier;M;25/11/1962;33;Cadre de la fonction publique;08/07/2024
```

**Colonnes utilisées :**
- `[0]` : Code département (01, 75, etc.)
- `[4]` : Code circonscription (0101, 7501, etc.)
- `[6]` : Nom
- `[7]` : Prénom
- `[8]` : Sexe (M/F)
- `[9]` : Date naissance (DD/MM/YYYY)
- `[11]` : Profession
- `[12]` : Date début mandat (DD/MM/YYYY)

### Sénateurs (`elus-senateurs-sen.csv`)
```csv
Code du département;Libellé du département;Code de la collectivité à statut particulier;Libellé de la collectivité à statut particulier;Nom de l'élu;Prénom de l'élu;Code sexe;Date de naissance;Code de la catégorie socio-professionnelle;Libellé de la catégorie socio-professionnelle;Date de début du mandat
01;Ain;;;BLATRIX CONTAT;Florence;F;30/03/1966;34;Professeur, profession scientifique;01/10/2020
```

**Colonnes utilisées :**
- `[0]` : Code département
- `[1]` : Nom département
- `[4]` : Nom
- `[5]` : Prénom
- `[6]` : Sexe (M/F)
- `[7]` : Date naissance (DD/MM/YYYY)
- `[9]` : Profession
- `[10]` : Date début mandat (DD/MM/YYYY)

---

## ✅ Résultat attendu

Après import, la base doit contenir :

```sql
SELECT source, COUNT(*) as total, COUNT(CASE WHEN en_exercice THEN 1 END) as actifs
FROM deputes_senateurs
GROUP BY source;
```

| source | total | actifs |
|--------|-------|--------|
| assemblee | 575 | 575 |
| senat | 348 | 348 |

---

## 🔍 Vérification

### Compter les élus :
```bash
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT source, COUNT(*) FROM deputes_senateurs GROUP BY source;"
```

### Voir un échantillon :
```bash
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT nom_complet, circonscription, profession FROM deputes_senateurs WHERE source = 'assemblee' LIMIT 5;"
```

### Tester sur le front :
- Députés : https://demo.objectif2027.fr/representants/deputes
- Sénateurs : https://demo.objectif2027.fr/representants/senateurs

---

## 🚨 Important

### 1. **Groupes politiques manquants**
Les CSV ne contiennent **PAS** les groupes politiques. Pour les ajouter :
- Option A : Compléter manuellement via l'API Assemblée/Sénat
- Option B : Créer un seeder qui associe les groupes par nom
- Option C : Importer via l'API NosDéputés.fr

### 2. **Photos manquantes**
Les photos ne sont pas dans les CSV. Pour les ajouter :
- API Assemblée Nationale : `https://www.assemblee-nationale.fr/dyn/deputes/{uid}`
- API Sénat : `https://www.senat.fr/senateur/{uid}`

### 3. **Données supplémentaires**
Pour enrichir avec :
- Propositions de loi
- Amendements
- Taux de présence
- Commissions

→ Utiliser l'API **NosDéputés.fr** ou l'API officielle

---

## 📁 Fichiers créés

| Fichier | Description |
|---------|-------------|
| `app/Console/Commands/ImportDeputesFromCsv.php` | Commande import députés |
| `app/Console/Commands/ImportSenateursFromCsv.php` | Commande import sénateurs |
| `scripts/import_representants.sh` | Script automatisé |
| `public/data/elus-deputes-dep.csv` | Données députés |
| `public/data/elus-senateurs-sen.csv` | Données sénateurs |

---

## 🎯 Prochaines étapes

1. ✅ Exécuter l'import : `bash scripts/import_representants.sh`
2. ⬜ Vérifier les données sur le front
3. ⬜ Compléter les groupes politiques (optionnel)
4. ⬜ Ajouter les photos (optionnel)
5. ⬜ Enrichir via API NosDéputés.fr (optionnel)

---

**Tout est prêt ! Les données réelles vont remplacer les données de démo. 🚀**

