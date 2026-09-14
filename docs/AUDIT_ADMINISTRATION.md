# Audit de l'administration — septembre 2026

## Pourquoi

La gestion des membres a livré trois symptômes visibles — mot de passe inopérant,
changement non validé, numéro d'adhérent qui ne reste pas — derrière lesquels se
cachaient douze champs non assignables et une synchronisation Dolibarr inopérante.
Aucun message n'avait jamais alerté : l'interface affichait « succès » pendant que rien
n'était écrit.

Ce n'est pas la gravité technique qui rendait ces défauts dangereux, c'est leur
**silence**. Sur un back-office tenu par des bénévoles, qui décide de ce qui est publié
sur objectif2027.fr, un faux positif fait croire qu'un travail est fait alors qu'il ne
l'est pas.

## La classe de défaut

Les quatorze points relevés se ramènent à **trois mécanismes** :

1. **Trois vocabulaires non contractualisés.** Une écriture traverse les clés du
   `useForm()` Vue, celles du `validate()` du contrôleur, et les colonnes réelles. Rien
   ne déclarait qu'ils devaient coïncider. Ils avaient dérivé : sur soixante-quatre
   champs de saisie répartis sur dix écrans de statistiques, dix-neuf atteignaient la
   base.
2. **L'échec est silencieux par construction.** `fill()` jette les clés hors
   `$fillable` sans rien dire ; `update()` retourne `false` si le modèle n'existe pas ;
   `updateOrCreate($clef, [])` ne produit aucun `UPDATE` ; `page.props.errors` n'était
   lu nulle part. Et au bout, `->with('success')` était appelé inconditionnellement.
3. **Le correctif au point d'impact.** `account_status` avait été ajouté au `$fillable`
   sans ses cinq compagnons ; `username` retiré d'`export()` mais pas des quatre autres
   appels du même fichier ; `form.errors` ajouté à un écran pendant que quarante et un
   restaient muets.

## Les garde-fous

Trois mécanismes empêchent la récidive. Ils s'exécutent en CI, sur **toutes** les
branches — jusqu'ici le workflow ne se déclenchait que sur `dev` et `main`, donc jamais
sur une branche de travail.

### `tests/Feature/Guardrails/ContratEcritureAdminTest.php`

Confronte, pour chaque route d'écriture de l'administration, les clés réellement
passées à l'enregistrement aux colonnes de la table et au `$fillable` du modèle.
Le moteur est `App\Support\AuditEcritures`.

L'analyse est statique et ne prétend pas tout lire : ce qu'elle ne comprend pas est
compté comme **indéterminé** plutôt que passé sous silence, et un second test surveille
que ce nombre ne grimpe pas. Écrire `$modele->update($validated)` ou passer par un
`FormRequest` rend une méthode lisible par l'analyse.

### `tests/Feature/Guardrails/ComposantsInertiaTest.php`

Vérifie que chaque cible `Inertia::render()` a son fichier `.vue`. Sans composant,
`app.blade.php` cherche le fichier dans le manifeste Vite : un chargement direct rend un
**500**, une navigation interne un **écran blanc**. Deux pages publiques étaient dans ce
cas, dont la politique de cookies liée depuis les CGU.

`config/civicdash.php` porte une liste de tolérance, aujourd'hui vide et à garder vide.
Un second test refuse qu'une entrée y demeure une fois l'écran écrit.

### `tests/Feature/Guardrails/EcransStatistiquesEcriventTest.php`

Relit la ligne en base après une soumission, au lieu de se contenter d'une redirection
302. Vérifié en cassant volontairement un écran : le test nomme les dix-sept colonnes
perdues.

### Mode strict Eloquent

`AppServiceProvider::configurerModeStrictEloquent()`. Hors production, une écriture hors
`$fillable` et la lecture d'un attribut absent **lèvent**. En production elles sont
journalisées dans le canal `audit`, avec l'URL et l'utilisateur : on ne casse pas
l'écran d'un bénévole en pleine session de modération, mais on sait.

`preventLazyLoading` reste opt-in (`ELOQUENT_STRICT_LAZY`) tant que les N+1 connus ne
sont pas tous traités : l'activer noierait le signal des deux autres gardes.

Ce mode a trouvé, dans les minutes qui ont suivi son activation, que `DocumentService`
était écrit contre un schéma disparu — `/api/documents/pending` et `/api/documents/stats`
renvoyaient un 500.

### `php artisan civicdash:audit-admin`

Les mêmes contrôles, en sortie lisible, exécutables **en production** où les tests ne
tournent pas. `--json` pour la CI, `--strict` pour un code de sortie non nul.

## Ce qui a été décidé, et pourquoi

**Les formulaires de statistiques dérivent du schéma.** `App\Support\ChampsStatistiques`
produit la liste des champs *et* les règles de validation à partir des colonnes.
Aligner dix formulaires à la main les aurait laissés libres de dériver à nouveau.
Ajouter une colonne l'expose ; en retirer une la retire des deux côtés.

