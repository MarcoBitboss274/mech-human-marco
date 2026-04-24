# Tab Panoramica — Lavorazione

## Contesto

La pagina di dettaglio di una lavorazione (sia lato admin `/operations/{id}` sia lato workspace `/workspace/operations/{id}`) espone già una tab `overview` che al momento è un placeholder vuoto. Serve riempirla con una vista sintetica e complessiva sullo stato della lavorazione e delle sue entità collegate, così che admin e customer possano farsi un'idea d'insieme senza dover attraversare tutte le tab specifiche. La Panoramica deve anche far emergere gli eventi operativi che richiedono un intervento (preventivo da accettare, fattura inviata), senza costringere a cambiare tab per agire.

## Flusso utente

1. L'utente (admin o customer) apre la pagina di una lavorazione.
2. La tab **Panoramica** è selezionata di default (è già la prima tab) ed è sempre abilitata, indipendentemente dallo stato della lavorazione (anche `draft`).
3. L'utente vede una vista a due colonne:
   - **Colonna sinistra — Riepilogo lavorazione**: card riassuntive delle entità collegate (prescrizione, fornitore, preventivi, produzione, fatture) e, inline, le card operative "speciali" quando c'è un evento azionabile.
   - **Colonna destra — Attori coinvolti**: card compatte con i principali soggetti della lavorazione (richiedente, struttura, agente, fornitore selezionato).
4. L'utente può:
   - Cliccare su una card riassuntiva → viene portato alla tab specifica dell'entità.
   - Interagire direttamente con una card inline operativa (es. preventivo inviato → bottoni Accetta/Rifiuta per il customer; annulla/modifica per l'admin, secondo i permessi) senza lasciare la Panoramica.
5. Quando un'entità non esiste ancora (o non è visibile all'utente corrente), la relativa card riassuntiva mostra un placeholder "Non ancora presente". La card inline speciale, in quel caso, non compare.

## Regole di business

### Regole generali

