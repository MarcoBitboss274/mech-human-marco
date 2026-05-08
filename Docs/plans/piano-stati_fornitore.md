# Piano: Stati e alert lavorazione lato Fornitore

**Specifiche**: [specifiche-stati_fornitore.md](../specs/specifiche-stati_fornitore.md)

## Stato del codebase

**Esiste già e va riusato**:
- Pivot `operation_supplier` con `selected`, `selected_at`, `status` ([create_operation_supplier_table](../../database/migrations/2026_02_23_120000_create_operation_supplier_table.php), [add_selected_at](../../database/migrations/2026_04_24_120200_add_selected_at_to_operation_supplier_table.php)) — *senza* model dedicato (operazioni sul pivot fatte via `updateExistingPivot`/`newPivotStatement`).
- [`Operation::getSupplierVisibleStatusAttribute()`](../../app/Models/Operation.php#L75) — accessor con la vecchia logica a 6 stati: da riscrivere.
- [`OperationService`](../../app/Services/OperationService.php) — già contiene `attachSupplier`, `swapSupplier`, `confirmProduction`, `cancelProduction`, `uploadSupplierDocument`, `deleteSupplierDocument`. Sono i punti di hook giusti per gli activity log nuovi e per il reset di `supplier_completed_at`.
- [`WorkspaceSupplierController`](../../app/Http/Controllers/WorkspaceSupplierController.php) — index/show già strutturati, filtri `applySupplierVisibleStatusFilter()` da rifare.
- 4 Notification class già esistenti in [`app/Notifications/Supplier`](../../app/Notifications/Supplier/) (Assigned, ProductionConfirmed, ProductionCanceled, Canceled) e [`NotificationService::sendToSupplier`](../../app/Services/NotificationService.php#L74) per il dispatch.
- `Prescription::activeRevision` e `Revision`/`RevisionReason` model già a posto ([Prescription.php#L218](../../app/Models/Prescription.php#L218)).
- [`ActivityLogController`](../../app/Http/Controllers/ActivityLogController.php) e [`ActivitySlider.vue`](../../resources/js/components/activity/ActivitySlider.vue) — endpoint generico filtrato per `model_type` e permesso. Oggi conosce solo `operation` con permesso `operations.activity.view`.
- Gate `supplierWorkspaceAbility` + [`SupplierWorkspacePermissionMap`](../../app/Services/SupplierWorkspacePermissionMap.php) + [`WorkspaceAbilityEnum`](../../app/Enums/WorkspaceAbilityEnum.php) — patterm per nuovi ability.
- [`ProductionsTab.vue`](../../resources/js/pages/operations/partials/ProductionsTab.vue) — tab M&H esistente, qui va aggiunto il badge `Completata`.
- Test pattern Pest in [tests/Feature](../../tests/Feature/) — vedi `PrescriptionRevisionTest.php`, `OperationServiceSelectSupplierTest.php`.

**Va creato da zero**:
- Migration per `supplier_completed_at` su `operation_supplier`.
- Endpoint POST complete e relativo gate `SUPPLIER_OPERATIONS_COMPLETE` in WorkspaceAbilityEnum/PermissionMap (per Admin **e** Member).
- `SupplierProductionCompletedNotification` (verso admins M&H — niente helper esistente per "agent della specifica operation", quindi passare per `NotificationService::sendToAdmins`).
- Riscrittura componente `SupplierOperationAlert.vue` per il nuovo modello (banner di stato mutex + alert eccezionali multi).

## Diff rispetto alle specifiche

- **Specifiche dicono "scope del filtro `supplier` in ActivityLogController" — realtà**: l'endpoint conosce solo `operation`, autenticato con permesso M&H `operations.activity.view`. **Implicazione**: estendere `$supported` con un secondo entry "supplier-scoped" (model_type es. `supplier_operation`) che richiede `supplierWorkspaceAbility` + filtro che mostra **solo gli eventi visibili al fornitore** (whitelist degli `event` strings) e **solo per Operation con pivot del fornitore corrente**.
- **Specifiche dicono reset di `supplier_completed_at` "quando l'Operation esce da PRODUCTION o si setta `production_canceled_at`" — realtà**: oggi [`OperationService::cancelProduction()`](../../app/Services/OperationService.php#L985) imposta `production_canceled_at = now()` e `status = WAITING_APPROVAL`. È l'unico punto di uscita non-cancel dalla `PRODUCTION`. **Implicazione**: il reset va concentrato lì + nel set di `canceled_at` in produzione (transizione separata, da identificare nel cancel flow di `OperationService`/controller — verificare durante implementazione).
- **Specifiche dicono "documenti editabili solo in `Nuovo caso`" — realtà**: oggi [`deleteSupplierDocument`](../../app/Services/OperationService.php#L1162) accetta DRAFT+REQUESTED e [`uploadSupplierDocument`](../../app/Services/OperationService.php#L1148) non ha vincolo. **Implicazione**: ampliare la whitelist di delete a REQUESTED+IN_PROGRESS+WAITING_APPROVAL (e DRAFT non visibile comunque al fornitore, può restare innocuo) + aggiungere lo stesso vincolo all'upload.
- **Specifiche dicono "notifica email + bell agli utenti M&H assegnabili (Admin/Superadmin/Agent)" — realtà**: [`NotificationService`](../../app/Services/NotificationService.php) ha `sendToAdmins()` (solo `admin`, non superadmin né agent specifici della Operation). **Implicazione**: usare `sendToAdmins` per la v1 e annotare come "limitazione attuale" se Marco vuole estendere agli agent assegnati va fatto un nuovo helper — fuori scope minimo.
- **Specifiche dicono "Operation in COMPLETED + supplier_completed_at null → visibile come Produzione confermata" — realtà**: [`operationsIndex`](../../app/Http/Controllers/WorkspaceSupplierController.php#L156) include `COMPLETED` nella whitelist di stati visibili: ✓ coerente, basta sistemare il mapping nell'accessor.

## Step ordinati

### 1. Schema + accessor
1. **Migration** `add_supplier_completed_at_to_operation_supplier_table` (dopo `selected_at`).
   - *Gate*: `php artisan migrate` ok, colonna nullable, default null.
2. **Riduci `SupplierVisibleStatusEnum` a 3 casi** (`NEW_CASE`, `PRODUCTION_CONFIRMED`, `COMPLETED`); aggiorna `label()`.
   - *Gate*: `php artisan tinker → SupplierVisibleStatusEnum::cases()` ritorna 3.
3. **Riscrivi `Operation::getSupplierVisibleStatusAttribute()`** con la nuova logica (vedi tabella in [specifiche §Modello stati](../specs/specifiche-stati_fornitore.md#regole-di-business)). L'accessor deve consultare il pivot del fornitore corrente: serve un `supplier_id` di contesto (recuperabile dall'utente loggato in WorkspaceSupplierController via `loadMissing` con `wherePivotIn`, oppure aggiungendo all'eager-load in `operationsIndex`/`operationsShow` un `supplier_completed_at` esposto come `addSelect` analogo a `assigned_at`).
   - *Gate*: pest test nuovo `SupplierVisibleStatusTest` con i 6 casi (DRAFT non visibile, REQUESTED→new_case, IN_PROGRESS→new_case, WAITING_APPROVAL→new_case, PRODUCTION→production_confirmed, PRODUCTION+supplier_completed_at→completed, COMPLETED+null→production_confirmed, completed_at + canceled→completed).

### 2. Service + endpoint complete
4. **Aggiungi ability** `SUPPLIER_OPERATIONS_COMPLETE` in `WorkspaceAbilityEnum`; assegnala ad Admin **e** Member in `SupplierWorkspacePermissionMap`.
5. **`OperationService::markSupplierProductionCompleted(Operation, User)`** — valida (stato base = `production_confirmed`, `canceled_at` null, `supplier_completed_at` null), valorizza pivot, logga `supplier_marked_completed`, dispatcha `SupplierProductionCompletedNotification` via `sendToAdmins`.
6. **Crea `SupplierProductionCompletedNotification`** (mail+database) speculare a `OperationProductionConfirmedForSupplierNotification`.
7. **Route + controller** `WorkspaceSupplierController::operationsMarkCompleted` con gate `supplierWorkspaceAbility('workspace.supplier.operations.complete')` e bind del modello.
   - *Gate*: pest test `SupplierProductionCompletedTest` (admin ok, member ok, stato sbagliato 422, già completato 422 idempotente, notifica admins inviata).

### 3. Reset automatici e activity log nei punti esistenti
8. **In `OperationService::cancelProduction`**: dopo l'update dell'Operation, se per il fornitore corrente `supplier_completed_at` è non null, azzeralo nel pivot e logga `supplier_completed_reset_for_production_cancel`.
9. **Annullamento lavorazione**: identifica il setter di `canceled_at` (da grep `'canceled_at' => now()` su OperationService/Controller) e logga `operation_canceled` lì. Verifica che il reset di `supplier_completed_at` **NON** avvenga (regola spec).
10. **Activity log negli altri punti esistenti**:
    - `attachSupplier` / `swapSupplier` → `supplier_assigned`.
    - `confirmProduction` (al passaggio a PRODUCTION effettivo) → `production_confirmed`.
    - `cancelProduction` (oltre al reset) → `production_canceled`.
    - `uploadSupplierDocument` / `deleteSupplierDocument` → `supplier_document_uploaded` / `supplier_document_removed` con properties `media_id`, `file_name`, `removed_by_admin: bool`.
    - *Gate*: pest test `SupplierActivityLogEventsTest` che asserta `Activity::query()` per ciascun evento dopo l'azione.

### 4. Documenti — ampliamento finestra editabile
11. **`uploadSupplierDocument(Operation, UploadedFile, User, bool $asAdmin = false)`**: aggiungi parametro `$asAdmin` e validazione "stato base = `Nuovo caso`" se `!$asAdmin`. WorkspaceSupplierController passa `false`, OperationController M&H passa `true`.
12. **`deleteSupplierDocument`**: amplia `$allowedStatuses` a `[REQUESTED, IN_PROGRESS, WAITING_APPROVAL]` quando `!$asAdmin`.
    - *Gate*: pest test (un file deletable in IN_PROGRESS dal supplier, undeletable in PRODUCTION).

### 5. Index, filtri, payload
13. **Refactor `WorkspaceSupplierController::operationsIndex`**:
    - filtro `supplier_visible_status` con i nuovi 3 valori (`new_case`, `production_confirmed`, `completed`) — `new_case` = REQUESTED/IN_PROGRESS/WAITING_APPROVAL e `supplier_completed_at` null; `production_confirmed` = PRODUCTION/COMPLETED e `supplier_completed_at` null; `completed` = `supplier_completed_at` non null;
    - nuovo filtro `canceled_state` (`only` / `excluded` / null=tutte) su `canceled_at`;
    - mantieni filtro `documents_state` invariato;
    - aggiungi `supplier_completed_at` come `addSelect` dal pivot.
14. **`operationsShow`**: assicurati che il payload includa `is_canceled`, `production_canceled_at`, `supplier_completed_at` (dal pivot) e `latest_prescription.active_revision` (già caricato).
    - *Gate*: visit della pagina lista filtra correttamente con i 3 nuovi valori; payload show contiene tutti i campi.

### 6. Activity log scope supplier
15. **Estendi `ActivityLogController::index`**: nuovo entry in `$supported` per `supplier_operation` con permission custom (gate `supplierWorkspaceAbility` + ability `SUPPLIER_OPERATIONS_VIEW`) e con whitelist di `event` (lista in spec) + filtro che `wherePivot('selected', true)` per il `supplier_id` dell'utente.
    - *Gate*: pest test che un supplier user vede solo gli eventi della whitelist e solo per Operation del proprio fornitore.

### 7. Frontend fornitore
16. **`Index.vue`**: 3 opzioni stato + nuovo `BbSelect` `Annullate`; cella `supplier_visible_status` mostra badge stato + eventuale badge `Annullata` affiancato (helper `useSupplierStatusBadge` da estrarre).
17. **`Show.vue`**: header con bottoni `Produzione completata` (con dialog di conferma irreversibile) + `Attività` (apre `ActivitySlider` con `model-type="supplier_operation"`); badge dello stato + badge `Annullata` affiancato; `isEditableState` ridotto a `new_case`.
18. **`SupplierOperationAlert.vue`**: separa rendering — banner di stato mutex (Nuovo caso / Produzione confermata / Completato) renderizzato in fondo + alert eccezionali indipendenti (Annullata, Produzione annullata, Prescrizione in revisione) renderizzati sopra in ordine. Estendi props per leggere `prescription.active_revision`.
    - *Gate*: visit manuale del workspace fornitore in tutti i casi: `Nuovo caso` puro, `Produzione confermata` puro, `Completato` puro, `+ Annullata`, `+ Revisione`, `Annullata + Revisione`, ecc. — verifica banner e bottoni.

### 8. Frontend M&H
19. **`ProductionsTab.vue`**: badge `Completata` (verde) sulla card di "Produzione confermata" quando il pivot `selected=true` ha `supplier_completed_at` non null. Tooltip con la data.
    - *Gate*: visit manuale del detail M&H prima e dopo il click "Produzione completata" del fornitore.

### 9. Test e cleanup
20. **Aggiorna i test esistenti** che asseriscono i 6 vecchi `SupplierVisibleStatus` (rimuovi `assigned_waiting_documents`, `documents_sent`, `under_evaluation`, `canceled` come stato e adegua le assertion).
    - *Gate*: `php artisan test --filter Supplier` verde.

## Rischi e punti di attenzione

- **Coordinare l'eager-load del pivot**: l'accessor `getSupplierVisibleStatusAttribute()` deve poter leggere `supplier_completed_at`. Senza precaricamento corretto rischia N+1 o valore mancante. Centralizza in un scope `withCurrentSupplierPivot($supplierId)` su `Operation` e usalo in index/show.
- **Reset `supplier_completed_at` su pivot**: serve `supplier_id` di contesto. Per `cancelProduction` l'`OperationService` può prenderlo da `$operation->selectedSupplier()->first()`. Documenta che si applica al fornitore *corrente* (se M&H ha già cambiato il fornitore prima di annullare la produzione, il pivot vecchio è già cancellato).
- **`production_canceled_at` non viene resettato in `cancelProduction`**: oggi rimane valorizzato anche dopo una nuova `confirmProduction` (che invece lo azzera in [OperationService.php#L968](../../app/Services/OperationService.php#L968)). Coerente con la regola spec del banner "Produzione annullata" che si scollega quando si torna a `PRODUCTION`.
- **`ActivityLog` event whitelist lato fornitore**: il pattern attuale di `ActivityLogController` ritorna `description` e `event` raw. Per i testi italiani user-facing ("M&H ti ha assegnato la lavorazione") serve la formattazione lato Vue, non lato controller. Centralizza in un mapper `supplierActivityFormatter.ts` nel componente.
- **`getActivitylogOptions`** di Operation logga solo `status` su update — non basta per gli eventi nuovi che vanno scritti con `activity()->event(...)->log(...)` espliciti. Non confondere i due meccanismi.
- **Notifica destinatari M&H**: `sendToAdmins` invia solo a `role=admin`. Né superadmin né agent specifico dell'Operation ricevono. Verificare con Marco se va bene per la v1 (vedi Domande aperte).
- **Filtro `canceled_state`**: la regola "default = tutte (incluse annullate)" cambia il comportamento attuale (oggi le annullate non hanno un filtro dedicato; sono comunque visibili dal mapping `canceled` rimosso dall'enum). Verifica che, dopo il refactor, le annullate continuino a comparire di default in lista.
- **Migrazione dati esistenti**: nessun valore `canceled` da migrare nell'enum (era derivato, non persistito). Non serve data migration.
- **Tipi TypeScript**: `Operation` e `Prescription` types vanno aggiornati con `supplier_completed_at` (sul payload supplier scope), `is_canceled`/`canceled_at` se non già esposti, `active_revision` (atteso già presente da `specifiche-revisione_aggiornamento.md`).

## Domande aperte

1. **Destinatari notifica `SupplierProductionCompletedNotification`**: la spec dice "Admin / Superadmin / Agent secondo policy esistenti". `NotificationService::sendToAdmins` invia solo a `role=admin`. Va bene per la v1 o serve includere superadmin + agent specifico dell'Operation? Se serve, va creato un nuovo helper. — *Blocco*: bassa priorità, può essere chiarita prima dell'implementazione del passo 6.
2. **Annullamento lavorazione**: dove vive oggi il setter di `canceled_at`? Dalla grep iniziale non è apparso un metodo dedicato in `OperationService` (solo `production_canceled_at`). Da identificare in `OperationController::cancel` o equivalente prima di scrivere lo step 9. — *Blocco*: prima di iniziare lo step 3.

## Ordine di commit suggerito

1. **Schema + enum + accessor** (step 1–3): migration + enum a 3 casi + accessor riscritto + test del mapping.
2. **Service complete + ability + notifica + endpoint** (step 4–7): backend completo dell'azione "Produzione completata".
3. **Reset automatici + activity log su eventi esistenti** (step 8–10): hook in `cancelProduction`, attach/swap, upload/delete docs, set `canceled_at`.
4. **Documenti — finestra editabile ampliata** (step 11–12).
5. **Index/show controller refactor** (step 13–14): filtri, payload.
6. **Activity log scope supplier** (step 15–16): controller + frontend slideover.
7. **Frontend fornitore** (step 17–18): Index, Show, Alert.
8. **Frontend M&H + cleanup test** (step 19–20).
