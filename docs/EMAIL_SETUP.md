# 📧 Configuration Email CivicDash

**Domaine :** `civis-consilium.eu`

---

## 🚀 Configuration actuelle (Développement)

### Mailpit - Serveur mail local

Mailpit capture tous les emails pour les visualiser sans les envoyer réellement.

**Configuration `.env` :**
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@civis-consilium.eu
MAIL_FROM_NAME="Civis Consilium"
```

**Accès interface web :** http://localhost:8025

Si `MAILPIT_AUTH` est configuré dans `.env`, une authentification sera requise.

**Démarrer Mailpit :**
```bash
docker compose up -d mailpit
```

---

## 🏭 Configuration Production

### Option 1 : Brevo (ex-Sendinblue) 🇫🇷 - Recommandé

Service français, 300 emails/jour gratuits, conforme RGPD.

**Configuration `.env` :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@civis-consilium.eu
MAIL_PASSWORD=votre-clé-smtp-brevo
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@civis-consilium.eu
MAIL_FROM_NAME="Civis Consilium"
```

### Option 2 : Mailjet 🇫🇷

200 emails/jour gratuits.

```env
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=votre-api-key
MAIL_PASSWORD=votre-api-secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@civis-consilium.eu
MAIL_FROM_NAME="Civis Consilium"
```

---

## 🌐 DNS à configurer pour civis-consilium.eu

### Enregistrements requis

```dns
# SPF - Autorise les serveurs à envoyer des emails
civis-consilium.eu.  TXT  "v=spf1 include:sendinblue.com ~all"

# OU pour Mailjet :
civis-consilium.eu.  TXT  "v=spf1 include:spf.mailjet.com ~all"

# DKIM - Fourni par le service (Brevo/Mailjet)
# Exemple Brevo :
mail._domainkey.civis-consilium.eu.  TXT  "k=rsa; p=MIGf..."

# DMARC - Politique de gestion des échecs
_dmarc.civis-consilium.eu.  TXT  "v=DMARC1; p=none; rua=mailto:dmarc@civis-consilium.eu"
```

### MX (si réception d'emails nécessaire)
```dns
civis-consilium.eu.  MX  10 mail.civis-consilium.eu.
```

---

## 📊 Logging des emails

Tous les emails sont automatiquement enregistrés dans `email_logs`.

### Consulter les logs

```bash
# Via Tinker
docker compose exec app php artisan tinker
>>> App\Models\EmailLog::latest()->take(10)->get(['to_email', 'subject', 'status', 'sent_at'])

# Statistiques 7 derniers jours
>>> App\Models\EmailLog::getStats(7)

# Emails échoués
>>> App\Models\EmailLog::failed()->get()
```

### Table `email_logs`

| Colonne | Type | Description |
|---------|------|-------------|
| to_email | string | Destinataire |
| subject | string | Sujet |
| mailable_class | string | Classe Laravel |
| status | string | sent/failed/queued |
| message_id | string | ID du message |
| sent_at | timestamp | Date d'envoi |

---

## 🔧 Test de la configuration

### 1. Démarrer Mailpit
```bash
docker compose up -d mailpit
```

### 2. Tester l'envoi
```bash
docker compose exec app php artisan tinker
```

```php
Mail::raw('Test email Civis Consilium', function($msg) {
    $msg->to('test@exemple.fr')->subject('Test configuration email');
});
```

### 3. Vérifier dans Mailpit
Ouvrir http://localhost:8025 pour voir l'email capturé.

---

## 📋 Checklist avant production

- [ ] Créer compte Brevo ou Mailjet
- [ ] Configurer les variables `.env`
- [ ] Ajouter SPF dans les DNS
- [ ] Configurer DKIM (fourni par le service)
- [ ] Ajouter DMARC dans les DNS
- [ ] Tester l'envoi réel
- [ ] Vérifier que les emails arrivent (pas en spam)
