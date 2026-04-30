# Area Fornitore

## Contesto

Oggi il modello `Supplier` esiste solo come anagrafica gestita lato Admin: non ha un account utente collegato e non ha alcuna area applicativa propria. Quando M&H sceglie un fornitore per una lavorazione, il fornitore non riceve nulla in app: i documenti, le richieste e gli scambi avvengono fuori dal sistema (telefono, email).

Serve trasformare il Fornitore in un attore di prima classe della piattaforma con:
- Una propria area autenticata (login, profilo, lista lavorazioni).
- Visibilità limitata e granulare sulle lavorazioni in cui è stato scelto.
- Un canale di comunicazione strutturato con M&H (chat dedicata + scambio documenti).
- Un meccanismo per cui i documenti caricati dal fornitore siano riutilizzabili da M&H nella compilazione del preventivo verso il Customer.

Il ruolo `SUPPLIER` è già definito in `RoleEnum` ma non è collegato ad alcun flusso. Il modello `Production` con stati `CONFIRMED`/`CANCELED` esiste già: è la fonte autorevole per "avvio produzione confermato/annullato" che il fornitore deve poter consultare.

## Flusso utente

### Onboarding account fornitore

1. Admin M&H, dalla scheda anagrafica `Supplier`, clicca **Invita utente**.
2. Inserisce email, nome, cognome e seleziona ruolo (**Gestore** o **Membro**).
3. Sistema crea uno `User` collegato al `Supplier` con ruolo `SUPPLIER` e ruolo interno scelto. Stato iniziale: `active`, password `null`.
4. Sistema invia email contenente un link firmato/temporaneo (TTL 7 giorni) per impostare la password.
5. L'invitato apre il link, imposta password, viene loggato e atterra su `/supplier/lavorazioni`.
6. Da quel momento può accedere via login standard `/login`.

### Login e routing

1. Fornitore accede da `/login` con email + password.
2. Sistema verifica ruolo: se `supplier`, redirect a `/supplier/lavorazioni`. Layout app dedicato (no sidebar admin, no aree workspace customer).
3. Le rotte fuori dall'area `/supplier/*` sono inaccessibili: middleware `role:supplier` reindirizza a `/supplier/lavorazioni`.

### Profilo

1. Fornitore clicca su menu utente → **Profilo**.
2. Vede tre sezioni:
   - **Anagrafica Fornitore**: nome, P.IVA, mail aziendale, telefono, indirizzo, CAP, città, provincia. Editabile **solo dai Gestori**. I Membri vedono in sola lettura.
   - **Account**: nome, cognome, email login, password (con conferma), ruolo interno (sola lettura). Editabile dall'utente stesso.
   - **Utenti del Fornitore** (visibile **solo ai Gestori**): tabella con tutti gli utenti del Fornitore, ruolo interno (Gestore/Membro), stato (Attivo/Disattivato), ultima azione. Da qui il Gestore può:
     - Invitare un nuovo utente (email + ruolo Gestore/Membro).
     - Promuovere un Membro a Gestore o declassare un Gestore a Membro.
     - Disattivare/riattivare un utente.
     - Rimuovere (soft-delete) un utente.

### Lista Lavorazioni

1. Fornitore atterra su `/supplier/lavorazioni`.
2. Vede tabella delle lavorazioni in cui è **attualmente** selezionato (`operation_supplier.selected = true`), comprese: in corso, completate, con avvio produzione annullato, archiviate.
3. Default: nasconde le **archiviate**; toggle "Mostra archiviate" per includerle.
4. Colonne: Tipologia, Codice di Lotto (`Operation.batch_number`), Riferimento (`Prescription.ref` della prescrizione più recente), Avvio produzione (badge: nessuno/Confermato/Annullato), Data ultimo aggiornamento.
5. Filtri: per tipologia, per stato avvio produzione, ricerca su batch_number e ref.
6. Click su una riga → dettaglio lavorazione.

### Dettaglio Lavorazione

