# Revisione Prescrizione — Diagramma dei flussi

Feature che introduce un ciclo di revisione motivato tra `SENT/CONFIRMED/REVISED → IN_REVIEW → REVISED → …`, con persistenza cronologica nell'activity log dell'Operation (Spatie). Sostituisce l'azione `reset` legacy.

## Stati prescrizione

[`app/Enums/PrescriptionStatusEnum.php`](../../app/Enums/PrescriptionStatusEnum.php)

- `DRAFT` = `'draft'` — Bozza
- `SENT` = `'sent'` — Inviata (primo invio)
- `IN_REVIEW` = `'in_review'` — In revisione (richiesta admin)
- `REVISED` = `'revised'` — Revisionata (reinviata da customer/admin)
- `CONFIRMED` = `'confirmed'` — Confermata

## Transizioni di stato

```
DRAFT --[customer/admin: sendPrescription]--> SENT
SENT --[admin: confirmPrescription]--> CONFIRMED
SENT/CONFIRMED/REVISED --[admin: requestRevision(reason)]--> IN_REVIEW
IN_REVIEW --[customer/admin: sendPrescription -> resubmitRevision]--> REVISED
REVISED --[admin: confirmPrescription]--> CONFIRMED
REVISED --[admin: requestRevision(reason)]--> IN_REVIEW (ciclo ripetibile N volte)
```

Nessuna transizione `CONFIRMED → DRAFT` (la legacy `reset` è stata rimossa).

---

## Flussi

### 1. Admin richiede revisione (da `SENT` / `CONFIRMED` / `REVISED`)

```
Admin clicca "Richiedi revisione" sulla tab Prescrizione
    (resources/js/pages/operations/partials/PrescriptionTab.vue, bottone condizionato su
     status ∈ [sent, confirmed, revised])
        ↓
BbDialog "Richiedi revisione al customer": BbTextarea motivo (5–2000 char)
        ↓
Submit: router.post(route('prescriptions.request-revision', {prescription}), {reason})
        ↓
routes/web.php:
  POST /prescriptions/{prescription}/request-revision
        ↓
PrescriptionController::requestRevision(RequestRevisionRequest, Prescription)
  - FormRequest RequestRevisionRequest
      - authorize(): Gate::allows('update', $prescription) → permesso 'prescriptions.edit'
      - rules: reason required|string|min:5|max:2000
        ↓
PrescriptionService::requestRevision(Prescription, string $reason, User $causer)
  - Guard: status ∈ [SENT, CONFIRMED, REVISED] altrimenti ValidationException
  - Legge $fromStatus = prescription.status (snapshot per log)
        ↓
DB update: prescriptions.status = 'in_review'
  - Hook booted(): status esce da CONFIRMED → confirmed_at = null
        ↓
Spatie activity log (subject = Operation):
  - event = 'prescription_revision_requested'
  - causedBy = admin loggato
  - properties = {prescription_id, reason, from_status}
  - description = "[Nome Admin] ha richiesto una revisione: [reason]"
        ↓
Notification mail:
  - NotificationService::sendToUser($prescription->user,
      new RequestPrescriptionRevisionForUser($prescription, $reason))
  - Template: resources/views/mail/user/request-prescription-revision-for-user.blade.php
  - Subject: "Mech & Human - Richiesta di revisione prescrizione [TYPOLOGY]"
  - Contiene motivo + link a workspace.operations.show
        ↓
back() → UI refresh: badge "In revisione" + card motivo corrente
```

Operation.status **non viene toccato**. Fornitori già collegati all'operation **non vengono toccati**.

---

### 2. Customer legge motivo revisione

```
Customer apre operation dal workspace (email/dashboard/lista)
    (resources/js/pages/workspace/operations/partials/PrescriptionTab.vue)
        ↓
Inertia prop: operation.prescriptions[0].latest_revision_reason
  ↳ Prescription model accessor `latestRevisionReason`:
       Activity::query()
         ->where('subject_type', Operation::class)
         ->where('subject_id', operation_id)
         ->where('event', 'prescription_revision_requested')
         ->where('properties->prescription_id', prescription_id)
         ->orderByDesc('id')->first()->properties->get('reason')
        ↓
UI renderizza banner arancio:
  - Titolo "Revisione richiesta dall'amministrazione"
  - Testo completo del motivo
  - Hint "Applica le modifiche richieste e reinvia la prescrizione"
```

Cronologia completa visibile **solo lato admin** tramite `ActivitySlider` su `operations/Show.vue`; il customer vede solo l'ultimo motivo.

---

### 3. Customer modifica prescrizione via wizard e reinvia (IN_REVIEW → REVISED)

