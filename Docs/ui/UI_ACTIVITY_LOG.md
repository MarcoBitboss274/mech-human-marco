# UI Activity Log

## 2026-04-24 — Revisione prescrizione: wizard edit per stato `in_review`

- `pages/operations/Create.vue` (admin) e `pages/workspace/operations/Edit.vue` (workspace): titolo pagina e bottone di submit finale condizionali sullo stato della prescrizione editata. In "revision mode" (prop `wizard.prescriptionStatus === 'in_review'`): titolo → "Revisiona prescrizione", bottone finale → "Invia revisione". Altrimenti copy invariato.
- Il submit del wizard in revision mode invia `submit_revision: true` al backend, che dopo aver salvato i campi chiama `PrescriptionService::sendPrescription` per portare la prescrizione da `IN_REVIEW` a `REVISED`. Stato operation non toccato, `expire_at` invariato. Il "Salva bozza" in revision mode non fa sendPrescription (rimane IN_REVIEW).
- `types/Operation.ts`: aggiunto `submit_revision: boolean` a `OperationCreateWizardForm`.
- Nessuna modifica CSS: stessa struttura/classi del wizard create.

## 2026-04-24 — Revisione prescrizione: stati, badge, dialog motivo, banner workspace

- `components/prescriptions/PrescriptionStatusBadge.vue`: aggiunti due nuovi stati — `in_review` (classi `!bg-orange-200 border-orange-500 !text-orange-700`, label "In revisione") e `revised` (classi `!bg-sky-200 border-sky-500 !text-sky-700`, label "Revisionata").
- `pages/operations/partials/PrescriptionTab.vue` (admin): rimosso il bottone "Resetta" (e relativo handler). Aggiunti bottoni condizionali: "Modifica" ora visibile anche per `in_review` (oltre a `draft`), "Invia revisione" per `in_review` (submit diretto senza dialog — l'admin è operatore interno, non firma disclaimer legale), "Conferma presa in carico" per `sent`/`revised`, "Richiedi revisione" (`variant="outline"`) per `sent`/`revised`/`confirmed`. Nuovo `BbDialog` "Richiedi revisione al customer" con `BbTextarea` motivo (validazione 5–2000 caratteri) + contatore `text-xs text-gray-500`. Nuova card "Motivo della revisione" (`.operations-show__revision-card`, border/bg arancio) mostrata per `in_review`/`revised` quando `latest_revision_reason` è presente.
- `pages/workspace/operations/partials/PrescriptionTab.vue` (workspace/customer): aggiunti banner "Revisione richiesta" (`.operations-show__revision-banner`, arancio) per `in_review` con testo del motivo + hint; messaggio informativo "Prescrizione revisionata inviata, in attesa di conferma" (`.operations-show__revision-info`, azzurro) per `revised`. Bottone "Modifica" ora visibile anche per `in_review`; nuovo bottone "Invia revisione" (riusa il dialog Disclaimer esistente) per `in_review`.
- `components/activity/ActivitySlider.vue`: label rivisti — ora la sidebar preferisce `description` (testo umano già salvato dai log di revisione) e ha mappa fallback per gli event `prescription_revision_requested` / `prescription_resubmitted` / `prescription_confirmed`.
- Tutte le classi sono locali ai tre SFC (namespace `.operations-show__*`). Valori in pixel pari (`p-4` 16px, `mt-2` 8px, `gap-2` 8px).
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

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
