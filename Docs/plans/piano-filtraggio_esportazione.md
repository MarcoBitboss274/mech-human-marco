# Piano: Filtri, counter ed esportazione lavorazioni — Index admin

**Specifiche**: [Docs/specs/specifiche-filtraggio_esportazione.md](../specs/specifiche-filtraggio_esportazione.md)

## Stato del codebase

**Esiste, da riusare**:
- [resources/js/pages/operations/Index.vue](../../resources/js/pages/operations/Index.vue) — i filtri `expire_at_from/to` e `send_at_from/to` (range) sono già attivi via `useIndexPage` ([righe 365-371](../../resources/js/pages/operations/Index.vue#L365-L371)).
- [app/Services/OperationService.php:145](../../app/Services/OperationService.php#L145) — `applyFilters(Builder, Request)`: punto unico in cui aggiungere `agent_id`.
- [app/Http/Controllers/OperationController.php](../../app/Http/Controllers/OperationController.php) — `index()` ritorna paginator (ha già `->total()` per il counter).
- [app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php](../../app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php) — registry dei permessi `operations.*` (riga 67-86). Admin/Superadmin ricevono *tutti* i permessi via `array_diff($permissions, [])` (riga 136); l'Agent riceve solo quelli enumerati esplicitamente (riga 142).
- [resources/js/composables/useIndexPage.ts](../../resources/js/composables/useIndexPage.ts) — già gestisce debounce, querystring, preserveState; nessun cambio.
- `BbDropdown` da `bitboss-ui` — già usato in [resources/js/components/layout/TopbarDefault.vue:50](../../resources/js/components/layout/TopbarDefault.vue#L50). È il componente da wrappare per `ExportDropdown.vue`, **non** costruire un menu custom.
- Pattern filtro agente: [resources/js/pages/buildings/Index.vue](../../resources/js/pages/buildings/Index.vue) (`agentIdFilter`) + [app/Services/BuildingService.php](../../app/Services/BuildingService.php) (filtraggio `agent_id`). Da replicare 1:1.

**Non esiste, va creato da zero**:
- `app/Exports/` (directory).
- Pacchetto Composer `maatwebsite/excel` (assente in [composer.json](../../composer.json)).
- Permesso `operations.export` (assente da `PermissionsUpgrade`).
- Route `operations.export` e `operations.export-all`.
- Componenti `OperationsResultsBar.vue` e `ExportDropdown.vue` ([resources/js/components/operations/](../../resources/js/components/operations/) non li contiene).

**Convenzioni del progetto**:
- I permessi non si gestiscono via seeder ma via *upgrade command* (`PermissionsUpgrade`); va eseguito a mano dopo il merge (`php artisan bitboss:upgrade:permissions` o equivalente — verificare il signature del command).
- Lookup di label utenti/strutture per il counter: usare `Cache::remember` o singleton di richiesta per evitare N query inline; in alternativa eager-load nel controller prima di chiamare `buildResultsLabel`.

## Diff rispetto alle specifiche

- **Specifiche dicono** "permesso/gate `operations.export` da aggiungere in `OperationPolicy`" — **realtà**: il progetto NON usa Policy per i permessi `operations.*`, li gestisce come *Spatie permissions* registrate in `PermissionsUpgrade`. **Implicazione**: aggiungere `'operations.export'` alla lista in [PermissionsUpgrade.php:67-86](../../app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php#L67-L86); admin/superadmin lo riceveranno automaticamente; l'agent NON va aggiunto al blocco `$agent->givePermissionTo([...])`. Niente Policy/Gate aggiuntivi: protezione delle route con `middleware(['can:operations.export'])`.
- **Specifiche dicono** "BbButton con dropdown" — **realtà**: esiste `BbDropdown` con `BbDropdownItem[]`, già utilizzato. **Implicazione**: `ExportDropdown.vue` wrappa `BbDropdown` con due item (CSV / Excel) che emettono un evento o aprono direttamente l'URL via `window.location.href`.
- **Specifiche dicono** "Per CSV streamDownload custom oppure Maatwebsite con WithEvents" — **realtà**: `maatwebsite/excel` v3.1+ supporta nativamente `\Maatwebsite\Excel\Excel::CSV` come `writerType` con la stessa classe di export. **Implicazione**: una sola classe `OperationsExport` parametrizzata, due formati gestiti dal controller con `Excel::download($export, $filename, $type)`. Niente writer custom.
- **Specifiche dicono** "label rinominate dei filtri data" — **realtà**: le label sono in [resources/js/pages/operations/Index.vue righe 93-108](../../resources/js/pages/operations/Index.vue#L93-L108). **Implicazione**: cambio puramente testuale (verificare se sono passate da `t()` o se sono stringhe inline; in caso di `t()`, aggiornare i file di traduzione).

## Step ordinati

### Gruppo 1 — Filtro Agente (backend + frontend)
1. Aggiungere `agent_id` in `OperationService::applyFilters` con `whereHas('building', fn($q) => $q->where('agent_id', $value))`. **Gate**: aggiungere un test feature in `tests/Feature/OperationIndexFilterTest.php` che fissa 2 building con agenti diversi e verifica che il filtro restituisce solo le Operation del building dell'agente selezionato.
2. Estendere il payload Inertia in `OperationController::index` con `agentsList` (utenti con role `agent`). **Gate**: `php artisan tinker` → `OperationController` resolve, oppure aprire la pagina e ispezionare props.
3. Aggiungere il `BbSelect` "Agente" in `Index.vue` (visibile solo se `userStore.hasAnyRole(['superadmin','admin'])`), copiando il pattern `agentIdFilter` da `buildings/Index.vue`. **Gate**: visivamente — login admin → filtro presente; login agent → filtro assente.

### Gruppo 2 — Rinomina label filtri data
4. Cambiare le label dei due range date in "Scadenza da: - a:" e "Invio da: - a:" ([Index.vue righe 93-108](../../resources/js/pages/operations/Index.vue#L93-L108)). **Gate**: visivamente.

### Gruppo 3 — Counter testuale
5. Aggiungere `OperationService::buildResultsLabel(Request, int $total): string` con la concatenazione descrittiva (regole in [specifiche §Counter testuale](../specs/specifiche-filtraggio_esportazione.md#counter-testuale)). Eager-load i lookup necessari (Building, User, Supplier) in una sola query prima della concatenazione. **Gate**: unit test `OperationServiceResultsLabelTest` con casi: zero filtri, un filtro testuale, un filtro su entità (struttura), un range solo "da", un range completo, range solo "a", più filtri insieme.
6. Estendere `OperationController::index` per esporre `resultsLabel: string` e `total: number` (oltre al paginator). **Gate**: ispezione props della pagina.
7. Creare `resources/js/components/operations/OperationsResultsBar.vue` (props: `resultsLabel`, `total`) e montarlo in `Index.vue` sopra la tabella. In questa fase **senza** bottone Esporta. **Gate**: visivamente — applicare 2-3 filtri, verificare la stringa.

🚦 **Punto di rilascio Fase 1**.

### Gruppo 4 — Pacchetto e permesso export
8. `composer require maatwebsite/excel:^3.1` ([composer.json](../../composer.json) aggiornato + vendor scaricato). **Gate**: `composer show maatwebsite/excel` ritorna versione installata.
9. Aggiungere `'operations.export'` all'array `$permissions` in `PermissionsUpgrade` (riga 67-86). Eseguire il comando di upgrade in dev. **Gate**: in tinker `$user->can('operations.export')` ritorna `true` per admin, `false` per agent.

### Gruppo 5 — Backend export
10. Creare `app/Exports/OperationsExport.php` che riceve in costruttore una `Builder` e un flag `includeCategoryColumn: bool`; implementa `FromQuery`, `WithHeadings`, `WithMapping`, `WithTitle('Lavorazioni')`. Le colonne sono in [specifiche §Colonne export](../specs/specifiche-filtraggio_esportazione.md#colonne-dellexport-set-fisso). **Gate**: unit test che esegue `$export->collection()->first()` su 1 Operation seed e verifica il mapping di tutte le colonne.
11. Creare `OperationController::export(Request)` e `OperationController::exportAll(Request)`:
    - Entrambi: validano `format` ∈ {csv, xlsx}; controllano soglia 50.000 (errore 422 se superata); chiamano `Excel::download($export, $filename, $type)`.
    - `export`: query base = `OperationService::search(...)` ma senza paginazione (riusare `applyFilters` su una `Builder`).
    - `exportAll`: query base = `Operation::query()` senza filtri, con flag `includeCategoryColumn=true`.
   **Gate**: chiamata cURL ai due endpoint con admin loggato → file scaricabile e apribile in Excel.
12. Registrare le route in `routes/web.php` con `middleware(['auth','can:operations.export'])`. **Gate**: chiamata cURL come agent → 403; come admin → 200.

### Gruppo 6 — Frontend export
13. Creare `ExportDropdown.vue` che wrappa `BbDropdown` con due item ("Esporta CSV", "Esporta Excel"), props: `exportUrl: string`, `disabled?: boolean`, `disabledReason?: string`. Click su un item → loading state → `window.location.href = exportUrl + '?format=csv'` (o `xlsx`). **Gate**: visivamente — click su un item avvia il download.
14. Inserire `<ExportDropdown>` in `OperationsResultsBar.vue` (export contestuale, `exportUrl=route('operations.export')` con i filtri correnti propagati come query string) e nell'header di `Index.vue` accanto al "+ nuova lavorazione" (export globale, `exportUrl=route('operations.export-all')`). Mostrarli solo se l'utente ha la permission `operations.export` (esposta nelle props della pagina o leggibile da `usePermissions()` se esiste). **Gate**: login admin → due bottoni visibili; login agent → entrambi assenti.
15. Gestire il toast di successo / errore e lo stato disabled "Nessun risultato da esportare" quando `total === 0` (export contestuale). **Gate**: applicare un filtro che dà 0 risultati → bottone disabilitato con tooltip; rimuoverlo → riattivato.

🚦 **Punto di rilascio Fase 2**.

## Rischi e punti di attenzione

- **Performance del counter**: `buildResultsLabel` rischia N query se ogni filtro fa un lookup separato. Eager-load esplicito o cache di richiesta — fondamentale.
- **N+1 nell'export**: `FromQuery` di Maatwebsite itera in chunk; serve `with(['building.agent','latestPrescription.user','selectedSupplier'])` sulla Builder usata. Verificare con `DB::listen` su un dataset di test.
- **Memoria con Excel a 50.000 righe**: usare `WithChunkReading` (chunk ≈ 1000) per evitare OOM. CSV di Maatwebsite è già streamato.
- **Permission upgrade non automatico**: dopo merge, in produzione qualcuno deve eseguire il comando di upgrade permessi — *non* è una migration. Documentare nel commit message.
- **Cambio label tradotte**: se le label dei filtri data passano da `t('...')`, aggiornare i file di traduzione `lang/` italiano (e ogni altra lingua presente) per evitare fallback all'inglese o chiave grezza.
- **Test esistenti su `OperationService::search`**: non riscriverli; aggiungere test nuovi per `agent_id` e `buildResultsLabel`.
- **Soft-deleted entities nel counter**: leggere [specifiche §Corner case](../specs/specifiche-filtraggio_esportazione.md#corner-case-e-gestione-errori) — fallback a "struttura #42" se nome assente.
- **Concorrenza download**: il bottone deve essere single-flight (disabilitato durante "Generazione…"); altrimenti due click rapidi avviano due download. Testare manualmente.

## Domande aperte

Nessuna.

## Ordine di commit suggerito

1. `feat(operations): aggiungi filtro agente alla index admin` — step 1-3.
2. `chore(operations): rinomina label filtri data scadenza/invio` — step 4.
3. `feat(operations): counter testuale risultati sopra la tabella` — step 5-7. **(fine Fase 1)**
4. `chore: aggiungi maatwebsite/excel e permesso operations.export` — step 8-9.
5. `feat(operations): esporta lavorazioni CSV/Excel (contestuale + globale)` — step 10-12 (backend) + 13-15 (frontend). Splittabile in due commit (BE/FE) se la review preferisce. **(fine Fase 2)**
