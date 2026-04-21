<x-mail::message>
# Ciao {{ $user->email ?? '' }}!

Sei stato invitato dalla struttura **{{ $building->name ?? '' }}**.
<br>
Clicca sul bottone qui sotto per procedere.

<x-mail::button :url="$url">
    Accetta
</x-mail::button>

</x-mail::message>
