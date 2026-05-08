# UI Activity Log

## 2026-05-08 — OverviewSection: pulsante arrow di redirect più piccolo

- `resources/css/admin_view_override.css`: nuova regola su `.overview-section__arrow-btn.bb-button--icon` → forza il bottone a 18×18px e l'icona interna (`.bb-icon` / `.bb-icon svg`) a 12×12px. Padding del button azzerato. Valori pari (design system).
- Nessuna modifica a `OverviewSection.vue` né a `base.css` / `main.css` / `theming.css`.

## 2026-05-06 — UI override globali admin/workspace + restyling header/counter Lavorazioni

- **Nuovo file `resources/css/admin_view_override.css`** importato dopo `main.css` in `resources/js/app.ts`. Contiene override globali (non si tocca alcuno dei 3 CSS base):
  - **Spazio titolo↔tab bar**: `.admin-view > div.mt-4:has(.bb-tab)` (e `.workspace-view`) → `margin-top: 4px` invece di 16px → tutte le Index admin/workspace hanno tab più vicine al titolo, senza modificare i template.
  - **Spazio label↔input nei filtri**: la barra `.flex.flex-wrap.items-end` figlia diretta di `.admin-view`/`.workspace-view` setta `--bb-label-spacing-y: 2px` (var di bitboss-ui, default 6px) sui suoi `.bb-base-input-outer-container` → label e input più ravvicinati solo nei filtri, non nei form.
- `pages/operations/Index.vue`: spostato il pulsante "Esporta tutte" **a sinistra del titolo** (nuovo wrapper `.operations-header__left` con `display: inline-flex; align-items: center; gap: 12px`); a destra resta solo "+ Nuova lavorazione". Rimosse le rule scoped locali ora coperte dal nuovo override globale; ripristinato `mt-4` sul wrapper delle tab (il valore effettivo lo dà la rule globale).
- `components/operations/OperationsResultsBar.vue`: `align-items: baseline` (era `center`) per allineare il testo del link "Esporta" alla baseline del testo del counter "Lavorazioni X: N".
- `components/operations/ExportDropdown.vue` (variante link): `padding: 0`, `margin: 0`, `align-items: baseline`, `vertical-align: baseline` → testo perfettamente allineato al counter accanto.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

## 2026-05-06 — Area Fornitore: utenti supplier, detail fornitore admin, workspace supplier

- Pivot `supplier_user` con unique su `user_id` (1 utente ↔ 1 fornitore). Enum `SupplierUserRoleEnum` (`admin`/`member`). Relazioni `Supplier::users()` / `Supplier::admins()` / `User::suppliers()`+`supplier()`+`supplierRole()`.
- `pages/users/partials/Form.vue`: nuova sezione condizionale `needSupplier = form.role === 'supplier'` con due `BbSelect` (Fornitore + Ruolo nel team). Singolo oggetto `supplier_relation = { supplier_id, role }` (non array). Nuova prop `defaults` con `{ role, supplier_id, locked }` per il flusso "Invita membro" che pre-compila e blocca i due select.
- `pages/users/Index.vue`: legge query string `?create=1&role=...&supplier_id=...` su `onMounted`, apre il modal create con `formDefaults` impostati e bloccati.
- Nuovo `pages/suppliers/Show.vue` (lato M&H) con due `BbTab`: "Dettagli fornitore" (riusa `suppliers/partials/Form.vue`) e "Membri" (tabella + bottone "Invita membro" che linka a `users.index?create=1&role=supplier&supplier_id=X`). Dropdown ruolo inline e bottone "Rimuovi" per ogni membro.
- `pages/suppliers/Index.vue`: aggiunto pulsante "Visualizza" sulla riga che naviga al detail.
- Backend: `SupplierService::attachUser/detachUser/updateUserRole` con regola "almeno 1 admin" enforcement (`assertCanRemoveAdmin`). Validation `supplier_relation` in `StoreUserRequest` con `required_if:role,supplier`. `UserService::store` orchestra l'attach/detach via `SupplierService`. Email anagrafica `Supplier.mail` resta sempre obbligatoria (validation invariata).
- Nuove route admin M&H: `suppliers.show`, `suppliers.members.store/update/destroy` protette da `can:suppliers.members.manage`. Permessi `suppliers.show` e `suppliers.members.manage` aggiunti a `PermissionsUpgrade`.
- `NotificationService::sendToSupplier(Supplier, Notification)`: helper destinatari che instrada un'email a `Supplier.mail` + ogni utente associato (deduplica per email lower-case). Da usare come building block per le notification operative coperte da `specifiche-area_fornitore_lavorazioni.md`.
- Nuovi gate workspace: `WorkspaceAbilityEnum::SUPPLIER_*` (7 valori) + `SupplierWorkspacePermissionMap::abilitiesFor(SupplierUserRoleEnum)` + `SupplierWorkspaceAuthorizationService::canForUser(User, ability)`. Gate `supplierWorkspaceAbility` registrato in `AppServiceProvider`. `HandleInertiaRequests` espone `auth.supplier_role` e `auth.supplierWorkspacePermissions`.
- Middleware `WorkspaceSupplier` aliasato `workspace.supplier`: redirige all'orphan se l'utente supplier non ha riga in `supplier_user` (con guard contro loop sul percorso orphan stesso).
- Route group `/workspace/supplier` (middleware `role:supplier`) + sotto-gruppo con `workspace.supplier`: `dashboard`, `profile`, `settings`, `team` (admin only via Gate), `orphan` (fuori dal sotto-gruppo per evitare redirect loop).
- Nuovo `WorkspaceSupplierController` con check Gate per ogni endpoint (settings/team admin only). `UpdateWorkspaceProfileRequest::authorize` esteso ad accettare anche `isSupplier()`.
- Frontend workspace supplier: nuovo `layouts/WorkspaceSupplierLayout.vue` (sidebar minimale 240px + main, voci condizionate da `auth.supplier_role === 'admin'`); pagine Vue `Dashboard.vue` (placeholder), `Profile.vue` (form dati personali + cambio password opzionale), `Settings.vue` (admin only — form anagrafica fornitore), `Team.vue` (admin only — tabella membri con select ruolo inline e rimuovi), `Orphan.vue` (alert + logout, layout Empty per non finire dentro la sidebar).
- Namespace CSS scoped nei nuovi componenti (`.supplier-workspace__*`, `.profile-form`, `.settings-form`, `.orphan-page`). Valori in pixel pari (16/24/32 padding, 240/280/480/720/960 width). Nessuna modifica a `base.css` / `main.css` / `theming.css`.

