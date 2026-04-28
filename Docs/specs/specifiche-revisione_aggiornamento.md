# Aggiornamento Feature Revisione Prescrizione

## Contesto

La feature Revisione esiste già in produzione ([specifiche-feature_revisione_prescrizione.md](../specs/specifiche-feature_revisione_prescrizione.md)) ma ha un difetto di design strutturale: la richiesta di revisione modifica lo **stato della prescrizione** nel DB, aggiungendo gli stati `IN_REVIEW` e `REVISED` all'enum. Questo confonde due concetti distinti: lo **stato di lavorazione** della prescrizione (Bozza → Inviata → Confermata) e l'**esistenza di una revisione in corso** sopra di essa.

L'aggiornamento chiesto nel brief ([revisione_aggiornamento.md](../brief/revisione_aggiornamento.md)) separa i due binari:

- La **prescrizione** ha solo 3 stati DB: `DRAFT`, `SENT`, `CONFIRMED`. Una revisione aperta **non** tocca questo stato.
- La **revisione** diventa un'entità DB autonoma con propri timestamp e proprie motivazioni, collegata 1:N alla prescrizione.
- Lo stato "In revisione" diventa una **rappresentazione frontend**: se esiste una revisione aperta per la prescrizione, la UI mostra il badge "In revisione" al posto dello stato reale; alla chiusura, la UI torna a mostrare lo stato reale.

Il refactor comporta: rimozione di 2 stati dall'enum, nuova tabella `revisions`, nuova tabella `revision_reasons`, riscrittura di `PrescriptionService::requestRevision()` e `resubmitRevision()`, adattamento dei badge frontend, nuova notifica di chiusura.

## Flusso utente

### Flusso principale (happy path)

1. **Customer** crea prescrizione → stato `DRAFT`.
2. **Customer** invia prescrizione → stato `SENT`, `send_at` valorizzato, `expire_at` = `send_at + 6 mesi`.
3. **M&H** conferma prescrizione → stato `CONFIRMED`, `confirmed_at` valorizzato.
4. **M&H** apre revisione con motivo iniziale (Motivo 1):
   - Lo stato DB della prescrizione resta `CONFIRMED`.
   - Viene creata una riga in `revisions` con `opened_at = now()`, `opened_by = user M&H`, `prescription_id`.
   - Viene creata una riga in `revision_reasons` con `content = "Motivo 1"`, `revision_id`, `created_by = user M&H`, `created_at = now()`.
   - Frontend: la prescrizione mostra badge "In revisione" (override visivo).
   - Notifica: `RequestPrescriptionRevisionForUser` al customer (template esistente, adattato).
5. **Customer** riceve notifica, apre la prescrizione e vede:
   - Banner "In revisione".
   - Storico motivi con Motivo 1 (testo + data richiesta).
   - Bottone "Invia modifiche" abilitato.
6. **Customer** modifica la prescrizione e invia modifiche:
   - Lo stato DB resta `CONFIRMED`.
   - `revisions.last_submitted_at = now()` (si sovrascrive ogni volta).
   - Frontend per M&H: badge "nuove modifiche" sulla card prescrizione.
   - Notifica: `ResubmittedPrescriptionForAdmin` agli admin (template esistente).
7. **M&H** apre la prescrizione e la visualizza (badge "nuove modifiche" visibile).
8. **M&H** decide:
   - **Opzione A — Chiude revisione**: passa a step 11.
   - **Opzione B — Chiede nuove modifiche (Motivo 2)**: aggiunge una nuova riga a `revision_reasons` per la revisione aperta. Il badge "nuove modifiche" sparisce (risposta = azione). La revisione resta aperta. Notifica `RequestPrescriptionRevisionForUser` al customer.
