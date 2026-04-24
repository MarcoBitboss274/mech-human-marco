<x-mail::message>
# Richiesta di revisione prescrizione {{ $prescription?->typology ?? '-' }}

Gentile {{ $notifiable?->full_name ?? '-' }}, ti informiamo che l'amministrazione Mech & Human ha richiesto una revisione della prescrizione relativa al caso {{ $prescription?->typology ?? '-' }}.
<br>
# Motivo della revisione
> {{ $reason }}
<br>
# Dati lavorazione
<ul>
    <li>Codice lotto: {{ $operation?->batch_number ?? '-' }}</li>
    <li>Tipologia lavorazione: {{ $prescription?->typology ?? '-' }}</li>
    <li>Struttura: {{ $prescription?->building?->name ?? '-' }}</li>
    <li>Riferimento interno: {{ $prescription?->ref ?? '-' }}</li>
</ul>
<br>
Accedi al portale per applicare le modifiche richieste e reinviare la prescrizione.

@if($url)
<x-mail::button :url="$url">
Vai alla prescrizione
</x-mail::button>
@endif

Grazie per la collaborazione,<br>
Il Team Mech & Human
</x-mail::message>