```
Customer clicca "Modifica" sulla tab Prescrizione (visibile se status ∈ [draft, in_review])
    (workspace/operations/partials/PrescriptionTab.vue, canEdit computed)
        ↓
router.get(route('workspace.operations.edit-wizard', {building, operation}))
        ↓
WorkspaceController::operationsEditWizard(Building, Operation)
  - abort se operation.building_id != building.id
  - $wizard = OperationService::getEditWizardData($operation)
        ↓
OperationService::getEditWizardData(Operation):
  - Guard: latestPrescription.status ∈ [DRAFT, IN_REVIEW]
  - Costruisce $form dal latestPrescription (tutti i campi + details + attachments placeholder)
  - Ritorna: {mode: 'edit', operationId, prescriptionId, prescriptionStatus, initialForm, buildings}
        ↓
Inertia::render('workspace/operations/Edit', {wizard})
        ↓
Vue Edit.vue:
  - computed isRevisionMode = (mode==='edit' && prescriptionStatus==='in_review')
  - pageTitle: "Revisiona prescrizione"
  - submitButtonLabel: "Invia revisione"
        ↓
Customer modifica i campi nello stepper (stessi step di workspace.operations.create)
        ↓
Customer clicca bottone finale "Invia revisione" allo step 5
        ↓
Disclaimer legale (BbDialog con BbCheckbox consenso) → "Conferma e invia"
        ↓
form.draft = false
form.submit_revision = isRevisionMode.value (true)
router.submit('put', route('workspace.operations.update-wizard', {building, operation}), form)
        ↓
routes/web.php:
  PUT /workspace/{building}/operations/{operation}/update-wizard
        ↓
WorkspaceController::operationsUpdateWizard(UpdateOperationWithPrescriptionRequest, Building, Operation)
  - abort se operation.building_id != building.id
  - FormRequest valida:
      - authorize(): workspaceAbility OPERATIONS_EDIT
      - rules: submit_revision optional boolean + tutti i campi prescrizione/details/attachments
      - withValidator: latestPrescription.status ∈ [DRAFT, IN_REVIEW]
  - $validated['building_id'] = $building->id
  - Se MEMBER → mantiene user_id originale
        ↓
OperationService::updateWithPrescription(Operation, array $payload)
  - Guard: latestPrescription.status ∈ [DRAFT, IN_REVIEW]
  - Ramo IN_REVIEW:
      - operation.fill({building_id, typology})  # NO status
      - operation.save()
      - latestPrescription.fill({tutti i campi CONTENUTO})  # NO status, NO send_at, NO expire_at
      - latestPrescription.save()
      - OperationService::syncPrescriptionDetails(...)  # upsert details/attachments
      - Nessuna notification qui
      - return (non entra nel ramo DRAFT)
        ↓
Torna al controller; request->boolean('submit_revision') === true
        ↓
PrescriptionService::sendPrescription($prescription, $request->user())
  - validatePrescription() (stessa del primo invio)
  - status corrente IN_REVIEW → chiama resubmitRevision()
        ↓
PrescriptionService::resubmitRevision(Prescription, User $causer)
  - DB update: status='revised', send_at=now()   # expire_at invariato
  - $revisionNumber = Activity::count(event='prescription_revision_requested' per questa prescription)
  - Spatie activity log:
      - event='prescription_resubmitted', causedBy=$causer
      - properties={prescription_id, revision_number}
      - description="[Nome] ha reinviato la prescrizione revisionata (revisione #N)"
  - NotificationService::sendToAdmins(new ResubmittedPrescriptionForAdmin($prescription, $revisionNumber))
        ↓
to_route('workspace.operations.show', {building, operation})
        ↓
UI workspace: banner informativo azzurro
  "Prescrizione revisionata inviata, in attesa di conferma"
```

---

### 4. Customer reinvia direttamente dalla tab (senza rientrare nel wizard)

```
Customer ha già modificato in un giro precedente e torna sulla tab.
Status della prescrizione: IN_REVIEW
    (workspace/operations/partials/PrescriptionTab.vue)
        ↓
Bottone "Invia revisione" visibile per in_review
        ↓
Click → Dialog disclaimer legale → Conferma
        ↓
router.post(route('workspace.prescriptions.send', {building, prescription}))
        ↓
WorkspaceController::prescriptionsSend(Request, Building, Prescription)
  - Gate::authorize('workspaceAbility', [$building, WorkspaceAbilityEnum::PRESCRIPTIONS_SEND])
  - abort se prescription.building_id != building.id
        ↓
PrescriptionService::sendPrescription($prescription, $request->user())
  - Status IN_REVIEW → resubmitRevision() (vedi flusso 3, stessa catena)
```

---

### 5. Admin modifica/reinvia via wizard admin