- La tab Panoramica è visibile e cliccabile per qualunque utente abbia il permesso di visualizzare la lavorazione (`operations.index` + `operations.view-all` o essere agente dell'edificio, come già gestito da `OperationPolicy::view`).
- Non richiede un nuovo permesso dedicato: chi vede la lavorazione vede la Panoramica.
- Il layout è a due colonne: riepilogo a sinistra, attori a destra.

### Sezione Attori coinvolti

Per ciascun attore si mostra una card compatta con:
- **Etichetta ruolo** (titolo della card): "Richiedente", "Struttura", "Agente", "Fornitore".
- **Nome / Denominazione**.
- **Email** principale.

Regole per ciascun attore:

- **Richiedente**: `User` legato alla prescrizione principale della lavorazione.
  - La "prescrizione principale" è l'ultima prescrizione (più recente per `created_at`) che sia visibile all'utente corrente (vedi regole visibilità sotto).
  - Se nessuna prescrizione visibile esiste, mostrare placeholder "Non ancora definito".
  - Si mostrano `name surname` dell'utente (decrittati se necessario) e `email`.

- **Struttura**: `Operation.building` (sempre presente, dato che `operation.building_id` è `not nullable`).
  - Mostrare `building.name` come denominazione. Come email, in mancanza di campo dedicato sulla Building, mostrare l'email dell'utente "owner" della struttura se presente, altrimenti omettere la riga email.

- **Agente**: `building.agent` (relazione `BelongsTo User` su `building.agent_id`).
  - Se `agent_id` è null, mostrare placeholder "Non ancora assegnato".
  - Solo admin vede questa card (vedi permessi).

- **Fornitore**: fornitore selezionato della lavorazione, ovvero il record `suppliers` con pivot `selected = true` (accessor `Operation::selectedSupplier`).
  - Se nessun fornitore è ancora selezionato, mostrare placeholder "Non ancora selezionato".
  - I fornitori "in valutazione" ma non selezionati **non** vengono mostrati in Panoramica.
  - Solo admin vede questa card (vedi permessi).

### Sezione Riepilogo lavorazione

Ogni entità collegata alla lavorazione ha una **card riassuntiva** con:
- **Titolo** dell'entità (es. "Prescrizione", "Preventivi", "Produzione", "Fatture", "Fornitori").
- **Stato** aggregato rappresentato dal badge già in uso nelle tab specifiche.
- **Contatore** quando l'entità è multipla (es. "3 preventivi"); omesso se singola.
- **Ultima data** di modifica (`updated_at` dell'elemento principale).
- **Click sulla card** → naviga alla tab dell'entità corrispondente (usando il route wayfinder o il sistema di tab già in uso in `Show.vue`).

Regole di **aggregazione stato** per entità multiple:
- **Fornitori** (solo admin): si mostra lo stato del fornitore `selected = true`. Se nessuno selezionato, stato "In valutazione" con contatore dei candidati.
- **Preventivi**: si mostra lo stato del preventivo accettato (`status = accepted`); se non c'è, quello più recente in `sent`; se non c'è, l'ultimo creato. Contatore = numero totale preventivi visibili all'utente.
- **Fatture**: si mostra lo stato della fattura più recente. Contatore = numero totale fatture visibili all'utente.
- **Prescrizione** e **Produzione**: in pratica singole; se per qualche motivo ce ne sono più di una, valgono le stesse regole: l'elemento più "avanzato" determina lo stato, con contatore.

Regole per la **card inline speciale** (inserita sotto/adiacente alla relativa card riassuntiva):

- **Preventivo da accettare**: se esiste almeno un preventivo in stato `sent` (non ancora accettato/rifiutato), la card inline riproduce la stessa card già presente in `QuotesTab.vue`, con **le stesse azioni** previste dal ruolo e permessi correnti (customer: Accetta/Rifiuta; admin con `operations.quote.manage`: Modifica/Annulla/ecc. — qualsiasi azione oggi disponibile in `QuotesTab.vue` per il ruolo).
- **Fattura inviata**: se esiste almeno una fattura in stato `sent` (non ancora `paid`), la card inline riproduce la stessa card già presente in `InvoicesTab.vue`, con le stesse azioni previste dal ruolo e permessi correnti (customer: visualizzazione/download; admin con `operations.invoice.manage`: cambio stato, modifica, ecc.).
- Se ci sono più preventivi in `sent` o più fatture in `sent` simultaneamente, si mostrano più card inline una sotto l'altra.
- **Produzione** e **Prescrizione** non hanno card inline speciale: solo riassuntiva.
- L'ordine visuale nella colonna sinistra è: Prescrizione → Fornitori (solo admin) → Preventivi (+ inline sent) → Produzione → Fatture (+ inline sent).

### Stato vuoto

Per qualunque entità riassuntiva non ancora presente (0 record visibili all'utente), la card mostra un placeholder "Non ancora presente" (o variante testuale più specifica, es. "Nessun preventivo ancora"). La card rimane visibile e il click è disattivato (oppure, se l'utente ha permessi di creazione, il comportamento di default della tab specifica gestirà il flusso — non si aggiungono CTA custom in Panoramica).

## Dati e relazioni

Tutte le entità, relazioni e stati necessari **esistono già** nel codebase:

- `Operation` (`app/Models/Operation.php`) con relazioni:
  - `building()` → `Building`
  - `prescriptions()` → `HasMany<Prescription>`
  - `suppliers()` → `BelongsToMany<Supplier>` con pivot `selected, status`
  - `selectedSupplier()` (accessor)
  - `quotes()` → `HasMany<Quote>`
  - `productions()` → `HasMany<Production>`
  - `invoices()` → `HasMany<Invoice>`
- `Building::agent()` → `BelongsTo<User>`
- Modelli: `Prescription`, `Quote`, `Production`, `Invoice`, `Building`, `Supplier`, `User`.
- Enum stato: `OperationStatusEnum`, `PrescriptionStatusEnum`, `QuoteStatusEnum`, `ProductionStatusEnum`, `InvoiceStatusEnum`.

**Nessuna nuova tabella, nessuna nuova colonna, nessuna nuova relazione**.

Campi letti dalla Panoramica (tutti già esistenti):
- `prescription.status`, `prescription.updated_at`, `prescription.user_id`, `prescription.send_at`
- `quote.status`, `quote.updated_at`, `quote.accepted_at`, `quote.rejected_at`
- `production.status`, `production.updated_at`, `production.confirmed_at`, `production.canceled_at`
- `invoice.status`, `invoice.updated_at`, `invoice.sent_at`
- `building.name`, `building.agent_id`
- `user.name`, `user.surname`, `user.email` (richiedente, agente)
- `supplier.name`, `supplier.mail`, pivot `selected`

Il backend dovrà passare via Inertia un blob con i dati già pre-aggregati per la Panoramica, per evitare calcoli pesanti lato Vue (es. conteggi, elemento principale, flag di visibilità applicati in base al ruolo).

## Permessi e ruoli

Allineamento con il sistema di permessi esistente (`OperationPolicy` + permission strings `operations.{entity}.view|manage`):

- **Accesso alla tab Panoramica**: chi ha `OperationPolicy::view()` → stessa gate della pagina Show. Nessun permesso dedicato.
- **Sezione Attori — card Agente**: visibile solo a chi ha `operations.view-all` (admin) o è l'agente stesso dell'edificio. **Nascosta al customer**.
- **Sezione Attori — card Fornitore selezionato**: visibile solo a chi ha `operations.supplier.view`. **Nascosta al customer** (di fatto customer non ha questo permesso, mantenere la stessa logica che oggi nasconde la tab Fornitori lato workspace).
- **Sezione Riepilogo — card Fornitori**: stessa regola della card Fornitore attore; visibile solo con `operations.supplier.view`.
- **Sezione Riepilogo — altre card (prescrizione, preventivi, produzione, fatture)**: visibili a chiunque abbia rispettivamente `operations.prescription.view`, `operations.quote.view`, `operations.production.view`, `operations.invoice.view`. Se il permesso manca, la card non compare del tutto (non "Non ancora presente", proprio assente).
- **Azioni sulle card inline speciali**: le azioni replicano esattamente quelle della tab specifica, quindi si appoggiano ai permessi `operations.{entity}.manage` già in uso (es. `operations.quote.manage`, `operations.invoice.manage`). Non cambia nulla rispetto alla logica attuale.

## Corner case e gestione errori

- **Lavorazione in `draft`** → tab Panoramica comunque accessibile. Colonna Riepilogo piena di placeholder "Non ancora presente"; colonna Attori mostra almeno Struttura (sempre presente) e Agente (se assegnato). Richiedente placeholder.
- **Nessuna prescrizione (o tutte in draft per il customer)** → card Richiedente placeholder "Non ancora definito".
- **Building senza agente (`agent_id = null`)** → card Agente placeholder "Non ancora assegnato" (solo admin la vede comunque).
- **Nessun fornitore selezionato** → card Fornitore (sia in Attori che in Riepilogo) placeholder "Non ancora selezionato".
- **Più preventivi in stato `sent` contemporaneamente** → tutte le card inline corrispondenti vengono mostrate, una sotto l'altra, sotto la card riassuntiva Preventivi.
- **Più fatture in stato `sent` contemporaneamente** → idem per Fatture.
- **Entità visibili lato admin ma non lato customer** (es. preventivo in `draft`, fattura in `draft`, prescrizione mai inviata) → per il customer quella entità è come se non esistesse: i contatori della card riassuntiva non la includono, e se è l'unica esistente la card riassuntiva mostra "Non ancora presente". Regola guida: una entità è visibile al customer **se e solo se è stata inviata**, ovvero ha un timestamp di invio (`prescription.send_at` / `quote` in stato ≥ `sent` / `invoice.sent_at` non null). Produzione è sempre visibile al customer una volta creata (non ha concetto di "invio").
- **Utente con lavorazione archiviata / cancellata** → Panoramica mostra comunque il suo contenuto congelato (stessa policy del resto della pagina Show).
- **Click su card riassuntiva di entità vuota** → nessuna navigazione (card non cliccabile oppure click che porta alla tab vuota nel caso sia comunque raggiungibile oggi — allinearsi al comportamento attuale del click sulle altre tab in stato vuoto).
- **Errori in azioni inline** (es. accettazione preventivo fallisce lato server) → si riusano i pattern di feedback già presenti in `QuotesTab.vue` / `InvoicesTab.vue` (toast via `useMainToast`), senza duplicare logica.

## UX e feedback

- **Stati di loading**: al primo render della tab, se i dati arrivano via Inertia con la pagina, nessun loader necessario. Se qualche dato viene fetchato in lazy (es. extra aggregati), mostrare skeleton per le singole card.
- **Feedback azioni inline**: stesso pattern delle tab specifiche (toast di successo/errore tramite `useMainToast`). Dopo un'azione su una card inline, la panoramica si aggiorna (via Inertia reload parziale o refetch della pagina) in modo che lo stato aggregato e la presenza/assenza della card inline si ricalcolino correttamente.
- **Navigazione da card riassuntiva**: transizione fluida all'interno della stessa pagina (cambio tab senza ricaricare). Se la tab di destinazione è disabilitata (es. preventivo ma nessun supplier selezionato → `enableQuotesTab = false`), allora il click sulla card riassuntiva deve rispettare lo stato disabilitato e non navigare (o disabilitare l'intera card riassuntiva).
- **Responsive**: il layout a due colonne deve collassare su una sola colonna (riepilogo sopra, attori sotto) su viewport strette, seguendo i breakpoint Tailwind già in uso nel progetto.
- **Nessuna modifica a CSS base / main.css / theming.css**: eventuali override/stili nuovi vanno in un file `*_override.css` dedicato; aggiornare `docs/ui/UI_ACTIVITY_LOG.md` dopo le modifiche CSS (da regola progetto).

## Fasi di implementazione

### Fase unica: implementazione completa Panoramica

- **Scope**: implementare la tab Panoramica end-to-end per admin e customer, con entrambe le sezioni (Attori, Riepilogo), card riassuntive per tutte le entità, card inline speciali per preventivi e fatture in stato `sent`, gestione placeholder, regole di visibilità per ruolo.
- **File coinvolti**:
  - **Backend**:
    - `app/Http/Controllers/OperationController.php` → aggiungere al payload della `show` i dati aggregati per la Panoramica (prescrizione/preventivi/produzione/fatture/fornitori filtrati per permessi, elemento principale, ultima data, contatori, attori).
    - Eventualmente un **ViewModel/Resource** dedicato (es. `app/Http/Resources/OperationOverviewResource.php`) per isolare la logica di aggregazione.
    - `app/Models/Operation.php` → eventuali accessor/metodi helper (es. `mainQuote`, `latestInvoice`) se utili per tenere leggero il controller/resource.
  - **Frontend**:
    - `resources/js/pages/operations/partials/OverviewTab.vue` → nuovo componente (lato admin).
    - `resources/js/pages/workspace/operations/partials/OverviewTab.vue` → nuovo componente (lato customer) — valutare se condividere un componente base e parametrizzare, per evitare duplicazione.
    - `resources/js/pages/operations/Show.vue` e `resources/js/pages/workspace/operations/Show.vue` → sostituire il placeholder della tab `overview` con il nuovo componente.
    - Componenti di supporto in `resources/js/components/operations/overview/` (es. `ActorCard.vue`, `SummaryCard.vue`) per le card riassuntive e le card attori.
    - Riuso delle card operative esistenti (`QuotesTab.vue`, `InvoicesTab.vue`): se le card inline per preventivo/fattura non sono già componenti estratti, estrarli in `QuoteCard.vue` / `InvoiceCard.vue` riusabili e usarli sia nella tab specifica sia nella Panoramica.
    - `resources/js/types/Operation.ts` → estendere il tipo se si aggiungono campi aggregati nel payload.
  - **Permessi**: nessun nuovo permesso; sfruttare quelli esistenti (`operations.*.view`, `operations.*.manage`).
  - **Test**:
    - Feature test Pest sul controller per verificare il payload della Panoramica (admin vs customer, filtri di visibilità, elemento principale corretto).
    - Test Policy/permessi sulla sezione Attori (customer non riceve Fornitore/Agente).
    - Test di presenza/assenza card inline in base allo stato `sent` di preventivi/fatture.
    - Eventuale smoke test browser (Pest 4) sulla pagina Show per verificare che la tab Panoramica si apra e renderizzi senza errori JS.
- **Dipende da**: nulla, l'infrastruttura (modelli, policy, enum, tab system, card operative) è già in piedi.
