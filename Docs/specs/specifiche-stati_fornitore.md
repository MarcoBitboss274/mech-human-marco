# Stati e alert lavorazione lato Fornitore — modello disaccoppiato, azione "Produzione completata", banner contestuali

## Contesto

Oggi lo stato visibile al fornitore nel workspace è derivato 1:1 dallo stato dell'`Operation` lato M&H, con sei sfumature (`assigned_waiting_documents`, `documents_sent`, `under_evaluation`, `production_confirmed`, `completed`, `canceled`). Questo modello è troppo legato al flusso M&H: il fornitore vede transizioni che non ha causato e che, dal suo punto di vista, sono solo "rumore" (es. `documents_sent` vs `under_evaluation` non gli aggiunge nulla; `completed` riflette solo quello che decide M&H, mai il fornitore).

Si vuole **disaccoppiare** lo stato lato fornitore dallo stato lato M&H. Il workspace fornitore parla di tre stati base — *Nuovo caso*, *Produzione confermata*, *Completato* — più un badge ortogonale *Annullata* che si affianca allo stato base se M&H annulla la lavorazione. Lo stato *Completato* viene **valorizzato esclusivamente da un'azione del fornitore** (click "Produzione completata"); l'analogo flusso M&H (`Operation.status = COMPLETED`) **non** propaga sul lato fornitore. Lato M&H, il completato del fornitore appare come badge informativo nel tab Produzione, senza impattare lo stato dell'Operation.

In aggiunta al refactor del modello stati, la specifica copre anche un nuovo banner contestuale dedicato per il fornitore: **"Prescrizione in revisione"**. È un alert puramente informativo, neutro, che segnala al fornitore quando M&H ha aperto un ciclo di revisione sulla prescrizione collegata alla lavorazione (vedi `specifiche-revisione_aggiornamento.md`). Non blocca alcuna azione del fornitore, non espone i motivi della revisione, e si affianca agli altri banner. Sono state esplicitamente escluse altre ipotesi (alert "documenti mancanti" dedicato, alert scadenza prescrizione, alert chat non letta, alert reinvio customer, alert documenti M&H aggiuntivi).

Questa specifica è un refactor di quanto definito in `Docs/specs/specifiche-area_fornitore_lavorazioni.md` (sezione "Stato visibile al fornitore"): sostituisce il mapping dei 6 stati derivati con 3 stati + 1 badge, introduce persistenza dell'azione "Produzione completata" sul pivot `operation_supplier`, aggiunge una timeline "Attività" lato fornitore, una notifica M&H, adegua filtri/UI e introduce il banner *Prescrizione in revisione*.

## Flusso utente

### A. Lista lavorazioni fornitore (refactor filtri/badge)

1. L'utente supplier apre `Lavorazioni`.
2. La colonna "Stato" mostra il nuovo badge dello stato base (uno tra: `Nuovo caso`, `Produzione confermata`, `Completato`).
3. Se la lavorazione è annullata (`canceled_at` non null), accanto al badge dello stato base appare un secondo badge `Annullata` (rosso). I due badge convivono.
4. Filtro "Stato" ha solo i 3 valori dello stato base. Filtro separato "Annullate" (booleano: tutte / solo annullate / escludi annullate) — di default le annullate sono incluse.
5. Filtro "Stato Documenti" rimane invariato (info ortogonale: `Caricati` / `Da caricare`).

### B. Dettaglio lavorazione fornitore

1. Header con tipologia, lotto, riferimento, badge dello stato base e (se applicabile) badge `Annullata` affiancato.
2. Bottoni nell'header:
   - **`Produzione completata`** (primario) — visibile solo se stato base = `Produzione confermata`, `supplier_completed_at` su pivot è null, `canceled_at` è null.
   - **`Attività`** — apre lo slideover storico (dettagli in C).
   - **`Chat`** — invariato rispetto allo stato attuale.
   - **`Indietro`** — invariato.
