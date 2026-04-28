<x-mail::message>
# Revisione chiusa — prescrizione {{ $prescription?->typology ?? '-' }}

Gentile {{ $notifiable?->full_name ?? '-' }}, ti informiamo che l'amministrazione Mech & Human ha chiuso la revisione sulla prescrizione relativa al caso {{ $prescription?->typology ?? '-' }}.
<br>
# Dati lavorazione
<ul>
    <li>Codice lotto: {{ $operation?->batch_number ?? '-' }}</li>
    <li>Tipologia lavorazione: {{ $prescription?->typology ?? '-' }}</li>
    <li>Struttura: {{ $prescription?->building?->name ?? '-' }}</li>
    <li>Riferimento interno: {{ $prescription?->ref ?? '-' }}</li>
</ul>
<br>
Puoi tornare al portale per visualizzare lo stato aggiornato della prescrizione.

@if($url)
<x-mail::button :url="$url">
Vai alla prescrizione
</x-mail::button>
@endif

Grazie per la collaborazione,<br>
Il Team Mech & Human
</x-mail::message>
