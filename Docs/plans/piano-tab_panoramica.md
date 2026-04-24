# Piano: Tab Panoramica Lavorazione

**Specifiche**: [specifiche-tab_panoramica.md](../specs/specifiche-tab_panoramica.md)

## Stato del codebase

**Pronto e da riusare**:
- Admin show: [OperationController::show](../../app/Http/Controllers/OperationController.php#L110-L126) → delega a [`OperationService::getAdminShowData`](../../app/Services/OperationService.php#L545-L575) che già eager-carica building, prescriptions (latest + `latestPrescription`), quotes, productions, invoices+media, suppliers e `selectedSupplier`. Passa dati come array (niente API Resource).
- Workspace show: [WorkspaceController::operationsShow](../../app/Http/Controllers/WorkspaceController.php#L331) → `WorkspaceService::getWorkspaceOperationShowData`. Esiste [`Workspace/OperationResource`](../../app/Http/Resources/Workspace/OperationResource.php) che rimuove già `selected_supplier`, `suppliers`, `selected_supplier_name` per il customer.
- Due pagine Show separate con tab BbTab: [operations/Show.vue](../../resources/js/pages/operations/Show.vue#L188-L199) (admin, usa `can()` + `enable*Tab`) e [workspace/operations/Show.vue](../../resources/js/pages/workspace/operations/Show.vue#L116-L123) (customer, meno granulare). Entrambe hanno già lo slot `#overview` con placeholder vuoto ([admin L42-43](../../resources/js/pages/operations/Show.vue#L42-L43), [workspace L35-36](../../resources/js/pages/workspace/operations/Show.vue#L35-L36)).
- Composable [useOperationStatus](../../resources/js/composables/useOperationStatus.ts) (enable* tabs) e [usePermissions](../../resources/js/composables/usePermissions.ts) (`can`, `isAdmin`, `isCustomer`).
- Types: [Operation.ts](../../resources/js/types/Operation.ts), `Quote.ts`, `Invoice.ts`, `Prescription.ts`, `Production.ts` con i campi richiesti dalla Panoramica.
- Relazione (non accessor scalare) [`Operation::selectedSupplier`](../../app/Models/Operation.php#L192-L198) — `BelongsToMany` filtrata su pivot `selected=true`. Va consumata via `->first()`.
- Test Pest 4 su [tests/Feature/OperationsTest.php](../../tests/Feature/OperationsTest.php) con `RefreshDatabase` + factory per Operation/Building/User/Quote/Invoice.

**Da creare**:
- `OverviewTab.vue` (admin) e `OverviewTab.vue` (workspace) dentro i rispettivi `partials/`.
- Componenti condivisi `ActorCard.vue` e `SummaryCard.vue` in `resources/js/components/operations/overview/`.
- Metodo di aggregazione dati Panoramica — allineato al pattern array-in-service, non nuovo Resource (vedi Diff).

**Da estrarre (refactor propedeutico)**:
- Card singolo preventivo oggi inline in [QuotesTab admin L290-374](../../resources/js/pages/operations/partials/QuotesTab.vue#L290-L374) e [QuotesTab workspace L114-147](../../resources/js/pages/workspace/operations/partials/QuotesTab.vue#L114-L147) → `QuoteCard.vue` riusabile.
- Card singola fattura oggi inline in [InvoicesTab admin L196+](../../resources/js/pages/operations/partials/InvoicesTab.vue#L196) → `InvoiceCard.vue` riusabile.

**Convenzioni**:
- Stile: scoped CSS nei `.vue` con namespace `.operations-<area>__*` (pattern già in uso in QuotesTab/InvoicesTab). Non esiste file `*_override.css` dedicato; **non** toccare `main.css`/`base.css`/`theming.css`.
- BEM custom con due underscore; i18n via `t(...)` di vue-i18n.

## Diff rispetto alle specifiche

- **Le specifiche propongono `OperationOverviewResource`** — realtà: il progetto non usa Laravel Resource per i payload admin, ma array costruiti in `OperationService`. Implicazione: aggregare in un nuovo metodo `OperationService::getOverviewData(Operation $op, User $user)` e iniettarne il risultato nel payload esistente, senza introdurre un Resource nuovo per admin. Per workspace: estendere lo stesso metodo (o chiamarlo con flag `asCustomer=true`) e continuare ad applicare il filtro di visibilità già in [Workspace/OperationResource](../../app/Http/Resources/Workspace/OperationResource.php) per rimuovere fornitori.
- **Le specifiche ipotizzano un `OverviewTab.vue` eventualmente condiviso admin+workspace** — realtà: i due Show.vue divergono su permessi e tab-bar, così come fanno QuotesTab e InvoicesTab che hanno già due copie. Implicazione: **due OverviewTab.vue separati** (admin/workspace) che condividono i sotto-componenti `ActorCard`/`SummaryCard` + le card inline estratte (`QuoteCard`/`InvoiceCard`). Coerente con il pattern del progetto.
- **Le specifiche parlano di accessor `selectedSupplier` scalare** — realtà: è una relazione `BelongsToMany`. Implicazione: ovunque si usi per la Panoramica, passare da `$op->selectedSupplier->first()` (come già fa `OperationService` L568). Nessun cambio al modello.
- **Le specifiche menzionano `enableOverviewTab`** implicitamente nel pattern tab — realtà: non serve, la Panoramica è sempre abilitata per spec. Non estendere `useOperationStatus`.
- **Le specifiche citano file di override CSS** — realtà: non esiste tale pattern in questo repo (lo stile vive negli scoped SFC). Tenere lo stile scoped nei nuovi SFC; la regola globale "non toccare main.css/base.css/theming.css" resta valida per coerenza con le regole utente.

## Step ordinati

### 1 — Refactor preparatorio (card inline → componenti riusabili)

1. Estrarre `resources/js/components/operations/QuoteCard.vue` dal markup inline di `QuotesTab` admin e workspace, con prop `quote`, `mode: 'admin' | 'customer'`, eventi per azioni (accept/reject/edit/cancel/delete/send).
   - **Gate**: `npm run build` verde + `php artisan test --filter=Quote` verde + apertura manuale della tab Preventivi admin e workspace, tutte le azioni preesistenti funzionano.
2. Idem per `resources/js/components/operations/InvoiceCard.vue` dal markup di `InvoicesTab`.
   - **Gate**: build verde + `php artisan test --filter=Invoice` verde + check manuale tab Fatture su un'operazione con fatture in stato `sent` e `paid`.

### 2 — Backend: payload Panoramica

3. Aggiungere `OperationService::getOverviewData(Operation $op, User $user): array` che produce:
   - Blocco `actors` (richiedente dalla `latestPrescription->user`, building, agent, selectedSupplier).
   - Blocco `summary` per ciascuna entità (stato aggregato, contatore, `updated_at`, id dell'elemento principale).
   - Blocco `inline` con l'elenco di preventivi in `sent` e fatture in `sent` (già visibili all'utente).
   - Flag di visibilità per ruolo (nasconde actors.selected_supplier + summary.suppliers per customer; filtra entità non inviate per customer).
   - Forma esatta del payload: vedi sezione *Dati e relazioni* delle [specifiche](../specs/specifiche-tab_panoramica.md#dati-e-relazioni).
   - **Gate**: nuovo test Pest `tests/Feature/OperationOverviewPayloadTest.php` con caso admin e caso customer, asserzioni sui contatori e sulla presenza/assenza dei blocchi per ruolo.
4. Iniettare il blocco `overview` nel payload di [`getAdminShowData`](../../app/Services/OperationService.php#L545-L575) e in `WorkspaceService::getWorkspaceOperationShowData`.
   - **Gate**: smoke test `php artisan test --filter=Operations` verde + visita manuale `/operations/{id}` e `/workspace/buildings/{b}/operations/{id}` non 500, `overview` presente nel payload Inertia (verificabile via devtools).
5. Verificare eager loading: la nuova aggregazione non deve introdurre N+1. Aggiungere al `with(...)` esistente eventuali relazioni mancanti (`latestPrescription.user`, `building.agent`).
   - **Gate**: abilitare `Laravel Debugbar` o `DB::enableQueryLog()` in un test ad-hoc e verificare query < 15 per una show con 3 preventivi + 2 fatture.

### 3 — Frontend: componenti condivisi

6. Creare `components/operations/overview/ActorCard.vue` (props: `role`, `name`, `email`, `placeholder?`).
   - **Gate**: render manuale in Storybook-style dentro `OverviewTab` (step 8).
7. Creare `components/operations/overview/SummaryCard.vue` (props: `title`, `status`, `count?`, `updatedAt?`, `empty: boolean`, `disabled?`, emit `click`).
   - **Gate**: stesso check manuale.

### 4 — Frontend: tab Panoramica admin

8. Popolare [operations/partials/OverviewTab.vue](../../resources/js/pages/operations/partials/OverviewTab.vue) (nuovo) con layout a due colonne (riepilogo sinistra, attori destra), collegando `SummaryCard` al click → switch di tab via `emit('change-tab', key)`.
9. Gestire l'apparizione delle card inline (`QuoteCard` per preventivi in `sent`, `InvoiceCard` per fatture in `sent`) con le stesse azioni della tab specifica (riuso props `mode='admin'`).
10. Collegare il componente al slot `#overview` di [operations/Show.vue](../../resources/js/pages/operations/Show.vue#L42-L43) rimuovendo il placeholder.
    - **Gate**: apertura manuale su operazione in ogni stato (draft, requested, in_progress, waiting_approval, production, completed). Verificare che: (a) placeholder "Non ancora presente" compare per entità mancanti; (b) click su card riassuntiva cambia tab correttamente; (c) card inline preventivo inviato → Accetta/Rifiuta funzionano e aggiornano la Panoramica.

### 5 — Frontend: tab Panoramica workspace

11. Popolare `workspace/operations/partials/OverviewTab.vue` analogo a quello admin ma con `mode='customer'` passato alle card inline e senza la colonna Fornitore.
12. Collegare il componente al slot `#overview` di [workspace/operations/Show.vue](../../resources/js/pages/workspace/operations/Show.vue#L35-L36).
    - **Gate**: login come customer, aprire una lavorazione con preventivo `sent` e fattura `sent`; verificare che la card Fornitore non sia visibile né in Attori né in Riepilogo; entità in `draft` non compaiono.

### 6 — Test & hardening

13. Aggiungere test Pest:
    - `OperationOverviewPayloadTest` (vedi step 3).
    - `OperationOverviewVisibilityTest` — customer non vede fornitori né entità non inviate (senza `sent_at` / stato `draft`).
    - Smoke browser test Pest 4 (`visit('/operations/{id}')` → select tab Overview, no JS errors).
    - **Gate**: `php artisan test` tutto verde.
14. Aggiornare `docs/ui/UI_ACTIVITY_LOG.md` con le modifiche di UI introdotte (regola utente su gestione UI).
    - **Gate**: commit con log aggiornato.

## Rischi e punti di attenzione

- **N+1 sulla lista multi-preventivo/multi-fattura**: se la card inline itera preventivi `sent`, servono media eager-caricati per fatture (`invoices.media`) e relazione `quotes` già caricata. Verificare query log.
- **Sincronia stato dopo azione inline**: dopo Accetta/Rifiuta su card inline, il payload `overview` va ricalcolato. Usare `router.reload({ only: ['overview', /* chiavi toccate */] })` per evitare refetch totale e stati incoerenti (card inline che resta visibile anche dopo `accepted`).
- **Estrazione QuoteCard/InvoiceCard è un refactor invasivo** sulle tab esistenti: rischio regressioni sulle tab originali. I due gate dello step 1 sono obbligatori prima di proseguire.
- **Divergenza admin/workspace**: il service `WorkspaceService` va esteso anch'esso — facile dimenticarsi il secondo flusso se si lavora solo su `OperationService`.
- **Lavorazioni `archived`/`canceled`**: la Panoramica deve restare visibile e congelata; verificare che l'aggregazione non tenti azioni inline su lavorazioni in quegli stati.
- **Privacy prescrizione**: `Prescription` ha `name`/`surname` cifrati — assicurarsi che il richiedente mostrato sia l'`user` della prescrizione (già non cifrato), non i dati del paziente.
- **Ordine commit importante per bisect**: il refactor card va isolato dai commit di feature, altrimenti bisect confonde i due cambiamenti.

## Domande aperte

- Se ci sono **più prescrizioni non-draft**, le specifiche dicono "l'ultima non-draft" per determinare il Richiedente. Va bene ordinare per `created_at` desc? In `OperationService` oggi `latestPrescription` usa il default `latestOfMany()` — confermare che sia coerente con l'intento "più recente per il customer".
- Il blocco `overview` deve essere **ricalcolato lato frontend** a ogni azione (via `router.reload`), o il backend già ri-renderizza tutto a ogni request Inertia? Confermare con una prova: dopo aver accettato un preventivo inline, il contatore "Preventivi: 3, accettato" deve aggiornarsi senza reload manuale.

## Ordine di commit suggerito

1. `refactor(frontend): estrai QuoteCard riusabile da QuotesTab`
2. `refactor(frontend): estrai InvoiceCard riusabile da InvoicesTab`
3. `feat(backend): aggrega payload overview in OperationService/WorkspaceService`
4. `feat(frontend): componenti condivisi ActorCard e SummaryCard`
5. `feat(frontend): tab Panoramica admin`
6. `feat(frontend): tab Panoramica workspace`
7. `test: copertura payload e visibilità Panoramica`
8. `docs: aggiorna UI_ACTIVITY_LOG per Panoramica`
