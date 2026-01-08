<x-mail::message>
# Invitation d'équipe

Bonjour,

{{ $inviterName }} vous invite à rejoindre l'équipe **{{ $teamName }}**.

<x-mail::button :url="url('/')">
Rejoindre l'équipe
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
