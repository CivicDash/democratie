<x-mail::message>
# 🎉 Bienvenue sur CivicDash, {{ $user->name }} !

Vous faites maintenant partie de la communauté citoyenne de transparence démocratique.

---

## 🚀 Par où commencer ?

### 1. Complétez votre profil

Ajoutez une photo et personnalisez vos préférences de notification.

<x-mail::button :url="$profileUrl" color="primary">
👤 Mon profil
</x-mail::button>

---

### 2. Découvrez les fonctionnalités

<x-mail::table>
| Fonctionnalité | Description |
|:---------------|:------------|
| 🏛️ **Parlement** | Suivez les votes et activités parlementaires |
| 📢 **Interpellations** | Posez vos questions aux élus |
| 💬 **Débats** | Participez aux discussions citoyennes |
| 🗳️ **Propositions** | Votez pour les idées de la communauté |
| 📊 **Statistiques** | Explorez les données publiques |
</x-mail::table>

---

### 3. Explorez les débats en cours

<x-mail::button :url="$discoverUrl" color="success">
🔍 Découvrir les propositions
</x-mail::button>

---

## 📱 Restez informé

Configurez vos préférences de notification pour ne rien manquer :
- **Réponses des élus** à vos interpellations
- **Résultats des votes** parlementaires
- **Mentions** dans les débats

---

## 🏆 Gagnez des badges !

CivicDash récompense votre participation avec des badges et de l'XP :
- 🏅 **Premier pas** : Créez votre premier sujet
- 💬 **Contributeur** : Participez aux débats
- 🗳️ **Citoyen actif** : Votez régulièrement

---

**Une question ?** Contactez-nous à support@civis-consilium.eu

Bonne découverte !  
**L'équipe CivicDash**

<x-mail::subcopy>
Vous recevez cet email car vous venez de créer un compte sur CivicDash.  
Gérez vos notifications depuis votre profil.
</x-mail::subcopy>
</x-mail::message>
