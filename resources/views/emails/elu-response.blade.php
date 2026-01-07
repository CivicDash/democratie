<x-mail::message>
# Bonne nouvelle, {{ $citizen->name }} !

**{{ $eluName }}** ({{ $eluTypeLabel }}) a répondu à votre interpellation.

---

## 📢 Votre interpellation

**{{ $topic->title }}**

---

## 💬 Réponse de {{ $eluName }}

<x-mail::panel>
{{ Str::limit($responseExcerpt, 400) }}
</x-mail::panel>

---

<x-mail::button :url="$topicUrl" color="success">
📖 Lire la réponse complète
</x-mail::button>

---

### 🗣️ Continuez le dialogue

Vous pouvez répondre à {{ $eluName }} directement sur la plateforme pour approfondir la discussion.

---

### 📊 Partagez cette réponse

Cette réponse officielle d'un élu peut intéresser d'autres citoyens. N'hésitez pas à la partager !

Merci de participer à la vie démocratique,  
**L'équipe CivicDash**

<x-mail::subcopy>
Pour gérer vos notifications, rendez-vous dans [Préférences]({{ config('app.url') }}/profile/notification-preferences).
</x-mail::subcopy>
</x-mail::message>