1. Fornitore clicca una lavorazione e vede una pagina semplificata con:
   - **Header**: Tipologia + Codice di Lotto + Riferimento. Nessun pulsante azione (nessuna delle azioni admin/customer).
   - **Banner avvio produzione** (solo se esiste un `Production` per la lavorazione):
     - Verde: "Avvio produzione confermato il [data]" se `Production.status = confirmed`.
     - Rosso: "Avvio produzione annullato il [data]" se `Production.status = canceled`.
   - **Documenti M&H**: lista dei documenti caricati da M&H, raggruppati per tipo (slot tipizzati per la tipologia della lavorazione). Ogni file è scaricabile.
   - **I miei documenti**: lista dei documenti che il fornitore ha caricato per questa lavorazione, con etichetta libera. Pulsante **Carica documento** che apre upload (file + etichetta). Per ogni file caricato dal fornitore: pulsanti **Sostituisci** e **Elimina** (solo se è ancora il fornitore selezionato).
   - **Chat M&H**: canale dedicato M&H ↔ questo Fornitore. Composer in basso, lista messaggi sopra, scroll come la chat operazione esistente.
2. Fornitore può: leggere documenti M&H, scaricare, caricare/modificare/eliminare i propri documenti, scrivere/leggere messaggi chat.

### Lato Admin M&H — gestione documenti tipizzati

1. Admin entra in dettaglio lavorazione → tab esistente (es. "Documenti" da creare, oppure inserito in tab esistente).
2. Vede gli slot tipizzati richiesti dalla tipologia (es. per Lybra Aligner: Prescrizione cliente, Dati allegati cliente, PDF richiesta lavorazione).
3. Per ogni slot può caricare un file. Se ricarica, sostituisce il precedente.
4. Caricando un file, se la lavorazione ha già un fornitore selezionato, il file diventa immediatamente visibile al fornitore (ed è spedita la notifica email).

### Lato Admin M&H — copia documenti del fornitore nel Preventivo

1. Admin compila il preventivo (`Quote`) di una lavorazione.
2. Sezione **Allegati**: pulsante **Carica file** (upload manuale) e pulsante **Allega da fornitore**.
3. **Allega da fornitore** apre un modal con la lista dei documenti caricati dal fornitore selezionato (etichetta + nome file + data upload). Admin seleziona uno o più file e conferma.
4. Sistema **duplica fisicamente** i file selezionati come allegati del Quote: il file originale del fornitore resta intatto; eventuali sue modifiche/cancellazioni successive non toccano la copia nel preventivo.
5. Quando il preventivo viene inviato al Customer e il Customer apre il dettaglio nel suo workspace, vede e può scaricare gli allegati.

### Cambio fornitore selezionato

1. Admin entra in lavorazione → tab Fornitori → seleziona un nuovo fornitore (deselezionando il precedente).
2. Sistema:
   - Imposta `selected = false` per il vecchio fornitore (mantiene lo storico nel pivot).
   - Imposta `selected = true` per il nuovo fornitore con `selected_at = now()`.
   - I documenti M&H restano legati alla lavorazione e diventano immediatamente visibili al nuovo fornitore.
   - I documenti caricati dal vecchio fornitore restano salvati e visibili ad Admin (per storico/preventivi), ma **non** al nuovo fornitore.
   - I messaggi chat con il vecchio fornitore restano salvati e visibili ad Admin; il nuovo fornitore vede una chat pulita (vedi sezione "Dati e relazioni").
   - Il vecchio fornitore perde immediatamente accesso alla lavorazione: alla prossima richiesta non comparirà più nella sua lista.
3. Notifica email al nuovo fornitore (evento "scelto su nuova lavorazione").

## Regole di business

### Account e ruoli interni Fornitore

- Un `User` con ruolo `supplier` appartiene a esattamente un `Supplier` (`User.supplier_id` not null per i supplier user).
- Ogni `User` supplier ha un ruolo interno: **Gestore** o **Membro** (`User.supplier_role`).
- Possono coesistere più Gestori per lo stesso Supplier.
- Un Gestore può: invitare nuovi utenti (Gestore o Membro), promuovere/declassare, disattivare/riattivare, rimuovere (soft-delete) altri utenti del proprio Supplier. **Non può rimuovere o declassare se stesso se rimarrebbe l'unico Gestore attivo.**
- Un Membro non può gestire utenti né modificare l'anagrafica del Supplier.
- L'admin M&H può sempre invitare/revocare/promuovere/declassare/disattivare qualunque utente di qualunque Fornitore (override).
- Un utente disattivato (`disabled_at` not null) non può autenticarsi finché non viene riattivato; resta in DB.
- Un utente soft-deleted (`deleted_at` not null) non compare in lista né può autenticarsi; storico mantenuto.
- L'invito ad un utente nuovo richiede email univoca (sull'intera tabella `users`, già da regola Laravel standard).
- Il link di invito ha TTL 7 giorni; scaduto → l'admin/Gestore può rispedirlo.