```
Admin clicca "Modifica" sulla tab Prescrizione admin (canEdit: draft OR in_review)
    (resources/js/pages/operations/partials/PrescriptionTab.vue)
        ↓
router.get(route('operations.edit-wizard', {operation}))
        ↓
OperationController::editWithPrescription(Operation)
  - Gate::authorize('update', $operation)
  - Gate::authorize('managePrescription', $operation)
  - $wizard = OperationService::getEditWizardData($operation)
        ↓
Inertia::render('operations/Create', {wizard})
        ↓
Vue Create.vue (stesso stepper del flusso create admin):
  - isRevisionMode, pageTitle="Revisiona prescrizione", submitButtonLabel="Invia revisione"
        ↓
Submit step 5 → Disclaimer → form.submit_revision = true
        ↓
router.submit('put', route('operations.update-wizard', {operation}))
        ↓
OperationController::updateWithPrescription(UpdateOperationWithPrescriptionRequest, Operation)
  - Gate authorize update + managePrescription
  - OperationService::updateWithPrescription (ramo IN_REVIEW, vedi flusso 3)
  - request->boolean('submit_revision') === true
  - PrescriptionService::sendPrescription($prescription, $request->user()) → resubmitRevision
        ↓
to_route('operations.show', {operation})
```

---

### 6. Admin reinvia direttamente dalla tab admin (IN_REVIEW → REVISED)

```
Admin sulla tab Prescrizione (status IN_REVIEW)
    (resources/js/pages/operations/partials/PrescriptionTab.vue)
        ↓
Bottone "Invia revisione" → submitRevision() (submit diretto, NO disclaimer)
        ↓
router.post(route('prescriptions.send', {prescription}))
        ↓
PrescriptionController::send(Request, Prescription)
  - Gate::authorize('update', $prescription)
  - PrescriptionService::sendPrescription($prescription, $request->user())
  - status IN_REVIEW → resubmitRevision() (vedi flusso 3)
```

---

### 7. Admin conferma prescrizione (SENT o REVISED → CONFIRMED)

```
Admin clicca "Conferma presa in carico" (visibile per status ∈ [sent, revised])
    (resources/js/pages/operations/partials/PrescriptionTab.vue)
        ↓
router.post(route('prescriptions.confirm', {prescription}))
        ↓
PrescriptionController::confirm(Request, Prescription)
  - Gate::authorize('update', $prescription)
        ↓
PrescriptionService::confirmPrescription(Prescription, User $causer)
  - Guard: status ∈ [SENT, REVISED] altrimenti ValidationException
  - DB update: prescriptions.status = 'confirmed'
      - Hook booted(): transita a CONFIRMED → confirmed_at = now()
  - OperationService::updateStatus($operation, OperationStatusEnum::IN_PROGRESS)
  - Spatie activity log:
      - event='prescription_confirmed', causedBy=$causer
      - properties={prescription_id}
      - description="[Nome] ha confermato la prescrizione"
```

Nessuna notifica email dedicata per la conferma.

---

### 8. Customer invia prescrizione (primo invio, DRAFT → SENT)

```
Customer clicca "Invia prescrizione" sulla tab (status=draft)
        ↓
Disclaimer legale → Conferma
        ↓
router.post(route('workspace.prescriptions.send', {building, prescription}))
        ↓
WorkspaceController::prescriptionsSend
        ↓
PrescriptionService::sendPrescription
  - validatePrescription()
  - Status corrente DRAFT → ramo primo invio:
      - prescription.update({status:'sent', send_at:now(), expire_at:now()+6mesi})
      - OperationService::updateStatus(operation, REQUESTED)
      - NotificationService::sendToAdmins(new SendPrescriptionForAdmin)
      - NotificationService::sendToUser(user, new SendPrescriptionForUser)
      - NotificationService::sendToUser(building.agent, new SendPrescriptionForAgent)
```

---

### 9. Tentativo di eliminazione prescrizione in revisione (bloccato)

```
(Qualsiasi UI che chiami DELETE /prescriptions/{id})
        ↓
PrescriptionPolicy::delete(User, Prescription):
  - Se status !== DRAFT → return false (403)
  - Altrimenti: user->can('prescriptions.destroy')
```

L'UI della lista `resources/js/pages/prescriptions/Index.vue` attualmente non mostra il bottone elimina (sezione commentata). Il backend garantisce comunque la sicurezza.

---

### 10. Visualizzazione cronologia (Activity Log sidebar)

