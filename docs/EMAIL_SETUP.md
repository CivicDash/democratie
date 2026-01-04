# 📧 Configuration Email CivicDash

## Options disponibles

### 1. Brevo (ex-Sendinblue) - Recommandé 🇫🇷

Service français, 300 emails/jour gratuits, conforme RGPD.

**Étapes :**
1. Créer un compte sur [brevo.com](https://www.brevo.com)
2. Aller dans Paramètres > API Keys
3. Créer une clé API SMTP

**Configuration `.env` :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@exemple.fr
MAIL_PASSWORD=votre-clé-smtp-brevo
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@civicdash.fr
MAIL_FROM_NAME="CivicDash"
```

**DNS à configurer :**
- SPF : `v=spf1 include:sendinblue.com ~all`
- DKIM : Fourni dans l'interface Brevo

---

### 2. Mailjet - Alternative 🇫🇷

200 emails/jour gratuits.

**Configuration `.env` :**
```env
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=votre-api-key
MAIL_PASSWORD=votre-api-secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@civicdash.fr
MAIL_FROM_NAME="CivicDash"
```

---

### 3. Docker Self-Hosted avec Postal 🐳

Solution open source pour envoyer depuis son propre serveur.

**docker-compose.mail.yml :**
```yaml
version: '3.8'

services:
  # Serveur SMTP léger pour relay
  mailserver:
    image: namshi/smtp
    container_name: civicdash-smtp
    restart: unless-stopped
    environment:
      - RELAY_HOST=smtp-relay.brevo.com
      - RELAY_PORT=587
      - RELAY_USERNAME=${BREVO_SMTP_USER}
      - RELAY_PASSWORD=${BREVO_SMTP_PASSWORD}
    networks:
      - civicdash-network

networks:
  civicdash-network:
    external: true
```

**Utilisation :**
```env
MAIL_MAILER=smtp
MAIL_HOST=mailserver
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

---

### 4. Envoi direct depuis le serveur (sans relay)

⚠️ **Attention** : Risque élevé de finir en spam sans SPF/DKIM/DMARC bien configurés.

**Configuration Docker :**
```yaml
services:
  postfix:
    image: boky/postfix
    container_name: civicdash-postfix
    restart: unless-stopped
    environment:
      - ALLOWED_SENDER_DOMAINS=civicdash.fr
      - HOSTNAME=mail.civicdash.fr
    volumes:
      - ./docker/postfix/dkim:/etc/opendkim/keys
    networks:
      - civicdash-network
```

**DNS requis :**
```
# SPF (TXT sur civicdash.fr)
v=spf1 ip4:VOTRE_IP_SERVEUR ~all

# DKIM (TXT)
default._domainkey.civicdash.fr  →  v=DKIM1; k=rsa; p=MIGf...

# DMARC (TXT)
_dmarc.civicdash.fr  →  v=DMARC1; p=quarantine; rua=mailto:dmarc@civicdash.fr

# PTR (Reverse DNS sur l'IP)
IP → mail.civicdash.fr
```

---

## 📊 Logging des emails

Pour suivre les emails envoyés, CivicDash utilise un système de logs intégré.

### Table `email_logs`

```sql
CREATE TABLE email_logs (
    id BIGSERIAL PRIMARY KEY,
    to_email VARCHAR(255),
    subject VARCHAR(500),
    mailable_class VARCHAR(255),
    status VARCHAR(50) DEFAULT 'sent',
    message_id VARCHAR(255),
    error_message TEXT,
    metadata JSONB,
    sent_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### Listener Laravel

Le listener `LogSentMessage` enregistre automatiquement tous les emails envoyés.

---

## 🔧 Test de la configuration

```bash
# Tester l'envoi d'un email
docker compose exec app php artisan tinker

# Dans Tinker :
Mail::raw('Test email CivicDash', function($msg) {
    $msg->to('votre-email@test.fr')->subject('Test');
});
```

---

## Recommandation

Pour commencer :
1. **Créer un compte Brevo** (gratuit, 300 emails/jour)
2. **Configurer SPF/DKIM** dans les DNS
3. **Tester** avec la commande ci-dessus

Pour la production à grande échelle :
1. **Postal** self-hosted ou **Brevo payant**
2. **Monitoring** avec logs dans la base de données
