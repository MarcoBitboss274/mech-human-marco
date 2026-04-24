<x-mail::message>
# Prescrizione revisionata — {{ $prescription?->typology ?? '-' }}

Gentile Utente, il customer {{ $prescription?->user?->full_name ?? '-' }} ha reinviato la prescrizione revisionata (revisione #{{ $revisionNumber }}).
<br>
# Dettagli richiesta
<ul>
    <li>Codice lotto: {{ $operation?->batch_number ?? '-' }}</li>
    <li>Tipologia lavorazione: {{ $prescription?->typology ?? '-' }}</li>
    <li>Richiedente: {{ $prescription?->user?->full_name ?? '-' }}</li>
    <li>Struttura: {{ $prescription?->building?->name ?? '-' }}</li>
    <li>Riferimento interno: {{ $prescription?->ref ?? '-' }}</li>
</ul>
<br>
Verifica le modifiche e procedi con la conferma o con una nuova richiesta di revisione.

@if($url)
<x-mail::button :url="$url">
Visualizza richiesta
</x-mail::button>
@endif

</x-mail::message>
