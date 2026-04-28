# Piano: Aggiornamento Feature Revisione Prescrizione

**Specifiche**: [specifiche-revisione_aggiornamento.md](../specs/specifiche-revisione_aggiornamento.md)

## Stato del codebase

**Esiste già e va riutilizzato/modificato**
- [app/Enums/PrescriptionStatusEnum.php:11-12](../../app/Enums/PrescriptionStatusEnum.php#L11-L12) — enum string-backed con `in_review` e `revised` da rimuovere. Campo DB è `VARCHAR` (non ENUM MySQL) → nessuna migration di ALTER TYPE serve.
- [app/Services/PrescriptionService.php:157-219](../../app/Services/PrescriptionService.php#L157-L219) — `requestRevision()` + `resubmitRevision()` attuali toccano `status`; vanno riscritti. Helper `logOnOperation()` a [riga 227-245](../../app/Services/PrescriptionService.php#L227-L245) da riusare per i nuovi eventi activity log.
- [app/Http/Requests/Prescription/RequestRevisionRequest.php:21](../../app/Http/Requests/Prescription/RequestRevisionRequest.php#L21) — regola `reason` già `required|string|min:5|max:2000`; riusare/rinominare in `OpenRevisionRequest` + `AddRevisionReasonRequest`.
- [routes/web.php:124](../../routes/web.php#L124) — unica route revisione esistente (`prescriptions.request-revision`); le nuove route si aggiungono nello stesso gruppo.
- [resources/js/components/prescriptions/PrescriptionStatusBadge.vue:28](../../resources/js/components/prescriptions/PrescriptionStatusBadge.vue#L28) — switch-case hardcoded su status letterali; va sostituito con logica "se `active_revision` presente → badge In revisione".
- [app/Notifications/User/RequestPrescriptionRevisionForUser.php](../../app/Notifications/User/RequestPrescriptionRevisionForUser.php) e [app/Notifications/Admin/ResubmittedPrescriptionForAdmin.php](../../app/Notifications/Admin/ResubmittedPrescriptionForAdmin.php) — già pronte, solo eventuale adattamento del payload `reason`.
- [Docs/ui/UI_ACTIVITY_LOG.md](../ui/UI_ACTIVITY_LOG.md) — presente, va aggiornato se tocchiamo CSS override.

**Convenzioni in uso da seguire**
- Service thin + controller thin, tutte le transizioni passano dal service (pattern già in `PrescriptionService`).
- Activity log con subject = `Operation` (non `Prescription`) via `logOnOperation()` — mantenere.
- Form requests separate per ogni azione (anche se vuote di input) per tenere l'autorizzazione centralizzata.
- Ziggy operativo ([resources/js/app.ts:13](../../resources/js/app.ts#L13)) → nei componenti Vue usare `route('...')`, non stringhe hardcoded.
- Test Pest; `RefreshDatabase` sui feature test.

**Non esiste / da creare da zero**
- Tabelle `revisions`, `revision_reasons` + modelli relativi.
- `PrescriptionFactory` (assente) — serve per i test Pest.
- Nessun test Pest su revisione oggi → si scrivono ex novo (le specifiche dicono "aggiornare i test esistenti", ma non ne esiste nessuno).
- Notifica `ClosedPrescriptionRevisionForUser` + blade.

## Diff rispetto alle specifiche

- **Specifiche**: "rimuovere i valori `IN_REVIEW`/`REVISED` dall'enum DB (se il campo è ENUM MySQL)". **Realtà**: il campo è `VARCHAR`, nessuna migration di `ALTER ENUM` serve — basta una `UPDATE` di normalizzazione dati + rimozione dei case dal PHP enum.
- **Specifiche**: "aggiornare test esistenti della feature revisione". **Realtà**: zero test Pest toccano "revision". I test vanno scritti ex novo, non aggiornati.
- **Specifiche**: assumono una `PrescriptionFactory` pronta per supportare i test. **Realtà**: non esiste alcuna factory; va creata (e di conseguenza anche `RevisionFactory` / `RevisionReasonFactory`).
- **Specifiche**: "unique index parziale su `(prescription_id) WHERE closed_at IS NULL`". **Realtà**: il progetto usa MySQL standard — indici parziali non supportati. Vincolo va implementato a livello applicativo nel service (`openRevision()` apre in transazione dopo check + lock), non a schema.

## Step ordinati

### 1 — Schema + modelli
1.1 Migration: creare `revisions` e `revision_reasons` + migrazione dati (`in_review`→`sent`, `revised`→`confirmed`) + rimozione case enum PHP. **Gate**: `php artisan migrate:fresh` verde su DB pulito; `php artisan tinker` → `Prescription::factory()->create()` non errora.
1.2 Creare modelli `Revision`, `RevisionReason` con relazioni come da specifiche; aggiungere `revisions()` e `activeRevision()` su `Prescription`. **Gate**: test unit `Prescription::factory()->has(Revision::factory())->create()->activeRevision` non null.

### 2 — Service + form requests
2.1 Aggiungere in `PrescriptionService` i metodi `openRevision`, `addRevisionReason`, `closeRevision`; riscrivere `resubmitRevision` per NON toccare `status` e aggiornare `last_submitted_at`. Tutti in transazione con lock pessimistico su `prescriptions` per evitare la doppia revisione aperta. **Gate**: test Pest "non permette due revisioni aperte" passa (anche in concorrenza simulata).
2.2 Creare `OpenRevisionRequest`, `AddRevisionReasonRequest`, `CloseRevisionRequest`; rimuovere o rinominare `RequestRevisionRequest`. **Gate**: `php artisan route:list | grep revision` mostra le 3 nuove route con le request giuste.
2.3 Verificare/estendere `PrescriptionPolicy` (gate `openRevision`, `addReason`, `close`). **Gate**: feature test "customer non può aprire revisione" → 403.

### 3 — Controller + routing + notifiche
3.1 Aggiungere endpoint in `PrescriptionController` (3 azioni admin); adattare `WorkspaceController::prescriptionsSend` alla nuova regola (consenti solo se `DRAFT` o `active_revision`). **Gate**: test Pest "customer riceve 403 se invia senza revisione aperta su SENT".
3.2 Registrare route in `routes/web.php` coerenti con naming esistente (`prescriptions.revisions.*`). **Gate**: `php artisan route:list` mostra tutte le nuove route, nessuna duplicata.
3.3 Creare notifica `ClosedPrescriptionRevisionForUser` + blade in `resources/views/mail/user/`; invocarla in `closeRevision`. **Gate**: test Pest `Notification::fake()` + assertSentTo sul customer.
3.4 Adattare eventi activity log (`prescription_revision_opened`, `_reason_added`, `_resubmitted`, `_closed`). **Gate**: grep `activity(` negli step service mostra i 4 eventi nuovi, il vecchio `_requested` rimosso.

### 4 — Frontend
4.1 Aggiornare `resources/js/types/Prescription.ts`: rimuovere unioni con `'in_review'`/`'revised'`, aggiungere `active_revision` con `last_submitted_at`, `opened_at`, `reasons[]`. **Gate**: `npm run build` senza errori TS.
4.2 Refactor `PrescriptionStatusBadge.vue`: se `active_revision` present → mostra "In revisione" arancio; altrimenti switch sui 3 stati rimasti. **Gate**: check visivo manuale su una prescrizione seed.
4.3 Admin `operations/partials/PrescriptionTab.vue`: nuovi pulsanti "Richiedi revisione" / "Aggiungi motivo" / "Chiudi revisione" con dialog; card storico motivi; badge "nuove modifiche" con logica da spec. **Gate**: test E2E manuale del flusso completo da admin.
4.4 Customer `workspace/operations/partials/PrescriptionTab.vue`: banner + storico motivi (testo + data), bottone "Invia modifiche" solo se `DRAFT || active_revision`, resto read-only. **Gate**: visita pagina come customer con revisione aperta → vede banner, testa rinvio → toast ok.

### 5 — Test
5.1 Creare `PrescriptionFactory`, `RevisionFactory`, `RevisionReasonFactory` (state `open`, `closed`). **Gate**: `php artisan test --filter=Factory` ok.
5.2 Scrivere feature test Pest nei casi: apertura su DRAFT bloccata, doppia apertura bloccata, aggiunta motivo consentita solo dopo invio customer, chiusura senza invio OK, race chiusura → 422, customer invia senza revisione → 403, `last_submitted_at` sovrascritto a rinvii multipli, notifica chiusura partita. **Gate**: `php artisan test tests/Feature/Prescription` tutto verde.
5.3 Smoke test browser (Pest 4): visita prescrizione admin+customer, apri+rispondi+chiudi senza JS errors. **Gate**: suite browser verde.

### 6 — Docs + cleanup
6.1 Aggiornare [Docs/ui/UI_ACTIVITY_LOG.md](../ui/UI_ACTIVITY_LOG.md) se introdotti override CSS per il nuovo banner/badge. **Gate**: file aggiornato con entry datata.
6.2 Deprecare / rimuovere [Docs/specs/specifiche-feature_revisione_prescrizione.md](../specs/specifiche-feature_revisione_prescrizione.md) (contenuto superato): aggiungere una nota in testa "Sostituita da specifiche-revisione_aggiornamento.md" e link. **Gate**: nota presente, link corretto.

## Rischi e punti di attenzione

- **MySQL senza indici parziali**: il vincolo "max una revisione aperta" è solo applicativo. Usare `lockForUpdate()` in `openRevision()` dentro transaction per reggere la concorrenza (due admin che aprono contemporaneamente).
- **Activity log storico**: gli eventi vecchi `prescription_revision_requested` restano in `activity_log`. Non cancellarli. Valutare se mantenere anche il nuovo nome per compat o rinominarli in migration (consigliato: non toccare lo storico).
- **`latestRevisionReason()` accessor** su `Prescription` ([Model riga 215-234](../../app/Models/Prescription.php#L215-L234)) oggi legge da `activity_log`. Con la nuova tabella va sostituito con query su `revision_reasons` dell'`active_revision`. Punto facile da dimenticare: l'accessor è esposto nel type TS.
- **`WorkspaceController::prescriptionsSend`** oggi riusa `resubmitRevision` per qualsiasi prescrizione in `IN_REVIEW`. Dopo il refactor la gate cambia a "solo se `active_revision`". Verificare che ogni chiamata lato customer sia coerente — rischio 403 inaspettati in scenari edge (es. revisione chiusa nel frattempo: è il caso 422 da spec, va gestito prima del generic 403).
- **Badge "nuove modifiche"**: la logica della spec confronta `last_submitted_at` con `max(opened_at, last_reason.created_at)`. Se due admin diversi vedono il badge, uno risponde, l'altro no → per l'altro il badge sparisce quando ricarica. Accettato.
- **Deploy**: la migration fa `UPDATE` su `prescriptions` in produzione. Su volumi grandi valutare lock. Per questo progetto (dev) non critico.
- **Pest browser smoke**: nuovi bottoni e dialog possono introdurre click-outside / aria issues. Testare anche teclado/escape.

## Domande aperte

Nessuna.

## Ordine di commit suggerito

1. `feat(db): tabelle revisions + revision_reasons e migrazione stati prescription`
2. `feat(models): Revision + RevisionReason + relazioni su Prescription + factories`
3. `refactor(service): PrescriptionService open/add/close/resubmit senza toccare status`
4. `feat(http): form requests, controller actions, routes e policy per revisioni`
5. `feat(notifications): ClosedPrescriptionRevisionForUser + activity log nuovi eventi`
6. `feat(frontend): types + badge + tab admin + tab customer per nuovo modello revisioni`
7. `test: copertura Pest feature + browser smoke per flusso revisione`
8. `docs: aggiorna UI_ACTIVITY_LOG + nota deprecata su vecchie specifiche`
