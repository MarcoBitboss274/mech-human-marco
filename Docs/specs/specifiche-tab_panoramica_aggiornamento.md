# Tab Panoramica — Aggiornamento sezione Riepilogo

> Documento di aggiornamento alle specifiche della tab Panoramica già implementata (vedi `Docs/specs/specifiche-tab_panoramica.md`). Sostituisce le regole della **sezione Riepilogo lavorazione** e introduce alcune piccole modifiche al data layer; la sezione **Attori coinvolti** rimane invariata.

## Contesto

La sezione Riepilogo della tab Panoramica oggi mostra, per ciascuna entità (Prescrizione, Fornitore, Preventivo, Produzione, Fattura), una **card** con stato + contatore + un timestamp generico "Aggiornato il …" basato su `updated_at` dell'elemento principale.

Questa visualizzazione è poco utile al customer: il timestamp di "ultimo aggiornamento" non racconta nulla di operativo (può essere stato spostato da un dettaglio interno irrilevante) e non c'è traccia degli eventi rilevanti del flusso (quando è stata inviata/confermata, chi è stato scelto e quando, quanti preventivi sono stati rifiutati, ecc.).

L'aggiornamento punta a:
1. Sostituire `updated_at` generico con **timestamp specifici** legati agli eventi del flusso operativo.
2. Mostrare **counter di stati storici rilevanti** (es. "2 rifiutati · 1 annullato") per dare contesto sulle iterazioni passate.
3. Trasformare le card in **sezioni** (header + contenuto, senza box) per una visualizzazione più leggibile.
4. Rendere l'azione di navigazione alla tab specifica esplicita tramite **bottone dedicato** anziché click sull'intera card.

## Flusso utente

1. L'utente (admin o customer) apre la tab Panoramica della lavorazione (è la prima tab, già selezionata di default).
2. Nella **colonna sinistra** vede il Riepilogo lavorazione strutturato in **sezioni** (non più card), nell'ordine: Prescrizione → Fornitore (solo admin) → Preventivi → Produzione → Fatture. Ogni sezione ha:
   - un **header** con il nome dell'entità ed eventuali counter sintetici a destra;
   - un **contenuto** specifico (timestamp degli eventi, card operative inline quando applicabili);
   - un **bottone "Vai a [Tab]"** che porta alla tab dedicata.
3. Nella **colonna destra** vede la sezione "Attori coinvolti" identica ad oggi (cards compatte: Richiedente, Struttura, Agente, Fornitore selezionato — gli ultimi due nascosti al customer).
4. L'utente può:
   - leggere la sintesi degli eventi senza dover entrare nelle singole tab;
   - agire direttamente sulle card operative inline (preventivo `sent` → Accetta/Rifiuta per il customer; ecc.) come oggi;
   - cliccare il bottone "Vai a [Tab]" per approfondire una sezione.
5. Quando un'entità è completamente vuota (0 record visibili) la sezione resta visibile con un placeholder testuale ("Nessun preventivo ancora", ecc.) e il bottone di navigazione segue il comportamento attuale (disabilitato se la tab di destinazione è disabilitata, altrimenti navigabile).

## Regole di business

### Sezione Prescrizione

- **Contenuto**:
  - Riga "Inviata il **[prescription.send_at]**" — visibile solo se `send_at !== null`.
  - Riga "Confermata il **[prescription.confirmed_at]**" — visibile solo se `confirmed_at !== null`.
- **Stato badge**: rimane il badge attuale collegato a `prescription.status`.
- **Counter**: se ci sono più prescrizioni visibili (caso raro), mostrare nell'header "**N prescrizioni**" come oggi; i timestamp mostrati sono quelli della **prescrizione più recente per `created_at`** (stessa logica "main" di oggi).
- **Vuoto** (admin: nessuna prescrizione; customer: nessuna prescrizione con `send_at` valorizzato): placeholder "Nessuna prescrizione ancora".

### Sezione Fornitore (solo admin)

- **Contenuto** (quando esiste un fornitore selezionato):
  - Riga "**[supplier.name]** — Selezionato il **[pivot.selected_at]**".
- **Niente badge stato** del fornitore in questa sezione (semplificazione richiesta).
- **Niente counter** di altri fornitori in valutazione.
- **Vuoto** (nessun fornitore o nessuno con `selected = true`): placeholder "Nessun fornitore ancora selezionato".
- **Visibilità**: la sezione è completamente nascosta al customer, esattamente come oggi (gate su `operations.supplier.view`).

