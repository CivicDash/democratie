# Politique de Sécurité

## Versions supportées

| Version | Support         |
| ------- | --------------- |
| 1.x     | ✅ Support actif |
| < 1.0   | ❌ PoC uniquement |

## Signaler une vulnérabilité

**⚠️ NE PAS créer d'issue publique pour les vulnérabilités de sécurité.**

Si vous découvrez une vulnérabilité de sécurité dans CivicDash, merci de nous en informer de manière responsable :

### 📧 Contact

Envoyez un email à : **security@civicdash.fr**

### 📝 Informations à inclure

1. **Description** de la vulnérabilité
2. **Étapes de reproduction** détaillées
3. **Impact potentiel** (confidentialité, intégrité, disponibilité)
4. **Version affectée** de CivicDash
5. **Votre environnement** (OS, navigateur, etc.)
6. **Preuve de concept** (si applicable)

### 🕐 Délai de réponse

- **Premier accusé de réception** : sous 48h
- **Analyse initiale** : sous 7 jours
- **Correctif** : selon la gravité (critique < 48h, haute < 7j, moyenne < 30j)

### 🏆 Reconnaissance

Les chercheurs en sécurité qui signalent des vulnérabilités de manière responsable seront :
- Mentionnés dans le CHANGELOG (si souhaité)
- Ajoutés à notre page SECURITY.md
- Remerciés publiquement

### 🔒 Domaines prioritaires

**Critique** (traitement immédiat) :
- Anonymat des votes (linkage identité/bulletin)
- Chiffrement des bulletins
- Authentification et autorisation
- Injection SQL/XSS/CSRF
- Fuite de données personnelles

**Haute priorité** :
- Rate limiting / DoS
- Élévation de privilèges
- Bypass de modération
- Manipulation des résultats de vote

**Moyenne priorité** :
- Validation de données
- Logs sensibles
- Configuration par défaut

### ✅ Bonnes pratiques

**En scope** :
- ✅ Vulnérabilités applicatives (Laravel)
- ✅ Failles de logique métier (vote, budget)
- ✅ Contournement d'anonymat
- ✅ Injection (SQL, XSS, Command)
- ✅ CSRF, SSRF
- ✅ Authentification/Autorisation

**Hors scope** :
- ❌ DoS nécessitant >100 req/s
- ❌ Vulnérabilités des dépendances (sauf si exploitables dans notre contexte)
- ❌ Social engineering
- ❌ Attaques physiques
- ❌ Spam / contenu inapproprié (utiliser la modération)

### 🛡️ Mesures de sécurité actuelles

- **Hashing** : Argon2id pour mots de passe
- **CSRF** : Protection Laravel native
- **XSS** : Sanitization Markdown stricte
- **Injection SQL** : Eloquent ORM + prepared statements
- **Rate limiting** : Throttle API
- **Bulletins de vote** : Chiffrés (Laravel Crypt), stockés sans user_id
- **Audit** : Logs append-only

### 📚 Références

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [ANSSI Bonnes pratiques](https://www.ssi.gouv.fr/)
- [CNIL Guide développeur](https://www.cnil.fr/fr/guide-du-developpeur)

---

**Merci de contribuer à la sécurité de CivicDash !** 🔒

