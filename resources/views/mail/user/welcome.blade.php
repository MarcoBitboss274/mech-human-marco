<x-mail::message>
# Ciao {{ $user->full_name ?? '' }}!

È stato creato un account per te!
<br>
Clicca sul link qui sotto per impostare la tua password ed accedere a tutte le informazioni sul progetto.

<x-mail::button :url="$url">
    Imposta la password
</x-mail::button>

</x-mail::message>
