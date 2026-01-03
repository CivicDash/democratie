<x-mail::message>
# Bonjour {{ $eluName }},

Un citoyen vous a interpellé sur la plateforme **CivicDash**.

---

## 📣 {{ $topic->title }}

**Type :** {{ ucfirst($topic->idea_type) }}  
**Auteur :** {{ $author->name }}  
**Date :** {{ $topic->created_at->format('d/m/Y à H:i') }}

### Contenu de l'interpellation :

> {{ Str::limit($topic->description, 500) }}

---

<x-mail::button :url="$dashboardUrl" color="primary">
🏛️ Accéder à mon espace élu
</x-mail::button>

<x-mail::button :url="$topicUrl" color="success">
📖 Voir l'interpellation complète
</x-mail::button>

---

### Comment répondre ?

1. Connectez-vous à votre **Espace Élu** sur CivicDash
2. Accédez à la section **Interpellations**
3. Cliquez sur cette interpellation pour y répondre

Votre réponse sera publique et visible par tous les citoyens.

---

**Pourquoi avez-vous reçu cet email ?**  
Vous êtes inscrit comme élu vérifié sur CivicDash et un citoyen vous a directement interpellé. Vous pouvez gérer vos préférences de notification depuis votre profil.

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
CivicDash est une plateforme citoyenne de transparence démocratique.  
Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.
</x-mail::subcopy>
</x-mail::message>
