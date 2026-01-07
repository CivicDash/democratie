<x-mail::message>
# 🎉 Bienvenue sur CivicDash, {{ $user->name }} !

Votre compte a été créé avec succès. Vous faites maintenant partie de la communauté citoyenne !

---

## 🚀 Premiers pas sur CivicDash

Voici ce que vous pouvez faire dès maintenant :

### 1️⃣ Complétez votre profil
Ajoutez une photo, une bio et vos centres d'intérêt pour vous faire connaître.

<x-mail::button :url="$profileUrl" color="primary">
👤 Compléter mon profil
</x-mail::button>

### 2️⃣ Découvrez les idées citoyennes
Parcourez les propositions de la communauté et votez pour celles qui vous tiennent à cœur.

<x-mail::button :url="$discoverUrl" color="success">
💡 Découvrir les idées
</x-mail::button>

---

## 📊 Ce que vous pouvez faire sur CivicDash

| Fonctionnalité | Description |
|:---------------|:------------|
| 🗳️ **Voter** | Soutenez ou opposez-vous aux propositions |
| 📢 **Interpeller** | Posez vos questions directement aux élus |
| 💬 **Débattre** | Participez aux discussions citoyennes |
| 📈 **Suivre** | Surveillez l'activité de vos élus |
| 🏆 **Progresser** | Gagnez des badges et de l'XP |

---

## 🔔 Notifications

Par défaut, vous recevrez des notifications sur le site. Vous pouvez activer les notifications par email dans vos préférences.

---

## ❓ Besoin d'aide ?

Notre [guide de démarrage]({{ config('app.url') }}/aide) vous accompagne pas à pas.

Vous pouvez aussi nous contacter à [contact@civis-consilium.eu](mailto:contact@civis-consilium.eu).

À très bientôt sur CivicDash !  
**L'équipe CivicDash**

<x-mail::subcopy>
Cet email confirme la création de votre compte le {{ now()->format('d/m/Y à H:i') }}.
</x-mail::subcopy>
</x-mail::message>
