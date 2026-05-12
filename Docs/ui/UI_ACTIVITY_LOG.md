# UI Activity Log

## 2026-05-11 — Area Admin: Dashboard nascosta, landing su Lavorazioni (tab Attive)

- `components/layout/LayoutSidebar.vue`: rimossa voce "Dashboard" (icona `chart-bar`, prima voce di menu). Ora la sidebar admin parte direttamente da "Utenti".
- `app/Http/Controllers/HomeController.php`: il metodo `dashboard()` per utenti non-customer/non-supplier (admin, superadmin, agent) ora redirige a `to_route('operations.index', ['mode' => 'active'])` invece di renderizzare `Dashboard`. Customer continua su `workspace.index`, supplier su `workspace.supplier.index`. Rimosso import inutilizzato `Inertia\Inertia`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (post-login `store()`): per utenti M&H (non-customer/non-supplier) bypass dell'`intended()` e redirect diretto a `route('operations.index', ['mode' => 'active'])`. Customer/supplier mantengono `redirect()->intended(route('dashboard'))` (poi HomeController instrada). Garantisce che l'admin atterri SEMPRE sulla tab "Attive" anche se la sessione era scaduta su un'altra pagina M&H.
- Effetti: entrando in `/`, `/dashboard`, o dopo il login, gli utenti M&H atterrano direttamente su `/operations?mode=active` (tab "Attive" della pagina Lavorazioni).
- File `resources/js/pages/Dashboard.vue` lasciato in place ma non più referenziato (page nascosta, non eliminata). La route `/dashboard` resta registrata (HomeController la usa come redirect target da `home`).

## 2026-05-11 — Nuovo stato "Produzione annullata" lato fornitore + flat section documenti