### Sezione Preventivi

La rappresentazione dipende dallo stato degli elementi visibili all'utente corrente (per il customer: visibili = non in `draft`).

Casi:

| Caso | Contenuto sezione |
|------|-------------------|
| Esiste **almeno un preventivo `sent`** | Una **card preventivo per ognuno in stato `sent`** (azionabile: Accetta/Rifiuta per il customer; azioni admin secondo permessi — stesso componente attuale `QuoteCard.vue`). |
| Esiste un preventivo **`accepted`** e nessuno `sent` | Una **card preventivo accettato in modalità read-only** (no bottoni Accetta/Rifiuta; mostra "Accettato il `[quote.accepted_at]`"). |
| Esistono solo preventivi `rejected` / `canceled` | Nessuna card, solo testo "Nessun preventivo attivo". |
| Nessun preventivo visibile | Placeholder "Nessun preventivo ancora". |

- **Counter nell'header sezione** (sempre, se > 0):
  - "**N rifiutati · M annullati**" (counter separati, separator `·`).
  - Se `N = 0` mostra solo "M annullati"; se `M = 0` mostra solo "N rifiutati"; se entrambi 0 nessun counter.
- **Visibilità per customer**: i preventivi in `draft` non sono visibili (né come card, né nei counter). Per l'admin tutti gli stati sono visibili e contano (i `draft` non rientrano comunque nei counter "rifiutati / annullati").

### Sezione Produzione

- **Contenuto**:
  - Se `status = confirmed`: riga "Confermata il **[production.confirmed_at]**".
  - Se `status = canceled`: riga "Annullata il **[production.canceled_at]**".
  - Altrimenti (produzione esistente ma né confermata né annullata): testo statico "**Non ancora confermata**" (senza data).
- **Stato badge**: rimane il badge attuale di `production.status`.
- **Counter**: se per qualche motivo esistono più produzioni, vale la stessa regola "main" di oggi (priorità: `confirmed` > prima disponibile) e il counter "N produzioni" nell'header.
- **Vuoto**: placeholder "Nessuna produzione ancora".

### Sezione Fatture

| Caso | Contenuto sezione |
|------|-------------------|
| Esiste **almeno una fattura `sent`** | Una **card fattura per ognuna in stato `sent`** (componente attuale `InvoiceCard.vue`, con azioni invariate per ruolo). |
| Esistono solo fatture `paid` o `canceled` | Nessuna card, solo testo "Nessuna fattura attiva". |
| Nessuna fattura visibile | Placeholder "Nessuna fattura ancora". |

