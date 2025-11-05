# Corrections nécessaires pour le seeder DemoDataSeeder

## ✅ Déjà corrigés
- profiles: scope + region_id/department_id + bio + citizen_ref_hash nullable
- groupes_parlementaires: slug, source, chambre, president, site_web, est_actif
- thematiques_legislation: slug
- proposition_loi_thematique: thematique_legislation_id
- topics: scope + region_id/department_id
- agenda_legislatif: date_debut, date_fin, date nullable
- votes_legislatifs: titre, pour/contre/abstention, source/numero_scrutin nullable
- amendements: objet, auteur_nom, auteur_groupe, expose_sommaire, statut, source nullable
- votes_groupes_parlementaires: pour/contre/abstention/non_votants (alias)

## ✅ Toutes les corrections appliquées !

Le seeder DemoDataSeeder devrait maintenant fonctionner sans erreurs de colonnes manquantes.

### Résumé des modifications
- **10 migrations** corrigées pour compatibilité avec le seeder
- **Colonnes ajoutées** : 25+ colonnes (alias et nouvelles colonnes)
- **Contraintes assouplies** : 8 colonnes NOT NULL → nullable

### Prochaine étape
Lancer `php artisan demo:setup --fresh --force` et vérifier que tout passe ! 🚀