### Visibilità lavorazioni e documenti

- Un Fornitore vede una lavorazione se e solo se esiste un record `operation_supplier` con `supplier_id = X` e `selected = true`. Visibilità immediata sull'azione "Scegli".
- Quando un Fornitore viene deselezionato, perde immediatamente accesso al dettaglio e la lavorazione esce dalla sua lista.
- I documenti M&H → Fornitore sono associati alla **lavorazione** (non al fornitore): restano dopo cambio fornitore e diventano visibili al nuovo selezionato.
- I documenti Fornitore → M&H sono associati alla **lavorazione + fornitore caricante**: dopo cambio fornitore restano visibili solo ad Admin, non al nuovo fornitore.
- I messaggi chat sono associati a **lavorazione + fornitore destinatario**: dopo cambio fornitore il nuovo non vede la chat con il vecchio.
- Il Fornitore non può vedere: Richiedente (Building/Customer), Struttura, Stato lavorazione (`Operation.status`), Preventivi, Fatture, qualsiasi azione admin nell'header.

### Tipi documento M&H → Fornitore

- Enum `DocumentTypeEnum` con casi:
  - `PRESCRIPTION_FORM` ("Prescrizione cliente")
  - `CUSTOMER_ATTACHMENTS` ("Dati allegati da cliente")
  - `INTERNAL_REQUEST_PDF` ("PDF richiesta lavorazione")
  - `PURCHASE_ORDER_PDF` ("PDF ODA Ordine di acquisto")
- Mappa tipologia → tipi richiesti:
  - `LYBRA_ALIGNER` → `[PRESCRIPTION_FORM, CUSTOMER_ATTACHMENTS, INTERNAL_REQUEST_PDF]`
  - `GUIDED_SURGERY` → `[PRESCRIPTION_FORM, CUSTOMER_ATTACHMENTS, INTERNAL_REQUEST_PDF]`
  - `THREE_D_MESH` → `[PRESCRIPTION_FORM, CUSTOMER_ATTACHMENTS, PURCHASE_ORDER_PDF]`
  - `PROTRUSOR` → `[PRESCRIPTION_FORM, CUSTOMER_ATTACHMENTS, PURCHASE_ORDER_PDF]`
  - `PROSTHESIS` → `[PRESCRIPTION_FORM, CUSTOMER_ATTACHMENTS, INTERNAL_REQUEST_PDF]`
  - `SEMI_FINISHED_PROSTHESES` → `[]` (nessun tipo: lavorazione senza area documenti M&H)
- Per `SEMI_FINISHED_PROSTHESES` la sezione "Documenti M&H" lato fornitore mostra messaggio "Nessun documento previsto per questa tipologia" e l'area upload lato admin non viene renderizzata.
- Slot multipli per `CUSTOMER_ATTACHMENTS` (più file ammessi). Per gli altri tipi: un solo file per slot (ricarico = sostituzione).

### Documenti Fornitore → M&H

- Upload libero: il Fornitore carica file con etichetta libera (testo) e file. Nessun tipo enumerato.
- Nessun limite hard sul numero di file per lavorazione (limite soft di dimensione singola = quello già configurato per MediaLibrary nel progetto).
- Il Fornitore può sostituire o eliminare solo i propri file, e solo se è ancora il fornitore selezionato.

### Allegati Preventivo (Quote)

- `Quote` ottiene MediaLibrary collection `attachments` (multi-file).
- Allegati possono essere caricati manualmente da Admin (upload diretto) o "duplicati da fornitore" (copia fisica del file dal MediaLibrary del fornitore al MediaLibrary del Quote).
- Ogni allegato del Quote è indipendente: cancellazione/modifica del file originale del fornitore non lo tocca.
- Il Customer, dal proprio workspace, vede e può scaricare gli allegati del Quote nello stato in cui erano al momento dell'invio (gli allegati sono editabili da Admin solo finché Quote è in DRAFT; vedi corner case).

### Avvio produzione

- Lo stato "Avvio produzione confermato/annullato" deriva dal modello `Production` esistente collegato all'`Operation`.
- Il Fornitore vede:
  - Nel dettaglio: banner verde "Avvio produzione confermato il [`Production.confirmed_at`]" se `status = confirmed`; banner rosso "Avvio produzione annullato il [`Production.canceled_at`]" se `status = canceled`. Nessun banner se non esiste un `Production` ancora.
  - Nella lista: badge accanto alla riga della lavorazione con stesso significato.
