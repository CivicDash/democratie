<x-mail::message>
# {{ $periodLabel }}

Bonjour {{ $user->name }},

@if($frequency === 'instant')
Un élu que vous suivez a une nouvelle activité :
@else
Voici les dernières activités des élus que vous suivez :
@endif

---

@foreach($groupedActivities as $eluNom => $eluActivities)
## {{ $eluNom }}

@foreach($eluActivities as $activity)
**{{ $activity['activity_icon'] ?? '📌' }} {{ $activity['activity_type'] === 'votes' ? 'Vote' : ucfirst($activity['activity_type']) }}**

{{ $activity['activity_title'] ?? 'Activité parlementaire' }}

@if(isset($activity['activity_detail']))
> {{ $activity['activity_detail'] }}
@endif

@if(isset($activity['activity_url']))
<x-mail::button :url="$activity['activity_url']" color="primary">
Voir le détail
</x-mail::button>
@endif

---

@endforeach
@endforeach

## 📊 Résumé

- **{{ $activities->count() }}** activité(s) détectée(s)
- **{{ $groupedActivities->count() }}** élu(s) concerné(s)

---

<x-mail::button :url="$preferencesUrl" color="secondary">
⚙️ Gérer mes élus suivis
</x-mail::button>

<x-mail::subcopy>
Vous recevez cet email car vous suivez des élus sur CivicDash.
Pour modifier la fréquence de ces notifications ou vous désabonner, rendez-vous dans vos [préférences]({{ $preferencesUrl }}).
</x-mail::subcopy>
</x-mail::message>
