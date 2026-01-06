<x-mail::message>
# Bonjour {{ $mentionedUser->name }},

**{{ $author->name }}** vous a mentionné dans {{ $contentTypeLabel }} sur CivicDash.

---

## 📌 {{ $contentTitle }}

<x-mail::panel>
{{ Str::limit($contentExcerpt, 300) }}
</x-mail::panel>

---

<x-mail::button :url="$contentUrl" color="primary">
💬 Voir et répondre
</x-mail::button>

---

### 💡 Pourquoi ai-je reçu cet email ?

Vous avez été mentionné avec `@{{ $mentionedUser->username ?? $mentionedUser->name }}` dans un contenu sur CivicDash.

Vous pouvez gérer vos préférences de notification depuis votre profil.

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Pour ne plus recevoir ces notifications, rendez-vous dans [Préférences de notification]({{ config('app.url') }}/profile/notification-preferences).
</x-mail::subcopy>
</x-mail::message>