- Quando lo stato di `Production` cambia da/per `confirmed`/`canceled`, viene spedita email di notifica al Fornitore (vedi UX e feedback).

### Chat M&H ↔ Fornitore

- Canale dedicato per coppia `(operation, supplier)`. Implementato estendendo `OperationChatMessage` con campo `supplier_id` (nullable):
  - `supplier_id = null` → messaggi del canale M&H ↔ Customer (chat esistente, comportamento invariato).
  - `supplier_id = X` → messaggi del canale M&H ↔ Fornitore X.
- Il Fornitore con `Supplier.id = X` vede solo messaggi della lavorazione con `supplier_id = X` (e attualmente selected).
- Admin M&H, lato dettaglio lavorazione, ha un selettore di canale (Customer / Fornitori scelti, anche storici); per ogni canale vede solo i messaggi di quel canale.
- I messaggi storici del canale M&H ↔ vecchioFornitore restano in DB e sono accessibili ad Admin tramite il selettore canale (anche per fornitori non più selected, finché esistono come `operation_supplier` storici).

### Permessi (Spatie)

- Ruolo `supplier` (esistente) riceve nuovi permessi:
  - `supplier-area.access` — accesso all'area `/supplier/*`.
  - `supplier-area.profile.update-own` — modifica del proprio account.
  - `supplier-area.profile.update-supplier` — modifica anagrafica Supplier (assegnato solo a Gestore tramite Gate, non a Membro).
  - `supplier-area.users.manage` — gestione utenti del Supplier (solo Gestore).
  - `supplier-area.operations.view` — vedere lavorazioni dove selected.
  - `supplier-area.operations.documents.upload` — upload propri documenti.
  - `supplier-area.operations.documents.delete-own` — cancellare propri documenti.
  - `supplier-area.operations.chat.read|send` — chat su lavorazioni dove selected.
- I controlli granulari Gestore vs Membro sull'admin pannello del Supplier passano per Policy/Gate (`SupplierUserPolicy`, `SupplierPolicy::updateAnagrafica`).

## Dati e relazioni

### Esistente da non duplicare

- `users` (auth, Spatie roles, MediaLibrary).
- `suppliers` (anagrafica, soft-delete).
- `operations`, `prescriptions`, `productions`, `quotes`, `invoices`, `operation_chat_messages`.
- Pivot `operation_supplier` con `selected`, `selected_at`, `status` (TO_CONTACT/CONTACTED).
- Enum `RoleEnum::SUPPLIER` (già definito).

### Da creare

- **Migrazione `users`** (alter):
  - `supplier_id` (FK nullable a `suppliers.id`, cascade on delete supplier? **No**: `set null` per non perdere lo user accidentalmente. Definire indice).
  - `supplier_role` (string nullable, valori `manager`/`member`).
  - `disabled_at` (timestamp nullable).
- **Modello/Enum `SupplierUserRoleEnum`** con casi `MANAGER` ("Gestore") / `MEMBER` ("Membro") + label.
- **Migrazione `operation_chat_messages`** (alter):
  - `supplier_id` (FK nullable a `suppliers.id`, indice composito `(operation_id, supplier_id)`).
- **Migrazione `quotes`**: nessuna modifica strutturale; aggiungere supporto MediaLibrary nel modello `Quote` (collection `attachments`, multi-file). Eventuali migration solo se servono tabelle di indicizzazione (gestite da Spatie).
- **Enum `DocumentTypeEnum`** con i 4 casi sopra elencati + label e mapping `forTypology(PrescriptionTypologyEnum)`.
- **Modello/Tabella `OperationDocument`** (consigliato per supportare la tipizzazione M&H → Fornitore mantenendo separazione da MediaLibrary già usata per Invoice):
  - `operation_id` (FK), `uploader` (`mech_human` | `supplier`), `supplier_id` (nullable, valorizzato per upload del fornitore), `document_type` (nullable, valorizzato solo per upload M&H tipizzati), `label` (nullable, valorizzato solo per upload fornitore), timestamps, soft-delete.
  - Usa `InteractsWithMedia` con collection `file` (single).
  - Indici: `(operation_id, uploader, document_type)`, `(operation_id, supplier_id, uploader)`.
