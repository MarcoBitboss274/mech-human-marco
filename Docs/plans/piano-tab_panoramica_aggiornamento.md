# Piano: Tab Panoramica — Aggiornamento sezione Riepilogo

**Specifiche**: [Docs/specs/specifiche-tab_panoramica_aggiornamento.md](../specs/specifiche-tab_panoramica_aggiornamento.md)

## Stato del codebase

**Da riutilizzare (esistente):**
- Pattern di sync `*_at` su `status`: [app/Models/Production.php#L36-L65](../../app/Models/Production.php#L36-L65) — template canonico per i nuovi sync.
- `Invoice::booted()` già gestisce `sent_at` ([app/Models/Invoice.php#L55-L74](../../app/Models/Invoice.php#L55-L74)) — va **esteso**, non riscritto.
- Punto unico di selezione fornitore: [app/Services/OperationService.php#L777-L803](../../app/Services/OperationService.php#L777-L803) `selectSupplier()` — è già l'unico posto che imposta `selected = true` (la transazione resetta tutti gli altri a false e poi `updateExistingPivot` sull'eletto). Sync di `selected_at` va aggiunto qui e basta.
- `buildOverviewPayload()` esistente: [app/Services/OperationService.php#L588-L672](../../app/Services/OperationService.php#L588-L672) — riscrivere il blocco `summary`, mantenere il blocco `actors` invariato.
- Componenti operativi inline già pronti: [QuoteCard.vue](../../resources/js/components/operations/QuoteCard.vue), [InvoiceCard.vue](../../resources/js/components/operations/InvoiceCard.vue), [ActorCard.vue](../../resources/js/components/operations/overview/ActorCard.vue).
- Helper formattazione date: [resources/js/utils/formatters/date.ts#L26](../../resources/js/utils/formatters/date.ts#L26) `dateTime()`.
- Composable per stato tab: [resources/js/composables/useOperationStatus.ts](../../resources/js/composables/useOperationStatus.ts) espone `enableSuppliersTab` / `enableQuotesTab` / `enableInvoicesTab` — usarlo per gestire il `disabled` dei nuovi bottoni "Vai a [Tab]".
- Pattern migration recente: [database/migrations/2026_04_03_130000_add_sent_at_to_invoices_table.php](../../database/migrations/2026_04_03_130000_add_sent_at_to_invoices_table.php) (timestamp nullable `after('status')`) e [2026_02_23_120000_create_operation_supplier_table.php](../../database/migrations/2026_02_23_120000_create_operation_supplier_table.php) per riferimento schema pivot.
- Test base già pronti: [tests/Feature/OperationOverviewTest.php](../../tests/Feature/OperationOverviewTest.php) — vanno **aggiornati** (alcune asserzioni cambieranno), non solo estesi.

**Da creare ex novo:**
- 3 migration (`prescriptions.confirmed_at`, `invoices.canceled_at`, `operation_supplier.selected_at`).
- Componente `resources/js/components/operations/overview/OverviewSection.vue` (header + slot counter + slot contenuto + bottone "Vai a [Tab]").
- Variante read-only di `QuoteCard.vue` (prop `readonly` o componente dedicato — vedi rischi).

**Convenzioni di progetto da rispettare:**
- CSS solo in file `*_override.css` (mai `base.css` / `main.css` / `theming.css`).
- Aggiornare [Docs/ui/UI_ACTIVITY_LOG.md](../ui/UI_ACTIVITY_LOG.md) dopo ogni modifica CSS.
- Pest 4 per i test (no PHPUnit nudo).

## Diff rispetto alle specifiche

- **Specifiche dicono**: il tipo `OverviewPayload` vive in `resources/js/types/Operation.ts`. **Realtà**: è definito **dentro** [OverviewTab.vue#L22-L44](../../resources/js/pages/operations/partials/OverviewTab.vue#L22-L44) (anche nel workspace c'è una definizione locale). **Implicazione**: estrarlo in `resources/js/types/Operation.ts` (o file dedicato `Overview.ts`) come **prerequisito** per evitare duplicazioni nelle due OverviewTab.
- **Specifiche dicono**: "valutare se decommissionare `SummaryCard.vue` se non più referenziato". **Realtà**: oggi è referenziato pesantemente sia in [operations/.../OverviewTab.vue](../../resources/js/pages/operations/partials/OverviewTab.vue) che in [workspace/operations/.../OverviewTab.vue](../../resources/js/pages/workspace/operations/partials/OverviewTab.vue) (12 + 8 istanze). **Implicazione**: il decommissionamento è un **side-effect** della riscrittura, non un task indipendente — eliminare il file solo dopo che le due OverviewTab non lo importano più.
- **Specifiche dicono**: aggiungere case `CANCELED` a `InvoiceStatusEnum` (con label "Annullata"). **Realtà**: [OperationInvoiceStatusBadge.vue#L21-L37](../../resources/js/components/operations/OperationInvoiceStatusBadge.vue#L21-L37) ha un `switch (status)` esaustivo con default e classi Tailwind per stato — aggiungere il case `'canceled'` è **obbligatorio** anche qui (altrimenti badge "--"), non solo nell'enum PHP.
- **Specifiche dicono**: il bottone "Vai a [Tab]" è disabled se la tab di destinazione lo è. **Realtà**: oggi `enableQuotesTab` / `enableInvoicesTab` / `enableSuppliersTab` dipendono **solo** da `prescription.status ∈ {sent, confirmed}` ([useOperationStatus.ts#L8-L11](../../resources/js/composables/useOperationStatus.ts#L8-L11)). **Implicazione**: il bottone su Prescrizione/Produzione/Attori è **sempre** attivo; quello su Suppliers/Quotes/Invoices segue la stessa regola di prescription.
- **Specifiche dicono**: serve un'azione admin per portare la fattura a `canceled`. **Realtà**: né `InvoiceCard.vue` né [UpdateOperationInvoiceStatusRequest](../../app/Http/Requests/Operation/UpdateOperationInvoiceStatusRequest.php) hanno oggi una transizione esplicita verso `canceled`. La validazione `Rule::in(InvoiceStatusEnum::toArray())` accetterà automaticamente il nuovo valore, ma manca un'azione UI. **Implicazione**: il bottone "Annulla" su `InvoiceCard.vue` admin è da aggiungere nello stesso commit del nuovo case enum (altrimenti non c'è modo di esercitare il flusso e non c'è nulla da contare in `canceled_count`).
- **Specifiche dicono**: per il customer le fatture `canceled` sono visibili solo se erano `sent` prima dell'annullamento. **Realtà**: il filtro attuale è `sent_at !== null` ([OperationService.php#L599](../../app/Services/OperationService.php#L599)) — coerente, **purché il sync di `canceled_at` non azzeri `sent_at`** (e infatti il pattern di Production preserva i timestamp diversi dallo stato corrente — vedi rischi).

## Step ordinati

### Gruppo 1 — Schema DB (timestamp + nuovo case enum)

1. Creare le 3 migration (prescriptions, invoices, operation_supplier).
   - **Gate**: `php artisan migrate` su DB locale verde; `php artisan migrate:rollback` rimuove tutte e 3 senza errori.
2. Aggiungere `case CANCELED = 'canceled'` (label "Annullata") in [InvoiceStatusEnum.php](../../app/Enums/InvoiceStatusEnum.php).
   - **Gate**: `vendor/bin/pest --filter=Invoice` verde + `./vendor/bin/phpstan analyse app/Enums app/Models/Invoice.php` senza warning su match non esaustivo.

### Gruppo 2 — Sync timestamp nei modelli

3. Aggiornare `Prescription`: cast `confirmed_at` → datetime, fillable, `booted()` con sync sullo stato (template Production).
   - **Gate**: nuovo test Pest che salva una `Prescription` passando `status` `draft → sent → confirmed → sent` e verifica che `confirmed_at` venga valorizzato/azzerato nei punti corretti.
4. Estendere `Invoice::booted()` per gestire anche `canceled_at` (mantenendo invariata la logica `sent_at`). Aggiungere cast e fillable.
   - **Gate**: nuovo test Pest che salva una `Invoice` passando `draft → sent → canceled` e verifica `sent_at` preservato + `canceled_at = now()`. Verifica anche `canceled → sent` (riapertura): `canceled_at` torna null.
5. Aggiornare [OperationService::selectSupplier()](../../app/Services/OperationService.php#L777-L803): aggiungere `'selected_at' => now()` all'`updateExistingPivot` dell'eletto e `'selected_at' => null` al reset di tutti gli altri. Aggiungere `selected_at` al `withPivot()` di `Operation::suppliers()`.
   - **Gate**: nuovo test Pest che chiama `selectSupplier` su un'operation con 3 fornitori, verifica che solo il selezionato abbia `selected_at` valorizzato; richiamando `selectSupplier` su un altro, il vecchio torna null e il nuovo viene valorizzato.

### Gruppo 3 — Backend payload

6. Riscrivere il blocco `summary` di [buildOverviewPayload()](../../app/Services/OperationService.php#L588-L672) secondo la shape in [Specifiche → Dati e relazioni](../specs/specifiche-tab_panoramica_aggiornamento.md#payload-inertia). Mantenere invariato il blocco `actors` e i filtri di visibilità customer (prescription `send_at`, quote non draft, invoice `sent_at`).
   - **Gate**: aggiornare le 3 asserzioni problematiche in [OperationOverviewTest](../../tests/Feature/OperationOverviewTest.php) (`overview.summary.quotes.count` line 159, `overview.summary.suppliers` line 218, `overview.summary.invoices.count` line 271) e aggiungere asserzioni sui nuovi campi (`prescription.sent_at`, `prescription.confirmed_at`, `quotes.accepted`, `quotes.sent_ids`, `quotes.rejected_count`, `quotes.canceled_count`, `production.confirmed_at`, `production.canceled_at`, `invoices.sent_ids`, `invoices.canceled_count`, `suppliers.selected.selected_at`). Tutti i test della suite `OperationOverviewTest` verdi.

### Gruppo 4 — Tipi TS + componenti condivisi

7. Estrarre `OverviewPayload` (e sotto-tipi) da [OverviewTab.vue#L22-L44](../../resources/js/pages/operations/partials/OverviewTab.vue#L22-L44) in `resources/js/types/Operation.ts` (o `resources/js/types/Overview.ts`). Importarlo in entrambe le `OverviewTab.vue` esistenti senza cambiare comportamento. Aggiornare la shape secondo lo Step 6.
   - **Gate**: `pnpm typecheck` (o equivalente) verde con la nuova shape; entrambe le OverviewTab continuano a compilare anche prima della riscrittura UI.
8. Creare `resources/js/components/operations/overview/OverviewSection.vue` con: prop `title`, slot `#counters`, slot default contenuto, prop `tabKey`/`disabled` per il bottone "Vai a [Tab]".
   - **Gate**: smoke test isolato (anche via Pest 4 browser su una pagina di prova, oppure check visivo manuale): la sezione renderizza header + counter + contenuto + bottone disabled/abled correttamente.
9. Aggiungere prop `readonly?: boolean` a [QuoteCard.vue](../../resources/js/components/operations/QuoteCard.vue) che nasconde Accetta/Rifiuta/Annulla/Modifica/Elimina lasciando solo le info statiche (incluso `accepted_at` formattato).
   - **Gate**: test browser Pest 4 che visita la pagina con un preventivo `accepted` e verifica che i pulsanti azione non siano nel DOM.
10. Aggiungere case `'canceled'` a [OperationInvoiceStatusBadge.vue#L21-L37](../../resources/js/components/operations/OperationInvoiceStatusBadge.vue#L21-L37) (testo "Annullata" + classi Tailwind dedicate, es. rosso/grigio).
    - **Gate**: arch test o snapshot del badge per ognuno dei 4 stati (draft, sent, paid, canceled).
11. Aggiungere a [InvoiceCard.vue](../../resources/js/components/operations/InvoiceCard.vue) modalità admin un bottone "Annulla" visibile solo se `status === 'sent'` che chiama l'endpoint esistente di cambio stato (`UpdateOperationInvoiceStatusRequest` accetta automaticamente il nuovo valore).
    - **Gate**: test feature Pest che invia POST/PUT al endpoint con `status = canceled` su una fattura `sent` come admin → 200, fattura ricaricata ha `canceled_at` valorizzato. Customer che tenta la stessa azione → 403.

### Gruppo 5 — Frontend OverviewTab (admin + customer)

12. Riscrivere [resources/js/pages/operations/partials/OverviewTab.vue](../../resources/js/pages/operations/partials/OverviewTab.vue) (admin) usando `OverviewSection` e la nuova shape payload. Sezioni nell'ordine: Prescrizione → Fornitore → Preventivi → Produzione → Fatture. Layout: due colonne (Riepilogo / Attori), invariato. Mantenere intatto il blocco Attori (continua a usare `ActorCard`). Rimuovere import di `SummaryCard`.
    - **Gate**: smoke browser Pest 4 su `/operations/{id}` come admin con un'operation che ha tutti gli stati popolati: render senza errori console, badge corretti, counter visibili, bottoni "Vai a [Tab]" presenti.
13. Riscrivere [resources/js/pages/workspace/operations/partials/OverviewTab.vue](../../resources/js/pages/workspace/operations/partials/OverviewTab.vue) (customer) — stessa struttura, ma senza sezione Fornitore e con `actors.agent`/`actors.supplier` null.
    - **Gate**: smoke browser come customer su `/workspace/operations/{id}`: sezione Fornitore assente, agente assente in colonna destra, counter `canceled` invoices coerente con la regola "solo se erano già sent".
14. Eliminare [SummaryCard.vue](../../resources/js/components/operations/overview/SummaryCard.vue) (non più referenziato).
    - **Gate**: `grep -r "SummaryCard" resources/js` → nessun risultato. `pnpm typecheck` verde.

### Gruppo 6 — Pulizia e doc

15. Aggiornare [Docs/ui/UI_ACTIVITY_LOG.md](../ui/UI_ACTIVITY_LOG.md) con le modifiche UI (sezioni vs card, nuovo `OverviewSection`, rimozione `SummaryCard`, badge `canceled`).
    - **Gate**: linea aggiunta in cima al log con data odierna.
16. Eseguire suite completa: `vendor/bin/pest` + `pnpm typecheck` + `pnpm lint`.
    - **Gate**: tutto verde.

## Rischi e punti di attenzione

- **`Invoice::booted()` esistente è "pulisce tutto"**: oggi quando lo status non è `sent`, fa `$invoice->sent_at = null`. Estendendo a `canceled`, **bisogna preservare `sent_at`** quando lo stato passa a `canceled` (altrimenti il customer perde la visibilità della fattura, dato che il filtro è `sent_at !== null`). Il pattern Production canonico è "valorizza il nuovo, lascia stare gli altri" — replicarlo, non copiare la logica attuale di Invoice.
- **Backfill dati esistenti**: la migration crea colonne nullable e lascia null per i record esistenti. È **giusto così** — non retro-popolare con `updated_at` (sarebbe un timestamp falso). Le sezioni gestiscono il null nascondendo la riga. Comunicarlo nel changelog/PR.
- **Eager loading**: il payload aggiornato non aggiunge nuove relazioni (legge solo colonne nuove sui modelli già caricati). Verificare comunque che `selectedSupplier` includa la pivot completa con `selected_at` dopo la modifica al `withPivot()` (rischio: cache di route/optimize che congela la definizione vecchia → `php artisan optimize:clear` come step "se non vedi i dati").
- **`OperationInvoiceStatusBadge.vue` ha un `default: '--'`**: questo nasconde silenziosamente errori se ci si dimentica di aggiungere il case `canceled` — non aspettarsi un errore TS, fare la verifica visiva.
- **Test esistenti che cambiano semantica, non solo asserzioni**: il test "customer non vede le fatture non ancora inviate" (line 223) oggi assume che invoices abbia `count` e `empty`. Con la nuova shape, il count va su `sent_ids.length` + (eventualmente) `canceled_count`. **Riscrivere il test**, non solo aggiungere asserzioni — altrimenti si testa una shape morta.
- **N+1 sui nuovi counter `rejected_count` / `canceled_count`**: si calcolano in PHP sulla collection già caricata (`$operation->quotes->where(...)->count()`), nessuna query extra. Verificarlo con `Telescope` o `DB::listen` durante lo smoke test.
- **Decommissione `SummaryCard.vue`**: prima di cancellarlo, fare un `grep` completo in tutto `resources/` (non solo in `pages/operations/`). Potrebbe essere importato da qualche test e2e o storybook che non è venuto fuori dall'analisi.
- **Wayfinder routes**: i bottoni "Vai a [Tab]" non navigano via URL ma cambiano la tab attiva (logica già in `Show.vue`). Verificare il pattern con cui le altre tab si attivano (probabilmente `change-tab` event o param query) e replicarlo — non inventare un nuovo meccanismo.

## Domande aperte

Nessuna. Le specifiche coprono ambiguità e edge case; le divergenze rispetto al codebase reale sono catturate nella sezione "Diff" sopra e non bloccano l'implementazione.

## Ordine di commit suggerito

1. **`feat(db): aggiungi confirmed_at, canceled_at, selected_at`** — solo le 3 migration + nuovo case enum + cast/fillable sui modelli (nessuna logica nuova). Test esistenti restano verdi.
2. **`feat(models): sync automatico timestamp su transizione di stato`** — `Prescription::booted()`, estensione `Invoice::booted()`, aggiornamento `OperationService::selectSupplier()` + `Operation::suppliers()` `withPivot`. Include i 3 test di sync. (Step 3-5)
3. **`refactor(types): estrai OverviewPayload in tipo condiviso`** — solo movimento del tipo, nessun cambio di shape ancora. Pulisce duplicazione admin/customer prima della riscrittura. (Step 7 parziale)
4. **`feat(backend): nuova shape payload Panoramica`** — riscrittura `buildOverviewPayload` + aggiornamento `OperationOverviewTest` (asserzioni + nuovi test su sezioni). (Step 6 + 7 finale)
5. **`feat(frontend): componenti OverviewSection + readonly QuoteCard + badge canceled`** — Step 8-11. Include il bottone "Annulla" su InvoiceCard admin con il relativo test feature.
6. **`feat(frontend): riscrittura OverviewTab admin e customer`** — Step 12-14, decommissione `SummaryCard`. Smoke test browser inclusi.
7. **`docs(ui): aggiorna UI_ACTIVITY_LOG`** — Step 15 (può essere accorpato al commit 6 se preferito).
