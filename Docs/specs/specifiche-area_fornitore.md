# Area Fornitore — Utenti supplier, dettaglio fornitore admin, area di lavoro fornitore

## Contesto

Oggi il Fornitore (modello `Supplier`) esiste in piattaforma solo come anagrafica aziendale, senza utenti associati. Le notifiche operative su una Lavorazione partono unicamente alla `mail` anagrafica del Supplier, gestita fuori sistema da chi riceve quella casella.

Si vuole evolvere la piattaforma in modo che ogni azienda Fornitore possa avere uno o più utenti registrati (utenti supplier) che si autenticano e operano nella propria area dedicata, mantenendo retrocompatibilità con i fornitori "solo anagrafica" (che continueranno ad esistere). Il pattern di riferimento è quello già consolidato per il Customer: `User ↔ Building` con pivot `BuildingUser` e ruolo `admin/member`.

In sintesi, questa specifica copre:
- Associazione utenti supplier ↔ azienda Fornitore con ruolo Admin / Membro;
- Onboarding utenti supplier dalla Pagina Utenti M&H;
- Dettaglio dell'azienda Fornitore lato admin con tab Dettagli e Membri;
- Area di lavoro dell'utente fornitore (dashboard, lavorazioni, profilo, impostazioni, team);
- Gestione delle email di notifica con coesistenza email anagrafica + N email di utenti registrati.

> **Nota:** la pagina Lavorazioni e il dettaglio Lavorazione lato Fornitore sono trattati nel brief separato `Docs/brief/area_fornitore_lavorazioni.md` e saranno specificati a parte. In questo documento sono solo elencati come pagine dell'area, senza definirne il contenuto.

## Flusso utente

### A. Onboarding di un utente supplier (pagina Utenti M&H)

1. Admin/Superadmin M&H apre la Pagina Utenti e clicca "Crea utente".
2. Seleziona il ruolo `Supplier` dal select dei ruoli.
3. Quando il ruolo è `Supplier`, nel form appare una sezione **"Fornitore associato"** che richiede:
   - select dell'azienda Fornitore (singolo, obbligatorio);
   - select del ruolo dell'utente nel team del fornitore: `Admin supplier` o `Membro supplier` (obbligatorio).
4. M&H compila nome, cognome, email e una password "finta" temporanea, poi salva.
5. Il sistema:
   - crea l'utente con ruolo `supplier`;
   - crea la riga in `supplier_user` con il ruolo scelto;
   - invia all'utente un'email di benvenuto contenente un link sicuro per impostare la password definitiva (riusa il flusso di password reset/setup esistente);
   - finché l'utente non completa il setup password, lo stato dell'utente è `pending` (login non utilizzabile).
6. Quando l'utente clicca il link nell'email e imposta la password, lo stato dell'utente diventa `active` e può accedere alla propria area di lavoro.

### B. Modifica/cambio fornitore associato (pagina Utenti M&H)

1. M&H apre l'edit di un utente supplier esistente.
2. Può modificare l'azienda Fornitore associata (sempre 1 sola) e/o il ruolo Admin/Membro.
3. Al salvataggio, l'utente perde l'accesso allo storico delle lavorazioni del precedente fornitore e vede solo quelle del nuovo.

### C. Detail dell'azienda Fornitore (area Admin M&H)

1. Dalla Pagina Fornitori, M&H clicca su un Fornitore in tabella.
2. Si apre il **detail Fornitore** con due tab:
   - **Tab "Dettagli fornitore"**: mostra ed edita i campi anagrafici dell'azienda (`name`, `vat`, `mail`, `phone`, `address`, `cap`, `city`, `province`, `status`).
   - **Tab "Membri"**: tabella degli utenti supplier associati al fornitore con colonne nome, email, ruolo (Admin/Membro), stato (pending/active). Per ogni riga sono disponibili le azioni: modifica ruolo, rimuovi dal team, edita anagrafica utente. Sopra la tabella c'è il pulsante **"Invita membro"**.
