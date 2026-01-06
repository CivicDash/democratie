<x-mail::message>
# Bonjour {{ $eluName }},

Vous êtes invité(e) à rejoindre **CivicDash**, la plateforme citoyenne de transparence démocratique.

---

## 🏛️ Pourquoi rejoindre CivicDash ?

En tant que **{{ $eluTypeLabel }}**, CivicDash vous permet de :

- **📢 Répondre aux interpellations** des citoyens de votre circonscription
- **📊 Suivre votre activité parlementaire** (votes, questions, amendements)
- **💬 Dialoguer directement** avec vos administrés
- **📈 Améliorer votre visibilité** avec un profil public enrichi

@if($personalMessage)
---

### 💬 Message de {{ $inviterName }} :

> {{ $personalMessage }}

@endif

---

## 🚀 Créez votre compte élu vérifié

La création de compte prend moins de 2 minutes. Une fois inscrit, vous pourrez :

1. Valider votre identité d'élu
2. Personnaliser votre profil public
3. Commencer à interagir avec les citoyens

<x-mail::button :url="$registerUrl" color="primary">
🏛️ Créer mon compte élu
</x-mail::button>

---

### 🔒 Confidentialité et sécurité

- Votre email n'est **jamais affiché publiquement**
- Vous contrôlez vos **préférences de notification**
- Toutes les données sont hébergées en **France**

---

### ❓ Des questions ?

Notre équipe est disponible pour vous accompagner dans la prise en main de la plateforme.

Contactez-nous à [contact@civis-consilium.eu](mailto:contact@civis-consilium.eu)

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Cette invitation a été envoyée par {{ $inviterName }} via CivicDash.  
Si vous n'êtes pas {{ $eluName }}, vous pouvez ignorer cet email.
</x-mail::subcopy>
</x-mail::message>