- **Tabella `supplier_invitations`** (per gestione token invito):
  - `id`, `supplier_id` (FK), `email`, `name`, `surname`, `role` (manager/member), `token` (string, hashed), `expires_at`, `accepted_at` (nullable), `invited_by_user_id` (FK), timestamps.
  - Quando accettato → crea `User` con `supplier_id`, `supplier_role`, ruolo Spatie `supplier`.
- **Notifications**:
  - `SupplierInvitationNotification` (email con link signed/temporaneo).
  - `SupplierAssignedToOperationNotification`.
  - `SupplierOperationDocumentUploadedNotification` (con throttle/digest).
  - `SupplierOperationProductionStatusChangedNotification`.
  - `SupplierOperationChatMessageNotification` (con throttle, vedi sezione UX).

### Permessi (PermissionsUpgrade)

Aggiungere i permessi elencati sopra e assegnarli al ruolo `supplier`. Granularità Gestore/Membro per i permessi di gestione utenti e anagrafica passa via Gate, non via Spatie ruolo separato (per non moltiplicare i ruoli di sistema).

## Permessi e ruoli

- **Admin M&H (`admin`/`superadmin`)**: tutto invariato + override completo su utenti Fornitore (invita, promuove, declassa, disattiva, soft-delete) e accesso lettura totale sulle chat di tutti i canali e sui documenti di tutti i fornitori (storici inclusi).
- **Customer**: nessuna nuova capability. Vede gli allegati del Quote nel suo workspace (nuova).
- **Supplier (Gestore)**: tutti i permessi della propria area + gestione utenti del proprio Supplier + modifica anagrafica del proprio Supplier. Non può vedere altri Supplier né lavorazioni di altri.
- **Supplier (Membro)**: tutti i permessi operativi sulle lavorazioni dove il proprio Supplier è selected (chat read/send, upload/delete documenti propri, lettura documenti M&H, lettura banner avvio produzione). Niente gestione utenti, niente modifica anagrafica.

Visibilità su singola lavorazione: condizione necessaria e sufficiente è `User.supplier_id == operation_supplier.supplier_id` con `selected = true`. Cessa immediatamente se selected diventa false.

## Corner case e gestione errori

