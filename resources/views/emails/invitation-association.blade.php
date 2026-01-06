<x-mail::message>
# Bonjour {{ $memberName }},

Vous êtes invité(e) à rejoindre **CivicDash** en tant que membre de **{{ $associationName }}**.

---

## 🤝 Pourquoi rejoindre CivicDash ?

En tant que **membre d'association**, CivicDash vous permet de :

- **📢 Interpeller vos élus** sur les sujets qui vous tiennent à cœur
- **📊 Suivre l'activité parlementaire** en toute transparence
- **💬 Participer aux débats** citoyens sur les politiques publiques
- **🗳️ Proposer des idées** et voter pour celles des autres citoyens
- **👥 Représenter votre association** dans les discussions

---

@if($personalMessage)
## 💬 Message de {{ $inviterName }} :

<x-mail::panel>
{{ $personalMessage }}
</x-mail::panel>
@endif

---

## 🚀 Rejoindre la communauté

<x-mail::button :url="$registerUrl" color="success">
🤝 Créer mon compte membre
</x-mail::button>

---

### ✨ Fonctionnalités disponibles

<x-mail::table>
| Fonctionnalité | Description |
|:---------------|:------------|
| 📢 Interpellations | Posez vos questions aux élus |
| 🗳️ Votes citoyens | Votez pour les propositions |
| 💬 Commentaires | Partagez votre expertise |
| 📊 Statistiques | Accédez aux données publiques |
| 🏷️ Badge association | Identifiez-vous comme membre |
</x-mail::table>

---

### 🏷️ Statut « Membre d'association »

Votre compte sera lié à **{{ $associationName }}**, ce qui vous permet :
- D'afficher votre appartenance dans vos contributions
- De représenter votre organisation dans les débats
- D'accéder aux fonctionnalités réservées aux associations

---

**Une question ?** Contactez-nous à support@civis-consilium.eu

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Cette invitation a été envoyée par {{ $inviterName }} au nom de {{ $associationName }}.  
Si vous n'êtes pas concerné(e), vous pouvez ignorer ce message.
</x-mail::subcopy>
</x-mail::message>