- **Stato fornitore "Produzione annullata"**: nuovo case `PRODUCTION_CANCELED = 'production_canceled'` su `SupplierVisibleStatusEnum` (label "Produzione annullata"). `Operation::getSupplierVisibleStatusAttribute` ora include nel mapping (in ordine, dopo `supplier_completed_at` e prima del match status) il check `production_canceled_at != null → PRODUCTION_CANCELED`. Quando l'admin annulla la produzione, il service già setta `production_canceled_at = now()` e resetta `supplier_completed_at`; un eventuale riconferma produzione (`confirmProduction`) rimette `production_canceled_at = null` → lo stato torna a `production_confirmed`.
- `components/operations/SupplierVisibleStatusBadge.vue`: nuovo case `production_canceled` con classi `!bg-red-200 border-red-500 !text-red-700` (badge rosso, label "Produzione annullata").
- **Sezione "Documenti fornitore" (admin)**: `pages/operations/partials/SuppliersTab.vue` → `.operation-suppliers__documents` ora ha solo `mt-6` (rimossi `rounded-lg border border-gray-200 bg-white p-4`). Titolo + contenuto sotto, senza card.
- **Sezione "Documenti da caricare" (fornitore)**: `pages/workspace/supplier/operations/Show.vue` → `.supplier-operation-show__section` ora ha solo `margin-top: 32px` (rimossi `padding: 16px`, `border: 2px solid`, `border-radius: 8px`). Titolo + contenuto sotto, senza card.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`. Valori pari (mt-6=24px, margin-top=32px, font-size titoli invariati).

## 2026-05-11 — Tab Fornitore (admin): solo download documenti, niente upload/elimina

- `pages/operations/partials/SuppliersTab.vue`: rimossi dal blocco `.operation-suppliers__documents` il bottone "Carica documento" + l'`<input type="file">` nascosto + il bottone rosso "Elimina" per riga documento. Sostituito il bottone elimina con un `BbButton` "Scarica" (variant `outline`, icona `download`, `href=doc.url target="_blank"`). Il filename rimane comunque cliccabile come prima. Rimossi anche gli helper non più usati: `onFileChange`, `deleteDocument`, refs `fileInput`/`uploading`/`deletingId`.
- `app/Http/Controllers/OperationController.php`: eliminati i metodi `uploadSupplierDocument()` e `deleteSupplierDocument()` (erano gli unici entry point admin per i documenti fornitore). Rimosso import ora inutilizzato `UploadOperationSupplierDocumentRequest`.
- `routes/web.php`: rimosse le route admin `operations.supplier-documents.store` (POST) e `operations.supplier-documents.destroy` (DELETE) — non più referenziate da nessun client.
- Lato fornitore (`WorkspaceSupplierController` → `operations.documents.store/destroy` + `OperationService::uploadSupplierDocument/deleteSupplierDocument` con `asAdmin: false`) **invariato**: il fornitore continua a poter caricare ed eliminare i documenti durante "Nuovo caso".

## 2026-05-11 — Chat lavorazione: allineamento tab bar al titolo

- `components/chat/OperationChatTabbed.vue`: aggiunta regola scoped con `:deep()` su `.bb-tab__label-boundary .bb-tab__label-container` → `padding-left: var(--bb-dialog-px)` (24px). Le tab "Chat Customer" / "Chat Fornitore" sono ora allineate orizzontalmente al titolo "Chat lavorazione" dell'offcanvas (entrambi a 24px dal bordo sinistro). Prima le tab partivano a 4px (default `--bb-ring-size` del label-container BbTab) mentre il titolo era a 24px (`--bb-dialog-px`). Valore pari (24px), nessuna modifica a `base.css` / `main.css` / `theming.css`.

## 2026-05-11 — Area Fornitore: Dashboard nascosta, landing su Lavorazioni

- `components/layout/workspace-supplier/LayoutSidebar.vue`: rimossa voce "Dashboard" (icona `chart-bar`, prima della voce "Lavorazioni"). Ora "Lavorazioni" è la prima voce di menu.
- `WorkspaceSupplierController::index()`: ora redirige a `workspace.supplier.operations.index` (era `workspace.supplier.dashboard`). Entrando in `/workspace/supplier` il fornitore arriva direttamente sulla lista lavorazioni.
- `WorkspaceSupplierController::dashboard()`: trasformato in semplice redirect a `workspace.supplier.operations.index` (rimossa renderizzazione `workspace/supplier/Dashboard` + Gate check `workspace.supplier.dashboard.view`). La route `/workspace/supplier/dashboard` resta registrata per non rompere eventuali bookmark/link.
- `Auth/InvitationController.php`: dopo l'accettazione dell'invito per `supplier_user`, ora `to_route('workspace.supplier.operations.index')` (era `workspace.supplier.dashboard`).
- File `pages/workspace/supplier/Dashboard.vue` lasciato in place ma non più referenziato (page nascosta, non eliminata). Enum `WorkspaceAbilityEnum::SUPPLIER_DASHBOARD_VIEW` e mapping in `SupplierWorkspacePermissionMap` lasciati invariati (dormienti).

## 2026-05-11 — Badge produzione (admin + fornitore) + alert completamento fornitore

- `components/productions/ProductionStatusBadge.vue` (admin): `confirmed` da verde (`bg-green-100`) → **giallo** (`!bg-yellow-200 border-yellow-500 !text-yellow-700`). `completed` da emerald (`bg-emerald-200`) → **verde** (`!bg-green-200 border-green-500 !text-green-700`). `canceled` invariato (rosso).
- `components/operations/SupplierVisibleStatusBadge.vue` (fornitore): `new_case` da giallo → **azzurro** (`!bg-sky-200 border-sky-500 !text-sky-700`); `production_confirmed` da viola → **giallo** (`!bg-yellow-200 border-yellow-500 !text-yellow-700`); `completed` allineato a `!text-green-700` (era `!text-green-600`).
- `pages/operations/Show.vue`: nuovo banner `.operations-show__supplier-completed-banner` (giallo) visibile in cima al dettaglio (sopra `OperationNotice`) quando `operation.supplier_completed_at` è valorizzato. Dismissibile con bottone "×". Persistenza: `localStorage[op-<id>-supplier-completed-<isoTimestamp>-seen]` settato al mount → alla seconda apertura il banner non riappare. Se il fornitore completa nuovamente la produzione dopo annullamento (nuovo `supplier_completed_at`), la chiave cambia e il banner ritorna. **Stile allineato a `.operation-notice__item--alert`** di `OperationNotice.vue` (border-yellow-300, bg-yellow-50, px-2 py-1.5, icona BbIcon warning + titolo `text-sm font-semibold text-yellow-700`); aggiunto `mb-4` di spazio sotto il banner. Pixel pari (mt-4=16, mb-4=16, py-1.5=6, px-2=8, gap-2/4=8/16, h-6/w-6=24).
- Backend: `OperationService::getAdminShowData` e `getAgentShowData` ora espongono `supplier_completed_at` come attributo top-level su `operation`, letto via `DB::table('operation_supplier')->where('selected', true)->value('supplier_completed_at')`. Prima era esposto solo lato workspace fornitore.
- Nessuna modifica a `base.css` / `main.css` / `theming.css`.

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
