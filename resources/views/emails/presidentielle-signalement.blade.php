<x-mail::message>
# 🚩 Nouveau signalement citoyen

Un signalement vient d'être reçu via **objectif2027.fr → Signaler une erreur**.

**Type :** {{ $typeLibelle }}
@if($signalement->candidat_slug)
**Candidat visé :** {{ $signalement->candidat_slug }}
@endif
@if($signalement->theme_slug)
**Thème visé :** {{ $signalement->theme_slug }}
@endif
@if($signalement->email)
**Contact du signaleur :** {{ $signalement->email }}
@else
**Contact :** _non renseigné (signalement anonyme)_
@endif

---

**Description :**

> {{ $signalement->description }}

@if($signalement->contexte_url)
**Page concernée :** [{{ $signalement->contexte_url }}]({{ $signalement->contexte_url }})
@endif

<x-mail::button :url="$boUrl" color="primary">
Traiter dans le back-office
</x-mail::button>

Statut initial : **nouveau**. Merci de le prendre en charge puis de le résoudre ou le rejeter.

Civis-Consilium — modération présidentielle
</x-mail::message>
