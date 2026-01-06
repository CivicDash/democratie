<x-mail::message>
@php
$resultEmoji = match($result) {
    'adopté' => '✅',
    'rejeté' => '❌',
    default => '🗳️',
};
$resultColor = match($result) {
    'adopté' => '#059669',
    'rejeté' => '#dc2626',
    default => '#1e40af',
};
$total = $votesFor + $votesAgainst + $abstentions;
$pourcentFor = $total > 0 ? round(($votesFor / $total) * 100) : 0;
$pourcentAgainst = $total > 0 ? round(($votesAgainst / $total) * 100) : 0;
@endphp

# {{ $resultEmoji }} Résultat du vote

Bonjour **{{ $user->name }}**,

Le vote que vous suiviez vient d'être publié.

---

## 🗳️ {{ $voteTitle }}

<div style="background-color: {{ $resultColor }}20; border-left: 4px solid {{ $resultColor }}; padding: 15px 20px; border-radius: 0 6px 6px 0; margin: 20px 0;">
<strong style="color: {{ $resultColor }}; font-size: 18px;">{{ strtoupper($result) }}</strong>
</div>

---

## 📊 Résultats détaillés

<x-mail::table>
| Vote | Nombre | Pourcentage |
|:-----|-------:|------------:|
| ✅ Pour | {{ number_format($votesFor) }} | {{ $pourcentFor }}% |
| ❌ Contre | {{ number_format($votesAgainst) }} | {{ $pourcentAgainst }}% |
| ⚪ Abstentions | {{ number_format($abstentions) }} | — |
| **Total** | **{{ number_format($total) }}** | **100%** |
</x-mail::table>

---

@if($eluPosition)
## 🏛️ Position de votre élu

<x-mail::panel>
{{ $eluPosition }}
</x-mail::panel>
@endif

---

<x-mail::button :url="$voteUrl" color="primary">
📊 Voir le détail du vote
</x-mail::button>

---

### 🔍 Que faire maintenant ?

- **Consultez** les positions individuelles des élus
- **Partagez** ce résultat avec d'autres citoyens
- **Interpellez** un élu pour comprendre son vote

---

Cordialement,  
**L'équipe CivicDash**

<x-mail::subcopy>
Vous recevez cet email car vous suivez ce vote sur CivicDash.  
Gérez vos notifications depuis votre profil.
</x-mail::subcopy>
</x-mail::message>
