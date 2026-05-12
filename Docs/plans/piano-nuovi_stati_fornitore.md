# Piano: Nuovi stati fornitore — vista solo Produzione

**Specifiche**: [specifiche-nuovi_stati_fornitore.md](../specs/specifiche-nuovi_stati_fornitore.md)

## Stato del codebase

Tutto ciò che le specifiche vogliono "spostare/eliminare" esiste già: il piano è **sottrattivo** sul lato Fornitore e **additivo** su un'unica area Admin.

Riutilizzabile direttamente:
- [CaseStatusBadge.vue](../../resources/js/components/operations/CaseStatusBadge.vue) — resta valido per la nuova UI admin.
- [CaseStatusEnum](../../app/Enums/CaseStatusEnum.php) — invariato.
- Accessor [`Operation::getCaseStatusAttribute`](../../app/Models/Operation.php#L77) e scope `withSupplierPivot` — invariati.
- Service esistenti [`markCaseCompleted`](../../app/Services/OperationService.php#L1334) e [`reopenCase`](../../app/Services/OperationService.php#L1379): vengono **rinominati** e adattati alla firma Admin (vedi Diff).
- [`OperationService::getAdminShowData`](../../app/Services/OperationService.php#L730) già espone `supplier_completed_at` ([L764](../../app/Services/OperationService.php#L764)) — il payload Admin ha già il dato; basta esporre anche `case_status`.
- [ProductionsTab.vue admin](../../resources/js/pages/operations/partials/ProductionsTab.vue) — qui va aggiunta la mini-sezione "Stato lavorazione".

Da rimuovere senza sostituto (creati nel piano precedente):
- `WorkspaceSupplierController::operationsCloseCase` / `operationsReopenCase` ([L373-393](../../app/Http/Controllers/WorkspaceSupplierController.php#L373-L393)).
- Route `workspace.supplier.operations.close-case` / `reopen-case` ([routes/web.php L225-226](../../routes/web.php#L225)).
- Notifiche [`SupplierCaseCompletedForAdmin`](../../app/Notifications/Admin/SupplierCaseCompletedForAdmin.php) e [`SupplierCaseReopenedForAdmin`](../../app/Notifications/Admin/SupplierCaseReopenedForAdmin.php).
- Ability `SUPPLIER_OPERATIONS_COMPLETE` ([WorkspaceAbilityEnum L41](../../app/Enums/WorkspaceAbilityEnum.php#L41)).
- Filtro `case_status` + addSelect `case_status` nell'`operationsIndex` fornitore ([L194-211](../../app/Http/Controllers/WorkspaceSupplierController.php#L194-L211)).
- Sul payload `operationsShow` fornitore ([L290-300](../../app/Http/Controllers/WorkspaceSupplierController.php#L290-L300)): `supplier_completed_at` e `production_status` settati a mano. Il `production_status` va tenuto, `supplier_completed_at` no.
- Colonna + filtro `case_status` + badge `CaseStatusBadge` in [Index.vue fornitore](../../resources/js/pages/workspace/supplier/operations/Index.vue) e header + dialog Chiudi/Riapri in [Show.vue fornitore](../../resources/js/pages/workspace/supplier/operations/Show.vue).
- Test relativi in [CaseAndProductionStatusTest.php](../../tests/Feature/CaseAndProductionStatusTest.php) (vedi Rischi).

Pattern già stabilito da seguire:
- Le 4 azioni produzione Admin ([OperationController L483-525](../../app/Http/Controllers/OperationController.php#L483)) usano `Gate::authorize('manageProduction', $operation)` + `to_route('operations.show', ...)`. **Stesso pattern** per le 2 nuove azioni case.
- I bottoni in [ProductionsTab.vue admin](../../resources/js/pages/operations/partials/ProductionsTab.vue#L48-L94) usano `router.post` con `preserveScroll: true` + toast. **Stesso pattern**.

## Diff rispetto alle specifiche

- **Specifiche dicono** di rinominare `markCaseCompleted`/`reopenCase` aggiungendo "ByAdmin" — **realtà**: le firme attuali sono `(Operation, Supplier, User $causer)` e ricavano il pivot dal supplier passato. Lato Admin il fornitore non è un input dell'azione, va dedotto da `operation->selectedSupplier()` — **implicazione**: le firme nuove diventano `(Operation, User $causer)`; il vincolo "fornitore selezionato esistente" diventa un check interno (404/ValidationException). Non un semplice rename: refactor della firma.
- **Specifiche dicono** "rimuovere `case_status` da `$appends` del model" e "esporlo accessibile lato Admin" — **realtà**: oggi `case_status` è in [$appends](../../app/Models/Operation.php#L66) e si appoggia a `withSupplierPivot($supplierId)` (richiede supplier id, oggi solo lato fornitore). Lato Admin il payload `getAdminShowData` non chiama `withSupplierPivot`. **Implicazione**: per esporre `case_status` lato admin senza N+1, leggere `supplier_completed_at` direttamente nel payload admin (come già fa [L764](../../app/Services/OperationService.php#L764)) e derivare `case_status` lato controller (`open`/`completed`), oppure aggiungere alla query admin uno scope analogo a `withSupplierPivot` ma per il fornitore selezionato. Semplificare: aggiungere `case_status` come campo derivato nel payload admin, non come accessor globale.
- **Specifiche dicono** "rimuovere filtro `case_status` BE — mantenere `production_status` lato BE, rinominare label UI in 'Stato'" — **realtà**: il param query BE attualmente si chiama `production_status`. Confermo: lascio `production_status` nei query param/filters per non rompere bookmark, cambio solo la label nei `<BbSelect>` e nella colonna.
- **Specifiche dicono** di rinominare le descrizioni activity log da "Hai segnato/riaperto" a "M&H ha segnato/riaperto" — **realtà**: oggi nel service la stringa è composta col nome del causer ("`{nome} ha segnato/riaperto`", [L1370](../../app/Services/OperationService.php#L1370)). **Implicazione**: il prefisso "Hai" non esiste — basta sostituire la stringa generica con "M&H ha segnato/riaperto la lavorazione" se vogliamo evitare di leakare il nome admin al fornitore. Ma siccome stiamo rimuovendo `case_completed`/`case_reopened` dalla whitelist fornitore, il fornitore non li vedrà mai → posso lasciare la stringa col nome causer (più informativo per l'Admin).

## Step ordinati

### A. Backend — service e controller

1. Refactor [OperationService](../../app/Services/OperationService.php):
   - Sostituire `markCaseCompleted(Operation, Supplier, User)` con `markCaseCompletedByAdmin(Operation, User)`: ricavare il selected supplier da `operation->selectedSupplier()->first()`; se assente, `ValidationException`. Idempotenza invariata. Rimuovere chiamata a `SupplierCaseCompletedForAdmin`.
   - Analogo per `reopenCaseByAdmin`.
   - Rimuovere `use` di `SupplierCaseCompletedForAdmin` / `SupplierCaseReopenedForAdmin`.
   - **Gate**: `php -l app/Services/OperationService.php` + `./vendor/bin/pest --filter=CaseAndProductionStatusTest` (alcuni test falliranno: ok, sistemati allo step E).

2. Aggiungere a [OperationController](../../app/Http/Controllers/OperationController.php) i metodi `markCaseCompleted` e `reopenCase` (Admin), pattern identico ai 4 metodi produzione esistenti.
   - **Gate**: `php artisan route:list | grep operations.case` mostra 2 nuove route.

3. Aggiungere le 2 route in [routes/web.php](../../routes/web.php) (`POST /operations/{operation}/case/complete`, `POST /operations/{operation}/case/reopen`) sotto il gruppo admin (vicino alle 4 route produzione [L114-117](../../routes/web.php#L114)).

### B. Backend — pulizia lato Fornitore

4. [WorkspaceSupplierController](../../app/Http/Controllers/WorkspaceSupplierController.php):
   - Rimuovere `operationsCloseCase` + `operationsReopenCase`.
   - Rimuovere `applyCaseStatusFilter` + relativo input `case_status` + chiave `filters.case_status`.
   - Rimuovere `withSupplierPivot` dall'`operationsIndex` (non serve più: `case_status` non è più esposto al fornitore).
   - In `operationsShow` rimuovere il `setAttribute('supplier_completed_at', ...)`. Lasciare `production_status`.
   - **Gate**: `php artisan route:list | grep workspace.supplier.operations` — non deve apparire `close-case` né `reopen-case`.

5. Rimuovere le 2 route `workspace.supplier.operations.close-case` / `reopen-case`.

6. Eliminare i file:
   - `app/Notifications/Admin/SupplierCaseCompletedForAdmin.php`
   - `app/Notifications/Admin/SupplierCaseReopenedForAdmin.php`
   - Caso `SUPPLIER_OPERATIONS_COMPLETE` dall'[WorkspaceAbilityEnum](../../app/Enums/WorkspaceAbilityEnum.php).
   - **Gate**: `grep -rn "SupplierCaseCompletedForAdmin\|SupplierCaseReopenedForAdmin\|SUPPLIER_OPERATIONS_COMPLETE" app/ resources/ tests/` → vuoto.

7. [ActivityLogController](../../app/Http/Controllers/ActivityLogController.php): rimuovere `case_completed` e `case_reopened` da `SUPPLIER_VISIBLE_EVENTS`.

### C. Backend — Operation model + payload admin

8. [Operation.php](../../app/Models/Operation.php): togliere `case_status` da `$appends`. Lasciare l'accessor pubblico (è già letto da `withSupplierPivot`, lato admin non lo usiamo).

9. Aggiornare [`getAdminShowData`](../../app/Services/OperationService.php#L730) per esporre `case_status` nel payload: derivarlo da `supplier_completed_at` già letto a [L764](../../app/Services/OperationService.php#L764). Fare lo stesso in [`getAgentShowData`](../../app/Services/OperationService.php#L939) per coerenza.
   - **Gate**: aprire la show admin in dev e verificare che `operation.case_status` arrivi nel payload Inertia.

### D. Frontend

10. [Index.vue fornitore](../../resources/js/pages/workspace/supplier/operations/Index.vue):
    - Rimuovere import `CaseStatusBadge`, colonna `case_status`, filtro `caseStatusModel` + relativo `<BbSelect>`.
    - Rinominare label colonna `production_status` da "Produzione" a "Stato".
    - Rinominare label `<BbSelect>` di `productionStatusModel` in "Stato".
    - **Gate**: visit `/workspace/supplier/operations` con utente supplier → una sola colonna "Stato" con badge produzione.

11. [Show.vue fornitore](../../resources/js/pages/workspace/supplier/operations/Show.vue):
    - Rimuovere `<CaseStatusBadge>` dall'header.
    - Rimuovere bottoni Chiudi/Riapri, i 2 dialog, i metodi `confirmCloseCase`/`confirmReopenCase`, refs `showCloseDialog`/`showReopenDialog`/`working`, computed `caseIsOpen`.
    - Mantenere `<ProductionStatusBadge>` e i metodi/canUpload/canDelete legati a `productionIsActive`.
    - **Gate**: visit show fornitore → un solo badge produzione, niente bottoni.

12. [ProductionsTab.vue admin](../../resources/js/pages/operations/partials/ProductionsTab.vue): aggiungere sotto la card produzione una sezione "Stato lavorazione (Fornitore)" con `<CaseStatusBadge>` + 1 pulsante (Segna completata / Riapri lavorazione) + data `supplier_completed_at` quando valorizzata. Riutilizzare l'helper `post()` già usato per le 4 azioni produzione. Mostrare la sezione solo se `operation.selected_supplier?.id` esiste.
    - **Gate**: visit show admin di una operation con fornitore selezionato → vedere mini-sezione e pulsante. Click → toast + badge cambia.

13. [Operation.ts](../../resources/js/types/Operation.ts): nessuna rimozione (entrambi i campi restano nel tipo: usati lato Admin payload).

### E. Test

14. Aggiornare [CaseAndProductionStatusTest.php](../../tests/Feature/CaseAndProductionStatusTest.php):
    - Sostituire chiamate `markCaseCompleted(op, supplier, user)` → `markCaseCompletedByAdmin(op, user)`. Idem `reopenCase`.
    - Rimuovere import `SupplierCaseCompletedForAdmin` / `SupplierCaseReopenedForAdmin` e i relativi `assertSentTo`.
    - Sostituire l'assert su notification admin con l'assert sull'effetto: pivot aggiornato + activity log entry.
    - Aggiungere test: `markCaseCompletedByAdmin fails when no selected supplier`.
    - Aggiornare il test `operations index payload exposes case_status and production_status`: il fornitore non vede più `case_status` → l'assert sul payload Inertia fornitore va su `production_status` solo. Aggiungere (opzionale) un test analogo sul payload admin (`getAdminShowData`) che espone `case_status`.
    - **Gate**: `./vendor/bin/pest tests/Feature/CaseAndProductionStatusTest.php` → tutti verdi.

15. Smoke test build: `npm run build` → no errori. Verifica route admin con `php artisan route:list | grep operations.case`.

## Rischi e punti di attenzione

- **Storico dati**: il backfill 2026_05_08_160000 + il rollback 2026_05_12_120100 garantiscono già coerenza Production. Il campo `supplier_completed_at` pre-esistente resta intatto — niente migration.
- **`$appends = ['case_status']`** è oggi sempre serializzato: qualsiasi `Operation->toArray()` lato admin ha `case_status` derivato da `withSupplierPivot`. Rimuovendolo da `$appends`, dobbiamo esporre `case_status` esplicitamente in `getAdminShowData` (step 9). Verificare che `latestQuoteStatus`, `selected_supplier_name` e altre code lato admin che fanno `toArray()` non si rompano.
- **Test `operations index payload exposes …`** ([L297-326 del test file](../../tests/Feature/CaseAndProductionStatusTest.php)): oggi asserisce `case_status = completed` dall'index fornitore — questo assert fallisce nel nuovo modello (campo non esposto). Va riscritto come "production_status presente nel payload" + nuovo test admin che verifica `case_status` nella show admin.
- **`SUPPLIER_OPERATIONS_COMPLETE` ability**: prima di rimuoverla, controllare che nessuna seeder/menu/permission map la referenzi (oltre al controller, già censito).
- **Route bookmarkati**: i fornitori che avevano in storia URL `?case_status=completed` riceveranno il filtro ignorato (param sconosciuto) — accettabile.
- **Whitelist activity log**: dopo rimozione dalla whitelist fornitore, eventuali bell-icons già emesse sul DB restano visibili nello slideover? No — la query filtra `whereIn('event', ...)`. Bene.
- **N+1 lato admin**: `getAdminShowData` esegue già 1 query per `supplier_completed_at` — derivare `case_status` lì costa zero query extra.

## Domande aperte

Nessuna.

## Ordine di commit suggerito

1. **Backend service + controller + routes** (step 1-3): refactor service e nuove azioni admin. Test legacy temporaneamente rotti (verranno fissati in commit 5).
2. **Backend cleanup fornitore + notifiche + ability** (step 4-7): rimozione tutto ciò che il fornitore non usa più, attenzione ai grep di verifica.
3. **Backend payload admin** (step 8-9): rimozione `case_status` da `$appends` + esposizione esplicita lato admin.
4. **Frontend** (step 10-13): UI fornitore semplificata + nuova mini-sezione admin.
5. **Test allineati** (step 14-15): test verdi, build verde.
