# UI Activity Log

## 2026-04-24 — Tab Panoramica lavorazione

- Nuovi componenti riusabili: `components/operations/QuoteCard.vue`, `components/operations/InvoiceCard.vue`, `components/operations/overview/ActorCard.vue`, `components/operations/overview/SummaryCard.vue`.
- Nuove tab Panoramica: `pages/operations/partials/OverviewTab.vue` (admin) e `pages/workspace/operations/partials/OverviewTab.vue` (customer), collegate ai rispettivi `Show.vue` nello slot `#overview` (prima placeholder vuoto).
- Layout a due colonne (riepilogo entità a sinistra, attori a destra) con collasso su schermi stretti (`lg:grid-cols-[1fr_320px]`).
- Card riassuntive navigabili verso la tab specifica tramite emit `change-tab`; card inline (preventivo `sent`, fattura `sent`) con azioni complete per ruolo.
- Namespace CSS: `.operations-overview__*` (scoped nei SFC nuovi). Le classi `.operation-quotes__card*` e `.operation-invoices__card*` sono state migrate dentro `QuoteCard.vue` / `InvoiceCard.vue` e rimosse dai tab originali per evitare duplicati.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.
