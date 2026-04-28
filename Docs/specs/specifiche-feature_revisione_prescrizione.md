# Revisione Prescrizione

> **Superata da [specifiche-revisione_aggiornamento.md](specifiche-revisione_aggiornamento.md).**
> Questo documento descrive il primo design in cui la revisione modificava lo stato della prescrizione (`IN_REVIEW`, `REVISED`). Il modello attuale separa revisione e stato: fare riferimento al nuovo documento.

## Contesto

Oggi la prescrizione compilata dal customer segue il ciclo `DRAFT → SENT → CONFIRMED`, con un'azione `reset` lato admin che la riporta a `DRAFT` senza obbligo di motivazione. Questo flusso non copre uno scenario reale e frequente: dopo aver "preso in carico" la prescrizione, l'admin (o il fornitore a cui la mostra) individua errori o dati mancanti e deve richiedere al customer di correggere la prescrizione, potenzialmente più volte, prima di procedere effettivamente con la lavorazione.

La feature introduce un ciclo esplicito di revisione in cui:
- l'admin richiede al customer una correzione motivata della prescrizione (da `SENT` o da `CONFIRMED`),
- il customer può modificare e reinviare la prescrizione revisionata,
- il ciclo può ripetersi N volte finché l'admin conferma,
- tutta la cronologia di motivi/richieste/reinvii è tracciata nell'activity log esistente dell'operation,
- la prescrizione in revisione resta modificabile ma non eliminabile.

Sostituisce l'attuale azione `reset` lato admin, che viene rimossa in favore della revisione motivata.

## Flusso utente

### Flusso 1 — Admin richiede revisione (da SENT o CONFIRMED)

1. Admin apre la tab "Prescrizione" dell'operation (lato admin area).
2. Stato prescrizione è `SENT` oppure `CONFIRMED`.
3. Admin vede, accanto ai bottoni esistenti, il bottone **"Richiedi revisione"**.
4. Admin clicca → si apre un dialog "Richiedi revisione al customer" con:
   - textarea obbligatoria **Motivo della revisione** (min 5 caratteri, max 2000),
   - bottone **Annulla** e bottone **Invia richiesta** (disabled finché il motivo è vuoto/sotto soglia).
5. Admin conferma → la prescrizione passa allo stato `IN_REVIEW`.
6. Viene loggato un record nell'activity log dell'operation: event `prescription_revision_requested`, properties `{prescription_id, reason, from_status}`, causer = admin loggato.
7. Viene inviata email al customer (owner della prescrizione) con motivo e link alla prescrizione.
8. Admin torna sulla tab prescrizione: vede il badge stato `In revisione`, il motivo corrente ben visibile (card/alert), e nella sidebar activity log compare la voce appena registrata.

### Flusso 2 — Customer modifica e reinvia (da IN_REVIEW)

1. Customer riceve email con motivo revisione e link.
2. Customer apre la prescrizione dalla tab "Prescrizione" dell'operation (workspace) oppure dal link email.
3. Vede banner/alert in evidenza: **"Revisione richiesta dall'admin"** + testo completo del motivo corrente.
4. Vede bottone **"Modifica prescrizione"** (stessa UX della modifica in `DRAFT`: apre wizard di edit).
5. Customer modifica i campi (tutti quelli normalmente modificabili in `DRAFT`: dati paziente, typology details, allegati/media, note, dati fatturazione) e salva.
6. La prescrizione resta in `IN_REVIEW` finché il customer non clicca **"Invia revisione"** (bottone visibile quando status = `IN_REVIEW`).
7. Al click su "Invia revisione":
   - viene validata come al primo invio (stessa `validatePrescription`),
   - se valida: status diventa `REVISED`, `send_at` viene aggiornato a `now()`, `expire_at` resta invariato rispetto al primo invio,
   - viene loggato un record activity: event `prescription_resubmitted`, properties `{prescription_id, revision_number}`, causer = customer loggato,
   - viene inviata email agli admin (tutti quelli con permesso `prescriptions.edit`) con oggetto "Prescrizione revisionata" e link all'operation.
8. Il banner "Revisione richiesta" lato customer scompare; al suo posto compare un messaggio informativo "Prescrizione revisionata inviata, in attesa di conferma".

### Flusso 3 — Admin valuta prescrizione revisionata (da REVISED)

