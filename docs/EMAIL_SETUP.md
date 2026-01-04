# 📧 Configuration Email Civis Consilium

**Domaine :** `civis-consilium.eu`

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    civis-consilium.eu                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   mail.civis-consilium.eu          app.civis-consilium.eu   │
│   ┌─────────────────────┐          ┌──────────────────────┐ │
│   │     BlueMind        │          │     CivicDash        │ │
│   │                     │◄─────────│                      │ │
│   │  • Webmail          │   SMTP   │  • Notifications     │ │
│   │  • Calendrier       │          │  • Interpellations   │ │
│   │  • Contacts         │          │  • 2FA               │ │
│   │                     │          │                      │ │
│   │  Adresses:          │          │                      │ │
│   │  • president@       │          │                      │ │
│   │  • secretaire@      │          │                      │ │
│   │  • tresorier@       │          │                      │ │
│   │  • contact@         │          │                      │ │
│   │  • noreply@         │          │                      │ │
│   └─────────────────────┘          └──────────────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📬 Adresses email prévues

### Conseil d'Administration
| Adresse | Usage |
|---------|-------|
| `president@civis-consilium.eu` | Président(e) |
| `secretaire@civis-consilium.eu` | Secrétaire |
| `tresorier@civis-consilium.eu` | Trésorier(ère) |
| `bureau@civis-consilium.eu` | Bureau (alias groupe) |

### Application
| Adresse | Usage |
|---------|-------|
| `noreply@civis-consilium.eu` | Notifications automatiques |
| `contact@civis-consilium.eu` | Support utilisateurs |
| `moderation@civis-consilium.eu` | Signalements |

### Membres (optionnel)
| Format | Usage |
|--------|-------|
| `prenom.nom@civis-consilium.eu` | Membres actifs |

---

## 🚀 Configuration Développement (Mailpit)

Mailpit capture les emails pour test sans les envoyer.

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

**Interface :** http://localhost:8025

```bash
docker compose up -d mailpit
```

---

## 🏭 Configuration Production (BlueMind)

### Configuration `.env` CivicDash

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.civis-consilium.eu
MAIL_PORT=587
MAIL_USERNAME=noreply@civis-consilium.eu
MAIL_PASSWORD=MotDePasseDansEnvUniquement
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@civis-consilium.eu
MAIL_FROM_NAME="Civis Consilium"
```

### DNS requis pour BlueMind

```dns
# MX - Réception des emails
civis-consilium.eu.           MX     10 mail.civis-consilium.eu.

# A - Serveur mail
mail.civis-consilium.eu.      A      <IP_SERVEUR_BLUEMIND>

# SPF - Autorisation d'envoi
civis-consilium.eu.           TXT    "v=spf1 mx a:mail.civis-consilium.eu ~all"

# DKIM - Signature (généré par BlueMind)
selector._domainkey.civis-consilium.eu.  TXT  "v=DKIM1; k=rsa; p=..."

# DMARC - Politique
_dmarc.civis-consilium.eu.    TXT    "v=DMARC1; p=quarantine; rua=mailto:dmarc@civis-consilium.eu"

# Autodiscover (Outlook)
autodiscover.civis-consilium.eu.  CNAME  mail.civis-consilium.eu.

# Autoconfig (Thunderbird)
autoconfig.civis-consilium.eu.    CNAME  mail.civis-consilium.eu.
```

### Ports à ouvrir (BlueMind)

| Port | Service | Usage |
|------|---------|-------|
| 25 | SMTP | Réception emails |
| 587 | SMTP/TLS | Envoi authentifié |
| 993 | IMAPS | Client mail (lecture) |
| 443 | HTTPS | Webmail BlueMind |

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
