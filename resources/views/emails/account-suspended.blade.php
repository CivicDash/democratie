<x-mail::message>
# ⚠️ Compte suspendu

Bonjour **{{ $user->name }}**,

Nous vous informons que votre compte CivicDash a été **suspendu temporairement**.

## Détails de la suspension

- **Durée** : {{ $days }} jour(s)
- **Fin de suspension** : {{ $endsAt->format('d/m/Y à H:i') }}
- **Raison** : {{ $reason }}

## Que se passe-t-il maintenant ?

Pendant la durée de votre suspension, vous ne pourrez pas accéder à votre compte ni publier de contenu sur la plateforme.

Votre compte sera automatiquement réactivé le **{{ $endsAt->format('d/m/Y à H:i') }}**.

## Contestation

Si vous estimez que cette décision est injuste, vous pouvez nous contacter par email à l'adresse suivante pour faire appel :

**moderation@civis-consilium.eu**

Merci de votre compréhension.

Cordialement,<br>
L'équipe de modération CivicDash
</x-mail::message>
