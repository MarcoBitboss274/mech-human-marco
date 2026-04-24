# Feature: Tab Panoramica lavorazione

Diagramma di flusso completo della tab **Panoramica** (prima tab di `operations/Show` e `workspace/operations/Show`), inclusi payload aggregato, rendering delle sezioni, azioni inline e sync automatico dei timestamp.

---

## 1. Caricamento tab Panoramica — lato admin

```
Utente apre /operations/{operation}
        ↓
OperationController::show(Request, Operation)
  - Gate::authorize('view', $operation)
  - Risolve UserService::currentUser()
  - match($user->role):
      ADMIN | SUPERADMIN → OperationService::getAdminShowData($operation)
      AGENT              → OperationService::getAgentShowData($operation)
      default            → []
        ↓
OperationService::getAdminShowData(Operation)
  - Eager-load: building (+agent), prescriptions.*, quotes, orders,
    productions, invoices (+media), suppliers, selectedSupplier,
    latestPrescription
  - $operationData = $operation->toArray()
  - $operationData['selected_supplier'] = selectedSupplier->first()?->toArray()
        ↓
OperationService::buildOverviewPayload($operation, asCustomer: false)
  - Filtri di visibilità: nessuno (admin vede tutto)
  - $visiblePrescriptions = $operation->prescriptions
  - $visibleQuotes        = $operation->quotes
  - $visibleInvoices      = $operation->invoices
  - $visibleProductions   = $operation->productions
        ↓
Costruzione "actors":
  - requester = prescription più recente (created_at desc) → user
  - building  = $operation->building (name)
  - agent     = $operation->building->agent (solo admin)
  - supplier  = selectedSupplier->first() (solo admin)
        ↓
Costruzione "summary":
  - prescription:
      empty/count/status/main_id
      sent_at      = main.send_at
      confirmed_at = main.confirmed_at
    (main = prescription più recente per created_at)
  - suppliers (solo admin):
      empty, selected{ id, name, selected_at }
      selected_at letto dal pivot operation_supplier
  - quotes:
      accepted     = quote con status=accepted (id + accepted_at)
      sent_ids[]   = id dei quote con status=sent
      rejected_count / canceled_count
      main_id = accepted ?? primo sent ?? primo visibile
  - production:
      main = first(confirmed) ?? first(canceled) ?? first()
      confirmed_at = main.confirmed_at se status=confirmed
      canceled_at  = main.canceled_at  se status=canceled
  - invoices:
      sent_ids[]      = id con status=sent
      canceled_count  = conto degli status=canceled
        ↓
Inertia::render('operations/Show', {
  operation,                    // toArray() completo
  overview: { actors, summary },
  chat: { can_read, can_send },
})
        ↓
Frontend: pages/operations/Show.vue → slot #overview
        ↓
OverviewTab.vue (admin)
  - Colonna sinistra: OverviewSection per
      Prescrizione (gate: operations.prescription.view)
      Fornitore   (gate: operations.supplier.view)
      Preventivi  (gate: operations.quote.view)
      Produzione  (gate: operations.production.view)
      Fatture     (gate: operations.invoice.view)
  - Colonna destra: ActorCard per Richiedente / Struttura /
                    Agente (se operations.view-all o presente) /
                    Fornitore (se operations.supplier.view)
  - Ogni sezione renderizza:
      header      = titolo + icon button BbButton arrow-right (ruotato -45deg)
      counters    = slot opzionale (es. "2 rifiutati · 1 annullato")
      body        = testo + QuoteCard / InvoiceCard inline
      click icon  = emit go-to → OverviewTab emit change-tab(key) → Show cambia tab
```

---

## 2. Caricamento tab Panoramica — lato customer (workspace)