3. Cliccando "Invita membro" si apre la create-form utente in modalità contestuale: ruolo `Supplier` preselezionato e bloccato, fornitore associato preselezionato e bloccato sul fornitore corrente. M&H compila solo i dati personali e il ruolo Admin/Membro. Da qui in poi il flusso è identico ad A.5–A.6.

### D. Accesso dell'utente supplier alla sua area di lavoro

1. L'utente supplier registrato e attivo (con setup password completato) effettua login.
2. Viene reindirizzato all'area `workspace/supplier`.
3. La sidebar/menu mostra le voci accessibili in base al ruolo nel team del fornitore (vedi sezione Permessi).

### E. Rimozione di un utente dal team del fornitore (tab Membri)

1. M&H clicca "Rimuovi" sulla riga dell'utente nel tab Membri.
2. Conferma in dialog.
3. Il sistema elimina la riga in `supplier_user`. L'utente resta in DB con ruolo `supplier` ma senza azienda associata.
4. Da quel momento, l'utente al login non può fare nulla (vedi sezione Corner case): vede una pagina che lo informa che non è associato ad alcuna azienda fornitore e di contattare M&H.

## Regole di business

- Un utente con ruolo `supplier` può essere associato a **una e una sola** azienda Fornitore alla volta.
- Il ruolo dell'utente supplier all'interno del team del fornitore è `admin` oppure `member` (concettualmente analoghi a `BuildingUserRoleEnum::ADMIN/MEMBER`, ma indipendenti).
- Solo un **Admin supplier** può:
  - modificare i dati anagrafici dell'azienda Fornitore (pagina Impostazioni nell'area di lavoro);
  - vedere e gestire la pagina Team (aggiungere — solo M&H può davvero creare un utente, vedi nota sotto — modificare, rimuovere utenti del team);
  *(nota: la creazione vera dell'utente è sempre fatta da M&H da Pagina Utenti; nel team page lato supplier-admin l'admin può solo richiedere a M&H di aggiungere un membro oppure modificare ruolo/rimuovere quelli esistenti — la modalità esatta di "richiesta" non è in scope di questa specifica e si userà inizialmente il solo path M&H. Vedi sezione Corner case per la versione minima.)*
- Un **Membro supplier** non può modificare dati Fornitore né accedere alla pagina Team/Impostazioni.
- Se un fornitore ha almeno un utente registrato deve avere **almeno un Admin supplier**: il sistema blocca la rimozione o il downgrade dell'ultimo admin con messaggio di errore.
- Un utente supplier non associato ad alcuna azienda Fornitore non può eseguire alcuna azione operativa nell'area di lavoro (stato "orfano", vedi Corner case).
- L'email anagrafica del Supplier (`Supplier.mail`) resta **sempre obbligatoria**, anche se il fornitore ha utenti registrati.
- L'email di un utente è univoca a livello globale (vincolo già presente per gli altri ruoli).
- M&H può cambiare l'azienda Fornitore associata a un utente supplier in qualsiasi momento dalla edit utente: l'utente perde accesso allo storico del precedente fornitore.
- Notifiche operative su Lavorazione: ogni notifica genera N invii indipendenti:
  - 1 invio all'email anagrafica del Supplier (`Supplier.mail`), sempre;
  - 1 invio per ogni utente supplier del fornitore in stato `active` (cioè con password impostata e setup completato).
  - Gli utenti `pending` (creati da M&H ma che non hanno mai impostato la password) sono **esclusi** dalle notifiche operative finché non completano il setup.

## Dati e relazioni

### Esistente (da non modificare)
- `users` — modello `User` con enum ruoli che già include `supplier` (`RoleEnum::SUPPLIER`), helper `makeSupplier()` e `isSupplier()`.
- `suppliers` — modello `Supplier` con campi `name`, `vat`, `mail`, `phone`, `address`, `cap`, `city`, `province`, `status`. Resta tale e quale; `mail` resta NOT NULL.
- `building_user` — pivot esistente `User ↔ Building` con colonna `role` (`BuildingUserRoleEnum`). Pattern di riferimento.
- `operation_supplier` — pivot esistente `Operation ↔ Supplier` con `selected` flag.

