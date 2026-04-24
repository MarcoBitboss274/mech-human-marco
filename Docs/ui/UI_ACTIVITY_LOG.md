# UI Activity Log

## 2026-04-24 — Tab Panoramica: redirect inline sul titolo di sezione

- `components/operations/overview/OverviewSection.vue`: rimosso il footer con bottone "Vai a [Tab]"; il redirect è ora un `BbButton icon="arrow-right" size="xs"` (stesso stile dei pulsanti icon-only Archivia/Cancella/Riattiva della lista operazioni: quadrato, colore primario `--bb-primary`) accostato a destra del titolo (gap `8px`). L'icona interna (`.bb-button__icon`) viene ruotata `-45deg` via CSS per ottenere la freccia obliqua verso l'alto-destra. Emit `go-to` al click; stato disabled ereditato da BbButton. Solo il button è cliccabile: il titolo resta testo statico.
- `pages/operations/partials/OverviewTab.vue` e `pages/workspace/operations/partials/OverviewTab.vue`: nelle sezioni Prescrizione e Produzione rimosso il badge di stato (`OperationStatusBadge`) dallo slot `#counters`; i contatori "N prescrizioni" / "N produzioni" restano visibili solo quando `count > 1`. Rimosso anche l'import ora inutilizzato di `OperationStatusBadge` da entrambi i file.
- `.overview-section` padding verticale aumentato da `py-4` (16px) a `py-8` (32px) per separare maggiormente le sezioni.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

## 2026-04-24 — Tab Panoramica: sezione Riepilogo aggiornata

- Nuovo componente `components/operations/overview/OverviewSection.vue`: header con titolo + slot `#counters`, slot di default per il contenuto, footer con bottone "Vai a [Tab]" (emit `go-to`). Stile: niente box card, separatore inferiore tra sezioni.
- `components/operations/overview/SummaryCard.vue` rimosso (non più referenziato): le card riassuntive sono state sostituite da sezioni con timestamp specifici degli eventi (Inviata/Confermata/Selezionato/Annullata) al posto del generico "Aggiornato il".
- `components/operations/QuoteCard.vue`: aggiunta prop `readonly` che disattiva tutti i bottoni di azione (Accetta/Rifiuta/Modifica/Invia/Annulla/Elimina); usata nella sezione Preventivi quando esiste solo un preventivo accettato.
- `components/operations/OperationInvoiceStatusBadge.vue`: aggiunto case `'canceled'` (label "Annullata", classi `bg-red-100 border-red-400 text-red-700`).
- Riscritte `pages/operations/partials/OverviewTab.vue` (admin) e `pages/workspace/operations/partials/OverviewTab.vue` (customer): la colonna Riepilogo ora usa `OverviewSection`. Counter sintetici nell'header sezione (Preventivi: "N rifiutati · M annullati"; Fatture: "M annullate"). La sezione Attori a destra resta invariata.
- Nuovo tipo TS condiviso `types/Overview.ts` (`OverviewPayload` + sotto-tipi) — prima era duplicato dentro le due `OverviewTab.vue`.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

## 2026-04-24 — Tab Panoramica lavorazione

- Nuovi componenti riusabili: `components/operations/QuoteCard.vue`, `components/operations/InvoiceCard.vue`, `components/operations/overview/ActorCard.vue`, `components/operations/overview/SummaryCard.vue`.
- Nuove tab Panoramica: `pages/operations/partials/OverviewTab.vue` (admin) e `pages/workspace/operations/partials/OverviewTab.vue` (customer), collegate ai rispettivi `Show.vue` nello slot `#overview` (prima placeholder vuoto).
- Layout a due colonne (riepilogo entità a sinistra, attori a destra) con collasso su schermi stretti (`lg:grid-cols-[1fr_320px]`).
- Card riassuntive navigabili verso la tab specifica tramite emit `change-tab`; card inline (preventivo `sent`, fattura `sent`) con azioni complete per ruolo.
- Namespace CSS: `.operations-overview__*` (scoped nei SFC nuovi). Le classi `.operation-quotes__card*` e `.operation-invoices__card*` sono state migrate dentro `QuoteCard.vue` / `InvoiceCard.vue` e rimosse dai tab originali per evitare duplicati.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.