9. **Customer** riceve notifica Motivo 2, vede lo storico aggiornato (Motivo 1 + Motivo 2) e rinvia modifiche → step 6.
10. Il ciclo può ripetersi N volte (N motivi + N rinvii).
11. **M&H** chiude revisione:
    - `revisions.closed_at = now()`, `closed_by = user M&H`.
    - Frontend: sparisce badge "In revisione", torna a mostrare lo stato reale (nel caso dell'esempio: `CONFIRMED`).
    - Il badge "nuove modifiche" (se presente) sparisce.
    - Notifica: **nuova** `ClosedPrescriptionRevisionForUser` al customer.

### Varianti / path alternativi

- **Apertura revisione su prescrizione `SENT`**: identica a step 4, ma alla chiusura la prescrizione torna visivamente a `SENT`, non `CONFIRMED`. M&H potrà poi confermarla con l'azione "Conferma" standard (azione separata, step dedicato).
- **Chiusura senza alcun invio del customer**: M&H può chiudere la revisione anche se `last_submitted_at` è `NULL` (aperta per errore, o customer non risponde). Flusso identico a step 11.

## Regole di business

- Una prescrizione può avere **N revisioni nel tempo**, ma **al massimo una aperta** alla volta (`closed_at IS NULL` per massimo una riga per `prescription_id`).
- L'apertura di una revisione è consentita **solo se la prescrizione non è in `DRAFT`** (quindi `SENT` o `CONFIRMED`).
- L'apertura di una revisione è consentita **solo se non esiste già una revisione aperta** per la stessa prescrizione.
- Alla creazione della revisione è **obbligatorio** inserire almeno un motivo (`revision_reasons`). Validazione testo: `min:5`, `max:2000` (stesso pattern già usato in `RequestRevisionRequest`).
- Ogni richiesta di "nuove modifiche" da parte di M&H aggiunge una riga a `revision_reasons` per la revisione attualmente aperta. Il testo del nuovo motivo è obbligatorio con stesse regole di validazione.
- L'invio modifiche da parte del customer **sovrascrive** `revisions.last_submitted_at` (non crea storico dei rinvii).
- L'invio modifiche da parte del customer è consentito **solo se**:
  - la prescrizione è in `DRAFT` (primo invio, come oggi), oppure
  - esiste una revisione aperta (`closed_at IS NULL`) per la prescrizione.
- La chiusura della revisione valorizza `closed_at` e `closed_by`. Non distingue tra "accettata" e "annullata": è un'unica azione.
- La chiusura della revisione **non** modifica lo stato della prescrizione. Se la prescrizione era `SENT`, resta `SENT`; se era `CONFIRMED`, resta `CONFIRMED`. La conferma è un'azione separata.
- `expire_at` della prescrizione **decorre normalmente** durante una revisione aperta. Non viene congelato né resettato.
- Lo stato visivo "In revisione" a frontend dipende solo dalla presenza di una revisione aperta (`prescription.active_revision` presente e con `closed_at IS NULL`).

## Dati e relazioni

### Modifiche all'esistente

- **Enum `PrescriptionStatusEnum`** ([app/Enums/PrescriptionStatusEnum.php](../../app/Enums/PrescriptionStatusEnum.php)):
  - **Rimuovere**: `IN_REVIEW`, `REVISED`.
  - Valori finali: `DRAFT`, `SENT`, `CONFIRMED`.
- **Tabella `prescriptions`**: nessun cambio schema. Manteniamo `status`, `send_at`, `confirmed_at`, `expire_at`.

### Nuove tabelle

- **`revisions`**
  - `id` — PK
  - `prescription_id` — FK `prescriptions.id`, indexed, onDelete cascade
  - `opened_at` — timestamp, NOT NULL
  - `opened_by` — FK `users.id`, NOT NULL (admin M&H che apre)
  - `last_submitted_at` — timestamp, NULLABLE (sovrascritto ad ogni rinvio customer)
  - `closed_at` — timestamp, NULLABLE
  - `closed_by` — FK `users.id`, NULLABLE (admin M&H che chiude)
  - `created_at`, `updated_at`
  - Indice parziale / unique su `(prescription_id)` dove `closed_at IS NULL` per garantire max una revisione aperta per prescrizione (o vincolo applicativo se l'indice parziale non è fattibile in MySQL — da decidere in fase di migration).

- **`revision_reasons`**
  - `id` — PK
  - `revision_id` — FK `revisions.id`, indexed, onDelete cascade
  - `content` — text, NOT NULL
  - `created_by` — FK `users.id`, NOT NULL (admin M&H che ha inserito il motivo)
  - `created_at`, `updated_at`
  - Ordinamento: per `created_at ASC` per mostrare lo storico cronologico al customer.

### Relazioni Eloquent

- `Prescription` → `hasMany(Revision::class)` (ordinamento default per `opened_at desc`)
- `Prescription` → `hasOne(Revision::class)->whereNull('closed_at')` accessor `activeRevision`
- `Revision` → `belongsTo(Prescription::class)`
- `Revision` → `hasMany(RevisionReason::class)` (default order `created_at asc`)
- `Revision` → `belongsTo(User::class, 'opened_by')` e `belongsTo(User::class, 'closed_by')`
- `RevisionReason` → `belongsTo(Revision::class)`
- `RevisionReason` → `belongsTo(User::class, 'created_by')`

### Migrazione dati esistenti

- Migration che imposta:
  - `UPDATE prescriptions SET status = 'SENT' WHERE status = 'IN_REVIEW'`
  - `UPDATE prescriptions SET status = 'CONFIRMED' WHERE status = 'REVISED'` (assumendo che REVISED arrivava sempre da un flusso con prescrizione già confermata — in caso contrario si può mappare su `SENT` e verificare con un check del log).
- Nessun record viene creato retroattivamente in `revisions`: lo storico revisioni parte da zero dopo il rilascio.
- Dopo l'update, rimuovere i valori `IN_REVIEW` e `REVISED` dall'enum DB (se il campo è ENUM MySQL) e dal PHP enum.

## Permessi e ruoli

- **Admin M&H** (gate `update` in [PrescriptionPolicy](../../app/Policies/PrescriptionPolicy.php)):
  - Aprire revisione su prescrizione non in `DRAFT` e senza revisione aperta.
  - Aggiungere motivo a revisione aperta.
  - Chiudere revisione aperta.
  - Vedere tutte le revisioni (aperte e storiche) e i relativi motivi.
- **Customer** (gate `workspaceAbility` con `WorkspaceAbilityEnum::PRESCRIPTIONS_SEND`, già esistente):
  - Inviare/rinviare modifiche se prescrizione in `DRAFT` o con revisione aperta.
  - Vedere lo storico motivi della revisione in corso sulla propria prescrizione.
  - Vedere lo stato visivo "In revisione" ma non aprire/chiudere revisioni.
- Nessun utente può **eliminare** revisioni o motivi (coerente con regola globale: niente azioni distruttive). Per "annullare per errore" si usa la chiusura normale.

## Corner case e gestione errori

- **M&H prova ad aprire revisione su prescrizione in `DRAFT`** → errore 403 / redirect con toast "Non è possibile aprire una revisione su una prescrizione in bozza."
- **M&H prova ad aprire una seconda revisione mentre ce n'è già una aperta** → errore bloccante a livello di form request, toast "Esiste già una revisione aperta per questa prescrizione."
- **M&H prova ad aggiungere un motivo a una revisione già chiusa** → errore 403 / toast "La revisione è già stata chiusa."
- **Customer prova a inviare modifiche con prescrizione in `SENT`/`CONFIRMED` senza revisione aperta** → bottone "Invia modifiche" non visibile; se chiamata diretta, errore 403.
- **Customer invia modifiche ma la revisione è stata chiusa nel frattempo (race condition)** → errore 422 con messaggio "La revisione è stata chiusa, non è più possibile inviare modifiche."
- **Prescrizione scade (`expire_at`) durante una revisione aperta** → la prescrizione è considerata scaduta a tutti gli effetti; il flusso di scadenza esistente prevale. La revisione resta aperta nel DB ma il customer non potrà più modificare (regole di scadenza già presenti nel servizio).
- **Motivo vuoto o troppo corto** → errore inline sul form (min 5, max 2000).
- **M&H chiude revisione senza che il customer abbia mai inviato modifiche** → consentito; la revisione si chiude normalmente con `last_submitted_at = NULL`.
- **Apertura revisione, rollback DB**: la creazione della `revision` e della prima `revision_reason` devono essere in una singola transazione.

## UX e feedback

### Admin (M&H) — [operations/partials/PrescriptionTab.vue](../../resources/js/pages/operations/partials/PrescriptionTab.vue)

- Badge stato: quando `prescription.active_revision` è presente, mostra "In revisione" (colore arancio) al posto dello status reale. Altrimenti lo status reale.
- Banner "nuove modifiche": full-width del container dei dettagli, visibile se `active_revision.last_submitted_at` è più recente di:
  - `opened_at` della revisione, se non c'è nessun `revision_reason` oltre al primo, **oppure**
  - `created_at` dell'ultimo `revision_reason`.
  Il banner mostra il titolo "Nuove modifiche dal customer" + la data/ora di invio (`last_submitted_at`). Sparisce quando M&H (a) chiude la revisione o (b) aggiunge un nuovo motivo.
- Pulsante **"Richiedi revisione"**: visibile solo se `status != DRAFT` e nessuna revisione aperta.
- Pulsante **"Aggiungi motivo"** / **"Chiedi nuove modifiche"**: visibile solo se revisione aperta e `last_submitted_at` presente (dopo almeno un invio modifiche del customer). Apre dialog con textarea obbligatoria.
- Pulsante **"Chiudi revisione"**: visibile se revisione aperta. Dopo conferma (dialog con domanda "Vuoi chiudere la revisione?"), chiude la revisione.
- Card con storico motivi: visibile se revisione aperta, lista cronologica con testo + data richiesta.
- Toast di successo dopo ogni azione ("Revisione aperta", "Motivo aggiunto", "Revisione chiusa").

### Index lavorazioni admin — [operations/Index.vue](../../resources/js/pages/operations/Index.vue)

- Nella colonna "Stato", accanto all'`OperationStatusBadge`, se `item.latest_prescription.active_revision` è presente viene mostrata un'icona 🔄 con tooltip "In revisione". L'icona sparisce appena la revisione viene chiusa (cioè `active_revision` diventa `null`). Nessuna aggiunta sulla Index delle prescrizioni (quella usa già il `PrescriptionStatusBadge` con prop `in-revision`).

### Customer — [workspace/operations/partials/PrescriptionTab.vue](../../resources/js/pages/workspace/operations/partials/PrescriptionTab.vue)

- Badge stato: stessa logica dell'admin ("In revisione" in arancio se revisione aperta).
- Banner arancio: visibile se revisione aperta, con testo "La tua prescrizione è in revisione. Modifica i dati richiesti e invia le modifiche." + **storico motivi della revisione corrente** (testo + data richiesta, ordine cronologico).
- Pulsante **"Invia modifiche"**: visibile solo se prescrizione in `DRAFT` (come oggi) oppure revisione aperta. Nello stato `DRAFT` il label resta "Invia prescrizione".
- Dopo invio modifiche: toast "Modifiche inviate", banner aggiornato se il server risponde.
- Read-only: se la prescrizione è `SENT`/`CONFIRMED` senza revisione aperta, tutti i campi sono disabilitati e nessun pulsante di invio è presente.

### Notifiche

- **`RequestPrescriptionRevisionForUser`** (esistente, adattata): inviata al customer quando si apre una revisione o si aggiunge un motivo. Il testo del motivo viene passato.
- **`ResubmittedPrescriptionForAdmin`** (esistente): inviata agli admin quando il customer rinvia modifiche.
- **`ClosedPrescriptionRevisionForUser`** (**nuova**): inviata al customer quando M&H chiude la revisione. Mail + DB notification, pattern identico alle altre (`notifiable` = customer, queued).

### Activity log

Mantenere i log con Spatie ActivityLog, aggiornando i payload:
- `prescription_revision_opened` (al posto di `prescription_revision_requested` quando si apre per la prima volta)
- `prescription_revision_reason_added` (per motivi successivi al primo)
- `prescription_revision_resubmitted` (invio modifiche customer)
- `prescription_revision_closed` (nuovo)

## Fasi di implementazione

### Fase unica: Refactor completo Revisione

- **Scope**: refactor integrale in un'unica release (feature interdipendente, spezzarla lascerebbe stati intermedi incoerenti in DB).
  1. Creazione migration: nuove tabelle `revisions` e `revision_reasons`, migrazione dati esistenti (IN_REVIEW → SENT, REVISED → CONFIRMED), rimozione valori enum.
  2. Creazione modelli `Revision`, `RevisionReason` con relazioni; aggiunta relazioni su `Prescription` (`revisions()`, `activeRevision()`).
  3. Aggiornamento `PrescriptionStatusEnum` (rimuovere `IN_REVIEW`, `REVISED`).
  4. Refactor `PrescriptionService`:
     - `requestRevision(prescription, reason, causer)` → crea revisione + primo motivo in transazione, non tocca più `status`.
     - `addRevisionReason(revision, reason, causer)` → nuovo metodo per motivi successivi.
     - `resubmitRevision(prescription, causer)` → aggiorna `active_revision.last_submitted_at`, non tocca più `status`.
     - `closeRevision(revision, causer)` → nuovo metodo.
  5. Aggiornamento controller `PrescriptionController` e `WorkspaceController`:
     - Route/metodo per apertura revisione.
     - Route/metodo per aggiunta motivo (separata da apertura).
     - Route/metodo per chiusura revisione.
     - Adattamento metodo `prescriptionsSend` per gestire nuova regola (solo DRAFT o revisione aperta).
  6. Aggiornamento policy `PrescriptionPolicy`: verificare che i gate coprano `openRevision`, `addRevisionReason`, `closeRevision` (o generico `update` esteso).
  7. Form requests: `OpenRevisionRequest`, `AddRevisionReasonRequest`, `CloseRevisionRequest` (gli ultimi due possono non avere input ma hanno autorizzazione).
  8. Nuova notifica `ClosedPrescriptionRevisionForUser` (mail + db), template blade coerente con gli esistenti.
  9. Aggiornamento frontend:
     - `Prescription.ts` type: rimuovere riferimenti a `IN_REVIEW`/`REVISED`, aggiungere `active_revision` con i suoi campi e array di motivi.
     - `PrescriptionStatusBadge.vue`: gestire il render "In revisione" in base a `active_revision` invece che allo status.
     - `operations/partials/PrescriptionTab.vue`: nuovi bottoni e dialog per apri/motivo/chiudi, badge "nuove modifiche" con logica aggiornata.
     - `workspace/operations/partials/PrescriptionTab.vue`: storico motivi, banner, bottone "Invia modifiche" con nuova regola di visibilità.
  10. Aggiornamento activity log: rinominare/aggiungere eventi come indicato sopra.
  11. Test Pest: aggiornare test esistenti della feature revisione; aggiungere casi per regole nuove (max una aperta, solo DRAFT blocca apertura, chiusura senza invio, race condition su chiusura, storico motivi).
  12. Aggiornamento [Docs/ui/UI_ACTIVITY_LOG.md](../ui/UI_ACTIVITY_LOG.md) per eventuali modifiche CSS correlate.

- **File coinvolti**:
  - Nuovi: `database/migrations/YYYY_MM_DD_create_revisions_table.php`, `database/migrations/YYYY_MM_DD_create_revision_reasons_table.php`, `database/migrations/YYYY_MM_DD_migrate_prescription_revision_statuses.php`, `app/Models/Revision.php`, `app/Models/RevisionReason.php`, `app/Http/Requests/Prescription/OpenRevisionRequest.php`, `app/Http/Requests/Prescription/AddRevisionReasonRequest.php`, `app/Http/Requests/Prescription/CloseRevisionRequest.php`, `app/Notifications/User/ClosedPrescriptionRevisionForUser.php`, `resources/views/mail/user/closed-prescription-revision-for-user.blade.php`, test Pest dedicati.
  - Modificati: `app/Enums/PrescriptionStatusEnum.php`, `app/Models/Prescription.php`, `app/Services/PrescriptionService.php`, `app/Http/Controllers/PrescriptionController.php`, `app/Http/Controllers/WorkspaceController.php`, `app/Policies/PrescriptionPolicy.php`, `app/Http/Requests/Prescription/RequestRevisionRequest.php` (da rivedere o sostituire), `routes/web.php`, `resources/js/types/Prescription.ts`, `resources/js/components/prescriptions/PrescriptionStatusBadge.vue`, `resources/js/pages/operations/partials/PrescriptionTab.vue`, `resources/js/pages/workspace/operations/partials/PrescriptionTab.vue`, test esistenti su revisione.
- **Dipende da**: nessuna (feature standalone). Prima del rilascio verificare che non siano attive prescrizioni in `IN_REVIEW`/`REVISED` in corso che richiedano coordinamento manuale (il reset viene fatto dalla migration, ma il customer perde il contesto revisione — comunicare o completare manualmente prima del deploy se serve).
