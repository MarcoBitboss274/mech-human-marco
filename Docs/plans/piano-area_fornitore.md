# Piano: Area Fornitore — utenti supplier, detail fornitore admin, area di lavoro fornitore

**Specifiche**: [Docs/specs/specifiche-area_fornitore.md](../specs/specifiche-area_fornitore.md)

## Stato del codebase

**Esiste, da riusare 1:1 come pattern di riferimento**:
- Pivot `building_user` ([2026_02_11_120000_create_building_user_table.php](../../database/migrations/2026_02_11_120000_create_building_user_table.php)) + invite fields ([2026_02_12_000000_add_invite_fields_to_building_user_table.php](../../database/migrations/2026_02_12_000000_add_invite_fields_to_building_user_table.php)) — riferimento per `supplier_user`, ma le spec NON richiedono `invite_token`/`accepted_at` (vedi Domande aperte).
- [app/Enums/BuildingUserRoleEnum.php](../../app/Enums/BuildingUserRoleEnum.php) — schema esatto da clonare in `SupplierUserRoleEnum`.
- [app/Models/BuildingUser.php](../../app/Models/BuildingUser.php) — modello pivot di riferimento.
- [app/Models/User.php#L307-L313](../../app/Models/User.php#L307-L313) — relazione `buildings()` con `withPivot(['role','accepted_at','invite_token','is_new'])`.
- [app/Services/UserService.php#L83-L93](../../app/Services/UserService.php#L83-L93) — gestione condizionale `building_relations` in `store()`: pattern da replicare per `supplier_relation` (singolare, perché vincolo 1↔1).
- [app/Services/UserService.php#L209-L218](../../app/Services/UserService.php#L209-L218) — `sendWelcomeEmail(User)` già funzionante: usa `auth.password.broker` per generare token reset e invia [Welcome notification](../../app/Notifications/User/Welcome.php). **Già pronto** per il flusso onboarding supplier — basta passare `send_invite=true`.
- [resources/js/pages/users/partials/Form.vue#L64](../../resources/js/pages/users/partials/Form.vue#L64) — `needType = computed(() => form.role === 'customer')` + `building_relations` array. Pattern da replicare: `needSupplier = form.role === 'supplier'` con un singolo oggetto `supplier_relation`.
- [app/Services/WorkspacePermissionMap.php](../../app/Services/WorkspacePermissionMap.php) + [app/Enums/WorkspaceAbilityEnum.php](../../app/Enums/WorkspaceAbilityEnum.php) — pattern role→abilities per workspace customer; clonarlo per supplier.
- [app/Http/Middleware/Workspace.php](../../app/Http/Middleware/Workspace.php) — middleware customer (carica building da slug + verifica `canAccessBuilding`); pattern da clonare per supplier.
- [resources/js/layouts/WorkspaceLayout.vue](../../resources/js/layouts/WorkspaceLayout.vue) + [resources/js/layouts/workspace/WorkspaceHeaderLayout.vue](../../resources/js/layouts/workspace/WorkspaceHeaderLayout.vue) — layout customer; il workspace supplier può riusarli o forkarli a seconda del menu.
- [app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php](../../app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php) — registry Spatie: qui vanno aggiunti i nuovi permessi globali; le abilities di workspace stanno invece in `WorkspaceAbilityEnum`/`WorkspacePermissionMap` (sono concetti distinti).

**Non esiste, da creare da zero**:
- Migration `create_supplier_user_table`, `SupplierUserRoleEnum`, modello `SupplierUser` (se serve un Pivot dedicato), relazioni `Supplier::users()` e `User::supplier()`.
- `app/Services/SupplierService.php` — controlli `attachUser`/`detachUser`/`updateUserRole` con regola "almeno 1 admin" (oggi `SupplierService` esiste solo per CRUD basico, vedi [app/Services/SupplierService.php](../../app/Services/SupplierService.php)).
- `SupplierController::show($supplier)` — la route `Route::resource('suppliers')` oggi è `->only(['index','store','update','destroy'])` ([routes/web.php#L154](../../routes/web.php#L154)): manca `show`.
- `resources/js/pages/suppliers/Show.vue` (detail con tab Dettagli/Membri).
- Notifiche: nessuna `Notification` strutturata oggi parte verso il fornitore (vedi Diff #2). Nuovo helper `NotificationService::sendToSupplier(Supplier, Notification)` da progettare.
- Workspace supplier: middleware `WorkspaceSupplier`, route group, layout, pagine Vue Dashboard/Profile/Settings/Team/Orphan, abilities supplier.

**Convenzioni del progetto**:
- I `ModelService` (pattern `static fetch/applyFilters/applySearch/applySorts/store`) sono il punto centrale per scrivere logica. `SupplierService` segue questo pattern, va esteso lì.
- I permessi Spatie sono dichiarati in `PermissionsUpgrade` e applicati via `middleware('can:...')` o `Gate::authorize`. Le abilities workspace sono invece risolte via `WorkspacePermissionMap::abilitiesFor(Role)` e mappate su `Gate::define` (pattern noto nel progetto).
- Welcome email viene innescata passando `send_invite=true` al `UserService::store`. Niente flusso di invito separato per Fase 1.

## Diff rispetto alle specifiche

- **Specifiche**: "estensione `NotificationService` per inviare email operative a `Supplier.mail` + utenti active del fornitore" — **realtà**: oggi NESSUNA mail strutturata parte al fornitore. L'unico riferimento a `selectedSupplier->mail` ([OperationService.php:804](../../app/Services/OperationService.php#L804)) è solo costruzione di un `actors` payload, non un invio. **Implicazione**: in Fase 1 si crea solo l'**helper destinatari** (`NotificationService::sendToSupplier(Supplier, Notification)`) che instrada email + utenti `active` del fornitore. Le `Notification` class operative (assegnazione/produzione confermata/annullamento) sono in scope di [specifiche-area_fornitore_lavorazioni.md](../specs/specifiche-area_fornitore_lavorazioni.md), non qui.

- **Specifiche**: "Stato `pending`/`active` derivato senza nuova colonna" — **realtà**: nel sistema non c'è un campo nativo che testimoni "password impostata dall'utente". `users.invited_at` viene settato in `sendWelcomeEmail` ma resta valorizzato anche dopo che l'utente ha completato il setup. `email_verified_at` non è auto-popolato dal password reset. **Implicazione**: serve una decisione (vedi Domande aperte). Senza, il filtro `active` per le notifiche e per i badge nel tab Membri non è implementabile in modo affidabile.

- **Specifiche**: "select fornitore singolo per utente" — **realtà**: il pattern frontend ([Form.vue#L83](../../resources/js/pages/users/partials/Form.vue#L83)) usa un array `building_relations`. **Implicazione**: usare nel form un singolo oggetto `supplier_relation = { supplier_id, role }` (non array), e in `UserService::store` `Supplier::find()->users()->sync([$user_id => ['role'=>...]])` chiamato dalla parte Supplier (oppure detach+attach manuale). Niente `building_relations` shape per supplier.

- **Specifiche**: nuovo gate `supplier.settings.*` / `supplier.team.*` — **realtà**: il progetto separa nettamente *Spatie permissions* (globali, in `PermissionsUpgrade`) dalle *workspace abilities* (mappate role→abilities in `WorkspacePermissionMap`). **Implicazione**: questi gate sono workspace abilities, non Spatie permissions. Aggiungere voci a `WorkspaceAbilityEnum` (es. `SUPPLIER_SETTINGS_VIEW`, `SUPPLIER_SETTINGS_UPDATE`, `SUPPLIER_TEAM_VIEW`, `SUPPLIER_TEAM_MANAGE`) e creare un `SupplierWorkspacePermissionMap::abilitiesFor(SupplierUserRoleEnum)`. La soluzione "estendere `WorkspacePermissionMap`" non funziona perché firma il param come `BuildingUserRoleEnum`: meglio classe nuova.

- **Specifiche**: route `Route::resource('suppliers')` con `show` — **realtà**: oggi `->only(['index','store','update','destroy'])` ([routes/web.php#L154](../../routes/web.php#L154)). **Implicazione**: aggiungere `'show'` ai metodi e aggiungere route nested per gestione membri (`POST /suppliers/{supplier}/members`, `PUT /suppliers/{supplier}/members/{user}`, `DELETE /suppliers/{supplier}/members/{user}`).

- **Specifiche**: middleware `workspace_supplier` — **realtà**: [Workspace.php](../../app/Http/Middleware/Workspace.php) è hard-coded sul concetto di Building (slug + `approved` + `canAccessBuilding`). Non riusabile. **Implicazione**: nuovo middleware `WorkspaceSupplier` che (1) verifica role=`supplier`, (2) carica `User::supplier()` corrente, (3) se null redirige alla pagina "orfano", (4) se non null espone il Supplier nel request o tramite singleton risolto al Gate. Niente slug nell'URL: il supplier dell'utente è univoco.

- **Specifiche**: "pagina orfano" — **realtà**: il [WorkspaceLayout.vue](../../resources/js/layouts/WorkspaceLayout.vue) probabilmente assume `building` nel contesto. **Implicazione**: la pagina "orfano" usa un layout minimale senza sidebar (es. `EmptyLayout` se esiste, altrimenti l'`AppLayout` admin con menù vuoto).

## Step ordinati

### Gruppo 1 — Schema dati e modelli (Fase 1)
1. Migration `create_supplier_user_table` (campi: `id`, `supplier_id` FK, `user_id` FK, `role` string, timestamps; **unique** su `user_id`). **Gate**: `php artisan migrate` e `\DB::table('supplier_user')->count()` ritorna 0 senza errori.
2. `app/Enums/SupplierUserRoleEnum.php` con `ADMIN`/`MEMBER` + `label()` (clone di `BuildingUserRoleEnum`). **Gate**: `tinker` → `SupplierUserRoleEnum::ADMIN->label()` ritorna `'Admin'`.
3. Relazioni: `Supplier::users()` (BelongsToMany `withPivot('role')`), `User::supplier()` (helper sul singolo Supplier via subquery o accessor). **Gate**: tinker → `Supplier::find(1)->users` e `User::find(X)->supplier` ritornano valori coerenti su seed manuale.

### Gruppo 2 — UserService e form Crea/Edit utente
4. Estendere `UserService::store` per gestire `supplier_relation = ['supplier_id'=>..., 'role'=>...]` quando `role==='supplier'`: detach esistente + attach del nuovo (rispettando unique). **Gate**: feature test che crea un utente supplier con relazione e verifica la riga in `supplier_user`.
5. Aggiungere validazione: se `role==='supplier'`, `supplier_relation.supplier_id` e `.role` sono obbligatori; email unica già coperta. **Gate**: feature test che invia un payload senza `supplier_relation` e si aspetta 422.
6. Implementare regola "almeno 1 admin" in `SupplierService` (nuovo metodo `assertCanDowngradeOrDetach(User $user, Supplier $supplier)`). Chiamarla sia in `UserService::store` (quando si modifica role o cambia supplier) sia in `SupplierController` per detach/role-change. **Gate**: feature test che tenta di rimuovere l'unico admin e si aspetta 422.
7. Aggiornare [resources/js/pages/users/partials/Form.vue](../../resources/js/pages/users/partials/Form.vue): `needSupplier = form.role === 'supplier'`, sezione condizionale con due `BbSelect` (Fornitore + Ruolo Admin/Membro). Nessun array, oggetto singolo `supplier_relation`. **Gate**: visivamente — selezionare Supplier nella combo ruolo fa apparire la sezione; salvare crea la pivot.
8. Pagina Utenti — flusso "Invita membro" dal tab Membri (vedi step 11): la create-form deve accettare query string `supplier_id` e bloccare i due select con i valori passati. **Gate**: visitare `/users/create?role=supplier&supplier_id=X` da link contestuale → form pre-compilato e disabilitato.

### Gruppo 3 — Detail Fornitore admin (route + controller + Vue)
9. Estendere `Route::resource('suppliers')->only([...,'show'])` + nuove route nested `suppliers.members.{store,update,destroy}`. **Gate**: `php artisan route:list --name=suppliers` mostra le 4 nuove route con middleware `role:superadmin|admin`.
10. `SupplierController::show($supplier)` carica supplier + utenti con pivot role; passa a `suppliers/Show.vue`. **Gate**: ispezione props della pagina — payload contiene `supplier`, `members[]` con `name/email/role/status`.
11. `resources/js/pages/suppliers/Show.vue` con due tab (Dettagli/Membri). Tab Dettagli riusa il form di edit anagrafica già esistente nel dialog di [suppliers/Index.vue](../../resources/js/pages/suppliers/Index.vue) — estraendo il `<Form>` in un partial riusabile. Tab Membri: tabella + bottone "Invita membro" (link al form Utenti pre-compilato). **Gate**: visivamente — entrambi i tab funzionano e il submit Dettagli salva.
12. Aggiornare [suppliers/Index.vue](../../resources/js/pages/suppliers/Index.vue) per rendere la riga clickable verso `suppliers.show`. **Gate**: visivamente — click apre il detail.

### Gruppo 4 — Notifiche supplier (helper destinatari)
13. Aggiungere `NotificationService::sendToSupplier(Supplier $supplier, Notification $n)` che risolve i destinatari: anagrafica (`Supplier.mail` come `Notification::route('mail', $supplier->mail)`) + tutti gli utenti `active` collegati. La definizione di `active` dipende dalla risoluzione della Domanda aperta sotto. **Gate**: unit test su `NotificationService::sendToSupplier` con un supplier seed (1 utente active, 1 pending, 1 mail anagrafica) — verifica che le mail intercettate siano 2 (anagrafica + active), non 3.
14. Verificare manualmente il flusso onboarding end-to-end: M&H crea utente supplier con `send_invite=true` → arriva la welcome email → click sul link → set password → login → atterraggio nel workspace supplier (in Fase 2). In Fase 1 si testa solo fino al login (atterra dove oggi atterra un supplier — niente workspace ancora).

🚦 **Punto di rilascio Fase 1**.

### Gruppo 5 — Workspace supplier: route, middleware, abilities (Fase 2)
15. Aggiungere voci a `WorkspaceAbilityEnum` (`SUPPLIER_SETTINGS_VIEW/UPDATE`, `SUPPLIER_TEAM_VIEW/MANAGE`, `SUPPLIER_OPERATIONS_VIEW`). Creare `SupplierWorkspacePermissionMap::abilitiesFor(SupplierUserRoleEnum)`. **Gate**: tinker — passare `ADMIN` ritorna 5 abilities, `MEMBER` ritorna 1.
16. Registrare i Gate corrispondenti in `AppServiceProvider` (parallelo al pattern customer già presente). **Gate**: in tinker su un user supplier admin, `Gate::allows('supplier.settings.update')` ritorna true; per un member ritorna false.
17. Nuovo middleware `WorkspaceSupplier` che redirige all'orphan se `User::supplier()` è null. Registrarlo in `bootstrap/app.php` o nel kernel HTTP (verificare la convenzione del progetto, presumibilmente i middleware aliasati). **Gate**: simulare un utente supplier orfano — visita `/workspace/supplier` → redirect a `/workspace/supplier/orphan`.
18. Aggiungere route group `/workspace/supplier` con `middleware: ['role:supplier','workspace.supplier']` parallelo a quello customer ([routes/web.php#L171](../../routes/web.php#L171)). **Gate**: `php artisan route:list --name=workspace.supplier` mostra le route.

### Gruppo 6 — Workspace supplier: pagine Vue (Fase 2)
19. Layout `WorkspaceSupplierLayout.vue` (o riusare `WorkspaceLayout` con prop di scope) con sidebar voci condizionate: Dashboard, Lavorazioni, Profilo, Impostazioni (admin), Team (admin). Voce "Lavorazioni" presente come placeholder in attesa di [specifiche-area_fornitore_lavorazioni.md](../specs/specifiche-area_fornitore_lavorazioni.md). **Gate**: visivamente — login admin supplier mostra 5 voci, member ne mostra 3.
20. Pagina `Dashboard.vue` placeholder, `Profile.vue` riusa il `WorkspaceController::profile/updateProfile` esistente, `Settings.vue` (admin only — riusa il partial Dettagli del Show admin), `Team.vue` (admin only — riusa il partial Membri in modalità ridotta), `Orphan.vue` (alert + logout). **Gate**: per ogni pagina, visita diretta come admin/member e verifica accessibilità coerente con la matrice nelle [specifiche §Permessi](../specs/specifiche-area_fornitore.md#permessi-e-ruoli).
21. Refactor del partial Membri lato workspace per non mostrare il bottone "Invita membro"/"Crea utente" (vincolo: in v1 la creazione passa solo da M&H). Solo modifica ruolo + rimuovi. **Gate**: visivamente — admin supplier non vede il bottone Invita; vede solo le azioni di edit/remove sui membri.

🚦 **Punto di rilascio Fase 2**.

## Rischi e punti di attenzione

- **Definizione di `active` non risolta** — bloccante per il filtro destinatari notifiche (step 13) e per il badge nel tab Membri (step 11). Vedi Domande aperte.
- **Vincolo unique `user_id` su `supplier_user`** crea attrito sul "cambio fornitore" via edit utente: serve detach esplicito prima dell'attach. Tradurre lato `UserService::store` in una transazione DB.
- **N+1 nel detail Fornitore**: caricare i membri con eager-load `users.pivot` e `users.media` (avatar) per evitare N+1 sulla tabella Membri.
- **Welcome email mandata ma non consegnata**: `Welcome` notification è `ShouldQueue` ([app/Notifications/User/Welcome.php#L11](../../app/Notifications/User/Welcome.php#L11)) — verificare che la coda sia attiva in dev/prod, altrimenti l'utente non riceve l'email e si crea uno stato "fantasma".
- **Permessi `PermissionsUpgrade`**: i nuovi permessi globali (es. `suppliers.show`, `suppliers.members.manage`) vanno aggiunti alla lista in [PermissionsUpgrade.php#L67](../../app/Console/Commands/Bitboss/Upgrade/PermissionsUpgrade.php#L67) e il comando va eseguito dopo il deploy. Le workspace abilities NO — vivono solo in `WorkspaceAbilityEnum`.
- **Pagina `users/create` con query param `supplier_id`** (step 8): bisogna sanitizzare il valore lato controller per evitare di permettere a un admin di creare un utente collegandolo a un supplier inesistente. Validare con `exists:suppliers,id`.
- **Layout workspace supplier**: se si forka `WorkspaceLayout`, attenzione a duplicare logica di store/notifiche (chat unread, ecc.). Preferire una prop di "scope" e un menu items array.
- **`Supplier.mail` invariato** — la regola "obbligatoria sempre" è già implicita nel campo `mail` del modello attuale, ma verificare nelle Form Request `StoreSupplierRequest`/`UpdateSupplierRequest` che la validation `required` resti.
- **Conflict con feature successiva**: la spec Lavorazioni ([specifiche-area_fornitore_lavorazioni.md](../specs/specifiche-area_fornitore_lavorazioni.md)) richiede gate sull'accesso supplier alle Operation. Quei gate ricadono in Fase 2 di QUESTA specifica (step 15: `SUPPLIER_OPERATIONS_VIEW`). Coordinare l'implementazione.
- **Testing manuale del cambio fornitore con sessione attiva**: Marco ha esplicitamente chiesto in [specifiche §Corner case](../specs/specifiche-area_fornitore.md#corner-case-e-gestione-errori) che non ci sia invalidazione forzata della sessione. Il middleware `WorkspaceSupplier` deve risolvere il `supplier` ad ogni request (non in cache) per riflettere il cambio.

## Domande aperte

1. **Come distinguere `pending` vs `active` per un utente supplier senza aggiungere colonne?** Le specifiche dicono "lo stato è derivato, non c'è una nuova colonna" ma il sistema non ha un meccanismo nativo (Laravel `email_verified_at` non viene popolato dal password reset). Opzioni:
   - (a) Aggiungere comunque un campo `users.password_set_at` (nullable) settato da un listener su `Illuminate\Auth\Events\PasswordReset`. **Pro**: semplice, robusto. **Con**: viola la spec.
   - (b) Considerare `pending` quando `users.invited_at IS NOT NULL && users.last_login_at IS NULL` (richiede aggiungere `last_login_at`, è comunque una colonna).
   - (c) Settare `email_verified_at = now()` sul listener `PasswordReset`. **Pro**: nessuna colonna nuova; `pending` ⇔ `email_verified_at IS NULL`. **Con**: non rispetta la semantica originaria di Laravel `email_verified_at`, può confondere flussi futuri.
   - (d) Rinunciare al filtro: tutti gli utenti supplier ricevono notifiche dal momento della creazione (anche pending). **Pro**: zero codice. **Con**: viola la spec destinatari.
   
   Senza questa decisione non si può chiudere lo step 13 (filtro destinatari) e lo step 11 (badge in tab Membri). Suggerimento: opzione (c) è il compromesso più snello e la più allineata alla spec.

## Ordine di commit suggerito

1. `feat(supplier): pivot supplier_user, enum, relazioni` — step 1-3.
2. `feat(users): supplier_relation in form Crea/Edit utente` — step 4-7.
3. `feat(suppliers): vincolo almeno 1 admin nel team + invita membro` — step 6 + 8.
4. `feat(suppliers): detail fornitore admin con tab Dettagli/Membri` — step 9-12.
5. `feat(notifications): NotificationService::sendToSupplier (helper destinatari)` — step 13. **(fine Fase 1)**
6. `feat(workspace): abilities, gate e middleware supplier` — step 15-18.
7. `feat(workspace): layout e pagine workspace supplier (dashboard/profile/settings/team/orphan)` — step 19-21. **(fine Fase 2)**