- **Counter nell'header sezione** (sempre, se > 0): "**M annullate**". Le fatture `paid` **non** vengono contate né mostrate (sono considerate chiuse e non rilevanti per la Panoramica).
- **Visibilità per customer**: le fatture in `draft` non sono visibili (come oggi: filtro su `sent_at !== null`). Le `canceled` (nuovo stato) sono visibili al customer solo se erano già state inviate (cioè avevano `sent_at !== null` prima dell'annullamento).

### Sezione Attori coinvolti — INVARIATA

Nessuna modifica rispetto alle specifiche attuali (`Docs/specs/specifiche-tab_panoramica.md`, sezione "Attori coinvolti"): cards compatte con Richiedente, Struttura, Agente (admin), Fornitore selezionato (admin), stessi placeholder e regole di visibilità.

## Dati e relazioni

L'aggiornamento richiede **tre nuove colonne** sul data layer (oltre a quanto già presente). Tutte seguono pattern già in uso nel codebase (sync automatico in `booted()` come per `Production` / `Invoice`).

### Modifiche allo schema

1. **`prescriptions.confirmed_at`** (nullable timestamp)
   - Migration: `add_confirmed_at_to_prescriptions_table`.
   - Cast a `datetime` in `Prescription::casts()`.
   - Aggiunto a `$fillable`.
   - Sync automatico in `Prescription::booted()`: quando `status` passa a `PrescriptionStatusEnum::CONFIRMED` valorizza `confirmed_at = now()`; quando esce da `confirmed` lo resetta a `null` (stesso pattern di `Production::booted()` per `confirmed_at`).

2. **`invoices.canceled_at`** (nullable timestamp) **+ nuovo case `canceled` su `InvoiceStatusEnum`**
   - Migration: `add_canceled_at_to_invoices_table`.
   - Aggiunta `case CANCELED = 'canceled'` in `app/Enums/InvoiceStatusEnum.php` con label "Annullata".
   - Cast `canceled_at` a `datetime`, aggiunto a `$fillable`.
   - Sync automatico in `Invoice::booted()`: quando `status` passa a `CANCELED` valorizza `canceled_at = now()`; quando esce da `canceled` lo resetta. Estendere la logica esistente che già gestisce `sent_at`.
   - Verificare/aggiornare i punti di codice che fanno match esaustivo su `InvoiceStatusEnum` (badge, filtri, transizioni di stato) per gestire il nuovo caso.

3. **`operation_supplier.selected_at`** (nullable timestamp sul pivot)
   - Migration: `add_selected_at_to_operation_supplier_table`.
   - Aggiungere `selected_at` ai `withPivot([...])` su `Operation::suppliers()`.
   - Sync automatico: quando il pivot viene aggiornato/inserito con `selected = true` e `selected_at` è null, valorizzarlo a `now()`. Quando `selected` torna a `false`, resettarlo a `null`. Il punto di sync va nello stesso luogo dove oggi si imposta `selected = true` sul pivot (cercare l'attuale logica di selezione fornitore in `OperationService` o `SupplierService`); se non esiste un punto unico, centralizzarla in un metodo `Operation::markSupplierSelected(Supplier $s)`.

### Payload Inertia

Il metodo `OperationService::buildOverviewPayload($operation, $asCustomer)` va aggiornato. La struttura `summary` cambia: rimuovere i campi `updated_at` generici e introdurre, per ciascuna sezione, i campi specifici elencati sotto. La struttura `actors` resta invariata.

```text
overview.summary = {
  prescription: {
    empty: bool,
    count: int,
    status: string|null,        // status del main
    main_id: int|null,
    sent_at: ISO8601|null,      // NEW — main.send_at
    confirmed_at: ISO8601|null, // NEW — main.confirmed_at
  },
  suppliers: {                  // SOLO se !asCustomer
    empty: bool,
    selected: {                 // NEW — null se nessuno selezionato
      id: int,
      name: string,
      selected_at: ISO8601,
    } | null,
    // count, status, updated_at, main_id RIMOSSI
  },
  quotes: {
    empty: bool,
    count: int,                 // totale visibili (compatibilità, opzionale)
    main_id: int|null,
    accepted: {                 // NEW — null se nessuno accettato
      id: int,
      accepted_at: ISO8601,
    } | null,
    sent_ids: int[],            // NEW — id dei preventivi sent (per render delle card inline)
    rejected_count: int,        // NEW
    canceled_count: int,        // NEW
  },
  production: {
    empty: bool,
    count: int,
    status: string|null,
    main_id: int|null,
    confirmed_at: ISO8601|null, // NEW — main.confirmed_at se status=confirmed
    canceled_at: ISO8601|null,  // NEW — main.canceled_at se status=canceled
  },
  invoices: {
    empty: bool,
    sent_ids: int[],            // NEW — id delle fatture sent (per render card inline)
    canceled_count: int,        // NEW — solo lato admin; lato customer conta solo le canceled che erano già sent
    // count, status, updated_at, main_id RIMOSSI (le pagate non si mostrano)
  },
}
```

Note sulla coerenza:
- I campi `id`/`sent_ids`/`accepted` servono al frontend per recuperare e renderizzare le card operative usando i componenti esistenti (`QuoteCard.vue`, `InvoiceCard.vue`). I dati completi delle entità (oggetti `Quote` / `Invoice` con relazioni) restano disponibili sul payload `operation` come oggi.
- Il `summary.invoices.canceled_count` lato customer deve contare **solo** le fatture `canceled` che avevano `sent_at !== null` (cioè inviate prima dell'annullamento). Lato admin conta tutte le `canceled`.
- Le filter di visibilità per customer rimangono invariate: prescription `send_at !== null`, quote `status !== draft`, invoice `sent_at !== null`.

### Tipi TypeScript

Aggiornare `resources/js/types/Operation.ts` (o il file dove vive il tipo `OverviewPayload`) per riflettere la nuova struttura `summary`. Rimuovere `updated_at` dai sub-payload, aggiungere i nuovi campi.

## Permessi e ruoli

Nessun nuovo permesso. La matrice di visibilità rimane identica a oggi:

- **Sezione Fornitore**: visibile solo con `operations.supplier.view` (admin); nascosta al customer.
- **Card preventivo `sent` con azioni Accetta/Rifiuta**: customer le vede e può agire (azioni esistenti); admin con `operations.quote.manage` mantiene le sue azioni.
- **Card preventivo accettato read-only**: visibile a chiunque veda la sezione Preventivi, nessuna azione (è una semplice rappresentazione storica).
- **Card fattura `sent`**: customer la vede in sola lettura/download; admin con `operations.invoice.manage` mantiene le sue azioni (incluso il nuovo passaggio a `canceled`).
- **Bottone "Vai a [Tab]"**: visibile sempre, ma disabilitato se la tab di destinazione è disabilitata per quell'utente (stesso meccanismo `enable*Tab` già usato in `Show.vue`).

## Corner case e gestione errori

- **Prescrizione mai inviata** (`send_at = null`) → lato admin: sezione mostra solo il badge stato (es. "Bozza"), nessuna riga timestamp. Lato customer: la prescrizione non è visibile, sezione mostra placeholder.
- **Prescrizione confermata senza send_at registrato** (caso teoricamente impossibile ma difensivo) → mostra solo "Confermata il [confirmed_at]", omette la riga "Inviata il …".
- **Backfill di `confirmed_at`, `canceled_at` (Invoice), `selected_at`** per dati esistenti → la migration può lasciare `null`. Le sezioni gestiscono il null (riga semplicemente non compare). **Non** retro-popolare con `updated_at`: sarebbe un dato falso.
- **Più preventivi `sent` contemporaneamente** → tutte le rispettive card vengono renderizzate, una sotto l'altra, dentro la sezione Preventivi (stessa regola di oggi).
- **Più fatture `sent` contemporaneamente** → idem per la sezione Fatture.
- **Preventivo accettato + altri `sent` ancora aperti** → mostrare le card dei `sent` (azionabili), **non** la card del `accepted`. Se il customer accetta uno di quelli ancora `sent`, la regola di business esistente (gestita altrove) deciderà la transizione.
- **Fattura passa a `paid`** → sparisce dalla sezione (nessuna card, nessun counter). La sezione può svuotarsi del tutto e mostrare "Nessuna fattura attiva".
- **Fattura `canceled` mai stata `sent`** (annullata mentre era `draft`) → lato customer: invisibile (non ha mai avuto `sent_at`); lato admin: visibile e contata in `canceled_count`.
- **Counter zero** → se sia `rejected_count` che `canceled_count` sono 0 nella sezione Preventivi, l'header non mostra alcun counter (non scrivere "0 rifiutati"). Stessa regola per Fatture.
- **Bottone "Vai a [Tab]" disabilitato** → segue lo stato `enable*Tab` già esposto al frontend: se la tab è disabilitata per quell'utente, il bottone è disabled (cursor + stile coerenti con altri bottoni disabilitati nel progetto).
- **Errori in azioni inline** → invariato rispetto a oggi (toast via `useMainToast`, refetch payload via Inertia partial reload per ricalcolare lo stato della sezione).

## UX e feedback

- **Layout**: sezioni composte da **header** (titolo entità a sinistra, counter sintetici a destra) + **contenuto** (righe testo / card inline) + **bottone "Vai a [Tab]"** (in fondo o in alto a destra dell'header — scegliere in fase di implementazione lo schema più coerente con i pattern Tailwind del progetto). Niente bordi/ombre tipo card. Separatore orizzontale leggero tra una sezione e l'altra.
- **Tipografia timestamp**: usare lo stesso helper `dateTime()` già usato da `SummaryCard.vue` per coerenza di formato (data + ora).
- **Stati di loading**: invariati (dati arrivano via Inertia col first render).
- **Feedback azioni inline**: invariato (toast `useMainToast` + reload parziale per refresh dello stato).
- **Responsive**: il layout a due colonne collassa su una colonna su viewport strette, come oggi.
- **Stile**: nuovi stili in un file `*_override.css` dedicato o nell'override esistente della Panoramica. **Mai modificare** `base.css` / `main.css` / `theming.css` (regola progetto). Aggiornare `Docs/ui/UI_ACTIVITY_LOG.md` dopo le modifiche CSS.
- **Componenti riusabili**: i componenti `SummaryCard.vue` e `ActorCard.vue` esistenti vanno valutati: `ActorCard.vue` rimane in uso (sezione Attori invariata); `SummaryCard.vue` non è più adatto al nuovo layout — sostituirlo con un nuovo componente `OverviewSection.vue` (slot per header / counter / contenuto / footer-bottone) oppure decommissionarlo se non più referenziato altrove. La card preventivo accettato read-only può essere ottenuta passando un prop `readonly` a `QuoteCard.vue` (o estraendo una variante `QuoteCardReadonly.vue` se la divergenza è significativa).

## Fasi di implementazione

### Fase unica: aggiornamento sezione Riepilogo

- **Scope**: implementare end-to-end le modifiche descritte, sia backend (schema + payload) sia frontend (componenti + tipi), per admin e customer. Aggiornare/aggiungere test.
- **File coinvolti**:
  - **Migration / DB**:
    - Nuova migration: `add_confirmed_at_to_prescriptions_table`.
    - Nuova migration: `add_canceled_at_to_invoices_table`.
    - Nuova migration: `add_selected_at_to_operation_supplier_table`.
  - **Backend**:
    - `app/Models/Prescription.php` → cast, fillable, `booted()` per sync `confirmed_at`.
    - `app/Models/Invoice.php` → cast, fillable, estendere `booted()` per sync `canceled_at`.
    - `app/Enums/InvoiceStatusEnum.php` → nuovo case `CANCELED` con label "Annullata".
    - `app/Models/Operation.php` → `withPivot('selected_at')` su `suppliers()`; eventuale helper `markSupplierSelected()` se utile per centralizzare il sync di `selected_at`.
    - Punto di codice che oggi imposta `selected = true` sul pivot fornitore (cercare in `OperationService` / `SupplierService`) → aggiornare per valorizzare/resettare `selected_at`.
    - `app/Services/OperationService.php` → riscrivere `buildOverviewPayload()` con la nuova struttura `summary` descritta sopra.
    - Eventuali altri punti che fanno match esaustivo su `InvoiceStatusEnum` (badge frontend, filter, transizioni) per gestire `canceled`.
  - **Frontend**:
    - `resources/js/pages/operations/partials/OverviewTab.vue` (admin) → riscrittura layout sezioni.
    - `resources/js/pages/workspace/operations/partials/OverviewTab.vue` (customer) → riscrittura layout sezioni.
    - Nuovo componente `resources/js/components/operations/overview/OverviewSection.vue` (header + counter + slot contenuto + bottone "Vai a tab").
    - `resources/js/components/operations/overview/SummaryCard.vue` → decommissionare se non più referenziato.
    - `resources/js/components/operations/QuoteCard.vue` → supportare modalità `readonly` (nessun pulsante Accetta/Rifiuta) per il caso "preventivo accettato".
    - `resources/js/types/Operation.ts` (o file analogo) → aggiornare il tipo `OverviewPayload`.
    - Eventuale CSS override dedicato per lo stile sezioni.
  - **Test** (`tests/Feature/OperationOverviewTest.php` + nuovi test):
    - Aggiornare i test esistenti del payload (i campi `updated_at` sono cambiati).
    - Verificare presenza di `prescription.sent_at` / `confirmed_at`, `quotes.accepted` / `sent_ids` / `rejected_count` / `canceled_count`, `production.confirmed_at` / `canceled_at`, `invoices.sent_ids` / `canceled_count`, `suppliers.selected.selected_at`.
    - Verificare visibilità customer: `prescription` con `send_at = null` non visibile, `quotes` `draft` non contate nei counter, `invoices` `canceled` con `sent_at = null` non visibili lato customer.
    - Verificare sync automatico dei nuovi timestamp:
      - `Prescription`: passaggio a `confirmed` valorizza `confirmed_at`; reset corretto.
      - `Invoice`: passaggio a `canceled` valorizza `canceled_at`; reset corretto.
      - Pivot `operation_supplier`: valorizzazione di `selected_at` quando `selected` passa a `true`.
    - Smoke test browser (Pest 4) sulla pagina Show admin e workspace per assicurarsi che la nuova Panoramica renderizzi senza errori JS.
  - **Documentazione**:
    - `Docs/ui/UI_ACTIVITY_LOG.md` → annotare le modifiche CSS/UI.
- **Dipende da**: nulla, l'infrastruttura della Panoramica e dei componenti operativi è già in piedi (è un aggiornamento incrementale).
