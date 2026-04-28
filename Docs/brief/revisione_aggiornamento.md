Devi correggere e aggiornare la feature di revisione in maniera importante:

Obiettivo: La revisione viaggia su binari paralleli alla prescrizione ma non ne deve modificare lo stato, lato db.

Quindi una prescrizione può avere solo stato Bozza, Inviata, Confermata. Anche quando è in corso una revisione lo stato della lavorazione rimane uno di questi.

L'apertura di una revisione di una prescrizione quindi nonva a incidere sullo stato della prescrizione. 
Immagino la revisione come una entità db con Data apertura, Motivazioni (possono essere N), data invio modifiche, data Chiusura. 

L'apertura di una revisione può avvenire solo per prescrizioni in stato > Bozza.



Flusso in sintesi:
1. M&H apre una revisione;
2. Customer esegue e invia modifiche; 
3. M&H Chiude vede le modifiche;
4. M&H può chiudere revisione o può chiedere altre modifiche (motivazioni);
5. Customer esegue e invia ulteriori modifiche;
5. Se soddisfatto M&H chiude revisione;

Quindi la revisione viene aperta e finche non è chiusa il customer può inviare modifiche; quando viene chiusa si riprende con il flusso normale.

Uno scenario esempio di come mi immagino che deve funzionare:

1. Customer crea prescrizione; -> Stato prescrizione = Bozza;
2. Customer invia prescrizione; -> Stato prescrizione = Inviata;
3. M&H conferma prescrizione; -> Stato prescrizione = Confermata;

4. M&H Mette in revisione la prescrizione; -> Stato prescrizione è sempre Confermata, si apre una revisione legta alla prescrizione (salva data apertura e motivo 1), nel front lo stato della prescrizione diventa "In revisione" ma il vero stato prescrizione rimane quello originale, in questo caso "Confermata";
5. Customer riceve notifica di richiesta revisione con Motivo 1;

6. Customer modifica la prescrizione e invia modifiche; -> Stato prescrizione è sempre Confermata, nel db la revisione avrà data invio modifiche; a frontend la prescrizione avràbadge "nuove modifiche".
7. M&H riceve notifica Modifiche per prescrizione;
8. M&H visualizza prescrizione con modifiche;


9. Admin M&H controlla la prescrizione revisionata, a questo punto può Confermare Revisione o chiedere altre modifiche (Motivo 2);
10. Se chiede nuove modifiche -> Customer riceve notifica "Richieste nuove modifiche (Motivo 2);
11. Customer modifica la prescrizione e invia modifiche; -> Stato prescrizione è sempre Confermata, nel db la revisione avrà data invio modifiche; a frontend la prescrizione avràbadge "nuove modifiche".
12. M&H riceve notifica Modifiche per prescrizione;
13. M&H visualizza prescrizione con modifiche;

14. M&H Conferma revisione -> Revisione viene chiusa, nel db ci sarà timestamp in data chiusura -> a front viene mostrato di nuovo stato vero prescrizione, in questo caso "Confermata";
15. Customer riceve notifica chiusura revisione;

