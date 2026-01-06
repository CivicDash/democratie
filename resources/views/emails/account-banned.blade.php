<x-mail::message>
# 🚫 Compte banni définitivement

Bonjour **{{ $user->name }}**,

Nous vous informons que votre compte CivicDash a été **banni définitivement**.

## Raison du bannissement

{{ $reason }}

## Conséquences

Vous ne pouvez plus accéder à votre compte ni utiliser la plateforme CivicDash avec cette adresse email.

## Faire appel

Si vous souhaitez contester cette décision, vous pouvez plaider votre cause en envoyant un email à :

<x-mail::panel>
📧 **{{ $appealEmail }}**
</x-mail::panel>

Dans votre email, merci d'indiquer :
- Votre nom d'utilisateur : **{{ $user->username ?? $user->name }}**
- Votre adresse email : **{{ $user->email }}**
- Les raisons de votre contestation

Notre équipe examinera votre demande et vous répondra dans les meilleurs délais.

Cordialement,<br>
L'équipe de modération CivicDash
</x-mail::message>
