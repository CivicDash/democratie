<x-mail::message>
# 💬 {{ $author->name }} vous a mentionné

Bonjour **{{ $mentionedUser->name }}**,

{{ $author->name }} vous a mentionné dans {{ $contentType }}.

---

## 📝 {{ $contentTitle }}

<x-mail::panel>
{{ Str::limit($contentExcerpt, 350) }}
</x-mail::panel>

---

<x-mail::button :url="$contentUrl" color="primary">
💬 Voir et répondre
</x-mail::button>

---

### 💡 Conseils

- **Répondez** pour participer à la discussion
- **Mentionnez** d'autres utilisateurs avec @pseudo
- **Suivez** le sujet pour être notifié des nouvelles réponses

---

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Vous recevez cet email car vous avez été mentionné sur CivicDash.  
Gérez vos notifications depuis votre profil.
</x-mail::subcopy>
</x-mail::message>