1. Admin apre la tab "Prescrizione" dell'operation.
2. Stato è `REVISED` → admin vede:
   - tutti i dati aggiornati della prescrizione (nessun diff vs versione precedente, solo stato corrente),
   - la sidebar activity log con cronologia completa (motivi, richieste, reinvii),
   - bottoni: **"Conferma"** e **"Richiedi revisione"** (stesso dialog del Flusso 1).
3. Se admin clicca "Conferma" → status = `CONFIRMED`, `confirmed_at = now()`, log activity `prescription_confirmed`.
4. Se admin clicca "Richiedi revisione" → torna al Flusso 1 (nuovo motivo, status → `IN_REVIEW`, nuovo log), il ciclo ricomincia.

### Flusso 4 — Ciclo ripetuto

Il ciclo `REVISED → IN_REVIEW → REVISED` può ripetersi N volte. Nessun limite tecnico. L'activity log dell'operation contiene la cronologia completa di ogni giro.

## Regole di business

- **Transizioni di stato consentite** (nuove oltre a quelle esistenti):
  - `SENT → IN_REVIEW` (admin richiede revisione con motivo)
  - `CONFIRMED → IN_REVIEW` (admin richiede revisione con motivo)
  - `IN_REVIEW → REVISED` (customer invia la prescrizione revisionata, previa validazione)
  - `REVISED → IN_REVIEW` (admin richiede ulteriore revisione con motivo)
  - `REVISED → CONFIRMED` (admin conferma)
- **Transizioni esistenti mantenute**: `DRAFT → SENT` (customer invia), `SENT → CONFIRMED` (admin conferma).
- **Transizione rimossa**: `CONFIRMED → DRAFT` (azione `reset`). L'azione viene eliminata: il ritorno indietro da `CONFIRMED` passa ora esclusivamente per la revisione motivata.
- **Motivo revisione obbligatorio**: l'admin non può richiedere revisione senza inserire un motivo valido (min 5 caratteri, max 2000). Validazione sia lato Form Request sia lato frontend.
- **Editing durante revisione**: il customer in stato `IN_REVIEW` può modificare gli stessi campi modificabili in `DRAFT`. Nessun campo è congelato.
- **Non eliminabile**: la prescrizione non è eliminabile (`destroy`) in stati `SENT`, `IN_REVIEW`, `REVISED`, `CONFIRMED`. È eliminabile solo in `DRAFT` (comportamento già attuale, esplicitato dalla feature).
- **Invio revisione = validazione piena**: il reinvio dal customer (`IN_REVIEW → REVISED`) passa per la stessa `validatePrescription` usata nel primo invio. Se manca un campo obbligatorio, l'invio fallisce con i medesimi errori.
- **Scadenza invariata**: `expire_at` viene settata solo al primo invio (`DRAFT → SENT`). I reinvii successivi aggiornano `send_at` ma non `expire_at`.
- **`confirmed_at`**:
  - viene settato al momento della conferma (come ora, tramite hook del model),
  - viene azzerato quando si esce da `CONFIRMED` (come ora, tramite hook).
- **Operation status non viene toccato dalla revisione**. Resta quello risultante dall'ultima azione sulla prescrizione (es. se era `IN_PROGRESS` perché la prescrizione era `CONFIRMED`, resta `IN_PROGRESS` anche se ora la prescrizione è `IN_REVIEW`). Può essere esposto un badge informativo in UI (vedi sezione UX).
- **Fornitori invariati**: se la prescrizione viene messa in revisione da `CONFIRMED`, le associazioni operation↔suppliers (pivot) non vengono toccate.
- **Ultimo motivo visibile al customer**: quando la prescrizione è in `IN_REVIEW`, il customer deve vedere il motivo dell'ultima richiesta di revisione (non tutto lo storico). Il motivo è ricavato dall'ultimo record activity `prescription_revision_requested` sull'operation.

## Dati e relazioni

### Modifiche al modello Prescription

- **Enum `PrescriptionStatusEnum`** (esistente, `app/Enums/PrescriptionStatusEnum.php`): aggiungere due nuovi valori:
  - `IN_REVIEW = 'in_review'` (label: "In revisione")
  - `REVISED = 'revised'` (label: "Revisionata")
