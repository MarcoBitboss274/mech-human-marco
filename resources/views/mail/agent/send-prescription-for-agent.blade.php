<x-mail::message>
# Mech & Human - Nuova richiesta {{ $prescription?->typology ?? '-' }} caricata da {{ $prescription?->building?->name ?? '-' }}

Gentile {{ $notifiable?->full_name ?? '-' }}, ti informiamo che il tuo cliente {{ $prescription?->building?->name ?? '-' }} ha caricato una nuova richiesta di lavorazione {{ $prescription?->typology ?? '-' }} sul nostro portale.
<br>
Puoi monitorare l'avanzamento del caso e la documentazione associata nella tua area riservata. 

<x-mail::button :url="$url">
Visualizza richiesta
</x-mail::button>

Graie per la collaborazione,<br>
Il Team Mech & Human
</x-mail::message>
