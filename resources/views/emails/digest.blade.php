<x-mail::message>
# 📬 Votre résumé {{ $periodLabel }}, {{ $user->name }}

Voici ce qui s'est passé sur CivicDash {{ $period === 'daily' ? 'aujourd\'hui' : 'cette semaine' }}.

---

## 🔔 {{ $notifications->count() }} notification(s)

@foreach($groupedNotifications as $group)
### {{ $group['icon'] }} {{ $group['label'] }} ({{ $group['count'] }})

@foreach($group['items'] as $notification)
- **{{ $notification->title }}**  
  {{ Str::limit($notification->message, 100) }}
@endforeach

@if($group['count'] > 5)
_... et {{ $group['count'] - 5 }} autres_
@endif

---
@endforeach

<x-mail::button :url="$dashboardUrl" color="primary">
📊 Voir tout sur mon tableau de bord
</x-mail::button>

---

@if(!empty($stats))
## 📈 Statistiques de la période

<x-mail::table>
| Métrique | Valeur |
|:---------|-------:|
@foreach($stats as $label => $value)
| {{ $label }} | **{{ $value }}** |
@endforeach
</x-mail::table>

---
@endif

## ⚙️ Gérer mes notifications

Vous recevez ce résumé **{{ $periodLabel }}**. Vous pouvez modifier la fréquence ou désactiver ces emails.

<x-mail::button :url="$preferencesUrl" color="success">
⚙️ Préférences de notification
</x-mail::button>

---

Merci de faire vivre la démocratie !  
**L'équipe CivicDash**

<x-mail::subcopy>
Ce résumé couvre la période du {{ $period === 'daily' ? now()->subDay()->format('d/m/Y') : now()->subWeek()->format('d/m/Y') }} au {{ now()->format('d/m/Y') }}.
</x-mail::subcopy>
</x-mail::message>
