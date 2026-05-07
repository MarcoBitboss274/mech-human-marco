# Piano: Area Fornitore — Pagina Lavorazioni e Dettaglio Lavorazione

**Specifiche**: [specifiche-area_fornitore_lavorazioni.md](../specs/specifiche-area_fornitore_lavorazioni.md)

**Dipendenza**: Fase 1 di [piano-area_fornitore.md](piano-area_fornitore.md) (pivot `supplier_user`, ruolo workspace supplier, stati `active`/`pending` ancora da risolvere — vedi Domande aperte).

## Stato del codebase

### Già presente e da riusare

- **Enum `SupplierVisibleStatusEnum`** ([SupplierVisibleStatusEnum.php](../../app/Enums/SupplierVisibleStatusEnum.php)) — i 6 valori previsti dalle specifiche sono già definiti con label IT.
- **Accessor derivato** ([Operation.php:65-79](../../app/Models/Operation.php#L65-L79)) — `getSupplierVisibleStatusAttribute()` già nel `$appends` e con la mappatura completa (`canceled_at` prevale, `requested → assigned_waiting_documents`, ecc.). **Manca solo** la transizione "documenti caricati ⇒ `documents_sent`": oggi `requested` con N documenti torna sempre `assigned_waiting_documents`.
- **Relazione `Operation::selectedSupplier()`** ([Operation.php:215-221](../../app/Models/Operation.php#L215-L221)) — già con `wherePivot('selected', true)`.
- **Relazione chat customer** `chatMessages()` / `chatReads()` ([Operation.php:226-237](../../app/Models/Operation.php#L226-L237)) e tutto lo stack chat funzionante: [ChatController.php](../../app/Http/Controllers/ChatController.php), `ChatService`, eventi `OperationChatMessageSent`/`OperationChatUnreadUpdated`, [ChatSlider.vue](../../resources/js/components/chat/ChatSlider.vue) (oggi parametrizzato solo per `operationId`/route hardcoded `operations.chat.*`).
- **Tabelle modello chat** ([2026_03_18_120000_create_operation_chat_messages_table.php](../../database/migrations/2026_03_18_120000_create_operation_chat_messages_table.php), `..._reads_table.php`) — la migration "fornitore" ne è copia 1:1, basta cambiare nome tabella + foreign key naming.
- **Spatie Media Library** già usata su [User](../../app/Models/User.php#L378) e [Invoice](../../app/Models/Invoice.php#L111). `Operation` **NON** la usa ancora — è il primo ingaggio.
- **Pagine Vue scheletro** [Index.vue](../../resources/js/pages/workspace/supplier/operations/Index.vue) e [Show.vue](../../resources/js/pages/workspace/supplier/operations/Show.vue) — già renderizzano tabella base + dettaglio con BbAlert inline. Vanno **estese**, non riscritte.
- **Controller** [WorkspaceSupplierController::operationsIndex/Show](../../app/Http/Controllers/WorkspaceSupplierController.php#L132-L186) con autorizzazione tramite `supplierWorkspaceAbility` e gate `workspace.supplier.operations.view`. Già fa search base e applica il filtro "operation con pivot di quel supplier".
- **Routes workspace supplier** ([web.php:259-260](../../routes/web.php#L259-L260)).
- **Componente `OperationNotice.vue`** ([OperationNotice.vue](../../resources/js/components/operations/OperationNotice.vue)) — gestisce già `scope: 'admin' | 'workspace'`. Estendibile a `'supplier'` aggiungendo blocchi nella `hintItems` filtrati per scope (pattern già in uso).
- **`SuppliersTab.vue` lato admin** ([SuppliersTab.vue](../../resources/js/pages/operations/partials/SuppliersTab.vue)) — UI multi-fornitore con add/select/remove. Va **rifatto** in chiave "1 slot".
- **`OperationService::selectSupplier()`** ([OperationService.php:986-1014](../../app/Services/OperationService.php#L986-L1014)) — oggi azzera `selected` su tutti i pivot e setta `selected=true` su uno. Cambia logica.

### Da creare ex novo

- Media collection `supplier_documents` su `Operation` (richiede `implements HasMedia` + `use InteractsWithMedia` + `registerMediaCollections()`, oggi assenti sul modello).
- Endpoint upload/delete documenti fornitore (sia versione admin sia versione workspace supplier).
- Sezione "Documenti del caso" lato supplier: legge media collection esistente sulla Prescription (verificare in fase di implementazione che esista, altrimenti la specifica dipende da Prescription media già wired da altri piani).
- Nuova migration + modelli + controller + eventi per chat fornitore.
- 4 Notification class + integrazione nei service di transizione stato.
- Refactor chat lato admin a 2 tab.
- Filtri lista lavorazioni: stato fornitore, stato documenti, riferimento, lotto (oggi c'è solo `query` unico).

### Convenzioni da seguire

- Form Request dedicate in `app/Http/Requests/...` (vedi cartella `Chat/`, `Supplier/`).
- Authorization via Gate stringa (`Gate::authorize('supplierWorkspaceAbility', '...')`) per workspace supplier; `can:'...'` middleware per admin (vedi web.php area `/operations`).
- Inertia Render con payload appiattito; pagination tramite `useIndexPage` ([Index.vue:81](../../resources/js/pages/workspace/supplier/operations/Index.vue#L81)).
- Toast via `useMainToast()` ([SuppliersTab.vue:15](../../resources/js/pages/operations/partials/SuppliersTab.vue#L15)).

## Diff rispetto alle specifiche

- **Specifiche**: "implementare accessor `supplier_visible_status` su Operation" — **realtà**: già presente ([Operation.php:65](../../app/Models/Operation.php#L65)), ma **manca la regola "≥1 documento ⇒ `documents_sent`"**. Implicazione: estendere l'accessor con un check `$this->getMedia('supplier_documents')->isNotEmpty()` quando `status === requested`. Costo: 1 query media per accesso (mitigare con `loadMissing('media')` nei controller).
- **Specifiche**: "creare `app/Services/SupplierWorkspaceService.php`" — **realtà**: il `WorkspaceSupplierController` oggi orchestra direttamente Eloquent. Implicazione: non serve un service nuovo dedicato a `Workspace` se il volume di logica resta basso; concentrare la logica nel controller, **a meno che** non emerga duplicazione fra controller admin e controller workspace per upload/delete documenti — in quel caso introdurre `OperationSupplierDocumentService` (più focalizzato e riusabile).
- **Specifiche**: "nuovo `SupplierOperationController` in `Workspace/Supplier/`" — **realtà**: i metodi `operationsIndex/Show` sono già su [WorkspaceSupplierController.php](../../app/Http/Controllers/WorkspaceSupplierController.php). Implicazione: aggiungere `operationsUploadDocument()` / `operationsDeleteDocument()` sullo stesso controller (mantiene coerenza con dashboard/profile/team) **oppure** estrarre tutto il blocco `operations*` in un controller dedicato `Workspace\Supplier\OperationController` se la dimensione del file supera ~250 righe. Decidere a fine Fase 1; default: stessa classe.
- **Specifiche**: "campo `selected_at` del pivot per data assegnazione" — **realtà**: presente ([2026_04_24_120200_add_selected_at_to_operation_supplier_table.php](../../database/migrations/2026_04_24_120200_add_selected_at_to_operation_supplier_table.php)). Nessuna migration aggiuntiva.
- **Specifiche**: "filtri destinatari notifiche su utenti `active`" — **realtà**: la colonna/derivazione `active`/`pending` su `supplier_user` non è ancora risolta ([piano-area_fornitore.md L90-L107](piano-area_fornitore.md#L90-L107)). Implicazione: la Fase 2 di questo piano è bloccata fino a quando piano-area_fornitore non chiude la sua Domanda aperta su `active`/`pending`.
- **Specifiche**: "modello multi-supplier resta in DB ma UI mostra solo `selected=true`" — **realtà**: `OperationService::selectSupplier()` ([L986](../../app/Services/OperationService.php#L986)) e `addSupplier`/`removeSupplier` lato controller sono ancora orientati a multi-record. Implicazione: nessuna migrazione dati, ma serve cambiare i 4 metodi controller (`OperationController::addSupplier/selectSupplier/removeSupplier/updateSupplierStatus`) e il service in modo che `addSupplier` rifiuti se esiste già un `selected=true` e `removeSupplier`+`addSupplier` diventino "Cambia fornitore" (delete hard + create con `selected=true`).
- **Specifiche**: "Stato Documenti binario" e "Data scadenza = `prescription.expire_at`" — **realtà**: la colonna `expire_at` esiste ([2026_03_20_120000_add_send_and_expire_at_to_prescriptions_table.php](../../database/migrations/2026_03_20_120000_add_send_and_expire_at_to_prescriptions_table.php)). Per "Stato Documenti" serve filtrare per `whereHas` o `whereDoesntHave` sulla collection media — non c'è oggi, va aggiunto al query builder dell'index.

## Step ordinati

### Fase 1 — Backend documenti + vincolo 1 fornitore + accessor

1. Aggiungere `implements HasMedia` + `use InteractsWithMedia` + `registerMediaCollections()` a [Operation.php](../../app/Models/Operation.php) registrando la collection `supplier_documents` (singleFile = false). **Gate**: `php artisan tinker` → `Operation::first()->getMedia('supplier_documents')` non lancia eccezioni.
2. Estendere `getSupplierVisibleStatusAttribute()` ([Operation.php:65](../../app/Models/Operation.php#L65)) con il branch "≥1 supplier_document ⇒ `documents_sent`" quando lo stato è `requested`. **Gate**: nuovo Pest test `tests/Unit/SupplierVisibleStatusTest.php` con dataset 6 stati × {0 doc, 1 doc} → mappa attesa.
3. Refactor `OperationService::selectSupplier()` + `addSupplier`/`removeSupplier` controller con vincolo "1 solo fornitore selezionato alla volta": `addSupplier` crea pivot con `selected=true, selected_at=now()`; se ne esiste già uno selezionato → 422. "Cambia fornitore" = nuovo endpoint `PATCH operations.suppliers.swap` o riuso di `addSupplier` con detach esplicito. **Gate**: Pest feature test su `OperationController` che verifica 422 su doppia assegnazione e successo dopo detach.
4. Aggiungere endpoint upload/delete documenti fornitore lato admin ([OperationController](../../app/Http/Controllers/OperationController.php)): `POST operations/{operation}/supplier-documents` e `DELETE operations/{operation}/supplier-documents/{media}`. Form Request validano file (mimetypes/size coerenti con altre upload del progetto). **Gate**: feature test che carica 1 file, lo elenca, lo elimina.
5. Aggiungere endpoint upload/delete lato supplier su [WorkspaceSupplierController](../../app/Http/Controllers/WorkspaceSupplierController.php): `operationsUploadDocument()` e `operationsDeleteDocument()` con gate `supplier.operations.documents.upload`/`...delete`. La validazione "stato originale ≤ requested per delete" è server-side. **Gate**: feature test sui due endpoint con utente supplier autenticato; tentativo di delete a stato `in_progress` → 403.
6. Aggiungere policy/gate: `OperationPolicy::accessAsSupplier(User, Operation)` che verifica `Operation::selectedSupplier()` matcha `user->suppliers` ([SupplierWorkspaceAuthorizationService.php](../../app/Services/SupplierWorkspaceAuthorizationService.php)). Usata in `operationsShow` per restituire 403 + redirect+toast (specs L108). **Gate**: feature test "supplier B prova ad aprire detail di operation assegnata a supplier A" → 403.

### Fase 1 — Frontend area supplier

7. Estendere [Index.vue](../../resources/js/pages/workspace/supplier/operations/Index.vue): aggiungere colonne "Stato Documenti" (badge derivato da `supplier_documents_count`), "Data assegnazione" (`pivot.selected_at`), filtri (riferimento, lotto, stato lavorazione, stato documenti) e default order `expire_at` ASC con NULL last. Adeguare il payload del controller (`withCount('media as supplier_documents_count')` + `pivot.selected_at` esposto). **Gate**: visivo — login come utente supplier, lista mostra tutte le colonne, ogni filtro restringe correttamente i risultati.
8. Estendere [Show.vue](../../resources/js/pages/workspace/supplier/operations/Show.vue) con tab unica e 3 sezioni (Documenti del caso / Documenti aggiuntivi M&H / Documenti da caricare). La sezione "Documenti da caricare" usa upload multi-file con barra progresso; rimozione gated lato Vue da `operation.supplier_visible_status` (`assigned_waiting_documents` o `documents_sent`). **Gate**: visivo — upload + lista + delete funzionano; in stato `under_evaluation` il bottone delete è disabled.
9. Estendere `OperationNotice.vue` (oppure creare `OperationSupplierAlert.vue` se l'accoppiamento al codice esistente diventa difficile) con i 6 banner della specifica L192-L199, filtrati da `scope === 'supplier'`. Mutua esclusione con priorità nell'ordine documentato. **Gate**: smoke test browser su una operation per ciascuno dei 6 stati visibili.

### Fase 1 — Frontend area admin (tab Fornitore)

10. Riscrivere [SuppliersTab.vue](../../resources/js/pages/operations/partials/SuppliersTab.vue) come "1 slot": empty state con "Aggiungi fornitore" → modale select singola → `POST` con `selected=true`; stato "presente" mostra card singola + dialog "Cambia fornitore" (conferma con messaggio "i documenti del precedente resteranno visibili al nuovo"); badge stato `OperationSupplierStatusEnum` cliccabile rimane invariato. **Gate**: visivo — flow add/change/status update; tentativo di add con uno già presente → 422 visualizzato come errore form.
11. Aggiungere sotto la card la sezione "Documenti fornitore" con elenco (nome, autore, data) + azioni Scarica/Elimina (admin sempre, anche post-`requested`). **Gate**: visivo — upload via UI fornitore, file appare nel tab admin, admin può eliminarlo a stato `production`.

### Fase 2 — Chat fornitore + Notifiche (sblocco dopo chiusura `active/pending`)

12. Migration `create_operation_supplier_chat_messages_table` + `..._reads_table` (clone delle migration customer chat). Modelli `OperationSupplierChatMessage`/`OperationSupplierChatRead` + relazioni `Operation::supplierChatMessages()` / `supplierChatReads()`. **Gate**: `php artisan migrate` ok; un Pest unit test che crea un message via factory.
13. `SupplierChatController` (clone strutturale di [ChatController.php](../../app/Http/Controllers/ChatController.php)) + `ChatService` esteso con metodi `listSupplierMessages`/`sendSupplierMessage`/`markSupplierAsRead` (oppure nuovo `SupplierChatService`) + eventi `OperationSupplierChatMessageSent`/`...UnreadUpdated`. Nuove route `operations.supplier-chat.*` lato admin e `workspace.supplier.operations.chat.*` lato supplier. **Gate**: feature test che invia + marca read da entrambi i lati.
14. Refactor chat lato admin in [operations/Show.vue](../../resources/js/pages/operations/Show.vue): wrapper a 2 tab (Customer/Fornitore) con badge unread per tab; tab Fornitore disabled se `!operation.selected_supplier`. Riusare `ChatSlider.vue` parametrizzando endpoint/scope (props `chatScope: 'customer' | 'supplier'`). **Gate**: visivo — switch tab istantaneo; tab disabilitata + tooltip se nessun fornitore.
15. Slider chat su [Show.vue supplier](../../resources/js/pages/workspace/supplier/operations/Show.vue) con stesso `ChatSlider` parametrizzato (`chatScope: 'supplier'`). **Gate**: visivo — supplier scrive, admin riceve in tab Fornitore.
16. 4 `Notification` class (assegnazione, produzione confermata, annullamento lavorazione, annullamento produzione) con channel `mail`+`database`. Helper `NotificationService::sendToSupplier()` (introdotto da Fase 1 di piano-area_fornitore) per inoltro a `Supplier.mail` + utenti `active`. Hook nei punti di transizione: `OperationService::selectSupplier`, `OperationService::confirmProduction` (o equivalente), set di `canceled_at`, uscita da `production`. **Gate**: `php artisan tinker` triggera ogni evento → log mail in `storage/logs/laravel.log` con destinatario corretto.

## Rischi e punti di attenzione

- **Eager-load media e N+1**: la nuova logica accessor `documents_sent` legge `media`. Senza `withCount` o `with('media')`, la lista lavorazioni esegue 1 query per riga. Il payload `Index.vue` deve usare `withCount(['media as supplier_documents_count' => fn($q) => $q->where('collection_name', 'supplier_documents')])` e l'accessor deve preferire `supplier_documents_count` se valorizzato.
- **`addSupplier` legacy**: nelle Operation esistenti possono esserci più righe `operation_supplier`. La nuova UI espone solo `selected=true`, ma il payload `props.operation.suppliers` ([SuppliersTab.vue:35](../../resources/js/pages/operations/partials/SuppliersTab.vue#L35)) oggi le ritorna tutte. Filtrare lato controller (relazione `selectedSupplier()` invece di `suppliers()`) per evitare di esporre i fornitori "fantasma".
- **`canceled_at` su Operation in stato `production`**: la specifica L172-173 chiede 2 banner distinti (annullata vs produzione annullata). L'accessor oggi mappa `canceled_at != null` → `canceled` indipendentemente dallo stato originale. Per distinguere "produzione annullata" servono dati extra (es. `previous_status` in activity log via `LogsActivity`, già attivo su Operation [L242](../../app/Models/Operation.php#L242)). Confermare il meccanismo in implementazione (vedi Domanda aperta #2).
- **Soft-delete su Operation**: `Operation` usa `SoftDeletes`. La cascade dei chat messages (`cascadeOnDelete` nelle migration) si applica solo su force-delete. Confermare che il comportamento soft-delete è coerente per la chat fornitore (replicare la stessa scelta).
- **Spatie Media Library su modello con `LogsActivity`**: la registerMediaCollections aggiunge eventi che possono interagire con il log; verificare che `getActivitylogOptions().logOnly(['status'])` continui a non loggare media events.
- **ChatSlider hardcoded routes**: oggi `route('operations.chat.messages', operationId)` è cablato dentro [ChatSlider.vue:79](../../resources/js/components/chat/ChatSlider.vue#L79). Per riusarlo a 2 scope serve introdurre prop `routePrefix` o `chatScope`. Refactor invasivo ma contenuto.
- **Documenti del caso = media Prescription**: la sezione "Documenti del caso" lato supplier dipende dalla collection media già registrata su Prescription (Prescrizione + Allegati). Verificare in fase di implementazione: se non esiste, è un blocker non previsto dalle specifiche.
- **Permessi `chat.read`/`chat.send`**: oggi sono ability admin ([web.php:38-45](../../routes/web.php#L38-L45)). Per la chat fornitore servono nuove ability `supplier.chat.*` e gate workspace coerenti con [SupplierWorkspacePermissionMap.php](../../app/Services/SupplierWorkspacePermissionMap.php).

## Domande aperte

1. **Quando l'accessor deve passare a `documents_sent`?** Specs L62 dice "`requested` con almeno 1 documento fornitore caricato". Domanda: anche se è stato caricato dall'admin di M&H (che la specifica permette esplicitamente, L160-161)? Probabile sì, ma da confermare. Opzioni: (a) qualunque media in `supplier_documents` conta, (b) solo media con `uploaded_by_user_id` di un utente supplier conta. Default proposto: (a) — coerente con "stato documentale binario" della specifica.
2. **Distinzione fra "annullata" e "produzione annullata"** (specs L172-173 e UX L195). L'accessor oggi non distingue. Opzioni: (a) introdurre colonna `production_canceled_at` su Operation, (b) derivare da activity log `LogsActivity` (già attivo); leggere l'ultima transizione di status per capire se l'Operation veniva da `production`. Default proposto: (a) — meno fragile, una sola query, semantica chiara. Richiede mini-migration in Fase 1.
3. **Filtro `active` per notifiche fornitore** — bloccato da [piano-area_fornitore.md Domanda aperta #1](piano-area_fornitore.md#L103). Senza decisione, Fase 2 step 16 non può partire.
4. **Visibilità "Documenti aggiuntivi M&H"**: la specifica (L34, L160) cita "funzionalità futura, fuori scope". Conferma: in questa implementazione la sezione esiste come placeholder vuoto e si nasconde se la collection è vuota? Default proposto: sì, sezione completamente nascosta finché non esistono media nella collection di riferimento (da definire — verosimilmente `prescription_supplier_notes` o simile, da confermare con specifiche future).

## Ordine di commit suggerito

1. `feat(operation): media collection supplier_documents + accessor documents_sent` (step 1-2 + test).
2. `feat(operation): vincolo 1 fornitore selezionato e cambio fornitore` (step 3 + test).
3. `feat(operation): endpoints upload/delete supplier_documents (admin + workspace)` (step 4-5 + policy step 6).
4. `feat(workspace-supplier): lista lavorazioni con filtri e dettaglio con sezioni documenti` (step 7-9).
5. `refactor(operations-admin): tab Fornitore a 1 slot + sezione documenti fornitore` (step 10-11).
6. `feat(chat): supplier chat — migration, modelli, controller, eventi` (step 12-13).
7. `feat(chat): refactor chat admin a 2 tab + slider supplier` (step 14-15).
8. `feat(notifications): 4 notifiche supplier + hook transizioni` (step 16) — solo dopo chiusura Domanda aperta #3.
