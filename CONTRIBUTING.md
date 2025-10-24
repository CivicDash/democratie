# Guide de Contribution - CivicDash

Merci de votre intérêt pour contribuer à CivicDash ! 🎉

## Code de Conduite

En participant à ce projet, vous acceptez de respecter notre [Code de Conduite](CODE_OF_CONDUCT.md). Soyez respectueux, bienveillant et constructif.

## Comment contribuer ?

### 🐛 Signaler un bug

1. Vérifiez que le bug n'a pas déjà été signalé dans les [Issues](https://github.com/votre-org/civicdash/issues)
2. Créez une nouvelle issue avec le template "Bug Report"
3. Décrivez clairement :
   - Ce qui s'est passé
   - Ce qui aurait dû se passer
   - Les étapes pour reproduire
   - Votre environnement (OS, version PHP, Docker, etc.)

### ✨ Proposer une fonctionnalité

1. Ouvrez une discussion dans [GitHub Discussions](https://github.com/votre-org/civicdash/discussions)
2. Expliquez le problème que vous voulez résoudre
3. Proposez votre solution
4. Attendez les retours avant de commencer à coder

### 🔧 Soumettre une Pull Request

1. **Fork** le projet
2. **Créez une branche** depuis `main` :
   ```bash
   git checkout -b feat/ma-fonctionnalite
   # ou
   git checkout -b fix/correction-bug
   ```

3. **Codez** en respectant les conventions (voir ci-dessous)

4. **Testez** votre code :
   ```bash
   ./vendor/bin/pest
   ./vendor/bin/pint
   ./vendor/bin/phpstan analyse
   ```

5. **Committez** avec des messages conventionnels :
   ```bash
   git commit -m "feat: ajout du scrutin à choix multiples"
   git commit -m "fix: correction du calcul d'agrégation budgétaire"
   ```

6. **Poussez** votre branche :
   ```bash
   git push origin feat/ma-fonctionnalite
   ```

7. **Ouvrez une Pull Request** vers `main` avec :
   - Un titre clair
   - Une description détaillée
   - Les issues liées (si applicable)
   - Des captures d'écran (si UI)

## 📝 Conventions de Code

### Commits (Conventional Commits)

Utilisez le format : `<type>(<scope>): <description>`

**Types** :
- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation uniquement
- `style`: Formatage, indentation (pas de changement de code)
- `refactor`: Refactoring sans changement de comportement
- `test`: Ajout/modification de tests
- `chore`: Tâches diverses (build, CI, dépendances)
- `perf`: Amélioration de performance

**Exemples** :
```
feat(ballot): ajout du scrutin préférentiel
fix(budget): correction somme != 100% 
docs(readme): mise à jour installation Docker
test(moderation): ajout tests workflow signalement
```

### Code PHP

- **PSR-12** : Standard de code PHP
- **Laravel Pint** : Formatter automatique
  ```bash
  ./vendor/bin/pint
  ```
- **PHPStan niveau 8** : Analyse statique
  ```bash
  ./vendor/bin/phpstan analyse
  ```
- **Type hints** : Toujours typer les paramètres et retours
- **Doc blocks** : Pour les méthodes publiques complexes

### Tests (Pest)

- **Obligatoire** pour toute nouvelle fonctionnalité
- **Coverage minimum 80%** pour les nouvelles features
- **Nommage clair** :
  ```php
  it('prevents voting before ballot token is obtained')
  it('aggregates budget allocations respecting min/max constraints')
  test('cannot reveal results before deadline')
  ```

### Frontend (Vue 3 + Inertia)

- **Composition API** : Préféré sur Options API
- **TypeScript** : Fortement recommandé
- **Tailwind** : Classes utilitaires, pas de CSS custom
- **Composants** : Petits, réutilisables, bien nommés

## 🔒 Sécurité & Confidentialité

**CRITIQUE** : Les contributions touchant à la sécurité ou l'anonymat doivent :

1. **Respecter strictement** les principes du projet :
   - Séparation identité/vote
   - Pas d'images/liens citoyens
   - Chiffrement des bulletins
   - Aucun traçage utilisateur

2. **Être testées exhaustivement** :
   ```php
   it('stores ballot without user_id')
   it('consumes token after single use')
   it('prevents vote linkage to identity')
   ```

3. **Être reviewées par 2+ mainteneurs**

### Signaler une vulnérabilité

**NE PAS** ouvrir d'issue publique. Envoyez un email à : **security@civicdash.fr**

## 🗂️ Structure des Migrations

```php
// Nom de fichier : YYYY_MM_DD_HHMMSS_create_topics_table.php
public function up(): void
{
    Schema::create('topics', function (Blueprint $table) {
        $table->id();
        $table->enum('scope', ['national', 'region', 'dept']);
        // ... colonnes
        $table->timestamps();
    });
}
```

## 📦 Ajout de Dépendances

Avant d'ajouter une dépendance Composer/NPM :

1. Vérifiez qu'elle est **nécessaire** (pas de bloat)
2. Licence compatible (AGPL, MIT, Apache)
3. Maintenance active (dernier commit < 6 mois)
4. Justifiez dans la PR pourquoi elle est ajoutée

## 🧪 Process de Review

Les mainteneurs vérifieront :

1. ✅ Tests passent
2. ✅ Code style respecté (Pint + PHPStan)
3. ✅ Pas de régression
4. ✅ Documentation mise à jour
5. ✅ Commits conventionnels
6. ✅ Sécurité/confidentialité respectées (si applicable)

**Délai de review** : ~3-7 jours (soyez patients !)

## 🎯 Priorités Actuelles

Consultez les [issues labelées "good first issue"](https://github.com/votre-org/civicdash/labels/good%20first%20issue) pour commencer !

**Besoins prioritaires** (PoC) :
- [ ] Seeders territoires FR complets
- [ ] UI composants réutilisables (boutons, formulaires)
- [ ] Tests E2E (vote flow complet)
- [ ] Documentation API REST
- [ ] Traductions i18n (fr/en)

## 📚 Ressources

- [Documentation Laravel](https://laravel.com/docs/11.x)
- [Pest PHP](https://pestphp.com/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [AGPL-3.0 FAQ](https://www.gnu.org/licenses/gpl-faq.html#AGPLv3)

## ❓ Questions

- **GitHub Discussions** : Questions générales, idées
- **Discord** : [lien discord si disponible]
- **Email** : contact@civicdash.fr

---

Merci de contribuer à un web civique plus démocratique et transparent ! 🇫🇷🗳️

