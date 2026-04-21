<x-mail::message>
# Nuova richiesta di lavorazione {{ $operation?->typology ?? '-' }} ricevuta

Gentile Utente, si informa che una nuova richiesta di lavorazione {{ $operation?->typology ?? '-' }} è stata ricevuta.
<br>
# Dettagli richiesta
<ul>
    <li>Codice loto: {{ $operation?->batch_number ?? '-' }}</li>
    <li>Tipologia lavorazione: {{ $prescription?->typology ?? '-' }}</li>
    <li>Richiedente: {{ $prescription?->user?->full_name ?? '-' }}</li>
    <li>Struttura: {{ $prescription?->building?->name ?? '-' }}</li>
    <li>Riferimento interno: {{ $prescription?->ref ?? '-' }}</li>
</ul>

<br>
Da questo momento è possibile procedere alla verifica tecnica dei file e dei dati clinici per la presa in carico.

<x-mail::button :url="$url">
Visualizza richiesta
</x-mail::button>

</x-mail::message>
