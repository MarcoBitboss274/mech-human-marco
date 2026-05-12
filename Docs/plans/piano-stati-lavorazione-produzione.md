# Piano — Stati Lavorazione e Produzione (rev. modello a 2 dimensioni)

## Obiettivo

Separare in **due dimensioni indipendenti** ciò che oggi vive in un unico enum derivato `SupplierVisibleStatusEnum`:

1. **Stato lavorazione** (case status) — `Aperta` / `Completata` — tocca **solo il Fornitore**, senza vincoli.
2. **Stato produzione** — `Null` / `Confermata` / `Annullata` / `Completata` — tocca **solo Admin M&H**.

Le due dimensioni sono **indipendenti**: l'annullo della produzione lato Admin non riapre più la lavorazione del fornitore.

## Matrice transizioni produzione (Admin)

| Da → A | Confermata | Annullata | Completata |
|---|:---:|:---:|:---:|
| **Null** | ✅ Conferma | — | — |
| **Confermata** | — | ✅ Annulla | ✅ Completa |
| **Annullata** | ✅ Riconferma | — | ❌ |
| **Completata** | ✅ Riapri | ✅ Annulla | — |

## Sorgenti di verità

| Dimensione | Persistenza |
|---|---|
| Case status | `operation_supplier.supplier_completed_at` (null = Aperta, timestamp = Completata) |
| Production status | `Production.status` (assenza record = Null) |

`operations.production_canceled_at` → **rimosso** (ridondante).

## Diff codebase

### Enum
- **Rimosso**: `App\Enums\SupplierVisibleStatusEnum`
- **Aggiunto**: `App\Enums\CaseStatusEnum` (`open`, `completed`)
- Invariato: `ProductionStatusEnum` (`confirmed`, `canceled`, `completed`)

### Model `Operation`
- Rimosso accessor `supplier_visible_status` e relativo append
- Rimosso `production_canceled_at` da `$fillable` e `$casts`
- Aggiunto accessor `case_status` (open/completed) basato su `supplier_completed_at` dal pivot
- Scope `withSupplierPivot` invariato

### Service `OperationService`
| Metodo | Cambio |
|---|---|
| `markSupplierProductionCompleted` | Rinominato `markCaseCompleted`. **Non tocca più `Production.status`**. Vincoli: assegnato al fornitore + non già completed. Nessun vincolo sullo stato produzione. |
| `reopenCase` (nuovo) | Setta `supplier_completed_at` = null. Notifica admin. |
| `confirmProduction` | Idempotente. Non resetta più nulla del pivot fornitore. Mantiene `operations.status = production`. Ammesso da Null/Annullata/Completata. |
| `cancelProduction` | **Non resetta** `supplier_completed_at` (dimensioni indipendenti). Mantiene side-effect su `operations.status` (PRODUCTION → WAITING_APPROVAL). Ammesso da Confermata/Completata. |
| `markProductionCompleted` (nuovo) | Admin → `Production.status = completed`. Solo da Confermata. Notifica fornitore. |
| `reopenProduction` (nuovo) | Admin → `Production.status = confirmed`. Solo da Completata. Notifica fornitore. |
| `resetSupplierCompletedForCurrentSupplier` | **Rimosso**. |

### Migrations
1. **drop** `operations.production_canceled_at`
2. **data**: `Production.status = completed` → `confirmed` (con `completed_at = null`) per tutte le righe storiche, perché il vecchio flusso fornitore impostava `completed` come effetto collaterale. Da ora "completed" è prerogativa Admin.

### Notifiche
- `SupplierProductionCompletedForAdmin` → rinominata `SupplierCaseCompletedForAdmin`
- Nuova `SupplierCaseReopenedForAdmin` (fornitore riapre lavorazione)
- Nuova `OperationProductionCompletedForSupplierNotification` (admin segna completata)
- Nuova `OperationProductionReopenedForSupplierNotification` (admin riapre produzione)
- Invariate: `OperationProductionConfirmedForSupplierNotification`, `OperationProductionCanceledForSupplierNotification`

### Controller fornitore
- Aggiunti endpoint:
  - `POST /workspace/supplier/operations/{operation}/close-case` → chiude lavorazione
  - `POST /workspace/supplier/operations/{operation}/reopen-case` → riapre lavorazione
- Sostituiti gli endpoint vecchi `complete` (rimossi)
- Filtri: `case_status` + `production_status` indipendenti

### Controller admin (`OperationController`)
- Nuovi metodi: `markProductionCompleted`, `reopenProduction`
- Routes:
  - `POST /operations/{operation}/productions/complete`
  - `POST /operations/{operation}/productions/reopen`

### Frontend
- **Rimosso** `SupplierVisibleStatusBadge.vue`
- **Aggiunto** `CaseStatusBadge.vue` (Aperta/Completata)
- `ProductionStatusBadge.vue` invariato (già supporta confirmed/canceled/completed/null)
- Index fornitore: 2 colonne separate (Stato, Produzione) + 2 filtri indipendenti
- Show fornitore: 2 badge + bottoni "Chiudi lavorazione" / "Riapri lavorazione" (sempre disponibili, senza vincoli)
- `ProductionsTab` admin: aggiunti pulsanti "Segna completata" e "Riapri produzione" oltre a Conferma/Annulla esistenti

### Types
- Rimossi `supplier_visible_status`, `production_canceled_at` da `Operation.ts`
- Aggiunti `case_status`, `supplier_completed_at` (già presente)

### Activity log
- Whitelist eventi fornitore aggiornata: rimossi `supplier_marked_completed`, `supplier_completed_reset_for_production_cancel`; aggiunti `case_completed`, `case_reopened`, `production_completed`, `production_reopened`.

## Test (Pest)
Sostituire `tests/Feature/SupplierVisibleStatusTest.php` con `CaseAndProductionStatusTest.php`:
- accessor `case_status` (open/completed)
- `markCaseCompleted` sempre permesso, idempotente
- `reopenCase` resetta supplier_completed_at
- `cancelProduction` NON resetta `supplier_completed_at`
- `markProductionCompleted` solo da confirmed
- `reopenProduction` solo da completed
- transizioni produzione admin: matrice completa
