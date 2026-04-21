<x-mail::message>
# Nuovo utente registrato sulla piattaforma

Gentile {{ $notifiable?->name ?? '-' }}, si informa che un nuovo utente ha completato con successo la procedura di registrazione.
<br>
# Dettagli utente
<ul>
    <li>Nome: {{ $user->name ?? '-' }}</li>
    <li>Cognome: {{ $user->surname ?? '-' }}</li>
    <li>Email: {{ $user->email ?? '-' }}</li>
    <li>Ruolo: {{ $user->role ?? '-' }}</li>
</ul>

<x-mail::button :url="$url">
Visualizza utenti
</x-mail::button>

</x-mail::message>