```
Utente apre /workspace/{building}/operations/{operation}
        ↓
WorkspaceController::showOperation(...)
        ↓
WorkspaceService::getWorkspaceOperationShowData(Building, Operation)
  - abort_unless(operation.building_id === building.id, 404)
  - Se l'utente NON ha workspaceAbility OPERATIONS_VIEW_ALL:
      verifica che sia il requester (latestPrescription.user_id === $user->id)
      altrimenti abort(404)
  - Eager-load filtrato per customer:
      quotes    → whereNotIn(status, [DRAFT])
      orders    → whereIn(status, [CONFIRMED])
      invoices  → whereIn(status, [SENT, CANCELED])
      prescriptions latest + user + building
        ↓
OperationService::buildOverviewPayload($operation, asCustomer: true)
  - Filtri di visibilità customer:
      prescriptions: send_at !== null
      quotes:        status !== draft
      invoices:      sent_at !== null
      productions:   nessun filtro
  - actors: agent=null, supplier=null
  - summary.suppliers: NON incluso
  - summary.invoices.canceled_count:
      solo invoices con status=canceled che avevano sent_at !== null
      (cioè annullate dopo l'invio — stesse visibili al customer)
        ↓
Inertia::render('workspace/operations/Show', {
  operation: OperationResource::make($operation)->resolve(),
  overview:  { actors, summary },
})
        ↓
Frontend: workspace/operations/Show.vue → slot #overview
        ↓
workspace/.../partials/OverviewTab.vue (customer)
  - Sezioni visibili: Prescrizione, Preventivi, Produzione, Fatture
    (nessuna Fornitore; Attori senza Agente/Fornitore)
  - Stesso OverviewSection del lato admin
  - QuoteCard in mode="customer" (Accetta/Rifiuta per sent)
  - InvoiceCard in mode="customer" (visualizzazione, no azioni gestionali)
```

---

## 3. Azioni inline — lato admin

```
OverviewTab.vue (admin) ─ QuoteCard sentQuotes
        ↓
Bottoni QuoteCard (mode="admin"):
  - edit    → emit → goToTab('quote')
  - send    → sendQuote(quote)       → POST /quotes/{quote}/send
  - accept  → acceptQuote(quote)     → POST /quotes/{quote}/accept
  - reject  → openRejectModal(quote) → dialog BbDialog → submitReject()
                                       → POST /quotes/{quote}/reject {notes}
  - cancel  → openCancelModal(quote) → dialog → submitCancel()
                                       → POST /quotes/{quote}/cancel {notes}
  - delete  → removeQuote(quote)     → DELETE /operations/{op}/quotes/{quote}
        ↓
router.post/delete con preserveScroll:
  onSuccess → useMainToast().success + notifyUpdated()
  onError   → useMainToast().error
        ↓
notifyUpdated():
  - router.reload({ only: ['operation', 'overview'] })
  - emit('operation:updated') al parent Show

OverviewTab.vue (admin) ─ InvoiceCard sentInvoices
        ↓
Bottoni InvoiceCard (mode="admin"):
  - edit         → goToTab('invoice')
  - send         → sendInvoice(invoice)  → POST /invoices/{invoice}/send
  - delete       → removeInvoice(invoice)
                 → DELETE /operations/{op}/invoices/{invoice}
  - status-click → openStatusModal(invoice) → BbDialog con BbSelect
                   (items = useSelect('invoice-statuses'))
                 → submitStatus() → PATCH /operations/{op}/invoices/{invoice}/status
                   {status: InvoiceStatusEnum}
        ↓
router.patch con preserveScroll + notifyUpdated()
```

---

## 4. Azioni inline — lato customer

```
OverviewTab.vue (workspace) ─ QuoteCard sentQuotes
        ↓
Bottoni QuoteCard (mode="customer"):
  - accept → acceptQuote(quote)
             → POST /workspace/{building}/quotes/{quote}/accept
  - reject → openRejectModal(quote) → submitReject()
             → POST /workspace/{building}/quotes/{quote}/reject {notes}
        ↓
notifyUpdated() → router.reload({ only: ['operation', 'overview'] })

OverviewTab.vue (workspace) ─ InvoiceCard sentInvoices
  - mode="customer": visualizzazione / download (no azioni gestionali)
```

---

## 5. Navigazione da Panoramica a tab dedicata