3. Sotto l'header, area alert con eventuali banner contestuali (vedi UX). I banner attivi possono coesistere: banner di stato base (uno solo, in fondo) + alert eccezionali indipendenti (Lavorazione annullata, Produzione annullata, **Prescrizione in revisione**). Il banner *Prescrizione in revisione* compare quando la prescrizione collegata ha una revisione aperta (`prescription.active_revision` non null) e mostra un messaggio neutro: titolo *"Prescrizione in revisione"*, corpo *"M&H sta verificando alcuni dati con il customer."*. È puramente informativo: non blocca alcuna azione, non espone i motivi della revisione (riservati alla conversazione M&H↔Customer).
4. Click su "Produzione completata":
   - Dialog di conferma: titolo "Confermi il completamento?", corpo "Una volta confermato, l'azione è irreversibile."
   - Conferma → POST al backend → `supplier_completed_at = now()` sul pivot del fornitore corrente; viene loggata l'azione; notifica email + bell agli utenti M&H assegnabili (Admin / Superadmin / Agent secondo policy esistenti per le notifiche operative).
   - UI: badge dell'header passa a `Completato`; il bottone "Produzione completata" sparisce; gli upload/delete documenti restano disabilitati come già erano in `Produzione confermata`; banner contestuale aggiornato (vedi UX).
5. Sezione "Documenti del caso", "Documenti aggiuntivi M&H" e "Documenti da caricare" rimangono come da specifica precedente. Cambia solo la regola di editabilità: vedi sezione Regole.

### C. Slideover "Attività" (workspace fornitore)

1. Click su `Attività` nell'header del detail apre uno slideover laterale (stesso identico design e componente dell'`ActivitySlider` già usato in `resources/js/pages/operations/Show.vue` lato M&H — riuso del componente con un nuovo scope `supplier`).
2. La lista è in ordine cronologico inverso (più recente in alto), ogni riga mostra: data/ora, descrizione testuale dell'evento (in italiano), eventuale autore (nome utente per le azioni del team del fornitore).
3. Eventi visibili al fornitore:
   - **Assegnazione**: "M&H ti ha assegnato la lavorazione".
   - **Caricamento documento**: "{Nome Cognome} ha caricato il documento *{file_name}*".
   - **Rimozione documento**: "{Nome Cognome} ha rimosso il documento *{file_name}*". Per rimozioni eseguite lato M&H: "M&H ha rimosso il documento *{file_name}*".
   - **Conferma produzione**: "M&H ha confermato la produzione".
   - **Annullamento produzione**: "M&H ha annullato la produzione".
   - **Annullamento lavorazione**: "M&H ha annullato la lavorazione".
   - **Click fornitore "Produzione completata"**: "{Nome Cognome} ha segnato la produzione come completata".
   - **Reset completato per annullamento produzione**: "Il completato è stato resettato perché M&H ha annullato la produzione".

### D. Lato M&H — tab Produzione: badge "Completata dal fornitore"

1. Nel tab "Produzione" del detail Lavorazione admin M&H, sulla card "Produzione confermata", quando per il fornitore corrente `operation_supplier.supplier_completed_at` non è null, appare un badge `Completata` (verde) accanto/sotto al titolo della card.
2. Il badge è puramente informativo: **non esiste alcuna azione di sblocco**. Una volta che il fornitore ha cliccato "Produzione completata", il dato resta valorizzato sul pivot e viene resettato **solo** come effetto collaterale automatico se M&H annulla la produzione (vedi Regole / Edge case).

## Regole di business

### Modello stati visibili al fornitore (sostituisce il mapping precedente)

Stato base — **derivato in lettura**, mai persistito esplicitamente come colonna:

| Condizione (in ordine di valutazione) | Stato base visibile |
|---|---|
| `Operation.status = DRAFT` | non visibile (la lavorazione non compare in lista) |
| `pivot.supplier_completed_at` non null | `Completato` |
| `Operation.status = PRODUCTION` (e `supplier_completed_at` null) | `Produzione confermata` |
| `Operation.status = COMPLETED` (e `supplier_completed_at` null) | `Produzione confermata` *(M&H ha chiuso lato suo, ma il fornitore non ha confermato il completamento dalla sua parte: i flussi sono indipendenti)* |
| `Operation.status ∈ {REQUESTED, IN_PROGRESS, WAITING_APPROVAL}` | `Nuovo caso` |

