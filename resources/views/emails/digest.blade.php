<x-mail::message>
@php
$periodLabel = $period === 'daily' ? 'quotidien' : 'hebdomadaire';
$periodTitle = $period === 'daily' ? 'Aujourd''hui' : 'Cette semaine';
@endphp

# 📰 Votre récap {{ $periodLabel }}

Bonjour **{{ $user->name }}**,

Voici ce qui s'est passé sur CivicDash du **{{ $startDate->format('d/m') }}** au **{{ $endDate->format('d/m/Y') }}**.

---

## 📊 En résumé

<table style="width: 100%; text-align: center; margin: 20px 0;">
<tr>
<td style="background: #f0f9ff; border-radius: 8px; padding: 20px;">
<span style="font-size: 28px; color: #1e40af; font-weight: bold;">{{ count($newVotes) }}</span><br>
<span style="color: #6b7280; font-size: 13px;">Nouveaux votes</span>
</td>
<td style="width: 10px;"></td>
<td style="background: #f0fdf4; border-radius: 8px; padding: 20px;">
<span style="font-size: 28px; color: #059669; font-weight: bold;">{{ count($eluResponses) }}</span><br>
<span style="color: #6b7280; font-size: 13px;">Réponses d'élus</span>
</td>
<td style="width: 10px;"></td>
<td style="background: #fef3c7; border-radius: 8px; padding: 20px;">
<span style="font-size: 28px; color: #d97706; font-weight: bold;">{{ $totalNotifications }}</span><br>
<span style="color: #6b7280; font-size: 13px;">Notifications</span>
</td>
</tr>
</table>

---

@if(count($newVotes) > 0)
## 🗳️ Votes récents

<x-mail::table>
| Vote | Résultat | Date |
|:-----|:---------|:-----|
@foreach(array_slice($newVotes, 0, 5) as $vote)
| {{ Str::limit($vote['title'], 40) }} | {{ $vote['result'] }} | {{ $vote['date'] }} |
@endforeach
</x-mail::table>

@if(count($newVotes) > 5)
<p style="color: #6b7280; font-size: 13px;">Et {{ count($newVotes) - 5 }} autres votes...</p>
@endif

---
@endif

@if(count($eluResponses) > 0)
## 💬 Réponses d'élus

@foreach(array_slice($eluResponses, 0, 3) as $response)
<x-mail::panel>
**{{ $response['elu_name'] }}** a répondu à « {{ Str::limit($response['topic_title'], 50) }} »
</x-mail::panel>
@endforeach

@if(count($eluResponses) > 3)
<p style="color: #6b7280; font-size: 13px;">Et {{ count($eluResponses) - 3 }} autres réponses...</p>
@endif

---
@endif

@if(count($popularTopics) > 0)
## 🔥 Sujets populaires

<x-mail::table>
| Sujet | Votes | Commentaires |
|:------|------:|-------------:|
@foreach(array_slice($popularTopics, 0, 5) as $topic)
| {{ Str::limit($topic['title'], 35) }} | {{ $topic['votes'] }} | {{ $topic['comments'] }} |
@endforeach
</x-mail::table>

---
@endif

<x-mail::button :url="$dashboardUrl" color="primary">
🏠 Accéder à mon tableau de bord
</x-mail::button>

---

### ⚙️ Fréquence des récaps

Vous recevez ce récap **{{ $periodLabel }}**. 
Pour changer la fréquence ou désactiver ces emails :

<x-mail::button url="{{ config('app.url') }}/profile/notification-preferences" color="success">
⚙️ Gérer mes préférences
</x-mail::button>

---

Bonne lecture !  
**L'équipe CivicDash**

<x-mail::subcopy>
Ce récap couvre la période du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}.  
Vous pouvez modifier la fréquence ou vous désabonner depuis vos préférences.
</x-mail::subcopy>
</x-mail::message>
