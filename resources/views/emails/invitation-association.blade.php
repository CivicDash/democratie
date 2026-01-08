<x-mail::message>
# Bonjour {{ $recipientName }},

**{{ $inviterName }}** vous invite à rejoindre **{{ $associationName }}** sur CivicDash !

---

## 🤝 À propos de CivicDash

CivicDash est une plateforme citoyenne qui permet aux associations et à leurs membres de :

- **📊 Suivre l'actualité politique** (votes, lois, élus)
- **📢 Participer aux débats citoyens** et proposer des idées
- **🗳️ Voter sur les propositions** de la communauté
- **💬 Interpeller les élus** sur des sujets qui vous concernent

@if($personalMessage)
---

### 💬 Message de {{ $inviterName }} :

> {{ $personalMessage }}

@endif

---

## 🚀 Rejoignez {{ $associationName }}

<x-mail::button :url="$registerUrl" color="primary">
🤝 Rejoindre l'association
</x-mail::button>

---

### 👤 Votre rôle

Vous serez inscrit en tant que : **{{ ucfirst($role) }}**

Une fois inscrit, vous pourrez :
- Accéder aux discussions du groupe
- Participer aux votes internes
- Représenter votre association dans les débats publics

---

### ❓ Des questions ?

Contactez {{ $inviterName }} ou notre équipe à [contact@civis-consilium.eu](mailto:contact@civis-consilium.eu)

À bientôt sur CivicDash !  
**L'équipe CivicDash**

<x-mail::subcopy>
Cette invitation a été envoyée par {{ $inviterName }} au nom de {{ $associationName }}.  
Si vous ne souhaitez pas rejoindre cette association, vous pouvez ignorer cet email.
</x-mail::subcopy>
</x-mail::message>
