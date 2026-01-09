<x-mail::message>
# Invitation d'équipe

Bonjour,

{{ $inviterName }} vous invite à rejoindre l'équipe **{{ $teamName }}**.

@php
$inviteUrl = url('/invitations/accept/' . $token);
@endphp

<x-mail::button :url="$inviteUrl">
Rejoindre l'équipe
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