```
OverviewSection.vue
        ↓
Utente clicca BbButton icon="arrow-right" (ruotato -45deg via CSS)
        ↓
handleGoTo() (se !goToDisabled)
  - emit('go-to')
        ↓
OverviewTab.vue
  - emit('change-tab', key) es. 'prescription' | 'quote' | 'supplier' |
                                'production' | 'invoice'
        ↓
pages/operations/Show.vue (o workspace/operations/Show.vue)
  - cambia tab attiva al key ricevuto
  - NO reload, navigazione interna via prop/state della pagina
```

---

## 6. Sync automatico dei timestamp (side-effect sui model)

### 6.1 `prescriptions.confirmed_at`

```
Prescription::booted()
        ↓
static::saving(Prescription $p):
  - if (!$p->isDirty('status')) return;
  - if ($p->status === CONFIRMED):
      if (original('status') !== CONFIRMED) → $p->confirmed_at = now()
  - else:
      $p->confirmed_at = null
```
Side-effect: ogni volta che una prescrizione cambia in CONFIRMED per la prima volta,
`confirmed_at` viene valorizzato. Uscita da CONFIRMED → reset a null.

### 6.2 `invoices.sent_at` + `invoices.canceled_at`

```
Invoice::booted()
        ↓
static::saving(Invoice $i):
  - if (!$i->isDirty('status')) return;
  - if ($i->status === SENT):
      if ($i->sent_at === null) → $i->sent_at = now()
      if (original('status') === CANCELED) → $i->canceled_at = null
      return;
  - if ($i->status === CANCELED):
      if (original('status') !== CANCELED) → $i->canceled_at = now()
      (sent_at PRESERVATO: serve al customer per vedere le annullate post-invio)
      return;
  - if ($i->status === DRAFT):
      $i->sent_at = null
      $i->canceled_at = null
      return;
  - else (PAID):
      $i->canceled_at = null
      (sent_at preservato)
```

### 6.3 Pivot `operation_supplier.selected_at`

```
OperationService::selectSupplier(Operation, Supplier)
  - Verifica supplier attached (suppliers()->where('suppliers.id', $s->id)->exists())
  - Se non attached → ValidationException
        ↓
DB::transaction():
  1. Reset di TUTTI i pivot della stessa operation:
       selected = false
       selected_at = null
       updated_at = now()
  2. updateExistingPivot($supplier->id, [
       'selected' => true,
       'selected_at' => now(),
       'updated_at' => now(),
     ])
```
Centralizzato in `selectSupplier` (non in `booted()` perché il pivot non è un Model).
`attachSupplier` inserisce sempre con `selected=false` e `selected_at` implicito null.
`detachSupplier` è un semplice detach (non serve resettare `selected_at`).

---

## 7. Regole di visibilità customer vs admin

| Entità        | Admin                        | Customer                                         |
|---------------|------------------------------|--------------------------------------------------|
| Prescrizione  | tutte                        | solo `send_at !== null`                          |
| Quote         | tutti gli stati              | tutti tranne `draft`                             |
| Invoice       | tutti gli stati              | solo `sent_at !== null` (include post-cancel)    |
| Production    | tutte                        | tutte                                            |
| Supplier      | sezione visibile             | sezione totalmente nascosta                      |
| Agente        | visibile in Attori           | attore non incluso                               |

Per il customer, `summary.invoices.canceled_count` conta solo le invoice con `status=canceled` **e** `sent_at !== null`.

---

## 8. Gerarchia chiave "main entity" per le sezioni

Quando esistono più record di un tipo, la sezione mostra un "main" scelto così:

- **Prescription**: `prescriptions.sortByDesc('created_at')->first()`
- **Production**: `firstWhere(status=CONFIRMED) ?? firstWhere(status=CANCELED) ?? first()`
- **Quote (main_id)**: `accepted ?? primo sent ?? primo visibile`
- **Invoice**: nessun main — si elencano tutte le `sent` come card inline, le altre contribuiscono solo ai counter

---

## 9. Entità dati

