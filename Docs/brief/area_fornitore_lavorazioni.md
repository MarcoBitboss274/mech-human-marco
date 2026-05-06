

AREA FORNITORE - STATO DI UNA LAVORAZIONE

Bisogna ragionare sul concetto di Stato di una lavorazione lato Fornitore.
Lo stato della lavorazione visibile al fornitore è sempre un derivato dello stato originale della lavorazione.
Aiutami a capire quali stati vede il fornitore. In teoria il ciclo di vita di una lavorazione lato fornitore è questo:
Il fornitore viene assegnato, visualizza i documenti di M&H, se tutto va bene deve caricare i suoi documenti che servono a M&H per fare il preventivo al customer, attende che M&H confermi la produzione, quando vede che la produzione è confermata allora procede con la produzione del pezzo, quando il pezzo è pronto lo comunica a M&H e poi quando gli invia il pezzo in teoria lui ha finito. Il fornitore deve sapere sopratutto quando gli viene assegnata una lavorazione, deve sapere se deve ancora caricare i suoi documenti, e deve sapere quando viene confermata la produzione. Inoltre deve anche sapere se la lavorazione è stata annullata e se la produzione è stata annullata.



----



AREA FORNITORE - PAGINA LAVORAZIONI

Nella pagina vanno riportate sotto forma tabellare le Lavorazioni a cui il fornitore è stato assegnato.

Il fornitore ha intresse di sapere:
- Tipologia Lavorazione;
- Codice di Lotto;
- Riferimento;
- Stato della lavorazione;
- Possibili Filtri: search generico, riferimento, lotto;
- Qualche timestamp;
- Stato dei Documenti che il forntiore deve caricare;




------



DETTAGLIO LAVORAZIONE

In generale, per una lavorazione il fornitore ha intresse di sapere:
- Tipologia Lavorazione;
- Codice di Lotto;
- Riferimento;
- Stato della lavorazione;
- Documenti relativi al caso, ovvero quelli che il Customer invia a M&H per avviare una richiesta di Lavorazione (Prescrizione e Allegati della prescrizione);
- Documenti aggiuntivi e note inseriti da M&H nella tab "Prescrizione" tramite semplice azione "Aggiungi documento" (funzionalità in sviluppo);
- Documenti che il fornitore ha caricato per renderli visibili a M&H;
- Alert e Banner che lo avvisano:
    - Se e quando M&H ha confermato l'avvio produzione;
    - Se e quando M&H ha annullato l'avvio produzione;
    - Stato dei documenti che deve caricare;
    - Se manca qualcosa;

Il fornitore NON deve assolutamente vedere o sapere:
- Dati del Richiedente;
- Dati della Struttura del richiedente;
- Stato originale della lavorazione (che vede solo l'admin);
- Preventivi;
- Fatture;
- Azioni nell'header;

In generale, quando il fornitore accede al dettaglio di una lavorazione per cui è stato scelto deve poter:
- Comunicare con M&H all'interno della singola lavorazione; Deve quindi avere una chat con M&H.
- Visualizzare i documenti del caso Prescrizione e Allegati ed eventuali documenti e Note della prescrizione per quella singola lavorazione;
- Caricare dei documenti relativi alla singola lavorazione per renderli visibili a M&H;


ATTENZIONE:
- Il fornitore deve poter visualizzare i documenti caricati e condivisi solo se è stato scelto.
- M&H deve poter cambiare il fornitore scelto senza dover ricaricare i documenti. Il nuovo fornitore vedrà quello che vedeva il primo.


IMPLICAZIONI DELL'AREA FORNITORE SULLA STRUTTURA DETTAGLIO LAVORAZIONE LATO FORNITORE:
- Header;
- Spazio per alert e banner;
- Unica tab organizzata a sezioni: 1) Documenti del caso (prescrizione, note ecc); 2) Documenti da caricare (quelli che il forntiore deve condividere con M&H);
- Chat con M&H (Uguale nel design e nella posizione con la chat che M&H ha con il customer);


IMPLICAZIONI DELL'AREA FORNITORE SULLA STRUTTURA DETTAGLIO LAVORAZIONE LATO M&H:
- Per prima cosa bisogna fare in modo che M&H possa aggiungere e scegliere 1 solo forntitore alla volta. Non può aggiungere N fornitori, ne può aggiungere 1 e lo può scegliere, se vuole cambiarlo deve eliminare quello vecchio e ripetere la procedura.
- Nella Tab Fornitore, ci deve essere una sezione superiore in cui appare la card del forntiore selezionato e una sezione sotto chiamata "Documenti fornitore" in cui appariranno i documenti caricati dal fornitore scelto.
- Chat con il Forintore. (Utilizzare l'attuale chat con il Customer ma renderla una chat a 2 Tab, ovvero una tab=Chat con customer, altra tab = Chat con Fornitore.);