### Nuovo
- **Tabella pivot `supplier_user`** (analoga a `building_user`):
  - `id` PK,
  - `supplier_id` FK,
  - `user_id` FK,
  - `role` ENUM/string (`admin`, `member`) — usare un nuovo `SupplierUserRoleEnum`,
  - `created_at`, `updated_at`,
  - vincolo unique `(user_id)` per garantire che un utente supplier appartenga a un solo fornitore (evita ambiguità anche a livello DB).
- **Enum `SupplierUserRoleEnum`** (`admin`, `member`).
- **Relazione `Supplier::users()`** — `belongsToMany(User::class)->withPivot('role')`.
- **Relazione `User::suppliers()`** o helper più espressivo `User::supplier()` (singolo) — coerente col vincolo 1:1 utente↔fornitore. Restituisce il Supplier associato e il ruolo nel team via pivot.
- **Stato utente `pending` vs `active`**: utilizzare il meccanismo esistente per password setup/welcome (es. `email_verified_at`, oppure `accepted_at` se già usato per Customer). In assenza di campo dedicato, usare la presenza di password impostata come proxy: l'utente è `active` quando ha effettivamente completato il setup. Lo stato è derivato, non c'è una nuova colonna.

### File coinvolti (alto livello)
- Migration nuova: `create_supplier_user_table`.
- `app/Enums/SupplierUserRoleEnum.php`.
- `app/Models/Supplier.php` — aggiungere relazione `users()`.
- `app/Models/User.php` — aggiungere relazione `supplier()` (singolo via pivot).
- `app/Http/Controllers/SupplierController.php` — aggiungere `show($supplier)` per detail con tab.
- Nuovo `app/Http/Controllers/SupplierMemberController.php` (o estensione di `SupplierController`) per le azioni del tab Membri (associate, update role, detach).
- `app/Http/Controllers/UserController.php` (o equivalente) — gestire la sezione condizionale "Fornitore associato" in store/update.
- `app/Services/SupplierService.php` — aggiungere `attachUser`, `detachUser`, `updateUserRole`, con check del vincolo "almeno 1 admin".
- `app/Services/NotificationService.php` (o dove parte oggi l'email a `Supplier.mail`) — aggiornare la logica di destinatari per includere gli utenti `active` del fornitore.
- `resources/js/pages/users/partials/Form.vue` — aggiungere sezione condizionale `needSupplierType = form.role === 'supplier'` con select fornitore + select ruolo Admin/Membro.
- `resources/js/pages/suppliers/Index.vue` — riga del fornitore deve linkare al detail.
- Nuovo `resources/js/pages/suppliers/Show.vue` (o equivalente) — detail con i due tab.
- Nuovi componenti area workspace supplier: `resources/js/pages/workspace/supplier/{Dashboard,Lavorazioni,Profile,Settings,Team}.vue` (la pagina Lavorazioni e il dettaglio della singola lavorazione sono out-of-scope di questa specifica).
- `routes/web.php` — nuovo gruppo route con `middleware: ['role:supplier', 'workspace_supplier']` e prefix `/workspace/supplier`, parallelo al gruppo customer.
- Nuovo middleware (o estensione del middleware workspace esistente) che blocca l'utente supplier senza fornitore associato e lo redirige a una pagina "non sei associato a nessun fornitore — contatta M&H".
- `app/Policies/SupplierPolicy.php` — aggiornare per i nuovi gate (vedi sezione Permessi).

## Permessi e ruoli

### Pagina Utenti M&H
- Solo Admin/Superadmin M&H possono creare/editare utenti supplier e gestire la sezione "Fornitore associato".

### Detail Fornitore lato admin M&H (Pagina Fornitori → Show)
- Solo Admin/Superadmin M&H accedono al detail e a entrambe le tab Dettagli e Membri.
- Le azioni di "Invita membro", "Rimuovi", "Cambia ruolo" sono disponibili solo a M&H.

### Area di lavoro supplier (`/workspace/supplier/...`)
Accessibile solo a utenti con ruolo `supplier` E che abbiano una riga in `supplier_user`. Senza riga, l'utente è "orfano" e viene redirezionato (vedi Corner case).

| Pagina | Admin supplier | Membro supplier |
|---|---|---|
| Dashboard | ✓ | ✓ |
| Lavorazioni (lista) | ✓ | ✓ |
| Dettaglio lavorazione | ✓ | ✓ |
| Profilo (account proprio) | ✓ | ✓ |
| Impostazioni (anagrafica azienda Fornitore) | ✓ (sola pagina disponibile, edit abilitato) | ✗ (voce nascosta in menu, route bloccata da gate) |
| Team (utenti del fornitore) | ✓ (visualizza, modifica ruoli, rimuove — la creazione passa comunque da M&H per la v1) | ✗ |

I gate aggiuntivi da definire (analoghi a quelli di workspace customer):
- `supplier.settings.view`, `supplier.settings.update` — solo admin supplier sul proprio supplier.
- `supplier.team.view`, `supplier.team.manage` — solo admin supplier sul proprio supplier.
- L'autorizzazione confronta sempre il `supplier_id` dell'utente loggato con quello della risorsa target.

## Corner case e gestione errori

- **Email già in uso al momento della creazione utente supplier** → errore di validazione inline sul campo email: "Email già in uso". M&H deve usare un'altra email.
- **Tentativo di rimozione/downgrade dell'ultimo Admin supplier** → operazione bloccata con errore: "Il fornitore deve avere almeno un Admin. Promuovi prima un altro membro ad Admin." (sia da edit utente in Pagina Utenti, sia da tab Membri).
- **Utente supplier "orfano" (senza riga in `supplier_user`) che effettua login** → middleware lo redirige a una pagina dedicata (`/workspace/supplier/orphan` o analoga) con messaggio: "Il tuo account non è associato a nessuna azienda Fornitore. Contatta M&H." Nessuna voce di menu è visibile.
- **Cambio fornitore associato a un utente con sessione attiva** → al successivo refresh/route guard, l'utente vede solo i dati del nuovo fornitore. Nessuna invalidazione forzata della sessione (pattern coerente con il cambio building del customer).
- **Tentativo di associare un utente già supplier di un altro fornitore** → bloccato dal vincolo unique `(user_id)` su `supplier_user`. UI mostra errore: "Questo utente è già membro del fornitore X. Modifica l'associazione dalla edit utente." (con link).
- **Fornitore senza utenti registrati che riceve una notifica** → l'email parte solo a `Supplier.mail` (comportamento attuale, retrocompatibile).
- **Utente supplier `pending` (creato ma password non impostata) che riceve una notifica** → escluso dai destinatari finché non completa il setup. Solo `Supplier.mail` lo riceve nell'intervallo.
- **Utente supplier che cerca di accedere a una lavorazione di un fornitore diverso** → 403 / not found, gate blocca confronto `Operation.supplier_id ≠ User.supplier.id`.
- **Tentativo di svuotare `Supplier.mail` da edit Dettagli** → errore di validazione "Email obbligatoria".
- **M&H disassocia un utente da un fornitore mentre quell'utente sta visualizzando una pagina del workspace supplier** → al successivo request, middleware lo intercetta come orfano e lo redirige alla pagina di cui sopra.

## UX e feedback

- **Form crea/edit utente con ruolo Supplier**: la sezione "Fornitore associato" appare con animazione/transizione coerente con quella già presente per il ruolo Customer (sezione `building_relations`). I due select (fornitore, ruolo) sono entrambi obbligatori.
- **Toast di conferma**:
  - Creazione utente supplier → "Utente creato. Email di benvenuto inviata a {email}."
  - Cambio ruolo Admin/Membro → "Ruolo aggiornato."
  - Rimozione dal team → "Utente rimosso dal team del fornitore."
  - Salvataggio anagrafica fornitore → "Modifiche salvate."
- **Errori bloccanti** (es. ultimo admin) → toast di errore + il form/azione resta nello stato corrente. Nessun cambio in DB.
- **Loading**: button con spinner inline durante salvataggio. Tabella Membri con skeleton durante il fetch iniziale del detail.
- **Email di benvenuto utente supplier**: usa il template/flusso di password setup esistente in piattaforma (lo stesso usato per altri ruoli creati da M&H). Subject e body in italiano, coerenti con il resto del sistema.
- **Detail Fornitore — tab Dettagli**: layout a due colonne (form anagrafica) come gli altri detail della piattaforma. Tab Membri con tabella stile uguale a quella già usata per altri elenchi utenti (riusa componenti BbTable/BbButton esistenti).
- **Pagina "orfano"**: layout minimale workspace, senza menu di sezione, con un alert informativo e un pulsante "Logout".
- **Indicatore stato utente nel tab Membri**: badge `Pending` (utenti senza password impostata) o `Active`. Filtrabile.

## Fasi di implementazione

La feature è organica ma sufficientemente ampia da giustificare due fasi sequenziali, ciascuna testabile indipendentemente. La pagina Lavorazioni e il dettaglio Lavorazione sono trattati nel brief separato `area_fornitore_lavorazioni.md` e quindi sono fuori scope qui.

### Fase 1: Modello dati, onboarding utente, detail fornitore admin, notifiche

- **Scope**:
  - Migration `supplier_user` + enum `SupplierUserRoleEnum`.
  - Relazioni in `Supplier` e `User`.
  - Estensione del form Crea/Edit utente con sezione condizionale "Fornitore associato".
  - Detail Fornitore (Pagina Fornitori M&H) con tab Dettagli e Membri (incluso bottone "Invita membro" che apre il form utenti pre-compilato).
  - Validazioni: almeno 1 admin, email unica, vincolo 1 utente ↔ 1 fornitore.
  - Estensione `NotificationService` (o equivalente) per inviare le email operative a `Supplier.mail` + utenti `active` del fornitore.
  - Email di benvenuto al nuovo utente supplier riusando il flusso password setup esistente.
- **File coinvolti**: vedi sezione "File coinvolti (alto livello)" per Backend/Vue M&H. *Niente* file nel workspace supplier in questa fase.
- **Dipende da**: nulla.

### Fase 2: Area di lavoro supplier (workspace) — solo struttura, senza pagina Lavorazioni

- **Scope**:
  - Route group `/workspace/supplier` con middleware `role:supplier` + check associazione fornitore.
  - Layout workspace supplier (sidebar/topbar) con voci di menu condizionate dal ruolo Admin/Membro.
  - Pagina Dashboard come placeholder/landing minimale (KPI definiti in una specifica successiva).
  - Pagina Profilo dell'utente (riusa il componente di profilo esistente).
  - Pagina Impostazioni del fornitore — solo admin — con form anagrafica (riusa lo stesso componente di Tab "Dettagli fornitore" lato admin M&H, in modalità self-service).
  - Pagina Team — solo admin — con lista membri (riusa il tab Membri lato admin in modalità ridotta: nessun bottone "Crea utente", solo modifica ruolo e rimuovi; la creazione passa sempre da M&H in v1).
  - Pagina "orfano" per utenti supplier senza fornitore associato.
  - Gate `supplier.settings.*` e `supplier.team.*`.
- **File coinvolti**: route web, middleware, layout workspace supplier, pagine Vue Dashboard / Profile / Settings / Team / Orphan, policy / gate.
- **Dipende da**: Fase 1 (richiede pivot e relazioni).

> La pagina Lavorazioni dell'area supplier e il dettaglio Lavorazione sono coperti da una specifica separata derivata da `Docs/brief/area_fornitore_lavorazioni.md`.
