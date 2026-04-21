<x-mail::message>
# Richiesta validazione Nuova Struttura: {{ $building->name ?? '-' }}

Gentile Utente, si informa che una nuova struttura è stata registrata sulla piattaforma.
<br>
# Dati struttura
<ul>
    <li>Ragione sociale: {{ $building->name ?? '-' }}</li>
    <li>P.IVA / CF: {{ $building->vat ?? $building->fiscal_code ?? '-' }}</li>
    <li>Indirizzo sede legale: {{ $building->legal_address ?? '-' }}</li>
    <li>Creata da: {{ $owner->full_name ?? '-' }}</li>
</ul>

<x-mail::button :url="$url">
Visualizza struttura
</x-mail::button>

</x-mail::message>