- **Nessuna nuova colonna** sulla tabella `prescriptions`. Il motivo corrente di revisione e lo storico non vengono persistiti in colonne dedicate ma come record nell'activity log dell'operation (vedi sotto).
- **Activity log**: aggiungere `use LogsActivity` al model `Prescription` **non è necessario**: gli eventi rilevanti vengono loggati direttamente sull'Operation (subject_type = `App\Models\Operation`) via `activity()->performedOn($operation)->causedBy(...)->withProperties(...)->event('...')->log('...')`, così la sidebar esistente li visualizza senza modifiche.

### Eventi activity log nuovi (subject = Operation)

| Event | Quando | Properties | Causer |
|---|---|---|---|
| `prescription_revision_requested` | Admin richiede revisione | `{prescription_id, reason, from_status}` | admin loggato |
| `prescription_resubmitted` | Customer invia prescrizione revisionata | `{prescription_id, revision_number}` | customer loggato |
| `prescription_confirmed` | Admin conferma | `{prescription_id}` | admin loggato |

`revision_number` è il conteggio progressivo di quante volte la prescrizione è stata revisionata (pari al numero di record `prescription_revision_requested` per quella prescrizione prima di questo reinvio, +1).

### Accessor sul model Prescription

- `latest_revision_reason` (accessor, non persistito): legge l'ultimo record `Activity` con `subject_type = Operation::class`, `subject_id = $this->operation_id`, `event = 'prescription_revision_requested'`, `properties->prescription_id = $this->id`, ordinato per `created_at desc`, e ritorna `properties->reason` o `null`. Usato solo quando `status = IN_REVIEW`.

### Route da aggiungere

**Admin area** (`routes/web.php`, gruppo admin):
- `POST /prescriptions/{prescription}/request-revision` → `PrescriptionController::requestRevision()` (rimpiazza concettualmente `reset`)
- **Rimuovere**: `POST /prescriptions/{prescription}/reset` e metodo `PrescriptionController::reset()`

**Workspace** (`routes/web.php`, gruppo workspace):
- Riusare l'esistente `POST /workspace/{building}/prescriptions/{prescription}/send` per il reinvio: il controller `WorkspaceController::prescriptionsSend()` gestisce già il caso `DRAFT → SENT`; estenderlo per gestire anche `IN_REVIEW → REVISED`. In alternativa, nuova route `POST /workspace/{building}/prescriptions/{prescription}/resubmit` se si preferisce separare (vedi "Corner case").

### Service

In `PrescriptionService`:
- Nuovo metodo `requestRevision(Prescription $prescription, string $reason, User $causer): void`
  - valida stato origine (deve essere `SENT`, `CONFIRMED` o `REVISED`),
  - setta `status = IN_REVIEW`,
  - se usciva da `CONFIRMED`, il hook model azzera `confirmed_at`,
  - registra activity log `prescription_revision_requested` sull'operation,
  - invia notifica `RequestPrescriptionRevisionForUser` al customer.
- Estensione di `sendPrescription(Prescription $prescription): void` (o nuovo metodo `resubmitPrescription` se preferito):
  - se status corrente è `DRAFT` → comportamento attuale (passaggio a `SENT`, set `send_at` e `expire_at`, invia `SendPrescriptionForAdmin/User/Agent`),
  - se status corrente è `IN_REVIEW` → valida, passaggio a `REVISED`, aggiorna solo `send_at` (non `expire_at`), registra activity `prescription_resubmitted`, invia notifica `ResubmittedPrescriptionForAdmin` agli admin. **Non** ri-invia le notifiche di primo invio (no `SendPrescriptionForAgent`, no `SendPrescriptionForUser`).
- `confirmPrescription(Prescription $prescription): void` (esistente): estendere l'accettazione degli stati d'ingresso a `SENT` e `REVISED` (oggi accetta solo `SENT`). Aggiungere log activity `prescription_confirmed`.

### Notifiche nuove

- `App\Notifications\User\RequestPrescriptionRevisionForUser` (canale mail):
  - Soggetto: `"Mech & Human - Richiesta di revisione prescrizione [TYPOLOGY]"`
  - Template: `mail.user.request-prescription-revision-for-user`
  - Payload: `prescription`, `operation`, `reason`, url alla prescrizione workspace
