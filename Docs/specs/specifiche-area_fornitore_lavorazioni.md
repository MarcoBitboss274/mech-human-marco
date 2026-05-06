# Area Fornitore — Pagina Lavorazioni e Dettaglio Lavorazione

## Contesto

Questa specifica è la naturale prosecuzione di `Docs/specs/specifiche-area_fornitore.md`, che ha definito utenti supplier, dettaglio fornitore admin, area di lavoro generale e gestione notifiche. Quel documento ha lasciato esplicitamente fuori scope **Pagina Lavorazioni** e **Dettaglio Lavorazione** lato fornitore, che vengono trattate qui.

L'utente fornitore (sia admin sia membro del team) deve poter visualizzare in tabella le sole lavorazioni a cui la sua azienda è stata assegnata e, dal dettaglio di ognuna, poter:
- prendere visione dei documenti del caso (Prescrizione, allegati, eventuali documenti/note aggiuntivi inseriti da M&H);
- caricare i propri documenti (necessari a M&H per fare il preventivo al customer);
- ricevere indicazioni chiare su cosa deve fare e quando (tramite alert/banner) e in particolare sapere quando: gli viene assegnata una lavorazione, deve ancora caricare documenti, M&H ha confermato/annullato la produzione, la lavorazione è stata annullata;
- comunicare con M&H attraverso una chat dedicata distinta dalla chat M&H ↔ Customer.

Il fornitore non deve mai vedere dati del Richiedente, dati della Struttura del richiedente, lo stato originale della lavorazione, preventivi, fatture o azioni di gestione esecutiva (cancellazione/archiviazione/produzione) presenti nell'header lato M&H.

In parallelo, lato M&H il modello di assegnazione fornitore evolve da "N fornitori associabili con uno selezionato" a "1 solo fornitore alla volta", e il dettaglio M&H deve essere aggiornato di conseguenza (tab Fornitore con card singola + sezione documenti fornitore, chat a 2 tab Customer/Fornitore).

## Flusso utente

### A. Lista lavorazioni (utente supplier — area di lavoro)

1. L'utente supplier autenticato e associato a un fornitore apre dal menu workspace la voce "Lavorazioni".
2. Il sistema mostra una tabella delle sole Operation in cui il pivot `operation_supplier` di quell'azienda fornitore esiste ed è attivo (vedi sezione Cambio fornitore per "attivo").
3. Colonne della tabella: Tipologia, Codice di Lotto, Riferimento, Stato (versione fornitore), Stato Documenti, Data di scadenza (`prescription.expire_at` della prescrizione collegata), Data di assegnazione (`selected_at` del pivot).
4. Filtri: search generico, filtro per riferimento, filtro per lotto, filtro per stato lavorazione (versione fornitore), filtro per stato documenti (Caricati / Mancanti).
5. Cliccando su una riga si apre il dettaglio della lavorazione.

### B. Dettaglio lavorazione (utente supplier)

1. L'utente apre il dettaglio di una Lavorazione assegnata.
2. Header: tipologia, codice di lotto, riferimento, badge dello stato fornitore, data di assegnazione. Nessuna azione di gestione (no cancella/archivia/riapri/ecc.).
3. Sotto l'header, area dedicata ad **alert e banner**, che cambia in funzione dello stato e del progresso documenti (vedi sezione UX).
4. **Unica tab** organizzata in sezioni:
   - **Documenti del caso**: documenti caricati dal Customer per la Prescrizione (Prescrizione + Allegati). In sola lettura, scaricabili.
   - **Documenti aggiuntivi e note di M&H**: eventuali documenti caricati da M&H nel tab Prescrizione lato admin tramite l'azione "Aggiungi documento" (funzionalità futura, fuori scope di questa specifica) ed eventuali note testuali. Mostrati in sola lettura. Se la sezione è vuota, è nascosta.
   - **Documenti da caricare** (sezione del fornitore): area di upload multi-file per i documenti che il fornitore deve condividere con M&H. Il fornitore può aggiungere e rimuovere documenti finché lo stato lavorazione non è "Produzione confermata" o successivi (vedi Regole di business). Ogni documento mostra nome, data caricamento, autore (utente del team che lo ha caricato).
