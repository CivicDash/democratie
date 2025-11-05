# Corrections nécessaires pour le seeder DemoDataSeeder

## ✅ Déjà corrigés
- profiles: scope + region_id/department_id
- groupes_parlementaires: slug, source, chambre
- thematiques_legislation: slug
- proposition_loi_thematique: thematique_legislation_id
- topics: scope + region_id/department_id
- agenda_legislatif: date_debut, date_fin, date nullable
- votes_legislatifs: titre, pour/contre/abstention, source/numero_scrutin nullable

## ❌ À corriger : Amendements

### Migration actuelle (`amendements`):
- `source` (NOT NULL)
- `numero`
- `dispositif`
- `expose_motifs`
- `auteurs` (JSON)
- `groupe_politique`
- `sort`
- `date_depot`
- `date_discussion`

### Seeder utilise:
- `auteur_nom` ❌ (n'existe pas)
- `auteur_groupe` ❌ (n'existe pas)
- `objet` ❌ (n'existe pas)
- `dispositif` ✅
- `expose_sommaire` ❌ (devrait être `expose_motifs`)
- `statut` ❌ (devrait être `sort`)
- `date_depot` ✅
- `date_discussion` ✅
- `sort` ✅
- PAS de `source` ❌ (NOT NULL dans migration)

### Solutions:
1. Ajouter colonnes manquantes à la migration
2. Rendre `source` nullable avec default
3. OU adapter le seeder pour utiliser les colonnes existantes

## Recommandation
Ajouter les colonnes manquantes à la migration pour compatibilité avec le seeder.