Badge ortogonale — **affiancato** allo stato base, non lo sostituisce:

| Condizione | Badge |
|---|---|
| `Operation.canceled_at` non null | `Annullata` |

Esempi di combinazioni: `Nuovo caso` + `Annullata`, `Produzione confermata` + `Annullata`, `Completato` + `Annullata`.

### Azione "Produzione completata" del fornitore

- Pulsante visibile **solo** se: stato base = `Produzione confermata`, `Operation.canceled_at` IS NULL.
- Possono cliccarlo **sia Admin sia Membro** del team del fornitore (gate `supplier.operations.complete`).
- Endpoint POST applica `supplier_completed_at = now()` sul pivot `operation_supplier` per il fornitore corrente (riga selezionata, `selected = true`).
- L'azione è **irreversibile** sia per il fornitore sia per M&H. Non esistono pulsanti "annulla completamento" né lato fornitore né lato admin. Il dato può solo essere resettato come effetto collaterale automatico (vedi sotto).
- Triggera:
  - Activity log: "{utente} ha segnato la produzione come completata" (visibile sia lato fornitore che lato M&H).
  - Notifica email + bell ai destinatari M&H del flusso operativo (stessi destinatari delle notifiche operative esistenti per Operation: Admin/Superadmin/Agent secondo policy in vigore).

### Reset automatico di `supplier_completed_at`

- Se M&H **annulla la produzione** (transizione che porta `Operation.status` fuori da `PRODUCTION` verso uno stato precedente, oppure setta `production_canceled_at`): `supplier_completed_at` viene **resettato a null** sul pivot del fornitore corrente come effetto collaterale, e l'evento è loggato in activity ("Il completato è stato resettato perché M&H ha annullato la produzione"). Lato fornitore lo stato torna a `Nuovo caso`.
- Se M&H **annulla la lavorazione** (`canceled_at` valorizzato): `supplier_completed_at` **NON viene resettato**. Lo stato base resta `Completato` e si affianca il badge `Annullata`.
- Se M&H riconferma la produzione dopo un annullamento, il fornitore deve nuovamente cliccare "Produzione completata" per arrivare a `Completato`.

### Documenti — editabilità

- Caricamento (`upload`) e rimozione (`delete`) di `supplier_documents` da parte del fornitore sono permessi **solo** quando lo stato visibile è `Nuovo caso`.
- Da `Produzione confermata` in poi (incluso `Completato` e qualsiasi combinazione con `Annullata`) la sezione "Documenti da caricare" diventa read-only per il fornitore: bottone disabilitato lato Vue + validazione 403 lato controller.
- Lato M&H resta sempre la possibilità di rimuovere documenti (pattern attuale invariato).

### Lista — filtri

- Filtro "Stato" ha solo 3 valori: `new_case`, `production_confirmed`, `completed`.
- Filtro indipendente "Annullate" con tre valori: `tutte` (default), `solo annullate`, `escludi annullate` — applica condizione su `canceled_at`.
- Filtro "Stato Documenti" invariato (`Caricati` / `Da caricare`).
- Visibilità della lavorazione in lista: invariata rispetto a oggi (Operation con pivot del fornitore corrente, `selected=true`, e `Operation.status ≠ DRAFT`).

### Lato M&H

- Tab Produzione del detail Lavorazione admin: card "Produzione confermata" mostra badge `Completata` accanto/sotto al titolo se `supplier_completed_at` non null per il fornitore corrente.
- Nessuna azione "Sblocca" / "Annulla completamento" lato M&H.
- Lo stato `Operation.status` non viene mai influenzato dal click del fornitore: i due flussi sono indipendenti.

### Banner "Prescrizione in revisione"