5. **Chat con M&H**: pannello laterale o slider, stessa posizione e design della chat M&H ↔ Customer ma alimentata da un thread separato. Tutti gli utenti del team del fornitore vedono e scrivono nella stessa chat di quella lavorazione. Ogni messaggio mostra l'autore.

### C. Tab Fornitore lato M&H (dettaglio Lavorazione admin)

1. Admin/Superadmin/Agent M&H apre il dettaglio Lavorazione e la tab "Fornitore".
2. Stato iniziale (nessun fornitore): UI di selezione che permette di **aggiungere un fornitore**. È disponibile un solo slot. La selezione contestualmente assegna e seleziona il fornitore (assegnazione + selezione in un unico step).
3. Stato "fornitore presente": card del fornitore corrente con dati anagrafici (nome, VAT, mail, phone, indirizzo) e stato di contatto (`OperationSupplierStatusEnum`: `to_contact` / `contacted`). Pulsante "Cambia fornitore" che apre una conferma e, in caso di OK, rimuove il pivot corrente e riapre la UI di selezione.
4. Sotto la card, sezione **"Documenti fornitore"**: elenco dei documenti caricati dal fornitore (collection media `supplier_documents` sull'Operation), in sola lettura, scaricabili.
5. Quando il fornitore viene cambiato (vedi sezione Cambio fornitore), i documenti già presenti restano visibili sull'Operation: il nuovo fornitore li vede ed M&H continua a vederli. Niente "ricarica documenti".

### D. Chat lato M&H — 2 tab Customer/Fornitore

1. Nel dettaglio Lavorazione admin, la chat (oggi unica con il customer) diventa una chat a 2 tab: "Chat Customer" e "Chat Fornitore".
2. La tab "Chat Fornitore" mostra il thread M&H ↔ azienda fornitore corrente per quella Lavorazione. Visibile solo se esiste un fornitore assegnato; in assenza, la tab è disabilitata con tooltip "Nessun fornitore assegnato".
3. Il design (componente, posizione, comportamento di unread/read) replica la chat customer.

## Regole di business

### Stato visibile al fornitore (derivato)
Il fornitore non vede mai lo stato originale dell'Operation. Si applica la seguente mappatura:

| Stato originale Operation | Stato visibile al Fornitore |
|---|---|
| `draft` | non visibile (la Lavorazione non compare nella sua lista finché non gli viene assegnata) |
| `requested` | "Assegnata, in attesa documenti" (se il fornitore non ha ancora caricato nessun documento) |
| `requested` con almeno 1 documento fornitore caricato | "Documenti inviati, in valutazione M&H" |
| `in_progress` | "In valutazione M&H" |
| `waiting_approval` | "In valutazione M&H" |
| `production` | "Produzione confermata" |
| `completed` | "Completata" |
| qualsiasi stato con `canceled_at` non null | "Annullata" (prevale sugli altri) |

> Lo stato visibile è derivato in lettura via accessor/value object e **non** è persistito come colonna separata. Va esposto sia in tabella sia in dettaglio.

### Documenti del fornitore
- Caricamento libero multi-file. Niente categorie predefinite né numero minimo.
- Stato documenti binario: `nessun documento caricato` / `almeno 1 documento caricato`.
- I documenti sono storati come media collection `supplier_documents` **sull'Operation** (non sul pivot, non sul Supplier). Questo è ciò che permette il "cambio fornitore senza ricaricare": cambiando il pivot, i documenti restano legati all'Operation e il nuovo fornitore li vede.
- Ogni media tiene traccia dell'utente che lo ha caricato (custom property `uploaded_by_user_id`).
- Il fornitore può **eliminare** un documento che ha caricato finché lo stato originale dell'Operation è `requested` o precedente. Da `in_progress` in avanti i documenti diventano read-only per il fornitore (non li può più rimuovere). M&H può comunque sempre rimuoverli (per gestire errori segnalati via chat).

### Tracking post-produzione
- Dopo "Produzione confermata", il fornitore comunica via chat (M&H ↔ Fornitore) i passaggi "pezzo pronto" e "pezzo spedito". **Nessun nuovo campo o stato** sul pivot per questi passaggi: il flusso operativo è documentale/conversazionale, lo stato visibile rimane "Produzione confermata" finché M&H non passa l'Operation a `completed`.

### Visibilità documenti del caso
- Il fornitore vede i documenti della Prescrizione (Prescrizione + Allegati) e gli eventuali documenti/note aggiuntivi caricati da M&H nel tab Prescrizione.
- Il fornitore non deve mai vedere: dati del Richiedente, dati della Struttura del richiedente, preventivi, fatture, azioni di gestione lavorazione, stato originale dell'Operation.

### Cambio fornitore lato M&H
- È sempre possibile assegnare al massimo **1 fornitore per Lavorazione** alla volta.
- Per le Operation che oggi hanno più righe in `operation_supplier`, **nessuna migrazione di dati**: la nuova UI mostra solo la riga con `selected=true`, le altre restano in DB ma non vengono esposte.
- Da ora in poi: aggiungere un fornitore = creare il pivot con `selected=true` e `selected_at=now()` in un unico step. Cambiare fornitore = **hard delete** del pivot esistente + creazione del nuovo. Non si tiene storico dei fornitori passati.
- Effetto della rimozione del pivot: il fornitore che non ha più una riga `operation_supplier` per quella Operation perde immediatamente l'accesso al detail e alla riga in lista. I suoi documenti restano nella collection `supplier_documents` dell'Operation e diventano visibili al nuovo fornitore.
- Il documento che M&H aveva ricevuto dal vecchio fornitore può, all'occorrenza, essere rimosso da M&H prima dell'assegnazione del nuovo (azione manuale dal tab Fornitore lato admin). Nessuna pulizia automatica.

### Chat M&H ↔ Fornitore
- Granularità: **per azienda fornitore corrente**. Tutti gli utenti supplier del team del fornitore vedono e scrivono nella stessa chat di quella Lavorazione.
- Ogni messaggio porta l'`user_id` dell'autore. UI mostra nome utente accanto a ogni messaggio.
- La chat è separata dal thread M&H ↔ Customer (modelli/tabelle distinti, vedi sezione Dati).
- Se M&H cambia fornitore, il thread M&H ↔ Fornitore della Lavorazione **resta** (non viene cancellato): il nuovo fornitore vedrà la cronologia precedente. Razionale: lo storico della conversazione è informazione utile alla nuova realtà, esattamente come accade per i documenti. Nota però: i messaggi storici contengono i nomi degli autori del fornitore precedente — la nuova realtà li vedrà come autori "esterni" senza poter risalire al loro account (l'utente in DB esiste sempre, ma non è in relazione con il fornitore attuale).

### Notifiche
- Eventi che generano notifica al fornitore corrente (azienda + utenti `active`):
  1. **Assegnazione del fornitore** (creazione del pivot con `selected=true`).
  2. **Conferma produzione** (transizione di stato Operation a `production`).
  3. **Annullamento lavorazione** (`canceled_at` settato non null).
  4. **Annullamento produzione** (Operation che esce da `production` per tornare a uno stato precedente, oppure `canceled_at` settato mentre era in `production`).
- Canali: **email** (a `Supplier.mail` + utenti supplier `active`) **e** notifica **bell in-app** per gli utenti supplier registrati. Logica destinatari email coerente con quanto definito in `specifiche-area_fornitore.md`.
- L'evento "ho ricevuto un nuovo messaggio in chat dal fornitore/da M&H" sfrutta il sistema di unread esistente (broadcast `OperationChatMessageSent` esteso al canale fornitore), come per la chat customer.

### Permessi/visibilità
- Solo utenti supplier `active` con riga `supplier_user` esistente accedono a `/workspace/supplier/operations` e vedono solo le Operation con pivot `operation_supplier` attivo per il loro fornitore.
- L'accesso al dettaglio di una specifica Operation richiede la riga `operation_supplier` per il fornitore dell'utente; in caso contrario 403 / redirect alla lista con toast "Questa lavorazione non è più assegnata alla tua azienda".
- Lato M&H, la tab Fornitore con card e documenti fornitore è visibile a Admin/Superadmin/Agent secondo i gate già esistenti per la gestione Operation.

## Dati e relazioni

### Esistente — riusato senza modifiche
- `operations` (modello `Operation`) — campi esistenti, enum `OperationStatusEnum`, `canceled_at`, `archived_at`.
- `operation_supplier` — pivot esistente con `status`, `selected`, `selected_at`. Il modello dati supporta già il "fornitore scelto"; cambia la UI lato M&H ma non lo schema.
- `OperationChatMessage` + `OperationChatRead` — modello esistente per la chat M&H ↔ Customer.
- Spatie Media Library — usata per i documenti.

### Nuovo
- **Media collection `supplier_documents`** sul modello `Operation` (registrazione tramite `registerMediaCollections()`). Custom properties per ogni media: `uploaded_by_user_id` (chi ha caricato), `uploaded_at` (timestamp).
- **Tabella `operation_supplier_chat_messages`** — analoga a `operation_chat_messages` ma dedicata al thread M&H ↔ Fornitore:
  - `id`, `operation_id`, `user_id` (autore: può essere utente M&H o utente supplier), `body`, `created_at`, `updated_at`.
- **Tabella `operation_supplier_chat_reads`** — analoga a `operation_chat_reads`, per il tracking unread sul thread fornitore (per utente).
- **Modelli**: `OperationSupplierChatMessage`, `OperationSupplierChatRead`. Relazione `Operation::supplierChatMessages()` e `Operation::supplierChatReads()`.
- **Event broadcast** `OperationSupplierChatMessageSent` e `OperationSupplierChatUnreadUpdated` — analoghi a quelli customer; canali distinti.
- **Notification class** dedicate (Laravel Notification, multi-channel email + database):
  - `SupplierAssignedToOperationNotification`
  - `OperationProductionConfirmedForSupplierNotification`
  - `OperationCanceledForSupplierNotification`
  - `OperationProductionCanceledForSupplierNotification`
- **Accessor/value object** per lo "Stato visibile al Fornitore": derivato in lettura, non persistito. Implementato come metodo su `Operation` (es. `getSupplierVisibleStatus()`) o come piccolo service `SupplierVisibleStatusResolver`.

### File coinvolti (alto livello)

Backend:
- `app/Models/Operation.php` — registra collection `supplier_documents`, aggiunge relazioni `supplierChatMessages()` e `supplierChatReads()`, aggiunge accessor `supplier_visible_status`.
- `app/Enums/SupplierVisibleStatusEnum.php` — nuovo, valori: `assigned_waiting_documents`, `documents_sent`, `under_evaluation`, `production_confirmed`, `completed`, `canceled`.
- `app/Services/OperationService.php` — aggiornare `selectSupplier()` per imporre vincolo "1 solo fornitore alla volta" (cambio = delete + create); esporre helper per la lista lavorazioni del fornitore.
- Nuovo `app/Services/SupplierWorkspaceService.php` (o estensione del service esistente) per: lista Operation del fornitore con stati derivati; gestione upload/delete documenti `supplier_documents`.
- Nuovo `app/Http/Controllers/Workspace/SupplierOperationController.php` con `index()`, `show()`, `uploadDocument()`, `deleteDocument()`.
- Nuovo `app/Http/Controllers/SupplierChatController.php` con `messages()`, `store()`, `markAsRead()` analogo a `ChatController` esistente.
- Nuove `Notifications/...` come elencate sopra; integrazione nei punti del ciclo Operation in cui oggi avvengono le transizioni di stato e l'annullamento (cercare `selectSupplier`, transizioni a `production`, set di `canceled_at`).
- Migration: `create_operation_supplier_chat_messages_table`, `create_operation_supplier_chat_reads_table`.
- `app/Policies/OperationPolicy.php` — aggiungere gate per accesso supplier (riga pivot esistente).

Frontend:
- `resources/js/pages/workspace/supplier/operations/Index.vue` — tabella lavorazioni con filtri (search generico, riferimento, lotto, stato, stato documenti).
- `resources/js/pages/workspace/supplier/operations/Show.vue` — dettaglio: header, area alert/banner, tab unica con sezioni Documenti del caso / Documenti M&H / Documenti da caricare, chat slider M&H.
- Componente nuovo o esteso `SupplierChatSlider.vue` (riusa al massimo `ChatSlider.vue` esistente).
- `resources/js/pages/operations/partials/SuppliersTab.vue` (lato admin M&H) — refactor: 1 solo slot, card fornitore corrente, pulsante "Cambia fornitore", sezione "Documenti fornitore".
- `resources/js/pages/operations/Show.vue` (lato admin M&H) — refactor della chat: dal componente singolo a un componente con 2 tab (Customer / Fornitore). La tab Fornitore è disabilitata se nessun fornitore è assegnato.
- Componente nuovo `OperationSupplierAlert.vue` (o riuso di `OperationNotice` con un nuovo `scope="supplier"`) per i banner contestuali al fornitore.

## Permessi e ruoli

| Azione | Admin/Superadmin/Agent M&H | Admin supplier | Membro supplier |
|---|---|---|---|
| Vedere lista Lavorazioni del fornitore | n/a (vede tutto da admin) | ✓ (solo le proprie) | ✓ (solo le proprie) |
| Aprire il dettaglio Lavorazione lato fornitore | n/a | ✓ | ✓ |
| Caricare documenti `supplier_documents` (se stato originale ≤ `requested`) | ✓ (sempre) | ✓ | ✓ |
| Eliminare un documento `supplier_documents` proprio o del team (se stato originale ≤ `requested`) | ✓ (sempre, anche dopo) | ✓ | ✓ |
| Scrivere/leggere chat M&H ↔ Fornitore | ✓ (lato M&H) | ✓ | ✓ |
| Tab Fornitore lato M&H (assegna, cambia, vede card + doc) | ✓ | ✗ | ✗ |
| Tab Chat 2-tab lato M&H | ✓ | ✗ | ✗ |

L'autorizzazione confronta sempre `Operation.suppliers()->wherePivot('selected', true)->id` con `auth()->user()->supplier->id` per le operazioni dell'utente supplier.

## Corner case e gestione errori

- **Utente supplier prova ad aprire il dettaglio di una Lavorazione che non è (più) assegnata al suo fornitore** → 403 / redirect alla lista con toast "Questa lavorazione non è più assegnata alla tua azienda."
- **Lavorazione annullata mentre il fornitore è nel detail** → al refresh il banner principale diventa "Lavorazione annullata" con stile error; le azioni di upload/delete documenti e chat sono disabilitate con tooltip "Lavorazione annullata".
- **Produzione annullata (Operation esce da `production`)** → banner "Produzione annullata da M&H", lo stato visibile torna a "In valutazione M&H" (in coerenza con la mappatura), la chat resta attiva.
- **Fornitore prova a eliminare un proprio documento dopo che lo stato è ≥ `in_progress`** → bottone "Elimina" disabilitato lato UI, e validazione lato backend che restituisce 403 con messaggio "Non è più possibile rimuovere questo documento. Contatta M&H via chat."
- **M&H cambia fornitore mentre l'utente supplier del vecchio fornitore sta visualizzando il dettaglio o sta scrivendo in chat** → al successivo request HTTP / al successivo ciclo del broadcast, l'utente viene rimbalzato con toast "Questa lavorazione non è più assegnata alla tua azienda."
- **Utente supplier `pending` (non ha completato setup password)** → non riceve email né bell in-app sui 4 eventi (regola coerente con `specifiche-area_fornitore.md`).
- **Fornitore senza utenti registrati** → riceve solo email a `Supplier.mail`. Bell in-app non si applica (non c'è nessun utente loggabile).
- **Operation in `draft`** → non compare in lista al fornitore anche se è già stato creato un pivot `operation_supplier` con `selected=true` (caso possibile se M&H prepara l'assegnazione in anticipo). Si aggancia all'assegnazione effettiva = transizione a `requested` (o, se M&H sceglie di assegnare già in `draft`, semplicemente non compare al fornitore finché il record passa a `requested`).
- **Tentativo M&H di aggiungere un secondo fornitore quando ce n'è già uno** → bottone "Aggiungi fornitore" non visibile/disabilitato. UI mostra solo "Cambia fornitore". Backend valida.
- **Cambio fornitore con documenti del precedente già caricati** → dialog di conferma: "Cambiando fornitore, i documenti caricati dal precedente resteranno visibili al nuovo fornitore. Procedere?" Se M&H vuole rimuoverli, deve farlo manualmente prima del cambio dalla sezione "Documenti fornitore".
- **Chat M&H ↔ Fornitore con thread già esistente quando il fornitore cambia** → la cronologia rimane. UI mostra autori storici col loro nome; nuova realtà non può cliccarci sopra per profili interni (deducibile dal contesto).
- **Upload di file troppo grande / formato non consentito** → errore inline standard (riusa policy/validazioni del media library già in uso nel resto del progetto).

## UX e feedback

### Pagina Lavorazioni (lista)
- Tabella con colonne: Tipologia, Codice di Lotto, Riferimento, **Stato (versione fornitore)**, **Stato Documenti** (badge: `Da caricare` se nessun documento, `Caricati` se ≥1), **Data di scadenza** (`prescription.expire_at` della prescrizione collegata; mostra "—" se la prescrizione non ha scadenza valorizzata), **Data di assegnazione** (`selected_at` del pivot).
- Filtri attivi sopra la tabella: search generico, riferimento, lotto, stato lavorazione, stato documenti.
- Ordinamento default: per data di scadenza ascendente (le più imminenti in alto), con le righe senza `expire_at` valorizzato in fondo. Cliccando le intestazioni si ordina per le altre colonne (riusare i pattern già adottati nel resto della app).
- Riga cliccabile su tutta la lunghezza per andare al detail.
- Empty state: "Non ti è ancora stata assegnata nessuna lavorazione."

### Dettaglio Lavorazione (utente supplier) — banner contestuali
Banner mostrati nella stessa posizione di `OperationNotice` ma con `scope="supplier"`. Mutuamente esclusivi con priorità in ordine elencato:
1. **Lavorazione annullata** (se `canceled_at != null`) — stile error, testo "Questa lavorazione è stata annullata da M&H".
2. **Produzione annullata** (se l'Operation è uscita da `production` senza `canceled_at`) — stile warning, "M&H ha annullato l'avvio produzione."
3. **Documenti mancanti** (se stato è `requested` e `supplier_documents` è vuoto) — stile info/attention, "Carica i documenti necessari a M&H per procedere con il preventivo." Pulsante "Vai a Documenti da caricare" (scroll alla sezione).
4. **Documenti inviati, in valutazione** (se stato è `in_progress` o `waiting_approval`) — stile info, "Documenti inviati. M&H sta elaborando il preventivo. Riceverai un avviso quando la produzione sarà confermata."
5. **Produzione confermata** (se stato è `production`) — stile success, "Produzione confermata. Puoi procedere con la lavorazione." Mantenuta visibile fintanto che lo stato resta `production`.
6. **Completata** (se stato è `completed`) — stile success-muted, "Lavorazione completata."

### Toast
- Upload documento OK → "Documento caricato."
- Delete documento OK → "Documento rimosso."
- Errore upload → "Caricamento fallito. Riprova."
- Errore delete (perché stato avanzato) → "Non è più possibile rimuovere questo documento."
- Cambio fornitore lato M&H → "Fornitore aggiornato."

### Loading
- Tabella con skeleton al primo fetch.
- Upload con barra di progresso inline per file e disabilitazione del pulsante durante l'invio.
- Chat con skeleton dei messaggi al primo open dello slider.

### Lato M&H — tab Fornitore
- Card del fornitore corrente con badge stato (`to_contact` / `contacted`) cliccabile per aggiornare lo stato (riusa pattern esistente).
- Pulsante "Cambia fornitore" con icona di sostituzione; dialog di conferma testuale.
- Sezione "Documenti fornitore": elenco con nome file, autore (utente che ha caricato), data, azione "Scarica" + (per M&H) azione "Elimina".

### Lato M&H — chat 2 tab
- Chat slider con tab Customer / Fornitore. Badge unread per ogni tab. Switch fra tab istantaneo. Tab Fornitore disabilitata + tooltip se nessun fornitore assegnato.

## Fasi di implementazione

La feature è ampia e si compone di blocchi logicamente separabili e testabili indipendentemente. Si propongono **due fasi**, in cui la prima abilita il flusso operativo end-to-end e la seconda completa l'esperienza con notifiche e refactor della chat M&H.

### Fase 1: Lista, dettaglio, documenti supplier, refactor tab Fornitore lato M&H, vincolo 1 fornitore

- **Scope**:
  - Enum `SupplierVisibleStatusEnum` e accessor `supplier_visible_status` su `Operation`.
  - Media collection `supplier_documents` su `Operation` con custom properties `uploaded_by_user_id`/`uploaded_at`.
  - Backend: `SupplierWorkspaceService` + `SupplierOperationController` (index, show, uploadDocument, deleteDocument). Policy/Gate.
  - Refactor `OperationService::selectSupplier()` con vincolo "1 solo fornitore alla volta" (cambio = delete + create del pivot).
  - Refactor `SuppliersTab.vue` lato admin M&H: card singola, "Cambia fornitore", sezione "Documenti fornitore" con elenco e azioni (download/delete).
  - Pagina Vue lato supplier: `Index.vue` (tabella + filtri) e `Show.vue` (header + banner + sezioni documenti). **Senza chat M&H ↔ Fornitore in questa fase** (la chat è in Fase 2).
  - Banner/alert contestuali al fornitore (componente `OperationSupplierAlert.vue` o `OperationNotice` con `scope="supplier"`).
  - Hard-rule UX: la regola di "documenti rimovibili solo se stato ≤ requested" è validata sia lato Vue sia lato controller.
- **File coinvolti**: vedi sezione "File coinvolti (alto livello)" — tutti tranne i file della chat fornitore e delle Notification class.
- **Dipende da**: Fase 1 di `specifiche-area_fornitore.md` (pivot `supplier_user`, ruoli e accessi area workspace supplier).

### Fase 2: Chat M&H ↔ Fornitore (con refactor chat lato M&H a 2 tab) e Notifiche

- **Scope**:
  - Migration `operation_supplier_chat_messages` e `operation_supplier_chat_reads`.
  - Modelli `OperationSupplierChatMessage`, `OperationSupplierChatRead`.
  - Controller `SupplierChatController` (messages/store/markAsRead) speculare a `ChatController`.
  - Event broadcast `OperationSupplierChatMessageSent`, `OperationSupplierChatUnreadUpdated`.
  - Refactor della chat lato M&H da componente singolo a tab Customer/Fornitore con badge unread per tab; tab Fornitore disabilitata se nessun fornitore.
  - Slider chat lato workspace supplier (riuso del `ChatSlider` esistente con prop di scope/endpoint).
  - Notification class per i 4 eventi (Assegnazione, Produzione confermata, Annullamento lavorazione, Annullamento produzione) con email + database channel.
  - Integrazione delle notifiche nei punti dove avvengono oggi le transizioni di stato Operation e l'annullamento.
  - Filtro destinatari: `Supplier.mail` + utenti supplier `active` del fornitore corrente; bell solo agli utenti registrati `active`.
- **File coinvolti**: tabelle/modelli chat fornitore, controller chat, refactor chat lato M&H, Notification class, integrazioni nei service di transizione stato Operation.
- **Dipende da**: Fase 1 di questa specifica (pivot vincolato a 1 fornitore, area workspace supplier funzionante) + Fase 1 di `specifiche-area_fornitore.md` (utenti `active`/`pending`, anagrafica destinatari).