```
Admin apre operation Show → ActivitySlider (sidebar off-canvas)
    (resources/js/components/activity/ActivitySlider.vue)
        ↓
GET route('activity-log.index') ?model_type=operation&model_id=X
        ↓
ActivityLogController::index(Request)
  - abort_unless user->can('operations.activity.view')
  - Activity::query()
      ->where('subject_type', Operation::class)
      ->where('subject_id', $modelId)
      ->with('causer')->latest()->limit(200)->get()
  - Mappa: {id, created_at, event, description, causer:{id,name,surname}}
        ↓
Frontend activityLabel():
  1. Se description non vuota → usala
     (es. "Mario Rossi ha richiesto una revisione: scan manca...")
  2. Altrimenti mappa event → label statica:
     - prescription_revision_requested → "Richiesta di revisione prescrizione"
     - prescription_resubmitted → "Prescrizione revisionata reinviata"
     - prescription_confirmed → "Prescrizione confermata"
  3. Altrimenti event raw o "--"
```

---

## Provenienza dei dati

| Campo / evento | Origine |
|---|---|
| `prescriptions.status` | Service (enum valori fissi) |
| `prescriptions.send_at` | `now()` al primo invio e ad ogni resubmit |
| `prescriptions.expire_at` | `now()+6mesi` **solo** al primo invio, mai aggiornato |
| `prescriptions.confirmed_at` | Hook model su transizione a/da CONFIRMED |
| `prescriptions.latest_revision_reason` (accessor) | Letto da `activity_log.properties.reason` dell'ultimo `prescription_revision_requested` |
| `activity_log.properties.reason` | Input admin (textarea, validato 5–2000 char) |
| `activity_log.properties.revision_number` | `COUNT` dei record `prescription_revision_requested` sulla prescrizione |
| `activity_log.properties.from_status` | Snapshot `prescription.status` prima di passare a IN_REVIEW |
| `activity_log.description` | Composto dal service con `causerName(User)` + testo umano |
| `activity_log.causer_*` | Utente autenticato passato dal controller al service |

Nessuna normalizzazione del testo `reason` (trim implicito via rule Laravel, nessun uppercase/strip).

---

## Gerarchia chiave di match (accessor `latest_revision_reason`)

```
1. WHERE subject_type = App\Models\Operation
2. AND subject_id = prescription.operation_id
3. AND event = 'prescription_revision_requested'
4. AND properties->prescription_id = prescription.id
5. ORDER BY id DESC
6. LIMIT 1 → ritorna properties->reason o null
```

Si filtra per `prescription_id` nelle properties perché una stessa operation potrebbe (in teoria) avere più prescrizioni nel tempo, e i log sono tutti agganciati al subject Operation.

---

## Entità dati

**Tabella `prescriptions`** — nessun nuovo campo introdotto dalla feature:

- chiavi: `id`, `operation_id`, `building_id`, `user_id`
- stato: `status` (string, valori `PrescriptionStatusEnum`)
- tempi: `send_at`, `expire_at`, `confirmed_at`, `created_at`, `updated_at`, `deleted_at`
- dati paziente (cifrati): `name`, `surname`, `age`, `gender`
- meta lavorazione: `typology`, `ref`, `manual`, `notes`
- fatturazione/spedizione: `company_name`, `address`, `city`, `province`, `cap`

**Tabella `activity_log`** (Spatie) — solo nuovi `event` custom, schema invariato:

- `log_name` (default `'default'`)
- `description` (testo umano)
- `subject_type` = `App\Models\Operation`
- `subject_id` = `operation.id`
- `causer_type` = `App\Models\User`
- `causer_id` = admin o customer che ha eseguito l'azione
- `event` ∈ {`prescription_revision_requested`, `prescription_resubmitted`, `prescription_confirmed`} (oltre ai default Spatie `created/updated/deleted`)
- `properties` (JSON):
  - `revision_requested`: `{prescription_id, reason, from_status}`
  - `resubmitted`: `{prescription_id, revision_number}`
  - `confirmed`: `{prescription_id}`

**Relazioni usate**:

- `Prescription belongsTo Operation` (via `operation_id`)
- `Prescription belongsTo Building` (via `building_id`)
- `Prescription belongsTo User` (customer owner, via `user_id`)
- `Operation uses LogsActivity` (Spatie) → il subject dei log è sempre l'Operation, mai la Prescription

**Enums coinvolti**:

- `PrescriptionStatusEnum`: DRAFT, SENT, IN_REVIEW, REVISED, CONFIRMED
- `OperationStatusEnum`: DRAFT, REQUESTED, IN_PROGRESS (gestiti dai flussi esistenti, non modificati dalla revisione se non nei rami primo-invio/confirm)
- `WorkspaceAbilityEnum::PRESCRIPTIONS_SEND`, `WorkspaceAbilityEnum::OPERATIONS_EDIT` (gate workspace)

**Permessi admin**: `prescriptions.edit` (per `update/send/confirm/requestRevision`), `operations.activity.view` (per sidebar).