- `App\Notifications\Admin\ResubmittedPrescriptionForAdmin` (canale mail):
  - Soggetto: `"Prescrizione revisionata — [TYPOLOGY]"`
  - Template: `mail.admin.resubmitted-prescription-for-admin`
  - Payload: `prescription`, `operation`, `revision_number`, url all'operation admin
- **Nessuna** notifica all'agent per eventi di revisione (scelta di prodotto).
- Le notifiche esistenti (`SendPrescriptionForAdmin/User/Agent`) restano invariate e vengono inviate solo al primo invio (`DRAFT → SENT`).

### Tabelle DB coinvolte

- `prescriptions`: nessun nuovo campo; cambiano solo i valori ammessi nella colonna `status`.
- `activity_log` (Spatie, già esistente): nuovi eventi custom, nessuna alterazione schema.
- Nessuna nuova migration necessaria.

## Permessi e ruoli

- **Richiedere revisione** (admin area): stesso permesso di `update` su Prescription → `prescriptions.edit`. Ruoli abilitati: `superadmin`, `admin`, `agent` (pattern esistente). Il controller `PrescriptionController::requestRevision` deve chiamare `Gate::authorize('update', $prescription)` come già fa `confirm`.
- **Confermare** (admin area): invariato → `prescriptions.edit` via `Gate::authorize('update', ...)`.
- **Reinviare prescrizione revisionata** (workspace, customer): `workspaceAbility` con `WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value` (pattern esistente — lo stesso del primo invio).
- **Modificare prescrizione in revisione** (workspace, customer): `workspaceAbility` con permesso già esistente per edit prescrizione in bozza (allineare `canEdit` del customer per includere gli stati `DRAFT` **e** `IN_REVIEW`).
- **Visibilità activity log sidebar** (admin): già gestita con `operations.activity.view` → invariata.
- **Visibilità motivo revisione lato customer**: nessun nuovo permesso; basta esporre il dato via Inertia props della pagina `workspace/operations/Show.vue` o `workspace/prescriptions/Show.vue` quando `status = IN_REVIEW` (il customer è sempre owner della prescrizione, non serve check aggiuntivo).

## Corner case e gestione errori

- **Admin tenta "Richiedi revisione" da stato non ammesso** (es. `DRAFT`) → il bottone non è visibile in UI; lato backend il controller risponde 422 con messaggio "Operazione non consentita in questo stato".
- **Customer tenta di inviare revisione con campi mancanti** → stesso comportamento dell'invio in `DRAFT`: `validatePrescription` lancia le validation exception esistenti, la prescrizione resta in `IN_REVIEW`, l'UI mostra gli errori inline.
- **Customer tenta di eliminare prescrizione in `IN_REVIEW`/`REVISED`** → policy nega, controller risponde 403 con messaggio "Una prescrizione in revisione non può essere eliminata".
- **Admin richiede revisione due volte senza intervento del customer** → stato già in `IN_REVIEW`: il bottone non deve essere disponibile (solo "Modifica motivo"? **no**, non previsto in questa iterazione). Se l'admin tenta comunque via API → 422.
- **Concorrenza**: admin conferma mentre customer sta per reinviare revisione → si applica controllo di stato nel controller, la seconda azione fallisce con 422 ("stato della prescrizione cambiato, ricarica la pagina"). Nessun lock ottimistico aggiuntivo.
- **Revisione richiesta da `CONFIRMED` con suppliers già attaccati all'operation** → nessun effetto sulle associazioni suppliers. Nessuna notifica a suppliers. È onere dell'admin avvisare i fornitori fuori sistema se necessario.
- **Email notifica fallita** (queue error) → gestita dal normale retry di Laravel queue (pattern esistente); l'azione dell'admin/customer non viene rollback-ata.
- **`expire_at` passato durante una revisione prolungata** → nessun blocco automatico nella feature corrente (i reinvii non rinnovano `expire_at`). Se serve un blocco "prescrizione scaduta", è fuori scope di questa feature.
- **Customer legge email revisione ma prescrizione è già stata riconfermata da admin** (raro) → aprendo il link, il customer vede lo stato corrente (`CONFIRMED`) senza il banner e senza il bottone "Invia revisione".
- **Prescrizione soft-deleted**: non rilevante (non si finisce in revisione una prescrizione eliminata).

## UX e feedback

