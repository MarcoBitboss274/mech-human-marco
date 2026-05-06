# Filtri aggiuntivi, counter risultati ed esportazione lavorazioni — Index admin M&H

## Contesto

La pagina Index lavorazioni lato admin M&H (`resources/js/pages/operations/Index.vue`) ha già un set di filtri (Cerca, Struttura, Richiedente, Tipologia, Stato, Preventivo, Fornitore, Data scadenza range, Data invio range) e tre tab "Bozze / Attive / Archiviate". Mancano però:

- un **filtro Agente** (utile soprattutto per superadmin/admin che gestiscono più realtà);
- una **rinomina** delle label dei due filtri range data per allinearle a un naming "da-a" più esplicito;
- un **counter testuale dei risultati** sopra la tabella, che renda leggibile a colpo d'occhio quali filtri sono attivi e quanti risultati il filtro produce;
- una **funzionalità di esportazione** dei risultati (CSV / Excel), sia rispettando il filtraggio corrente sia, come azione globale, su tutte le lavorazioni di tutte le tab.

L'obiettivo è migliorare il workflow di analisi e reportistica admin, senza alterare le pagine workspace customer/agent/supplier (che restano fuori scope).

## Flusso utente

### A. Filtri (sidebar/topbar filtri della Index admin)

1. L'admin apre la Index lavorazioni.
2. La sidebar filtri presenta, in aggiunta a quelli odierni, un nuovo controllo **"Agente"** (BbSelect) con la lista degli utenti con ruolo `agent`. Posizione: subito dopo "Richiedente" (a discrezione di chi implementa, purché coerente con l'ordine attuale).
3. I due filtri data esistenti vengono rinominati:
   - "Data scadenza" → "Scadenza da: - a:"
   - "Data invio" → "Invio da: - a:"
   I componenti restano i medesimi (`BbDatePickerInput` con `range=true`), il backend non cambia (campi querystring `expire_at_from/to`, `send_at_from/to` restano).
4. L'admin seleziona uno o più filtri. La querystring viene aggiornata da `useIndexPage` come oggi e la lista lavorazioni viene ricaricata.

### B. Counter testuale (sopra la tabella, in ogni tab)

1. Sopra la tabella di ciascuna tab (Bozze, Attive, Archiviate) compare una stringa testuale che descrive la query corrente e il numero di risultati totali (non per pagina).
2. Senza filtri attivi (oltre alla tab implicita): es. "Lavorazioni Attive: 1.234".
3. Con filtri attivi: concatenazione descrittiva delle clausole, separate da virgole, nell'ordine in cui i filtri appaiono nella sidebar, suffisso `: N`. Esempio:
   - Tab "Attive" + filtro Struttura "Studio Rossi" + filtro Agente "Mario Bianchi" + Scadenza da 01/01/2026 a 31/01/2026 → **"Lavorazioni Attive, struttura Studio Rossi, agente Mario Bianchi, scadenza 01/01/2026 - 31/01/2026: 200"**.
4. Il counter si aggiorna automaticamente al cambio di tab e al cambio dei filtri (vive dentro lo stesso ciclo Inertia di reload).

### C. Esportazione contestuale (accanto al counter)

1. Accanto al counter compare un pulsante **"Esporta"** con icona download e dropdown menu (CSV / Excel).
2. L'admin clicca "Esporta" e sceglie un formato:
   - "CSV" → download immediato di un file `.csv` con encoding UTF-8 (BOM) e separatore `;` (compatibile Excel italiano);
   - "Excel" → download immediato di un file `.xlsx`.
3. Il file contiene **i risultati corrispondenti ai filtri e alla tab attualmente applicati**, senza paginazione (tutte le righe filtrate).
4. Nome file: `lavorazioni_{tab}_{YYYYMMDD_HHmmss}.{csv|xlsx}` (es. `lavorazioni_attive_20260506_143012.xlsx`).

### D. Esportazione globale (accanto a "+ nuova lavorazione")

1. Nell'header della Index, accanto al pulsante "+ nuova lavorazione", compare un secondo pulsante **"Esporta tutte"** con dropdown (CSV / Excel) coerente con quello contestuale.
2. Click su "Esporta tutte" → download di un file con **tutte le lavorazioni della piattaforma**: Bozze + Attive + Archiviate, **ignorando tab corrente e filtri applicati**.
3. Nome file: `lavorazioni_tutte_{YYYYMMDD_HHmmss}.{csv|xlsx}`.
4. Nel file, una colonna aggiuntiva **"Categoria"** indica per ogni riga la tab di appartenenza (`Bozza` / `Attiva` / `Archiviata`).

## Regole di business

### Filtro Agente
- Sorgente lista: utenti `User` con role `agent` (Spatie/`RoleEnum::AGENT`), attivi (status non disabled secondo le regole già in vigore per il system).
- Filtraggio backend: l'Operation è inclusa se la sua `building.agent_id` corrisponde all'agente selezionato (`whereHas('building', fn($q) => $q->where('agent_id', $value))`). Operation non ha `agent_id` diretto: la relazione passa per Building come per le policy esistenti.
- Querystring param: `agent_id` (singolo valore intero).
- Visibilità del controllo: solo per utenti loggati con ruolo `superadmin` o `admin`. L'utente con ruolo `agent` non vede il filtro (vede già solo le proprie lavorazioni per policy).

### Rinomina label
- Il filtro "Data scadenza" diventa "Scadenza da: - a:" (label).
- Il filtro "Data invio" diventa "Invio da: - a:" (label).
- Nessun cambio funzionale, nessun cambio di nome dei query param.

### Counter testuale
- Il counter è **sempre presente** sopra la tabella di ciascuna tab.
- Tab è la prima clausola obbligatoria della stringa: `Lavorazioni {Bozze|Attive|Archiviate}`.
- Per ogni filtro attivo si aggiunge una clausola descrittiva nell'ordine in cui i filtri appaiono nella sidebar:
  - `cerca "{testo}"` per il filtro testuale;
  - `struttura {nome building}` per `building_id`;
  - `richiedente {nome cognome user}` per `user_id`;
  - `tipologia {label tipologia}` per `typology`;
  - `stato {label stato}` per `status` (label utente-friendly, non l'enum value);
  - `preventivo {label}` per il filtro preventivo;
  - `fornitore {nome supplier}` per `supplier_id` (visibile solo se l'utente ha la relativa permission);
  - `agente {nome cognome}` per `agent_id`;
  - `scadenza {gg/mm/aaaa} - {gg/mm/aaaa}` per il range scadenza (solo lato presente: se è valorizzato solo il "da" o solo il "a", la clausola si adatta es. `scadenza dal 01/01/2026` o `scadenza fino al 31/01/2026`);
  - `invio {gg/mm/aaaa} - {gg/mm/aaaa}` per il range invio (stessa logica del precedente).
- Suffisso: `: N` con `N` formattato con il separatore migliaia italiano (es. `1.234`).
- `N` è il **totale dei risultati che soddisfano i filtri**, non la pagina corrente. Va calcolato sullo stesso paginatore già esistente (`->total()` del paginator).
- Quando nessun filtro è attivo: solo `Lavorazioni {Tab}: N`.
- La composizione della stringa deve avvenire **lato backend** (Inertia payload) per evitare divergenze fra la traduzione dei valori sul DB e i label client. Il payload Inertia espone una proprietà `resultsLabel: string` insieme a `total: number`, già pronte da renderizzare.

### Esportazione contestuale
- Si applicano gli stessi filtri attivi (incluso filtro tab implicito) della query corrente. Nessuna paginazione: tutto il risultato.
- Stesse permission della Index (utente deve poter vedere quelle Operation), e in più la permission specifica di export (vedi sezione Permessi).
- Soglia di sicurezza (vedi Corner case): se il numero di righe da esportare supera 50.000, l'export è bloccato con messaggio.

### Esportazione globale
- Esporta tutte le Operation della piattaforma (Bozze + Attive + Archiviate), incluse quelle annullate/archiviate. Nessun filtro applicato.
- Aggiunge la colonna "Categoria" che vale `Bozza` se `status = draft`, `Archiviata` se `archived_at != null`, `Attiva` altrimenti.
- Stessa soglia di sicurezza 50.000.

### Colonne dell'export (set fisso)
Identico per export contestuale e globale (con l'eccezione della colonna `Categoria` presente solo nel globale):

1. ID lavorazione
2. Codice di lotto (`batch_number`)
3. Riferimento (`latestPrescription.ref`)
4. Tipologia (label leggibile)
5. Struttura (`building.name`)
6. Richiedente (`latestPrescription.user.full_name` o equivalente)
7. Stato (label leggibile dello status)
8. Stato preventivo (label leggibile dello stato preventivo, se calcolabile come oggi)
9. Fornitore selezionato (`selectedSupplier.name` oppure vuoto se non assegnato)
10. Agente (`building.agent.full_name` oppure vuoto)
11. Data creazione (`created_at`, formato `dd/MM/yyyy HH:mm`)
12. Data invio prescrizione (`latestPrescription.send_at`, `dd/MM/yyyy`)
13. Data scadenza prescrizione (`latestPrescription.expire_at`, `dd/MM/yyyy`)
14. Data assegnazione fornitore (`selectedSupplier.pivot.selected_at`, `dd/MM/yyyy`)
15. *(solo nell'export globale)* Categoria (Bozza / Attiva / Archiviata)

Tutte le colonne valgono "" (stringa vuota) se il dato non è disponibile.

### Formato file
- **CSV**: encoding `UTF-8 con BOM` (per compatibilità con Excel italiano), delimitatore `;`, racchiudere i valori che contengono `;`, `"` o newline tra virgolette doppie con escaping. Header sulla prima riga.
- **Excel**: `.xlsx` con un solo foglio chiamato "Lavorazioni"; prima riga = header in bold; nessuna formula, nessuna formattazione condizionale.
- Pacchetto: `maatwebsite/excel` v3.1+ (da installare; non presente nel `composer.json` attuale).

## Dati e relazioni

### Esistente — riusato senza modifiche
- `OperationService::search($request, ...)` (`app/Services/OperationService.php`) — paginatore + applicazione filtri.
- Filtri attuali via `applyFilters` (struttura, richiedente, tipologia, stato, preventivo, fornitore, scadenza range, invio range, search) restano invariati.
- Tabs Bozze/Attive/Archiviate gestite via querystring `mode` (o equivalente) attraverso `useIndexPage`.
- Spatie Permission, `RoleEnum::AGENT`.
- Modello `Building.agent_id` (relazione esistente con User).
- Componenti `BbSelect`, `BbDatePickerInput`, `BbButton`, `BbTab`, e composable `useIndexPage` (`resources/js/composables/useIndexPage.ts`).

### Nuovo
- **Filtro `agent_id`** in `OperationService::applyFilters` con `whereHas('building', fn($q) => $q->where('agent_id', $value))`.
- **Endpoint backend per export**:
  - `GET /operations/export` con query param `format=csv|xlsx` + tutti i filtri della Index (riusare il request shape attuale + nuovo `agent_id`).
  - `GET /operations/export-all` con query param `format=csv|xlsx`, ignora i filtri.
- **Controller method**: `OperationController::export(Request $request)` e `OperationController::exportAll(Request $request)` che chiamano un nuovo `OperationsExporter` (service o classe Maatwebsite).
- **Classe export**: `app/Exports/OperationsExport.php` (implements `FromQuery`/`WithHeadings`/`WithMapping`/`WithTitle` per Excel; per CSV un piccolo writer custom su `streamDownload`, oppure `WithEvents` di Maatwebsite con `fromArray` se più semplice — scelta a discrezione di chi implementa, purché entrambi i formati condividano la stessa funzione di mapping).
- **Composizione `resultsLabel` lato backend**: helper in `OperationService` (es. `buildResultsLabel(Request $request, int $total): string`) che riusa la logica di `applyFilters` per leggere i valori e produrre la stringa formattata in italiano. Il controller include `resultsLabel` e `total` nel payload Inertia inviato a Index.
- **Componente Vue `OperationsResultsBar.vue`**: barra sopra la tabella con la stringa `resultsLabel` e il pulsante "Esporta" (BbButton con dropdown). Riceve via prop `resultsLabel`, `total`, `exportUrl`, `canExport`.
- **Componente Vue `ExportDropdown.vue`**: pulsante con dropdown menu (CSV / Excel) riusabile sia in `OperationsResultsBar` sia nell'header (per "Esporta tutte"). Riceve `exportUrl: string` e gestisce il download via `window.location.href` o `<a download>`.
- **Pacchetto Composer**: aggiunta di `maatwebsite/excel` in `composer.json`.

### File coinvolti (alto livello)

Backend:
- `composer.json` — aggiungere `maatwebsite/excel` (v3.1+).
- `app/Http/Controllers/OperationController.php` — aggiungere `export` ed `exportAll`; estendere `index` per restituire `resultsLabel` e `total` nel payload.
- `app/Services/OperationService.php` — aggiungere il filtro `agent_id` in `applyFilters`; aggiungere `buildResultsLabel`; predisporre query base per export (riuso di `applyFilters`).
- `app/Exports/OperationsExport.php` — nuova classe che data una `Builder` e un set di colonne produce CSV / Excel.
- `routes/web.php` — nuove route `operations.export` e `operations.export-all` con middleware `role:superadmin|admin` e permission gate.
- `app/Policies/OperationPolicy.php` (o equivalente Gate) — gate `operations.export` per Superadmin/Admin.

Frontend:
- `resources/js/pages/operations/Index.vue` — aggiunta del filtro Agente (visibile solo a superadmin/admin), rinomine label "Scadenza da: - a:" / "Invio da: - a:", inserimento del nuovo `OperationsResultsBar` sopra la tabella, inserimento di `ExportDropdown` accanto a "+ nuova lavorazione".
- `resources/js/components/operations/OperationsResultsBar.vue` — nuovo.
- `resources/js/components/operations/ExportDropdown.vue` — nuovo.

## Permessi e ruoli

| Funzionalità | Superadmin | Admin | Agent | Customer/Supplier |
|---|---|---|---|---|
| Vede filtro Agente | ✓ | ✓ | ✗ | n/a (non accedono alla Index admin) |
| Vede pulsante "Esporta" (contestuale) | ✓ | ✓ | ✗ | n/a |
| Vede pulsante "Esporta tutte" | ✓ | ✓ | ✗ | n/a |
| Vede counter testuale | ✓ | ✓ | ✓ | n/a |
| Vede label rinominate dei filtri | ✓ | ✓ | ✓ | n/a |

Backend gate: nuovo permesso/gate `operations.export` legato ai ruoli `superadmin` e `admin`. Le route export sono protette da questo gate; in caso di tentativo non autorizzato, 403.

## Corner case e gestione errori

- **Nessun risultato corrispondente ai filtri** → counter mostra `Lavorazioni {Tab}, ... : 0`. Bottone "Esporta" disabilitato con tooltip "Nessun risultato da esportare".
- **Click "Esporta" su una query con > 50.000 righe** → l'endpoint risponde 422 / errore JSON; lato UI toast di errore: "Troppi risultati per l'esportazione (limite 50.000). Restringi i filtri."
- **Click "Esporta tutte" quando il totale Operation supera 50.000** → stessa gestione (limite hardcoded all'inizio, alzabile in futuro).
- **Filtro agente che seleziona un utente che non ha più il ruolo `agent`** (caso limite: ruolo modificato dopo che l'utente è stato selezionato) → backend ignora il filtro e restituisce risultati come se non fosse applicato; frontend al refresh ricarica la lista degli agenti, e se l'id selezionato non è più valido lo pulisce.
- **Tab corrente vuota dopo l'applicazione del filtro Agente** → il counter mostra "0", la tabella mostra il proprio empty state. Pulsante "Esporta" disabilitato.
- **`build_resultsLabel` con filtro che fa riferimento a un'entità cancellata/archiviata** → si recupera comunque il nome (anche se soft-deleted) per coerenza dello storico; in mancanza, si mostra l'ID con prefisso (`struttura #42`) come fallback.
- **Click rapido su "Esporta" mentre l'utente sta ancora cambiando filtri** → il bottone diventa loading state finché il download non parte (single-flight). Niente coda di richieste sovrapposte.
- **Browser che blocca il download** → comportamento standard del browser; nessuna gestione speciale.
- **Range date con "da" > "a"** → validazione lato backend e UI: errore inline "La data di inizio non può essere successiva alla data di fine" (riusa pattern già in vigore se presente; altrimenti semplice check).
- **Counter testuale con stringhe molto lunghe** (molti filtri attivi insieme) → la stringa va a capo naturalmente; nessun truncation. Posizionato sopra la tabella in un blocco a tutta larghezza.
- **Permission `operations.export` mancante** → pulsanti "Esporta" non renderizzati lato UI; richiesta diretta agli endpoint risponde 403.

## UX e feedback

### Filtri
- Filtro Agente è un BbSelect con search (riusa pattern `BuildingsIndex.vue` — `agentIdFilter`). Placeholder: "Tutti gli agenti". Valore "Nessuno selezionato" = nessun filtro applicato.
- Le label dei due range date diventano "Scadenza da: - a:" e "Invio da: - a:". Nessun altro cambio sui controlli.

### Counter + Esporta contestuale
- Layout: barra orizzontale sopra la tabella, counter a sinistra (testo), bottone "Esporta" a destra. Stile coerente con le altre Index (riuso `BbButton` outline + icona download).
- Bottone "Esporta" → click apre dropdown menu sotto al bottone con due voci: "Esporta CSV" / "Esporta Excel".
- Click su una voce → loading state sul bottone (spinner + label "Generazione…"), download del file (browser default), reset del bottone allo stato normale al completamento.
- Toast di conferma post-download: "Esportazione completata".
- Toast di errore: "Esportazione non riuscita. Riprova" se il download fallisce.

### Esporta tutte
- Posizionamento: nell'header della Index, immediatamente a sinistra di "+ nuova lavorazione" (mantenendo il bottone "+ nuova lavorazione" come azione principale a destra).
- Stesso comportamento del bottone contestuale (dropdown CSV/Excel).
- Tooltip al hover: "Esporta tutte le lavorazioni di tutte le tab, ignorando i filtri".

### Loading
- Cambio filtri → debounce 400ms (già esistente in `useIndexPage`); durante il reload la tabella mostra skeleton standard, il counter aggiorna `total` al termine.
- Generazione export → spinner sul bottone con label "Generazione…". Nessun overlay full-page.

## Fasi di implementazione

La feature è coesa ma con due aree tecnicamente distinte: (1) filtri/counter (puro lavoro su Index esistente), (2) esportazione (richiede nuova dipendenza Composer e nuove route/classi). Si propongono due fasi sequenziali, ciascuna shippabile e testabile indipendentemente.

### Fase 1: Filtro Agente, rinomine label, counter testuale

- **Scope**:
  - Aggiunta del filtro Agente in sidebar (visibile solo a superadmin/admin) con relativa logica nel template Vue e in `OperationService::applyFilters`.
  - Rinomina label "Scadenza da: - a:" e "Invio da: - a:" (solo testo).
  - Backend: `OperationService::buildResultsLabel(Request, int total)` con tutta la concatenazione descrittiva in italiano e i lookup delle entità referenziate.
  - Controller `OperationController::index` arricchito del payload (`resultsLabel`, `total`).
  - Componente Vue `OperationsResultsBar.vue` con la stringa e (in questa fase) **senza** bottone Esporta.
- **File coinvolti**: `app/Services/OperationService.php`, `app/Http/Controllers/OperationController.php`, `resources/js/pages/operations/Index.vue`, `resources/js/components/operations/OperationsResultsBar.vue` (nuovo).
- **Dipende da**: nulla.

### Fase 2: Esportazione CSV/Excel (contestuale e globale)

- **Scope**:
  - Aggiunta dipendenza Composer `maatwebsite/excel`.
  - `app/Exports/OperationsExport.php` con mapping colonne fisse (compresa "Categoria" condizionale per export-all).
  - Route + controller `OperationController::export` (filtri correnti) e `OperationController::exportAll` (tutto).
  - Gate/permission `operations.export` per superadmin/admin.
  - Componente Vue `ExportDropdown.vue` (CSV / Excel).
  - Inserimento del dropdown in `OperationsResultsBar` (export contestuale) e nell'header della Index (export globale, accanto a "+ nuova lavorazione").
  - Soglia 50.000 righe.
- **File coinvolti**: `composer.json`, `app/Http/Controllers/OperationController.php`, `app/Services/OperationService.php` (esposizione query base per export riusando `applyFilters`), `app/Exports/OperationsExport.php` (nuovo), `routes/web.php`, `app/Policies/OperationPolicy.php` o gate equivalente, `resources/js/pages/operations/Index.vue`, `resources/js/components/operations/OperationsResultsBar.vue` (estensione), `resources/js/components/operations/ExportDropdown.vue` (nuovo).
- **Dipende da**: Fase 1 (riuso di `OperationsResultsBar` e di `applyFilters` esteso con `agent_id`).