- **Trigger di visibilità**: il banner compare se e solo se la prescrizione collegata alla lavorazione (la `latestPrescription` su `Operation`) ha `active_revision` non null. Si scollega automaticamente quando la revisione viene chiusa lato M&H (`active_revision` torna null al refresh successivo della pagina).
- **Indipendenza dallo stato base**: la regola si applica a prescindere dallo stato base visibile. Vale sia in `Nuovo caso`, sia in `Produzione confermata`, sia in `Completato`. Vale anche se la lavorazione è annullata (coesistenza con il banner *Lavorazione annullata*).
- **Nessun effetto operativo**: l'apertura di una revisione **non** modifica i permessi del fornitore. I documenti restano editabili/non-editabili secondo le regole di stato base, il bottone "Produzione completata" resta disponibile/non disponibile secondo le regole di stato base, la chat resta sempre operativa.
- **Nessuna esposizione dei motivi**: i contenuti delle `revision_reasons` non vengono mai renderizzati lato fornitore. Il payload può continuare a comprenderli (perché `PrescriptionDetailsCard` lato fornitore già imposta `:in-revision` sulla base della presenza di `active_revision`), ma il banner mostra solo il messaggio neutro definito.
- **Niente alert "documenti mancanti" dedicato**: l'informazione resta veicolata dal banner di stato `Nuovo caso` esistente ("Carica i documenti necessari…") e dalla sezione documenti vuota. Non c'è un alert aggiuntivo per il caso "il fornitore non ha caricato documenti".
- **Nessun altro alert aggiuntivo** in questa iterazione (scadenza prescrizione, chat non letta, reinvio customer, documenti M&H aggiuntivi).
- **Nessuna nuova notifica al fornitore**: l'apertura/chiusura della revisione non triggera email o bell verso il fornitore. Il fornitore se ne accorge solo entrando nel detail della lavorazione.

## Dati e relazioni

### Esistente — riusato senza modifiche
- `operations` — `status`, `canceled_at`, `production_canceled_at` invariati.
- `operation_supplier` — pivot esistente con `supplier_id`, `operation_id`, `selected`, `selected_at`, `status` (OperationSupplierStatusEnum: `to_contact`/`contacted`).
- `Spatie\Activitylog` — già attivo su `Operation`. Si estende ai nuovi eventi.
- `ActivitySlider.vue` — componente esistente in `resources/js/components/activity/`. Riuso con scope dedicato.
- `Prescription::activeRevision` — relazione `hasOne(Revision)->whereNull('closed_at')` già introdotta in `specifiche-revisione_aggiornamento.md`.
- `WorkspaceSupplierController::operationsShow()` — già esegue eager load di `prescriptions` con `activeRevision.reasons` (vedi `app/Http/Controllers/WorkspaceSupplierController.php:289`). Per il banner "Prescrizione in revisione" è sufficiente il flag `active_revision != null`: nessuna modifica al controller.
- `PrescriptionDetailsCard.vue` — già usa `prescription.active_revision` per il prop `in-revision` del badge.