### Lato Admin (area admin, tab prescrizione dell'operation)

- **Badge stato prescrizione**: estendere `PrescriptionStatusBadge.vue` con i nuovi stati:
  - `IN_REVIEW` → colore warning/arancio, label "In revisione"
  - `REVISED` → colore info/azzurro, label "Revisionata"
- **Bottoni disponibili per stato**:
  - `DRAFT`: **Modifica**, **Invia prescrizione** (invariati)
  - `SENT`: **Conferma**, **Richiedi revisione** (nuovo) — `Resetta` **rimosso**
  - `IN_REVIEW`: nessun bottone d'azione; label "In attesa del customer". Mostra **card con motivo corrente** (sola lettura) e pulsante opzionale "Vedi cronologia" che scrolla alla sidebar activity.
  - `REVISED`: **Conferma**, **Richiedi revisione** — `Resetta` **rimosso**
  - `CONFIRMED`: **Richiedi revisione** (nuovo) — `Resetta` **rimosso**
- **Dialog "Richiedi revisione"**:
  - titolo: "Richiedi revisione al customer"
  - textarea motivo (obbligatoria, placeholder: "Descrivi cosa deve essere corretto…")
  - contatore caratteri (5–2000)
  - bottoni "Annulla" / "Invia richiesta" (loading state durante submit)
  - dopo success: toast verde "Richiesta di revisione inviata al customer"
  - dopo error: toast rosso con messaggio
- **Card "Motivo corrente revisione"**: visibile quando status ∈ (`IN_REVIEW`, `REVISED`). Mostra testo motivo più recente + data + nome admin che l'ha richiesta. In `REVISED` serve a ricordare cosa era stato chiesto.
- **Sidebar Activity log**: le nuove voci compaiono automaticamente con descrizione leggibile:
  - "Mario Rossi ha richiesto una revisione della prescrizione — Motivo: [reason]"
  - "Giulia Bianchi ha reinviato la prescrizione revisionata (revisione #2)"
  - "Mario Rossi ha confermato la prescrizione"
- **Badge operation opzionale**: se utile, aggiungere nella tab Panoramica un mini-badge "Prescrizione in revisione" quando `prescription.status = IN_REVIEW`, per segnalare che la lavorazione è in attesa del customer pur restando `IN_PROGRESS` a livello di operation. *(Implementazione semplice: badge condizionale, nessuna nuova colonna.)*

### Lato Customer (workspace, tab prescrizione dell'operation)

- **Badge stato**: stessa estensione di `PrescriptionStatusBadge.vue`.
- **Banner/alert "Revisione richiesta"**: visibile solo quando `status = IN_REVIEW`. Contiene:
  - icona warning + titolo "Revisione richiesta dall'admin"
  - testo completo dell'ultimo motivo (`latest_revision_reason`)
  - data richiesta + nome admin
- **Bottoni disponibili per stato** (workspace):
  - `DRAFT`: **Modifica**, **Invia prescrizione** (invariati)
  - `SENT`: sola lettura (invariato)
  - `IN_REVIEW`: **Modifica prescrizione**, **Invia revisione** (nuovi; "Invia revisione" apre lo stesso dialog disclaimer legale usato per il primo invio — il testo può essere adattato ma non è obbligatorio per questa iterazione)
  - `REVISED`: sola lettura + messaggio informativo "Prescrizione revisionata inviata, in attesa di conferma"
  - `CONFIRMED`: sola lettura (invariato)
- **Toast post-invio revisione**: verde, "Prescrizione revisionata inviata correttamente".
- **Errore validazione al reinvio**: stessi messaggi inline già usati al primo invio.
- **Cronologia storica lato customer**: non esposta in UI in questa iterazione (la sidebar activity log è attualmente disponibile solo lato admin area). Il customer vede solo il motivo corrente. Se in futuro serve, si potrà estendere `ActivityLogController` con supporto per richieste autenticate come customer con filtri appropriati.

### Email

- **Email a customer (revisione richiesta)** — template `mail.user.request-prescription-revision-for-user`:
  - soggetto: "Mech & Human - Richiesta di revisione prescrizione [TYPOLOGY]"
  - corpo: saluto, indicazione che admin X ha chiesto di revisionare la prescrizione per l'operation Y, **blocco motivo** in evidenza, CTA "Vai alla prescrizione" verso workspace.