### Tabelle / campi chiave

| Tabella                 | Campo                     | Tipo               | Uso Panoramica                         |
|-------------------------|---------------------------|--------------------|----------------------------------------|
| `operations`            | `id`, `building_id`, `status`, `archived_at`, `canceled_at` | — | soggetto della pagina |
| `prescriptions`         | `operation_id`, `user_id`, `status`, `send_at`, `confirmed_at` (nuovo), `created_at` | timestamp | `sent_at` / `confirmed_at` mostrati |
| `quotes`                | `operation_id`, `status` (enum), `accepted_at` | — | `accepted`, `sent_ids`, counter rejected/canceled |
| `productions`           | `operation_id`, `status` (enum), `confirmed_at`, `canceled_at` | — | timestamp del main |
| `invoices`              | `operation_id`, `status` (enum inc. `CANCELED`), `sent_at`, `canceled_at` (nuovo) | timestamp | `sent_ids` + `canceled_count` |
| `operation_supplier`    | `operation_id`, `supplier_id`, `selected`, `selected_at` (nuovo) | pivot | `suppliers.selected` + timestamp selezione |
| `buildings`             | `id`, `name`, `agent_id`  | —                  | actors.building, actors.agent          |
| `users`                 | `id`, `name`, `surname`, `email` | —          | actors.requester, actors.agent         |
| `suppliers`             | `id`, `name`, `mail`      | —                  | actors.supplier, sezione Fornitore     |

### Migrations aggiunte per la feature

1. `2026_04_24_120000_add_confirmed_at_to_prescriptions_table` → `dateTime confirmed_at nullable after expire_at`.
2. `2026_04_24_120100_add_canceled_at_to_invoices_table` → `timestamp canceled_at nullable after sent_at`.
3. `2026_04_24_120200_add_selected_at_to_operation_supplier_table` → `timestamp selected_at nullable after selected`.

### Enum toccati

- `App\Enums\InvoiceStatusEnum`: aggiunto `case CANCELED = 'canceled'` (label "Annullata"). Presenti: `DRAFT`, `SENT`, `PAID`, `CANCELED`.

### Relazioni Eloquent rilevanti

- `Operation` hasMany `Prescription` / `Quote` / `Production` / `Invoice`
- `Operation` belongsToMany `Supplier` con `withPivot(['status', 'selected', 'selected_at'])`
- `Operation` hasOne `latestPrescription` (ordered by latest)
- `Operation` belongsToMany `selectedSupplier` (filtro `wherePivot('selected', true)`)
- `Prescription` belongsTo `User` (requester)
- `Building` belongsTo `User` (agent via `agent_id`)

### Tipi TypeScript

`resources/js/types/Overview.ts` espone `OverviewPayload` + sotto-tipi (`PrescriptionSummary`, `SuppliersSummary`, `QuotesSummary`, `ProductionSummary`, `InvoicesSummary`, `ActorPerson`). `SuppliersSummary` è opzionale nel payload (presente solo admin).

### Componenti Vue

- `components/operations/overview/OverviewSection.vue` — wrapper sezione (header + counter + body + icon button freccia obliqua).
- `components/operations/overview/ActorCard.vue` — card compatta Richiedente/Struttura/Agente/Fornitore.
- `components/operations/QuoteCard.vue` — card preventivo, prop `readonly` per modalità sola-lettura.
- `components/operations/InvoiceCard.vue` — card fattura con azioni per ruolo.
- `pages/operations/partials/OverviewTab.vue` — orchestrazione admin.
- `pages/workspace/operations/partials/OverviewTab.vue` — orchestrazione customer.

### Servizi backend

- `OperationService::buildOverviewPayload(Operation, bool $asCustomer)` — costruzione payload.
- `OperationService::getAdminShowData(Operation)` — entry-point lato admin.
- `OperationService::selectSupplier(Operation, Supplier)` — sync `selected_at` nel pivot.
- `WorkspaceService::getWorkspaceOperationShowData(Building, Operation)` — entry-point lato customer.