- **Email duplicata in invito** → l'admin/Gestore inserisce un'email già usata → mostra errore inline "Esiste già un utente con questa email" e blocca invio.
- **Invito a email già usata da utente di altro Supplier** → stesso errore (un User appartiene a un solo Supplier).
- **Link invito scaduto** → pagina di accettazione mostra "Invito scaduto, richiedi un nuovo invito al tuo Gestore o all'Admin"; admin/Gestore può rispedire generando nuovo token.
- **Gestore tenta di declassare/rimuovere/disattivare se stesso come unico Gestore** → bloccato con messaggio "Devi prima nominare un altro Gestore".
- **Admin elimina un Supplier (anagrafica)** → soft-delete a cascata di tutti gli `User` collegati (impostando `supplier_id = null` non basta: senza Supplier l'utente non ha senso). Decisione: il Supplier non si elimina se ha utenti attivi; l'admin deve prima disattivare/rimuovere gli utenti.
- **Cambio fornitore mentre il vecchio fornitore è loggato** → al prossimo refresh/navigation, la lavorazione esce dalla lista. Se il vecchio fornitore era nel dettaglio della lavorazione, viene reindirizzato a `/supplier/lavorazioni` con flash message "Non sei più il fornitore di questa lavorazione".
- **Fornitore prova ad accedere al dettaglio di una lavorazione dove non è selected** → 403/redirect a lista con messaggio "Lavorazione non più disponibile".
- **Utente Fornitore disattivato durante sessione attiva** → al prossimo request, middleware verifica `disabled_at` → logout forzato + flash "Account disattivato".
- **Documento M&H mancante per slot tipizzato richiesto** → lato fornitore: lo slot mostra "In attesa di caricamento da M&H". Nessun blocco operativo: il fornitore può comunque vedere quelli presenti, caricare propri documenti e chattare.
- **Tipologia `SEMI_FINISHED_PROSTHESES`** → niente sezione documenti M&H lato fornitore; il fornitore vede solo "I miei documenti" e chat. La lavorazione è comunque visibile.
- **Fornitore tenta upload con file > limite Spatie** → errore inline standard MediaLibrary.
- **Fornitore tenta di eliminare un proprio documento già duplicato in un Quote** → l'eliminazione riesce sul documento del fornitore; l'allegato del Quote (copia fisica) resta intatto. Nessun avviso necessario.
- **Quote viene "inviato" al Customer** (status SENT) e successivamente Admin tenta di modificare gli allegati → bloccato: gli allegati del Quote sono editabili solo in stato DRAFT. Per modifiche a Quote SENT: serve creare una nuova versione (logica già presente per Quote? da verificare in fase di implementazione e adeguare).
- **Cambio fornitore quando il preventivo ha già allegati duplicati dal vecchio fornitore** → nessun effetto: gli allegati del Quote sono indipendenti. Admin può, opzionalmente, allegare anche file del nuovo fornitore.
- **Conflitto upload concorrente sullo stesso slot tipizzato (M&H)** → ultimo vince (sostituzione su collection single-file Spatie, comportamento standard). Nessun lock specifico.
- **Fornitore senza utenti attivi** ma selected su lavorazioni → lato admin nessun blocco (la scelta è ammessa anche se non ci sono ancora utenti collegati). I documenti caricati da M&H restano comunque salvati. Quando un utente verrà invitato, vedrà tutto già pronto.
- **Email univocità**: un utente Fornitore non può essere anche Customer/Admin con la stessa email (vincolo già esistente sulla tabella users).
- **Production che oscilla confirmed → canceled → confirmed** → ogni cambio invia email; il banner riflette sempre lo stato corrente.
- **Allegato del Quote senza file fisico originale** (file fornitore eliminato) → lato Customer mostra l'allegato normalmente, file scaricabile dalla copia.

## UX e feedback

### Toast e messaggi

- Invito utente Fornitore: success toast "Invito inviato a [email]".
- Promozione/declassamento: toast "Ruolo aggiornato".
- Disattivazione/riattivazione: toast "Utente disattivato/riattivato".
- Soft-delete utente: toast "Utente rimosso".
- Modifica anagrafica Supplier: toast "Anagrafica aggiornata".
- Modifica account: toast "Profilo aggiornato".
- Upload documento (entrambi i lati): toast "Documento caricato".
- Cancellazione documento: toast "Documento eliminato".
- Errore upload: toast errore con messaggio specifico.

### Banner e badge avvio produzione

- Banner persistente in alto al dettaglio lavorazione (verde/rosso) con icona, testo, data.
- Badge nella riga della lista lavorazioni (icona piccola con tooltip, colorazione coerente).

### Stati di loading

- Upload documento: progress bar standard MediaLibrary; pulsante upload disabilitato durante upload.
- Invio messaggio chat: messaggio mostrato in stato "in invio" finché ack server, poi normale; coerente con la chat esistente.
- Invito utente: bottone "Invita" mostra spinner durante richiesta.

### Redirect

- Dopo accettazione invito: redirect a `/supplier/lavorazioni`.
- Dopo logout: `/login`.
- Dopo deselezione mentre nel dettaglio: redirect a `/supplier/lavorazioni` con flash informativo.

### Email (notifiche)

- **Destinatari**: tutti gli utenti del Fornitore con `disabled_at = null` e `deleted_at = null` (Gestori + Membri). Email centralizzata `Supplier.mail` non usata per notifiche transazionali.
- **Eventi**:
  - **Scelto su nuova lavorazione**: email con link al dettaglio. Subject: "Nuova lavorazione assegnata: [batch_number]".
  - **Nuovo documento M&H caricato**: email per ogni upload tipizzato. Throttle: max 1 email per slot per giorno per ridurre spam (se M&H ricarica più volte, solo la prima entro 24h fa partire la mail).
  - **Avvio produzione confermato/annullato**: email a ogni cambio di stato del Production. Subject differenziato.
  - **Nuovo messaggio chat**: throttle/digest. Una mail per ogni cluster di messaggi entro finestra di 10 minuti per coppia (operation, fornitore). Se il fornitore apre la chat in app entro la finestra, la mail viene soppressa.

### UI Admin (pannello Supplier)

- Nuova tab/section "Utenti" nella scheda Supplier admin: tabella utenti, azione "Invita", "Promuovi/Declassa", "Disattiva/Riattiva", "Rimuovi", filtro stato/ruolo.
- Pulsante "Invita utente" sempre visibile. Form: email, nome, cognome, ruolo interno.

### UI Admin (dettaglio lavorazione)

- Tab esistente Fornitori: invariato per scelta/rimozione/cambio fornitore.
- Nuova sub-section o tab "Documenti M&H" con slot tipizzati per typology.
- Nuova sub-section "Documenti Fornitore" (lettura): elenco file caricati dal fornitore selezionato con possibilità di download (mai cancellazione lato admin).
- Chat: aggiunto selettore canale (Customer / [lista fornitori storici, evidenziato il selected]). Default su Customer per retrocompatibilità.
- Tab Preventivi (Quote editing): aggiunto pulsante "Allega da fornitore" + lista allegati + upload manuale.

### UI Customer (workspace)

- Pagina dettaglio Quote: nuova sezione Allegati con elenco file scaricabili.

## Fasi di implementazione

La feature è ampia ma le aree sono fortemente interconnesse: divido in 5 fasi sequenziali, ognuna autonomamente testabile.

### Fase 1: Foundation account Fornitore

- **Scope**: collegamento User-Supplier, ruoli interni, invito via email, area `/supplier` base con login funzionante e Profilo. Lista lavorazioni renderizzata vuota o con placeholder (logica documenti/chat in fase 3).
- **File coinvolti**:
  - `database/migrations/<ts>_add_supplier_fields_to_users_table.php` (aggiunge `supplier_id`, `supplier_role`, `disabled_at`).
  - `database/migrations/<ts>_create_supplier_invitations_table.php`.
  - `app/Enums/SupplierUserRoleEnum.php` (nuovo).
  - `app/Models/User.php` (relazione `supplier()`, scope, metodi `isSupplierManager`, `isSupplierMember`).
  - `app/Models/Supplier.php` (relazione `users()`, helper `activeUsers`, `managers`).
  - `app/Models/SupplierInvitation.php` (nuovo).
  - `app/Services/SupplierUserService.php` (nuovo): invite, accept, promote, demote, disable, enable, softDelete con guardie su unico Gestore.
  - `app/Notifications/SupplierInvitationNotification.php` (nuovo).
  - `app/Http/Controllers/Supplier/AuthController.php` (nuovo, accettazione invito).
  - `app/Http/Controllers/Supplier/ProfileController.php` (nuovo): proprio account, anagrafica supplier (gate manager), gestione utenti (gate manager).
  - `app/Http/Controllers/Supplier/DashboardController.php` (nuovo, redirect a lavorazioni).
  - `app/Http/Controllers/Supplier/OperationsController.php` (nuovo, lista vuota in fase 1).
  - `app/Http/Requests/Supplier/Profile/*` (FormRequest).
  - `app/Policies/SupplierUserPolicy.php`, `SupplierPolicy@updateAnagrafica`.
  - `app/Http/Middleware/EnsureSupplierUserActive.php` (nuovo).
  - `routes/web.php`: gruppo `/supplier` con middleware `auth`, `role:supplier`, `EnsureSupplierUserActive`.
  - `resources/js/layouts/SupplierLayout.vue` (nuovo): header, menu utente, link Profilo / Lavorazioni / Logout.
  - `resources/js/pages/supplier/auth/AcceptInvitation.vue` (nuovo).
  - `resources/js/pages/supplier/profile/Index.vue` con sezioni Account, Anagrafica, Utenti.
  - `resources/js/pages/supplier/operations/Index.vue` (placeholder).
  - Estensione `app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php` (nuovi permessi).
  - Tab "Utenti" sul pannello Supplier admin: `resources/js/pages/suppliers/Show.vue` o equivalente, con azioni "Invita / Promuovi / Disattiva / Rimuovi".
  - `app/Http/Controllers/SupplierController.php` (estensione metodi per gestione utenti lato admin) o controller dedicato `SupplierUsersController.php`.
- **Dipende da**: niente.

### Fase 2: Lista e dettaglio Lavorazioni Fornitore (sola lettura)

- **Scope**: il fornitore vede la lista delle sue lavorazioni con filtri/badge; entra nel dettaglio e vede header, banner avvio produzione, sezioni "Documenti M&H" e "I miei documenti" e Chat (le ultime due come placeholder, popolate in fase 3 e 4). Visibilità basata su `selected = true`.
- **File coinvolti**:
  - `app/Http/Controllers/Supplier/OperationsController.php` (index reale + show).
  - `app/Services/SupplierOperationService.php` (nuovo): query lavorazioni del supplier con scope `selected`.
  - `app/Policies/OperationPolicy.php` (nuovo metodo `viewAsSupplier`).
  - `resources/js/pages/supplier/operations/Index.vue` (filtri + tabella + badge avvio produzione).
  - `resources/js/pages/supplier/operations/Show.vue` (header, banner, layout sezioni).
  - Componenti `SupplierProductionBanner.vue`, `SupplierProductionBadge.vue`.
- **Dipende da**: Fase 1.

### Fase 3: Documenti tipizzati e upload Fornitore

- **Scope**: enum DocumentTypeEnum, modello/tabella OperationDocument, area upload tipizzata lato Admin (slot per typology), area upload libera lato Fornitore, area lettura/scarico file M&H lato Fornitore, gestione cambio fornitore (visibilità documenti), eliminazioni.
- **File coinvolti**:
  - `database/migrations/<ts>_create_operation_documents_table.php`.
  - `app/Enums/DocumentTypeEnum.php` (con `forTypology()`).
  - `app/Models/OperationDocument.php` (nuovo, `InteractsWithMedia`).
  - `app/Services/OperationDocumentService.php`.
  - `app/Http/Controllers/Supplier/OperationDocumentsController.php`: index/store/update/destroy lato fornitore.
  - `app/Http/Controllers/OperationDocumentsController.php`: index/store/destroy lato admin (tipizzato).
  - `app/Policies/OperationDocumentPolicy.php`.
  - `resources/js/pages/supplier/operations/Show.vue` (popolare sezioni documenti).
  - `resources/js/pages/operations/partials/DocumentsTab.vue` (nuovo) integrato in `Show.vue` admin.
  - `app/Notifications/SupplierOperationDocumentUploadedNotification.php` (con throttle).
- **Dipende da**: Fase 2.

### Fase 4: Chat dedicata M&H ↔ Fornitore

- **Scope**: estensione `OperationChatMessage` con `supplier_id`, separazione canali, selettore canale lato Admin, chat dedicata lato Fornitore, gestione cambio fornitore (storia non visibile al nuovo).
- **File coinvolti**:
  - `database/migrations/<ts>_add_supplier_id_to_operation_chat_messages_table.php`.
  - `app/Models/OperationChatMessage.php` (relazione supplier, scope `forChannel`).
  - `app/Policies/OperationChatPolicy.php` (estensione canali).
  - `app/Http/Controllers/OperationChatController.php` (estensione query/store con channel).
  - `app/Http/Controllers/Supplier/OperationChatController.php` (nuovo).
  - `resources/js/components/ChatSlider.vue` (selettore canale per admin).
  - `resources/js/pages/supplier/operations/Show.vue` (integrazione chat dedicata).
  - `app/Notifications/SupplierOperationChatMessageNotification.php` (con throttle/digest).
- **Dipende da**: Fase 2 (lo show dettaglio è il punto d'integrazione).

### Fase 5: Allegati Preventivo + copia da fornitore

- **Scope**: MediaLibrary collection sul Quote, UI upload manuale, modal "Allega da fornitore" con lista documenti del fornitore selezionato e copia fisica, visibilità allegati nel workspace Customer, blocco editing allegati su Quote SENT.
- **File coinvolti**:
  - `app/Models/Quote.php` (`InteractsWithMedia`, collection `attachments`).
  - `app/Services/QuoteService.php` (metodo `attachFromSupplierDocuments` che duplica i media).
  - `app/Http/Controllers/QuoteAttachmentsController.php` (nuovo) o estensione `QuoteController` con azioni `attachManual`, `attachFromSupplier`, `destroyAttachment`.
  - `resources/js/pages/operations/partials/QuotesTab.vue` (UI allegati: upload + bottone "Allega da fornitore" + modal).
  - `resources/js/components/AttachFromSupplierModal.vue` (nuovo).
  - `resources/js/pages/workspace/quotes/Show.vue` (sezione allegati).
  - `app/Policies/QuotePolicy.php` (gate sull'editing allegati con blocco se Quote SENT/ACCEPTED/REJECTED).
- **Dipende da**: Fase 3 (servono i documenti del fornitore per popolare il modal).

### Notifiche email (trasversale)

Le notifiche specifiche di ogni fase vanno aggiunte e testate nella fase relativa. La notifica generica "scelto su nuova lavorazione" (`SupplierAssignedToOperationNotification`) si aggancia all'azione `selectSupplier` di `OperationService` e va inserita in **Fase 1** (per testarla bastano fornitore + utente attivo, anche senza UI lavorazioni).
