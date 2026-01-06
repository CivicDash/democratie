<x-mail::message>
# 💬 {{ $eluName }} a répondu !

Bonne nouvelle, **{{ $citizen->name }}** ! Un élu a répondu à votre interpellation.

---

## 🏛️ Votre interpellation

**{{ $topic->title }}**

---

## 📢 Réponse de {{ $eluName }}

<x-mail::panel>
**{{ $eluFunction }}**

{{ Str::limit($responseExcerpt, 400) }}
</x-mail::panel>

---

<x-mail::button :url="$topicUrl" color="primary">
📖 Lire la réponse complète
</x-mail::button>

---

## ✨ Et maintenant ?

- **Répondez** à l'élu pour approfondir le débat
- **Partagez** cette réponse avec d'autres citoyens
- **Suivez** l'élu pour être informé de ses prochaines interventions

---

### 📊 Statut de l'interpellation

<x-mail::table>
| Statut | Date |
|:-------|:-----|
| 📢 Créée | {{ $topic->created_at->format('d/m/Y') }} |
| 💬 Répondue | {{ now()->format('d/m/Y') }} |
</x-mail::table>

---

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Vous recevez cet email car vous avez interpellé {{ $eluName }} sur CivicDash.  
Gérez vos notifications depuis votre profil.
</x-mail::subcopy>
</x-mail::message>