- **Email agli admin (prescrizione revisionata)** — template `mail.admin.resubmitted-prescription-for-admin`:
  - soggetto: "Prescrizione revisionata — [TYPOLOGY]"
  - corpo: "Il customer [nome] ha reinviato la prescrizione revisionata (revisione #N) per l'operation [batch_number]", CTA "Vai all'operation" verso admin area.

## Fasi di implementazione

### Fase unica — Revisione prescrizione

- **Scope**: implementare il ciclo completo di revisione (backend + frontend) come descritto nelle sezioni precedenti. La feature è sufficientemente coesa da essere progettata e implementata in una singola sessione; le sottoparti (stati, service, notifiche, UI admin, UI customer, rimozione reset) sono interdipendenti e rilasciarne una senza le altre produrrebbe uno stato inconsistente.
- **File da modificare**:
  - `app/Enums/PrescriptionStatusEnum.php` → aggiunta `IN_REVIEW`, `REVISED`
  - `app/Models/Prescription.php` → accessor `latest_revision_reason`, eventuale estensione hook per log activity (se si preferisce centralizzare)
  - `app/Services/PrescriptionService.php` → nuovo `requestRevision`, estensione `sendPrescription` per caso `IN_REVIEW → REVISED`, estensione `confirmPrescription` per accettare `REVISED`, rimozione `resetPrescription`
  - `app/Http/Controllers/PrescriptionController.php` → nuovo metodo `requestRevision`, rimozione metodo `reset`
  - `app/Http/Controllers/WorkspaceController.php` → `prescriptionsSend` (o nuovo `prescriptionsResubmit`) che gestisce anche `IN_REVIEW → REVISED`
  - `app/Http/Requests/Prescription/RequestRevisionRequest.php` → nuovo Form Request con validazione `reason` (obbligatorio, min:5, max:2000)
  - `app/Policies/PrescriptionPolicy.php` → blocco `delete` per stati non-`DRAFT` (se non già implicito); verifica metodo `update` continua a coprire "Richiedi revisione"
  - `routes/web.php` → nuova route `POST /prescriptions/{prescription}/request-revision`, rimozione route `reset`
  - `app/Notifications/User/RequestPrescriptionRevisionForUser.php` → nuova
  - `app/Notifications/Admin/ResubmittedPrescriptionForAdmin.php` → nuova
  - `resources/views/mail/user/request-prescription-revision-for-user.blade.php` → nuovo template
  - `resources/views/mail/admin/resubmitted-prescription-for-admin.blade.php` → nuovo template
  - `resources/js/types/Prescription.ts` → aggiunta valori enum status
  - `resources/js/components/prescription/PrescriptionStatusBadge.vue` → colori/label nuovi stati
  - `resources/js/pages/operations/partials/PrescriptionTab.vue` (admin) → bottone "Richiedi revisione", dialog motivo, card motivo corrente, rimozione bottone "Resetta", adattamento bottoni per `REVISED`/`IN_REVIEW`
  - `resources/js/pages/workspace/operations/partials/PrescriptionTab.vue` (workspace) → banner motivo revisione, bottoni "Modifica" e "Invia revisione" per stato `IN_REVIEW`, messaggio per `REVISED`
  - eventuale `resources/js/pages/operations/Show.vue` → badge "Prescrizione in revisione" in tab Panoramica (se desiderato)
- **Dipende da**: nessuna fase precedente. Richiede solo che lo stack ActivityLog Spatie sia operativo (è già).
- **Test consigliati** (pattern esistente Pest):
  - feature test admin → `request-revision` da `SENT`, da `CONFIRMED`, da `REVISED`; rifiuto da `DRAFT`/`IN_REVIEW`; validazione motivo; permessi
  - feature test workspace → reinvio revisione da `IN_REVIEW` con validazione; blocco reinvio da `DRAFT` (usa comunque primo invio); blocco delete in `IN_REVIEW`/`REVISED`
  - feature test → verifiche activity log (3 event type) e invio notifiche (`Notification::assertSentTo`)
  - feature test → conferma da `REVISED` funziona; conferma da `IN_REVIEW` fallisce
  - test rimozione reset → verifica che la route e il metodo siano spariti (o che rispondano 404/405)