**L'édition d'un sénateur a été retirée, pas réparée.** `senateurs` est une VUE
PostgreSQL en `DISTINCT ON` avec trois jointures : PostgreSQL refuse tout `UPDATE`
dessus. Le `$fillable` écartait les champs validés, ce qui donnait un no-op silencieux —
et « corriger le `$fillable` » aurait transformé ce silence en 500 à chaque
enregistrement. L'identité vient de l'import Sénat et se corrige à la source ; l'écran
n'édite plus que l'enrichissement Wikipédia, qui vit dans sa propre table.

**Douze routes ont été supprimées.** Elles rendaient un composant qui n'a jamais existé
et n'étaient liées depuis nulle part — zéro occurrence de `route()` dans tout le front.
Elles n'étaient atteignables qu'en tapant l'URL, et l'URL renvoyait un 500.

**Publier est plus grave que supprimer.** Le seul acte réellement irréversible est la
publication : la donnée part sur objectif2027.fr, elle est lue, indexée, archivée. La
suppression, elle, est un soft-delete qui renvoie la proposition en file. L'interface
disait l'inverse — seule la suppression demandait confirmation. Le référentiel
`resources/js/actions.js` inverse ce rapport.

## Le référentiel d'actions

| Verbe | Traitement | Confirmation |
|---|---|---|
| Valider | bleu plein — geste interne, fréquent, ne publie rien | non |
| 2ᵉ validation | violet plein — autre nature de geste, exercé par un **autre** modérateur | non |
| **Publier** | **vert plein, réservé** — « c'est sur le site public » | **oui, en nommant l'objet** |
| Dépublier | ambre léger — réparation, doit rester rapide | non |
| Rejeter | contour rouge — change un statut, ne détruit rien | selon motif |
| Supprimer | rouge plein | oui |
| Neutre | contour gris — Modifier, Aperçu, Export, Sync | non |

Les classes sont écrites **en toutes lettres** dans `actions.js`. Jamais
d'interpolation : Tailwind ne verrait pas `bg-${couleur}-600` et purgerait la classe —
le build resterait vert et les boutons sortiraient sans style. C'est aussi pourquoi
`tailwind.config.js` scanne désormais `resources/js/**/*.js`.

## Contrainte métier à ne pas enfreindre

Le sens d'un fait est porté par la **liaison** argument ↔ mesure, jamais par le fait
lui-même : un même fait étaye une mesure et contredit son opposée.

- Aucun affichage pour/contre en dehors du niveau d'**une** mesure.
- Le sens sort du référentiel de couleurs : il s'écrit **« étaye »** / **« contredit »**,
  en gris, toujours collé au titre de la mesure visée.
- Un tableau de bord agrégé compte des liaisons **sans jamais les ventiler par sens**.
  « N liaisons en attente de 2ᵉ validation » est licite, puisque la double validation ne
  s'applique qu'aux liaisons « contredit » ; « N faits contre » ne l'est pas.

## Déploiement

`Dockerfile.frankenphp` fait `composer dump-autoload --classmap-authoritative` et
`opcache.validate_timestamps=0`. Donc :

| Nature du changement | Protocole |
|---|---|
| Corps d'une classe PHP **existante** | `docker cp` + `docker compose restart` — correctif temporaire, effacé au prochain `up -d` |
| **Nouvelle** classe PHP | rebuild obligatoire (classmap autoritatif) |
| `.vue`, `routes/web.php`, migration | rebuild obligatoire |

Cet audit introduit de nouvelles classes (`ChampsStatistiques`, `AuditEcritures`,
`AuditAdministration`), une migration et de nombreux composants Vue : **un rebuild
d'image est nécessaire**. Convention maison : taguer `backup-pre-X` avant.

## Ce qui reste

- **La suite de tests compte 87 échecs préexistants**, sans rapport avec cet audit :
  fabriques incomplètes, colonnes inexistantes dans des tests, assertions obsolètes.
  Ils étaient invisibles parce que la CI ne tournait pas sur les branches de travail.
  Deux causes structurelles ont été corrigées au passage (formats Faker propres à
  `en_US` alors que la CI tourne en `fr_FR`, tests `Unit` reliés à aucun `TestCase`) :
  138 échecs au départ, 87 aujourd'hui.
- **Trente-six routes d'écriture restent « indéterminées »** pour l'analyse statique.
- **Deux systèmes de sanction coexistent** — `sanctions` pour la modération de contenu,
  `user_sanctions` pour l'accès au compte. L'incohérence observable est corrigée (un
  compte banni ne peut plus publier), mais la question de les fusionner reste ouverte.
  Les deux tables sont vides : c'est le bon moment pour trancher.
- **`.gitlab-ci.yml` duplique `.github/workflows/tests.yml`.** Deux CI divergeront.
