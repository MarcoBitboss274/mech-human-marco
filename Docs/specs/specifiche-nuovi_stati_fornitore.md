# Nuovi stati fornitore — vista solo Produzione

## Contesto

Nel refactor precedente ([piano-stati-lavorazione-produzione.md](../plans/piano-stati-lavorazione-produzione.md)) è stato introdotto un modello a 2 dimensioni:
1. **Stato lavorazione (case_status)**: Aperta/Completata — gestito dal Fornitore
2. **Stato produzione**: Null/Confermata/Annullata/Completata — gestito da Admin M&H

Nel nuovo brief Marco vuole **semplificare il punto di vista del Fornitore**: il Fornitore non deve vedere né gestire lo stato lavorazione. Vede e tiene traccia **solo dello stato della Produzione** (che continua a essere prerogativa esclusiva dell'Admin). Lo stato lavorazione (`case_status` / `supplier_completed_at`) resta in DB e lato Admin per uso interno: l'Admin può segnarla/riaprirla manualmente.

## Flusso utente

### Fornitore — area workspace

1. **Index lavorazioni**: il Fornitore vede le lavorazioni assegnate con **una sola colonna "Stato"** che mostra lo stato Produzione (`—` / Confermata / Annullata / Completata). Nessun riferimento a Aperta/Completata.
2. **Filtri**: nessun filtro `case_status`. Resta il filtro `production_status` (rinominato semplicemente "Stato" lato UI), il filtro `canceled_state` e il filtro `documents_state`.
3. **Show lavorazione**: nell'header un solo badge — lo stato Produzione. Nessun bottone "Chiudi lavorazione" / "Riapri lavorazione".
4. **Upload/Delete documenti**: invariato. Il Fornitore può caricare/eliminare documenti durante la fase pre-produzione (vincolo backend invariato: `operations.status` ∈ REQUESTED/IN_PROGRESS/WAITING_APPROVAL).
5. **Activity log / Chat**: invariati. La whitelist eventi del Fornitore non include più `case_completed` / `case_reopened` (eventi causati dall'Admin che il Fornitore non deve vedere).

### Admin M&H — scheda lavorazione

1. **Stato Produzione**: invariato — 4 stati, 4 azioni nella `ProductionsTab` admin (Conferma / Annulla / Completa / Riapri).
2. **Stato Lavorazione (case)**: nuova UI lato Admin per gestione manuale.
   - Posizionato nella `ProductionsTab` admin (sotto la sezione Produzione), oppure come blocco accanto: una riga "Stato lavorazione" con badge `CaseStatusBadge` (Aperta/Completata) + bottone d'azione (Segna completata / Riapri lavorazione).
   - Visibile solo agli utenti con permesso `operations.production.manage` (riusiamo lo stesso permesso della produzione per coerenza, non aggiungiamo un nuovo permesso dedicato).
3. **Nessun vincolo sulla transizione**: l'Admin può chiudere/riaprire quando vuole. Il pulsante mostrato dipende solo dallo stato corrente:
   - `case_status = open` → mostra "Segna lavorazione completata".
   - `case_status = completed` → mostra "Riapri lavorazione".

## Regole di business

- Il Fornitore **non scrive mai** su `operation_supplier.supplier_completed_at`.
- L'Admin scrive su `operation_supplier.supplier_completed_at` tramite due nuove azioni service:
  - `OperationService::markCaseCompletedByAdmin(Operation, User $causer)` — setta `supplier_completed_at = now()` sul pivot del fornitore selezionato.
  - `OperationService::reopenCaseByAdmin(Operation, User $causer)` — setta `supplier_completed_at = null`.
- Idempotenza:
  - `markCaseCompletedByAdmin` fallisce con `ValidationException` se `supplier_completed_at` è già valorizzato.
  - `reopenCaseByAdmin` fallisce con `ValidationException` se `supplier_completed_at` è null.
- Se la lavorazione non ha un fornitore selezionato (`operation_supplier.selected = true`), entrambe le azioni falliscono con `ValidationException`.
- Le due dimensioni restano indipendenti: nessuna delle azioni admin su case tocca `Production.status`, e viceversa `confirmProduction` / `cancelProduction` / `markProductionCompleted` / `reopenProduction` **continuano a non toccare** `supplier_completed_at`.
- Le 4 azioni Admin sulla Produzione restano invariate: matrice transizioni produzione [come da piano precedente](../plans/piano-stati-lavorazione-produzione.md#matrice-transizioni-produzione-admin).

## Dati e relazioni

| Entità | Cambio |
|---|---|
| `operation_supplier.supplier_completed_at` | **Mantenuto**. Storico preservato. Solo Admin scrive. |
| `Operation::case_status` (accessor) | **Mantenuto**. Resta accessibile lato Admin payload. **Non più esposto** nel payload Inertia del Fornitore (rimosso dall'`addSelect` controller fornitore e dall'`appends` lato Show fornitore). |
| `Production.status` | Invariato (Null/Confermata/Annullata/Completata). |
| Endpoint fornitore `POST /workspace/supplier/operations/{id}/close-case` | **Rimosso**. |
| Endpoint fornitore `POST /workspace/supplier/operations/{id}/reopen-case` | **Rimosso**. |
| Nuovi endpoint admin `POST /operations/{id}/case/complete`, `POST /operations/{id}/case/reopen` | Aggiunti, protetti da `Gate::authorize('manageProduction', $operation)`. |
| Ability `WorkspaceAbilityEnum::SUPPLIER_OPERATIONS_COMPLETE` | **Rimossa**. Nessun endpoint Fornitore la usa più. |
| Metodi `OperationService::markCaseCompleted` / `reopenCase` | **Rimossi** (erano usati solo dal Fornitore). Sostituiti dalle versioni admin. |

## Permessi e ruoli

- **Fornitore**: nessuna azione su stati. Solo lettura `production_status`. Upload/delete documenti invariato.
- **Admin M&H** (permesso `operations.production.manage`):
  - Mantiene le 4 azioni sulla Produzione.
  - Acquisisce le 2 azioni nuove sul Case (segna completata / riapri).

## Corner case e gestione errori

- **Lavorazione senza fornitore selezionato + Admin clicca "Segna lavorazione completata"** → `ValidationException` "Nessun fornitore selezionato per questa lavorazione". Lato UI: i bottoni Case non vengono renderizzati se `operation.selected_supplier` è null.
- **Admin clicca "Segna completata" su una lavorazione già completata** → `ValidationException` "Lavorazione già completata". (Lato UI il bottone non viene mostrato in questo stato, ma proteggiamo comunque a backend per race condition.)
- **Admin clicca "Riapri" su una lavorazione non completata** → `ValidationException` analoga.
- **Annullamento operation (`canceled_at`)** → non blocca le azioni Case admin (coerente col fatto che è una dimensione ortogonale).
- **Concorrenza**: stale state lato Admin (es. due tab aperte). La doppia idempotenza in backend è sufficiente; lato UI il toast di errore invita ad aggiornare la pagina.

## UX e feedback

### Lato Fornitore
- **Index**: la colonna oggi etichettata "Stato" mostra il badge `ProductionStatusBadge` con il `production_status`. La colonna "Produzione" separata viene **rimossa** (i valori sono ora nell'unica colonna "Stato"). Il filtro associato si chiama "Stato" (BE: parametro query rinominato da `production_status` a `status` per coerenza, oppure si mantiene `production_status` lato BE e si mostra "Stato" come label UI — scelgo di mantenere `production_status` lato BE per non rompere link bookmarkati, è solo cambio di etichetta).
- **Show**: header con un solo badge (produzione) + eventuale badge "Annullata" (operation.canceled_at). Nessun bottone Chiudi/Riapri.
- **Alert**: invariato (`SupplierOperationAlert.vue` già non riferisce `case_status`).

### Lato Admin
- **ProductionsTab admin**: in fondo alla card Produzione aggiungere una mini-sezione "Stato lavorazione (Fornitore)" con:
  - Badge `CaseStatusBadge` corrente.
  - Pulsante "Segna lavorazione completata" o "Riapri lavorazione" a seconda dello stato.
  - Se la data `supplier_completed_at` è valorizzata, mostrare anche "Completata il [data]".
- **Toast** "Lavorazione segnata come completata" / "Lavorazione riaperta" su successo. "Si è verificato un errore" su fallimento.

## Migrazione dati

- **Nessuna migration di schema**. Il campo `supplier_completed_at` resta com'è.
- I dati attuali in `supplier_completed_at` (lavorazioni che il Fornitore aveva chiuso nel modello precedente) vengono mantenuti: l'Admin li vedrà come "Lavorazione completata" e potrà riaprirli se necessario.

## Activity log

- Eventi `case_completed` / `case_reopened` mantenuti, **ma il causer è sempre Admin**.
- Le descrizioni vengono aggiornate da "Hai segnato/riaperto" a "M&H ha segnato/riaperto la lavorazione".
- La whitelist `SUPPLIER_VISIBLE_EVENTS` in `ActivityLogController` **rimuove** `case_completed` e `case_reopened` (il Fornitore non li deve vedere nello slideover Attività).

## Notifiche

| Notifica | Stato |
|---|---|
| `SupplierCaseCompletedForAdmin` | **Rimossa** (il Fornitore non genera più questo evento) |
| `SupplierCaseReopenedForAdmin` | **Rimossa** |
| `OperationProductionConfirmedForSupplierNotification` | Invariata |
| `OperationProductionCanceledForSupplierNotification` | Invariata |
| `OperationProductionCompletedForSupplierNotification` | Invariata |
| `OperationProductionReopenedForSupplierNotification` | Invariata |

Nessuna nuova notifica per le azioni Case lato Admin (il Fornitore non vede la dimensione case_status, quindi non ha senso notificarlo).

## Fasi di implementazione

### Fase 1 — Rimozione case_status lato Fornitore + UI Admin

- **Scope**: una sola fase, sequenza tecnica:
  1. **Backend**
     - `OperationService`: rinominare `markCaseCompleted`/`reopenCase` in `markCaseCompletedByAdmin`/`reopenCaseByAdmin` (causer = User admin). Aggiungere controllo "esiste fornitore selezionato".
     - Rimuovere le notifiche `SupplierCaseCompletedForAdmin` e `SupplierCaseReopenedForAdmin` (file + import in service + chiamate `sendToAdmins`).
     - `OperationController`: aggiungere `markCaseCompleted` / `reopenCase` (Admin), con `Gate::authorize('manageProduction', $operation)`.
     - `WorkspaceSupplierController`:
       - Rimuovere `operationsCloseCase` e `operationsReopenCase`.
       - Rimuovere il filtro `case_status` da `operationsIndex`.
       - Rimuovere l'`addSelect` di `case_status` (resta `production_status` e `supplier_completed_at` per i dati admin, ma in payload fornitore non serve).
       - In `operationsShow`: rimuovere `setAttribute('supplier_completed_at', ...)` e l'append `case_status` dal payload.
     - `routes/web.php`:
       - Rimuovere `workspace.supplier.operations.close-case` e `workspace.supplier.operations.reopen-case`.
       - Aggiungere `operations.case.complete` (POST `/operations/{operation}/case/complete`) e `operations.case.reopen` (POST `/operations/{operation}/case/reopen`).
     - `WorkspaceAbilityEnum`: rimuovere `SUPPLIER_OPERATIONS_COMPLETE`.
     - `ActivityLogController`: rimuovere `case_completed` e `case_reopened` dalla `SUPPLIER_VISIBLE_EVENTS`. Aggiornare descrizioni eventi nel service (da "Hai segnato" a "M&H ha segnato").
     - **Operation model**: rimuovere `case_status` da `$appends` (non più desiderato nel payload globale; resta accessibile come accessor on-demand). In alternativa, lasciarlo nel payload solo lato Admin tramite include esplicito.

  2. **Frontend**
     - **Index Fornitore**: rimuovere colonna `case_status`, rimuovere filtro `case_status`, rinominare label colonna `production_status` in "Stato".
     - **Show Fornitore**: rimuovere `CaseStatusBadge` dall'header, rimuovere bottoni Chiudi/Riapri, rimuovere i due dialog di conferma e i metodi `confirmCloseCase`/`confirmReopenCase`.
     - **ProductionsTab Admin** (`resources/js/pages/operations/partials/ProductionsTab.vue`): aggiungere mini-sezione "Stato lavorazione (Fornitore)" con badge + pulsante Segna/Riapri. Mostrare data `supplier_completed_at` quando valorizzata.
     - **Operation payload admin** (`OperationService::getAdminShowData`): esporre `case_status` insieme a `supplier_completed_at` per uso del frontend admin (è già presente in `supplier_completed_at`, basta aggiungere l'accessor).
     - **Types** `Operation.ts`: `case_status` resta nel tipo (utile lato Admin); rimuovo solo i type ad-hoc nel componente fornitore.

  3. **Test**
     - `CaseAndProductionStatusTest.php`: aggiornare i test che riferiscono `markCaseCompleted`/`reopenCase` in `markCaseCompletedByAdmin`/`reopenCaseByAdmin`, e i test sulle notifiche admin (rimuoverli, non esistono più). Aggiungere test per il vincolo "fornitore selezionato richiesto".
     - Rimuovere i test sull'endpoint `workspace.supplier.operations.close-case`/`reopen-case`.

- **File coinvolti**:
  - `app/Services/OperationService.php`
  - `app/Http/Controllers/OperationController.php`
  - `app/Http/Controllers/WorkspaceSupplierController.php`
  - `app/Http/Controllers/ActivityLogController.php`
  - `app/Models/Operation.php`
  - `app/Enums/WorkspaceAbilityEnum.php`
  - `app/Notifications/Admin/SupplierCaseCompletedForAdmin.php` *(da eliminare)*
  - `app/Notifications/Admin/SupplierCaseReopenedForAdmin.php` *(da eliminare)*
  - `routes/web.php`
  - `resources/js/pages/workspace/supplier/operations/Index.vue`
  - `resources/js/pages/workspace/supplier/operations/Show.vue`
  - `resources/js/pages/operations/partials/ProductionsTab.vue`
  - `resources/js/types/Operation.ts`
  - `tests/Feature/CaseAndProductionStatusTest.php`

- **Dipende da**: il piano precedente [piano-stati-lavorazione-produzione.md](../plans/piano-stati-lavorazione-produzione.md) (completato 2026-05-12).