## 2026-05-06 — Lavorazioni admin: filtro Agente, counter testuale, esportazione CSV/Excel

- `pages/operations/Index.vue` (admin/agent): aggiunto filtro **Agente** (`BbSelect`) tra "Richiedente" e "Tipologia", visibile solo a Superadmin/Admin (`v-if="isAdmin"`). Carica via `useSelect('agents')`. Querystring param `agent_id`.
- Rinominate le label dei due `BbDatePickerInput` range: "Data di scadenza" → "Scadenza da: - a:", "Data di invio" → "Invio da: - a:". Stessi controlli, stessi querystring param (`expire_at_from/to`, `send_at_from/to`).
- Nuovo componente `components/operations/OperationsResultsBar.vue`: barra sopra la tabella con la stringa descrittiva del filtraggio corrente e il numero totale di risultati. Slot `#actions` per ospitare il pulsante Esporta. CSS scoped namespace `.operations-results-bar__*`, valori in pixel pari (8px gap/padding, 14px font).
- Nuovo componente `components/operations/ExportDropdown.vue`: wrappa `BbDropdown` (bitboss-ui) con due voci "Esporta CSV" / "Esporta Excel". Stato loading sul bottone, toast di avvio/errore, gestione stato disabled con `BbTooltip` quando non ci sono risultati. Riceve `exportUrl`, `label`, `disabled`, `disabledReason`.
- Header pagina: aggiunto pulsante **"Esporta tutte"** (`ExportDropdown`) immediatamente prima di "+ Nuova lavorazione", visibile solo se `can('operations.export')`.
- Counter + `ExportDropdown` per export contestuale: la barra `OperationsResultsBar` riceve nello slot `#actions` il dropdown configurato sull'URL `route('operations.export')` con i filtri correnti propagati come query string. Disabilitato con tooltip "Nessun risultato da esportare" quando `resultsTotal === 0`.
- Stringa counter composta lato backend (`OperationService::buildResultsLabel`): tab corrente come prima clausola obbligatoria (`Lavorazioni Bozze|Attive|Archiviate`), poi una clausola descrittiva per ogni filtro attivo (es. `struttura Studio Rossi`, `agente Mario Bianchi`, `scadenza 01/01/2026 - 31/01/2026`), suffisso `: N` con `N` formattato in italiano (`number_format($n, 0, ',', '.')`).
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

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