### Nuovo
- **Colonna `supplier_completed_at`** (`timestamp nullable`) sulla tabella `operation_supplier`.
- **Reset enum `SupplierVisibleStatusEnum`**: si riducono i casi a `NEW_CASE`, `PRODUCTION_CONFIRMED`, `COMPLETED`. Eliminati i casi `ASSIGNED_WAITING_DOCUMENTS`, `DOCUMENTS_SENT`, `UNDER_EVALUATION`, `CANCELED` (l'annullata diventa flag ortogonale, non valore di enum).
- **Notification class** `SupplierProductionCompletedNotification` — multi-channel (`mail`, `database`) destinata agli utenti M&H rilevanti per la lavorazione.
- **Eventi activity log nuovi** (causer = utente che esegue, subject = `Operation`):
  - `supplier_assigned`
  - `supplier_document_uploaded` (custom property: media_id, file_name)
  - `supplier_document_removed` (custom property: media_id, file_name, removed_by_admin: bool)
  - `production_confirmed`
  - `production_canceled`
  - `operation_canceled`
  - `supplier_marked_completed`
  - `supplier_completed_reset_for_production_cancel`
- **Gate** `supplier.operations.complete` — admin e member del team del fornitore corrente possono triggerare l'azione, purché lo stato base sia `Produzione confermata` e la lavorazione non annullata.
- **Endpoint** `POST /workspace/supplier/operations/{operation}/complete` — applica `supplier_completed_at = now()` con check di gate, di stato e di idempotenza (se già non null, 422).
- **Endpoint** GET `/workspace/supplier/operations/{operation}/activities` (oppure riuso del controller esistente `ActivityLogController` con `model_type=operation` e filtro server-side che restituisce solo gli eventi visibili al fornitore) — alimenta lo slideover Attività lato fornitore.

### File coinvolti (alto livello)

Backend:
- Migration `add_supplier_completed_at_to_operation_supplier_table`.
- `app/Enums/SupplierVisibleStatusEnum.php` — semplificare ai 3 casi e relativi label.
- `app/Models/Operation.php` — riscrivere `getSupplierVisibleStatusAttribute()` con la nuova logica; assicurarsi che la query carichi `supplier_completed_at` dal pivot (scope/eager); rimuovere dipendenza da `hasSupplierDocuments()` per il calcolo dello stato base (non serve più).
- `app/Http/Controllers/WorkspaceSupplierController.php`:
  - Rimuovere il vecchio `applySupplierVisibleStatusFilter()` per i casi obsoleti, sostituirlo con la nuova logica a 3 valori + filtro indipendente per `canceled`.
  - Aggiungere action `operationsMarkCompleted(Operation $operation)`.
  - Aggiornare il payload della `operationsShow()` per includere il flag `is_canceled` e per restituire stato base e flag separati.
- `app/Services/OperationService.php`:
  - Nuovo metodo `markSupplierProductionCompleted(Operation $operation, User $user)` che valida stato base, gate e idempotenza, scrive `supplier_completed_at`, logga, dispatcha notifica.
  - Hook nei punti di transizione M&H: dove oggi `Operation.status` esce da `PRODUCTION` o viene settato `production_canceled_at`, aggiungere reset di `supplier_completed_at` sul pivot del fornitore corrente (se valorizzato) + log evento.
  - Hook in `selectSupplier` / `uploadSupplierDocument` / `deleteSupplierDocument` / transizioni a `PRODUCTION` / set di `canceled_at` per emettere i corrispondenti eventi activity log.
- `app/Notifications/SupplierProductionCompletedNotification.php` — nuovo file, mail + database channel.
- `routes/web.php` — aggiungere route `POST /workspace/supplier/operations/{operation}/complete` e (se necessario) la GET attività se non si riusa l'esistente.
- `app/Policies/OperationPolicy.php` o gate workspace — nuovo gate `supplier.operations.complete`.
- Pest test: aggiornare i test che oggi assertano il vecchio mapping a 6 stati; aggiungere test per il nuovo mapping, l'azione complete, i reset automatici, gli edge case.

Frontend:
- `resources/js/pages/workspace/supplier/operations/Index.vue`:
  - Ridurre `supplierStatusOptions` ai 3 valori.
  - Aggiungere filtro "Annullate" come nuovo `BbSelect` con `tutte`/`solo annullate`/`escludi annullate`.
  - Cella `supplier_visible_status` aggiornata per mostrare badge dello stato base + eventuale badge `Annullata` affiancato (helper di rendering condiviso con la pagina Show).
- `resources/js/pages/workspace/supplier/operations/Show.vue`:
  - Aggiungere bottone "Produzione completata" nell'header (visibile in base alle condizioni; dialog di conferma).
  - Aggiungere bottone "Attività" nell'header che apre `ActivitySlider` con scope `supplier`.
  - Aggiornare `statusBadgeVariantClass`/`supplierStatusLabels` ai nuovi valori; rendering del doppio badge se `is_canceled`.
  - Aggiornare `isEditableState` per riflettere "solo `Nuovo caso`".
- `resources/js/pages/workspace/supplier/operations/partials/SupplierOperationAlert.vue`:
  - Riscrivere i banner secondo il nuovo modello: banner di stato base (mutex tra loro: `Nuovo caso` / `Produzione confermata` / `Completato`) + alert eccezionali indipendenti (`Lavorazione annullata`, `Produzione annullata`, **`Prescrizione in revisione`**) che si affiancano. Vedi sezione UX per l'ordine visivo.
  - Estendere i Props per leggere `prescription.active_revision` dal payload (oppure ricevere direttamente `prescription` come prop dedicato) per gestire il banner *Prescrizione in revisione*. Riusare le classi CSS `supplier-alert` + `supplier-alert--info` esistenti.
- `resources/js/pages/operations/partials/ProductionTab.vue` (o equivalente) lato M&H:
  - Sulla card "Produzione confermata", mostrare badge `Completata` quando `supplier_completed_at` non null per il fornitore corrente.
- `resources/js/components/activity/ActivitySlider.vue`:
  - Aggiungere prop/scope `supplier` o riuso con filtro server-side; testare che renda gli eventi nuovi con la formattazione corretta (testi in italiano, autore, "M&H" come label generica per gli eventi sistemici).
- `routes.ts` / Wayfinder (se attivo): ri-generare route tipizzate per `complete` e attività.

## Permessi e ruoli

| Azione | Admin/Superadmin/Agent M&H | Admin supplier | Membro supplier |
|---|---|---|---|
| Vedere stato visibile fornitore (su lista e detail) | n/a | ✓ | ✓ |
| Cliccare "Produzione completata" | ✗ (l'azione è del fornitore) | ✓ | ✓ |
| Caricare/eliminare `supplier_documents` (solo in `Nuovo caso`) | ✓ (sempre, anche oltre) | ✓ | ✓ |
| Vedere slideover "Attività" lato fornitore | n/a | ✓ | ✓ |
| Vedere badge `Completata` su card Produzione (tab Produzione M&H) | ✓ | n/a | n/a |
| Sbloccare il completato del fornitore | n/a (azione non esistente) | n/a | n/a |
| Ricevere notifica email + bell del completato fornitore | ✓ (Admin/Superadmin/Agent secondo policy notifiche operative) | ✗ | ✗ |

L'autorizzazione del gate `supplier.operations.complete` confronta sempre `auth()->user()->suppliers()->first()->id` con il `supplier_id` del pivot `selected=true` dell'Operation target.

## Corner case e gestione errori

- **Fornitore clicca "Produzione completata" ma lo stato base nel frattempo è cambiato (es. M&H ha annullato la produzione)** → 409/422 con messaggio "Lo stato della lavorazione è cambiato. Aggiorna la pagina."
- **Fornitore clicca "Produzione completata" ma `supplier_completed_at` è già valorizzato** → 422 idempotente con messaggio "Lavorazione già completata."
- **Fornitore aveva completato e M&H annulla la produzione** → reset automatico di `supplier_completed_at` a null, log "Il completato è stato resettato perché M&H ha annullato la produzione", stato torna a `Nuovo caso`. Banner warning "M&H ha annullato la produzione" per il refresh successivo del fornitore.
- **Fornitore aveva completato e M&H annulla la lavorazione** → `supplier_completed_at` resta valorizzato. Stato base = `Completato`, badge `Annullata` affiancato nell'header. Nell'area alert coesistono il banner "Lavorazione annullata" (error, in alto) e il banner di stato "Completato" (success-muted, in fondo).
- **Stato base `Produzione confermata`, M&H annulla la lavorazione** → stato base resta `Produzione confermata`, badge `Annullata` affiancato. Bottone "Produzione completata" non più visibile (perché `canceled_at` non è null). Nell'area alert coesistono il banner "Lavorazione annullata" e il banner di stato "Produzione confermata".
- **M&H porta `Operation.status = COMPLETED` ma il fornitore non ha mai cliccato** → fornitore continua a vedere `Produzione confermata`. Bottone "Produzione completata" resta visibile. Sui filtri lista, la lavorazione compare ancora come `Produzione confermata`.
- **Fornitore prova a caricare un documento dopo `Produzione confermata`** → bottone disabilitato lato Vue + 403 lato controller con messaggio "Non è più possibile caricare documenti dopo la conferma produzione."
- **Cambio fornitore lato M&H mentre il vecchio fornitore aveva `supplier_completed_at` valorizzato** → coerente con la regola di `specifiche-area_fornitore_lavorazioni.md`: hard delete del pivot vecchio e creazione del nuovo. Il `supplier_completed_at` del vecchio fornitore va via con il record. Il nuovo fornitore parte da `Nuovo caso` (a meno che lo status M&H sia già `PRODUCTION`, nel qual caso parte da `Produzione confermata`). Nessun reset speciale: la sostituzione del fornitore è una rottura netta.
- **Lavorazione in `DRAFT` con pivot già creato** → non visibile in lista (regola invariata da `specifiche-area_fornitore_lavorazioni.md`).
- **Filtri combinati** (es. stato `Completato` + `Solo annullate`) → query restituisce le lavorazioni con `supplier_completed_at` non null E `canceled_at` non null. Empty state generico se zero risultati.
- **Lavorazione senza prescrizione collegata** (caso teorico, non dovrebbe accadere in produzione) → banner *Prescrizione in revisione* non viene renderizzato (`active_revision` non leggibile), nessun errore.
- **Prescrizione con revisione aperta ma `revision_reasons` vuoto** (bordo: solo se i requisiti di `specifiche-revisione_aggiornamento.md` non venissero rispettati) → banner mostrato comunque, perché si basa solo su `active_revision != null`. Coerente con il fatto che i motivi non vengono comunque esposti al fornitore.
- **Revisione chiusa mentre il fornitore ha la pagina aperta** → al refresh successivo / nuova visita il banner sparisce. Nessun broadcast realtime previsto (out-of-scope).
- **Revisione aperta su lavorazione `Annullata` o `Completata`** → il banner *Prescrizione in revisione* compare comunque, in coesistenza con gli altri banner. Coerente con la regola "indipendenza dallo stato base".
- **Errori di payload / `active_revision` non presente nei props** → il banner non viene renderizzato (failsafe con optional chaining `!!operation.latest_prescription?.active_revision`).

## UX e feedback

### Badge dello stato — colori
- `Nuovo caso` → giallo (riusare la palette già in uso oggi per `assigned_waiting_documents`).
- `Produzione confermata` → viola.
- `Completato` → verde.
- `Annullata` (badge ortogonale affiancato) → rosso.

### Header del detail
- Il bottone "Produzione completata" usa la variante primaria di `BbButton`. Stato disabilitato durante il request inflight con spinner inline.
- Dialog di conferma (`BbModal` o equivalente):
  - Titolo: "Confermi il completamento?"
  - Corpo: "Stai segnando la produzione come completata. **L'azione è irreversibile.**"
  - Pulsanti: `Annulla` / `Conferma`.
- Bottone "Attività" usa l'icona `activity` (già in design system).

### Banner contestuali (`SupplierOperationAlert.vue`)

L'area alert può mostrare **più banner contemporaneamente**: distinguiamo banner di stato base (uno solo alla volta, mutuamente esclusivi) e alert eccezionali (indipendenti, si affiancano al banner di stato e tra loro). Coerente con la composizione descritta in `specifiche-alert_fornitore.md`.

**Banner di stato base** (mutuamente esclusivi tra loro — uno e uno solo, in fondo all'area alert):
- **Nuovo caso** — info: "Carica i documenti necessari a M&H per procedere con il preventivo." Pulsante "Vai a Documenti da caricare".
- **Produzione confermata** — success: "Produzione confermata. Quando hai finito, clicca su 'Produzione completata'."
- **Completato** — success-muted: "Hai segnato la produzione come completata."

**Alert eccezionali** (indipendenti, possono coesistere col banner di stato e tra loro, renderizzati sopra il banner di stato in ordine di urgenza dall'alto):
1. **Lavorazione annullata** (`canceled_at` non null) — error: "Questa lavorazione è stata annullata da M&H."
2. **Produzione annullata** (`production_canceled_at` non null e stato base = `Nuovo caso` per via del reset automatico) — warning: "M&H ha annullato la produzione. Lo stato è tornato a Nuovo caso."
3. **Prescrizione in revisione** (`prescription.active_revision` non null) — info / azzurro: titolo "Prescrizione in revisione", corpo "M&H sta verificando alcuni dati con il customer." Nessun bottone/CTA, nessun gating su azioni. Riusa la classe `supplier-alert--info` esistente.

Esempi di coesistenza:
- Stato base `Completato` + lavorazione annullata → due banner: "Lavorazione annullata" (error, in alto) + "Hai segnato la produzione come completata" (success-muted, in fondo).
- Stato base `Produzione confermata` + prescrizione in revisione → due banner: "Prescrizione in revisione" (info, in alto) + "Produzione confermata" (success, in fondo).
- Stato base `Nuovo caso` + lavorazione annullata + prescrizione in revisione → tre banner.

### Toast
- Click "Produzione completata" → success "Produzione segnata come completata."
- Errore di stato (cambio nel frattempo) → error "Lo stato è cambiato. Aggiorna la pagina."
- Errore upload/delete documento per stato avanzato → error "Non è più possibile modificare i documenti."

### Loading
- Bottone "Produzione completata": spinner + disabled durante il request.
- Slideover Attività: skeleton (riusa quello dell'`ActivitySlider` esistente).

### Lato M&H — tab Produzione
- Card "Produzione confermata": badge `Completata` (verde) accanto al titolo, con tooltip "Il fornitore ha segnato la produzione come completata il {data}".

## Fasi di implementazione

La feature è organica: tocca dato (migration), backend (service, notifica, activity log), frontend fornitore (lista, detail, banner, slideover) e frontend M&H (badge tab Produzione). Tutti i pezzi devono andare insieme per non lasciare il sistema in stato incoerente. **Una sola fase**.

### Fase 1: Refactor stati fornitore + azione "Produzione completata" + activity log + badge M&H + banner "Prescrizione in revisione"

- **Scope**:
  - Migration `add_supplier_completed_at_to_operation_supplier_table`.
  - Aggiornamento `SupplierVisibleStatusEnum` (riduzione a 3 casi).
  - Riscrittura `Operation::getSupplierVisibleStatusAttribute()` con la nuova logica e dipendenza dal pivot.
  - Endpoint `POST /workspace/supplier/operations/{operation}/complete` con gate, validazione di stato, idempotenza, log e notifica.
  - `OperationService::markSupplierProductionCompleted()` + reset automatico nelle transizioni di annullamento produzione.
  - `SupplierProductionCompletedNotification` (mail + database).
  - Eventi activity log nuovi (assegnazione, documenti, transizioni produzione, annullamenti, completato fornitore, reset per annullamento produzione).
  - Frontend fornitore: aggiornamento `Index.vue` (filtri stato + filtro annullate), `Show.vue` (header con bottoni, doppio badge, dialog), `SupplierOperationAlert.vue` (banner di stato base mutex + alert eccezionali indipendenti incluso il nuovo **banner *Prescrizione in revisione***), riuso `ActivitySlider` con scope `supplier`.
  - Frontend M&H: badge `Completata` nella card "Produzione confermata" del tab Produzione del detail Lavorazione admin.
  - Verifica/aggiornamento types TypeScript di `Operation`/`Prescription` per includere `active_revision: { id; opened_at; closed_at; ... } | null` (se non già allineati a `specifiche-revisione_aggiornamento.md`).
  - Aggiornamento test Pest che assertano sui vecchi 6 stati e aggiunta dei nuovi: stato base secondo le regole, edge case di reset, irreversibilità lato fornitore, idempotenza endpoint, notifica destinatari, attività log per ogni evento. Test manuale del banner *Prescrizione in revisione* (apertura/chiusura revisione lato admin → comparsa/sparizione del banner nel workspace fornitore).
  - Aggiornamento documentazione UI log se previsto dalle regole di progetto.
- **File coinvolti**: vedi sezione "File coinvolti (alto livello)".
- **Dipende da**:
  - Fase 1 di `specifiche-area_fornitore_lavorazioni.md` (pivot `operation_supplier` con vincolo 1 fornitore, area workspace supplier funzionante, tab Produzione M&H esistente).
  - `specifiche-revisione_aggiornamento.md` (per il modello `Revision` e la relazione `Prescription::activeRevision`, necessari al banner *Prescrizione in revisione*).
