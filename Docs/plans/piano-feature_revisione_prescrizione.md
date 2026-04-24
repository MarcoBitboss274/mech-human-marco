# Piano: Revisione Prescrizione

**Specifiche**: [Docs/specs/specifiche-feature_revisione_prescrizione.md](../specs/specifiche-feature_revisione_prescrizione.md)

## Stato del codebase

**Da riutilizzare così com'è**:
- [app/Models/Operation.php:13-19](../../app/Models/Operation.php#L13-L19) usa già `LogsActivity` di Spatie → gli event custom si agganciano qui con `activity()->performedOn($operation)`.
- [app/Http/Controllers/ActivityLogController.php](../../app/Http/Controllers/ActivityLogController.php) già serve `activities` per `subject_type = operation` → la sidebar admin vede i nuovi event senza modifiche backend, serve solo estendere payload con `properties` (vedi sotto).
- [resources/js/components/activity/ActivitySlider.vue:123](../../resources/js/components/activity/ActivitySlider.vue#L123) mostra `activity.event` come label → basta mappare lato frontend i nuovi event in etichette leggibili, nessuna nuova offcanvas.
- [app/Policies/PrescriptionPolicy.php:37-40](../../app/Policies/PrescriptionPolicy.php#L37-L40) `update()` già delega a `prescriptions.edit` → usato per gate di `requestRevision`.
- [app/Services/PrescriptionService.php:81-102](../../app/Services/PrescriptionService.php#L81-L102) contiene già la pipeline `sendPrescription`/`validatePrescription` → il reinvio revisione estende `sendPrescription` ramificando sullo stato in ingresso.
- [resources/js/pages/operations/partials/PrescriptionTab.vue](../../resources/js/pages/operations/partials/PrescriptionTab.vue) e [resources/js/pages/workspace/operations/partials/PrescriptionTab.vue](../../resources/js/pages/workspace/operations/partials/PrescriptionTab.vue) hanno già il pattern bottoni-condizionati-per-status + dialog modal (Disclaimer) → replicare pattern per "Richiedi revisione" e "Invia revisione".
- Pattern notifica esistente: [app/Notifications/User/SendPrescriptionForUser.php](../../app/Notifications/User/SendPrescriptionForUser.php) + template [resources/views/mail/user/send-prescription-for-user.blade.php](../../resources/views/mail/user/send-prescription-for-user.blade.php) → copiare struttura per le due nuove notifiche.
- [resources/js/components/prescriptions/PrescriptionStatusBadge.vue:21-23](../../resources/js/components/prescriptions/PrescriptionStatusBadge.vue#L21-L23) pattern "classi condizionate per status" → aggiungere due rami per `in_review` / `revised`.

**Da creare da zero**:
- `PrescriptionController::requestRevision()` + FormRequest `RequestRevisionRequest` con validazione `reason` (min:5, max:2000).
- Metodo `PrescriptionService::requestRevision()`.
- Due Notification + due Mailable blade.
- Dialog motivo revisione (admin) + banner motivo corrente (entrambe le tab) — niente componenti condivisi da estrarre, è codice locale alla singola tab.

**Convenzioni del progetto**:
- Enum con trait `BaseEnum` e metodo `label()` ([app/Enums/PrescriptionStatusEnum.php:7-20](../../app/Enums/PrescriptionStatusEnum.php#L7-L20)).
- Controller sottili, logica nel `*Service` statico.
- Route admin in gruppo `role:superadmin|admin|agent` a [routes/web.php:122-125](../../routes/web.php#L122-L125); route workspace in gruppo `role:customer,onboarding` a [routes/web.php:198-200](../../routes/web.php#L198-L200).
- Frontend usa `BbDialog`, `BbButton`, `BbCheckbox` da `bitboss-ui` + `router.post` di Inertia + `useMainToast` per feedback.

## Diff rispetto alle specifiche

- **Specifiche dicono** "accessor `latest_revision_reason` legge `properties->reason`" — **realtà**: [app/Http/Controllers/ActivityLogController.php:52-62](../../app/Http/Controllers/ActivityLogController.php#L52-L62) oggi **non espone** `properties` nel JSON (ritorna solo id/created_at/event/description/causer) — **implicazione**: la sidebar vedrà solo `event` e `description`; per non ri-fetchare lato frontend, salveremo il motivo sia in `properties.reason` (per accessor PHP) sia nella `description` testuale (per UI sidebar). Nessuna modifica al controller.
- **Specifiche dicono** "bottone 'Invia revisione' apre lo stesso dialog disclaimer legale del primo invio" — **realtà**: il disclaimer legale ha senso al primo invio, non al reinvio (il consenso è già stato dato). **Implicazione**: proposta di skippare il dialog di disclaimer al reinvio (il bottone "Invia revisione" fa POST diretto con conferma nativa `confirm()` o semplice toast "Invio in corso"). Da confermare con utente — cfr. Domande aperte.
- **Specifiche dicono** "policy nega destroy in stati ≠ DRAFT (comportamento già attuale, esplicitato dalla feature)" — **realtà**: [app/Policies/PrescriptionPolicy.php:45-48](../../app/Policies/PrescriptionPolicy.php#L45-L48) delega solo al permesso `prescriptions.destroy` senza alcun check di stato; inoltre [app/Http/Controllers/PrescriptionController.php:57-62](../../app/Http/Controllers/PrescriptionController.php#L57-L62) non verifica lo stato. **Implicazione**: va aggiunto un check esplicito di stato nella Policy (`delete` ritorna false se status ≠ `DRAFT`) — non è quindi solo un "esplicitare" ma un vincolo nuovo. Probabilmente già il comportamento atteso ma assente.
- **Specifiche dicono** "nuovo accessor `latest_revision_reason`" che interroga ogni volta la tabella `activity_log` — **realtà**: tabella `activity_log` può diventare grande; l'accessor va chiamato solo in stato `IN_REVIEW` e comunque mirato con indice su `(subject_type, subject_id, event, created_at)` che Spatie non crea di default. **Implicazione**: aggiungere migration con indice composito, oppure limitare il fetch a `take(1)->orderByDesc('id')` e accettare il costo (bassissimo per singola operation).
- **Specifiche dicono** "`confirmPrescription` estendere l'accettazione degli stati d'ingresso a `SENT` e `REVISED`" — **realtà**: [app/Services/PrescriptionService.php:107-114](../../app/Services/PrescriptionService.php#L107-L114) non controlla lo stato in ingresso oggi (lo forza comunque a `CONFIRMED`). **Implicazione**: serve aggiungere un guard esplicito (ValidationException se status ∉ [SENT, REVISED]) altrimenti il ciclo è forzabile da qualsiasi stato.
- **Specifiche dicono** "`resetPrescription` rimosso" — **realtà**: [app/Services/PrescriptionService.php:119-128](../../app/Services/PrescriptionService.php#L119-L128) esiste e aggiorna anche lo `OperationService::updateStatus(... DRAFT)`. **Implicazione**: la rimozione tocca tre punti (service, controller, route, frontend bottone) — già nelle specifiche ma va verificato che nessun test/seeder/fixture chiami `resetPrescription` o la route `prescriptions.reset`.

## Step ordinati

### 1. Enum + Policy + Model

1.1 Estendere `PrescriptionStatusEnum` con `IN_REVIEW` e `REVISED` (+ `label()`). **Gate**: `php artisan tinker` → `PrescriptionStatusEnum::cases()` elenca 5 casi.
1.2 Aggiungere check stato in `PrescriptionPolicy::delete` (return false se status ≠ DRAFT). **Gate**: nuovo test Pest `delete_is_denied_on_non_draft_prescription`.
1.3 Aggiungere accessor `latest_revision_reason` al model `Prescription` (query Activity con eager cap su 1 riga). **Gate**: test unit che salva un activity manualmente e verifica l'accessor.

### 2. Service + Notification

2.1 Nuovo metodo `PrescriptionService::requestRevision(Prescription, string $reason, User $causer)` che: guard status ∈ [SENT, CONFIRMED, REVISED], update status a IN_REVIEW, `activity()->performedOn($operation)->causedBy($causer)->withProperties([...])->event('prescription_revision_requested')->log("{$name} ha richiesto una revisione: {$reason}")`, poi `NotificationService::sendToUser($prescription->user, new RequestPrescriptionRevisionForUser(...))`. **Gate**: test feature `admin_can_request_revision_from_sent/confirmed/revised` + `Notification::assertSentTo` + assert record in `activity_log`.
2.2 Estendere `PrescriptionService::sendPrescription` per gestire ramo `IN_REVIEW → REVISED`: se status corrente = IN_REVIEW → validazione → update solo `status` + `send_at`, log activity `prescription_resubmitted` con `revision_number`, notifica `ResubmittedPrescriptionForAdmin` via `sendToAdmins`. Se status = DRAFT → branch esistente invariato. **Gate**: test `customer_can_resubmit_revised_prescription_sets_status_to_revised` + assert `expire_at` invariato + assert Notification.
2.3 Estendere `PrescriptionService::confirmPrescription` con guard `status ∈ [SENT, REVISED]` e log activity `prescription_confirmed`. **Gate**: test `confirm_is_denied_on_in_review` + test `confirm_works_from_revised_and_sets_confirmed_at`.
2.4 Rimuovere `PrescriptionService::resetPrescription`. **Gate**: `grep -rn "resetPrescription\|prescriptions.reset" app resources` ritorna solo occorrenze che sto rimuovendo.
2.5 Creare `RequestPrescriptionRevisionForUser` (mail) + blade `mail/user/request-prescription-revision-for-user.blade.php` copiando la struttura di `SendPrescriptionForUser`. **Gate**: `Notification::fake()` + assert soggetto.
2.6 Creare `ResubmittedPrescriptionForAdmin` (mail) + blade `mail/admin/resubmitted-prescription-for-admin.blade.php` copiando la struttura di `SendPrescriptionForAdmin`. **Gate**: idem.

### 3. Controller + Route + FormRequest

3.1 Creare `app/Http/Requests/Prescription/RequestRevisionRequest.php` con `reason: required|string|min:5|max:2000` e `authorize()` = `Gate::allows('update', $this->route('prescription'))`. **Gate**: test `Validator` rifiuta stringhe < 5 char.
3.2 Aggiungere `PrescriptionController::requestRevision(RequestRevisionRequest, Prescription)` che chiama il service. Rimuovere `PrescriptionController::reset()`. **Gate**: `php artisan route:list | grep prescription` mostra `request-revision` e **non** mostra `reset`.
3.3 Aggiornare `routes/web.php`: aggiungere POST `prescriptions/{prescription}/request-revision`, rimuovere POST `prescriptions/{prescription}/reset`. **Gate**: idem sopra.
3.4 Estendere `WorkspaceController::prescriptionsSend` per accettare prescrizioni in stato `IN_REVIEW` (oggi accetta di fatto tutti gli stati via service, ma va aggiunta la abilitazione policy workspace per modifica in `IN_REVIEW`; verificare `WorkspaceAbilityEnum::PRESCRIPTIONS_SEND` basta). **Gate**: test feature workspace per reinvio.

### 4. Frontend — Admin

4.1 Estendere `PrescriptionStatusBadge.vue` con classi/label per `in_review` e `revised` (arancio/azzurro). **Gate**: smoke visivo su storybook o in page (status forzato via tinker).
4.2 Aggiornare `resources/js/types/Prescription.ts` con i due nuovi valori status (+ eventuale campo virtuale `latest_revision_reason?: string | null`). **Gate**: `npm run typecheck` passa.
4.3 Modificare `resources/js/pages/operations/partials/PrescriptionTab.vue`:
   - rimuovere bottone "Resetta" e handler `resetPrescription`,
   - aggiungere bottone "Richiedi revisione" visibile per status ∈ [sent, confirmed, revised] + dialog con textarea motivo obbligatoria,
   - aggiungere card "Motivo revisione corrente" visibile per status ∈ [in_review, revised] che legge `latestPrescription.latest_revision_reason`.
   **Gate**: dev server + click-test manuale dei 5 stati (mock prescription via seeder o tinker).
4.4 Aggiornare label event nella sidebar: in `ActivitySlider.vue` mappare `prescription_revision_requested`, `prescription_resubmitted`, `prescription_confirmed` in stringhe umane (fallback già presente via `description`). **Gate**: aprire slider su operation reale con log inseriti a mano, verificare testo.

### 5. Frontend — Workspace (customer)

5.1 Modificare `resources/js/pages/workspace/operations/partials/PrescriptionTab.vue`:
   - aggiungere banner warning "Revisione richiesta" con testo `latest_revision_reason` per status = in_review,
   - aggiungere bottone "Modifica prescrizione" (apre wizard edit come in draft) visibile per status = in_review,
   - aggiungere bottone "Invia revisione" visibile per status = in_review → POST `workspace.prescriptions.send` (stessa route),
   - aggiungere messaggio informativo "In attesa di conferma" per status = revised.
   **Gate**: dev server + click-test manuale come sopra, con utente customer.
5.2 Verificare che il wizard edit (`workspace.operations.edit-wizard`) sia aperto anche in status `IN_REVIEW` lato backend (il wizard oggi probabilmente verifica solo `operation` non `prescription.status`; check: [WorkspaceController::operationsUpdateWizard](../../app/Http/Controllers/WorkspaceController.php)). **Gate**: test feature che modifica una prescrizione IN_REVIEW via wizard.

### 6. Test finali + pulizia

6.1 Far girare `php artisan test --filter=Prescription` e `npm run typecheck`. **Gate**: tutti verdi.
6.2 Smoke test end-to-end manuale seguendo lo scenario della bozza originale (12 step). **Gate**: checklist su [Docs/ui/UI_ACTIVITY_LOG.md](../../docs/ui/UI_ACTIVITY_LOG.md) aggiornata se ci sono modifiche CSS.
6.3 Cercare residui: `grep -rn "reset\b" app/Services/PrescriptionService.php app/Http/Controllers/PrescriptionController.php routes resources/js/pages` per assicurarsi che `reset` sia stato davvero rimosso. **Gate**: zero occorrenze rilevanti.

## Rischi e punti di attenzione

- **Indice su `activity_log`**: l'accessor `latest_revision_reason` fa una query `WHERE subject_type = ? AND subject_id = ? AND event = ?`. Spatie di default ha indice solo su `(subject_type, subject_id)`. Per una singola operation il costo è trascurabile (poche righe), ma valutare di aggiungere una migration che crea indice composto con `event` se i volumi crescono. Non bloccante.
- **`ActivityLogController` non espone `properties`**: se in futuro serve leggere `revision_number` o `reason` dal frontend via sidebar (non solo da accessor PHP), va esteso il JSON. Oggi si risolve scrivendo stringhe complete in `description` del log.
- **Dialog disclaimer al reinvio**: attualmente il customer firma il disclaimer legale ad ogni invio. Se al reinvio si skippa, attenzione a eventuali requisiti legali. Vedi Domande aperte.
- **Policy delete più restrittiva**: aggiungere check stato in `PrescriptionPolicy::delete` può rompere UI/test esistenti che assumono di poter eliminare prescrizioni non-DRAFT (improbabile, ma da verificare via `grep destroy prescriptions`).
- **`OperationService::updateStatus(... DRAFT)`**: `resetPrescription` riportava anche l'operation a `DRAFT`. Con la sua rimozione nessuna azione porta più l'operation a DRAFT dalla tab prescrizione — verificare se altri flussi dipendevano da questa transizione (improbabile, ma da controllare).
- **Log sidebar visibilità lato workspace**: l'ActivitySlider oggi è montato solo in `resources/js/pages/operations/Show.vue` (admin). Lato workspace non è esposta e le specifiche confermano che non va esposta ora; attenzione se qualcuno in futuro la aggancia senza estendere i controlli di autorizzazione in `ActivityLogController` (oggi richiede `operations.activity.view`, permesso admin).
- **Seeder/fixture di test**: verificare che `database/seeders` e `database/factories` non includano ancora stati solo `draft/sent/confirmed` come ENUM DB-side (colonna è `string` non `enum` SQL, quindi safe). Se la tabella usa enum nativo MySQL servirebbe migration — **non è il caso qui** (colonna string, verificato nelle specifiche).
- **i18n**: nuove etichette (`In revisione`, `Revisionata`, `Richiedi revisione`, `Motivo della revisione`, banner customer) — il progetto usa `useI18n` con `t()` già in uso; aggiungerle al file traduzioni se esiste (cercare `lang/`).

## Domande aperte

- **Dialog disclaimer legale al reinvio**: le specifiche dicono di riusare lo stesso dialog del primo invio, ma il consenso è già stato prestato. Opzioni: (a) riusare dialog identico (semplice, leggermente ridondante), (b) skippare dialog e submit diretto con toast, (c) mostrare dialog semplificato "Confermi invio della revisione?" senza checkbox. **Blocca step 5.1**. Ipotesi default se non risposto: (a) per coerenza UX e sicurezza legale.

## Ordine di commit suggerito

1. `feat(enum): add IN_REVIEW and REVISED to PrescriptionStatusEnum` — enum, badge, types TS. Verde senza altre modifiche perché nessuno ancora produce quegli stati.
2. `refactor(prescription): remove reset flow` — service method, controller method, route, bottone frontend admin. Verde con test.
3. `feat(prescription): request revision endpoint and service` — FormRequest, controller method, service method, notifica user, policy delete guard, accessor `latest_revision_reason`. Test feature backend.
4. `feat(prescription): resubmit revision flow` — estensione `sendPrescription` + `confirmPrescription` guards + notifica admin. Test feature backend.
5. `feat(prescription-ui): admin request-revision dialog and reason card` — modifiche `operations/partials/PrescriptionTab.vue` + etichette sidebar. Check visivo.
6. `feat(prescription-ui): workspace revision banner and resubmit button` — modifiche `workspace/operations/partials/PrescriptionTab.vue`. Check visivo.
7. `test(prescription): end-to-end revision cycle` — test feature che simula lo scenario completo della bozza (richiesta → reinvio → richiesta → reinvio → conferma).
