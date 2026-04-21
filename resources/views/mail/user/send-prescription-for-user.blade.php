<x-mail::message>
# Conferma invio richiesta di lavorazione {{ $prescription?->typology ?? '-' }}

Gentile {{ $notifiable?->full_name ?? '-' }}, ti confermiamo che la richiesta di lavorazione per il caso {{ $prescription?->typology ?? '-' }} è stata inviata correttamente ai nostri sistemi.
<br>
# Dati lavorazione
<ul>
    <li>Codice loto: {{ $operation?->batch_number ?? '-' }}</li>
    <li>Tipologia lavorazione: {{ $prescription?->typology ?? '-' }}</li>
    <li>Richiedente: {{ $prescription?->user?->full_name ?? '-' }}</li>
    <li>Struttura: {{ $prescription?->building?->name ?? '-' }}</li>
    <li>Riferimento interno: {{ $prescription?->ref ?? '-' }}</li>
</ul>
<br>
I dati e i file caricati sono attualmente in fase di controllo tecnico.
<br>
Qualora i dati forniti non fossero sufficienti, verrai contattato dal nostro ufficio tecnico o riceverai una richiesta di integrazione dati direttamente sul portale. 
<br>

Graie per la collaborazione,<br>
Il Team Mech & Human
</x-mail::message>
