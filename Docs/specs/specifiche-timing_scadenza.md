# Alert prescrizione in scadenza / scaduta sulle lavorazioni attive

## Contesto

Le lavorazioni hanno una prescrizione collegata che ha una propria data di scadenza (`Prescription.expire_at`). Oggi questa data è semplicemente mostrata in tabella (colonna "Scade il") senza alcun meccanismo di evidenziazione: l'utente non viene aiutato a riconoscere proattivamente le lavorazioni con prescrizione prossima alla scadenza, e nemmeno quelle con prescrizione già scaduta ma con lavorazione ancora attiva.

Si vuole introdurre un meccanismo di evidenziazione visiva che, sulle lavorazioni in stato attivo, segnali:
- la prescrizione **in scadenza** (entro 30 giorni dalla data di scadenza), con stile warning;
- la prescrizione **già scaduta** (data passata) ma con lavorazione ancora attiva, con stile error.

L'evidenziazione va mostrata in **tutte le 4 aree** in cui le lavorazioni sono visualizzate (Index e Detail) per i ruoli: admin/superadmin/agent M&H, Customer (workspace), Fornitore (workspace supplier — in costruzione nelle specifiche `specifiche-area_fornitore_lavorazioni.md`).

## Flusso utente

### A. Index lavorazioni (tutti i 4 ruoli)

1. L'utente apre la pagina lista lavorazioni della propria area.
2. Per ogni riga di una lavorazione **attiva** (vedi Regole di business per la definizione di "attiva") la cella "Data di scadenza" mostra:
   - se la prescrizione è "in scadenza" (entro 30 giorni): la data + un'icona ⚠️ warning a destra del testo, con `BbTooltip` che al hover mostra "Prescrizione in scadenza il: {data scadenza}";
   - se la prescrizione è "scaduta" (data passata): la data + un'icona 🔴 error a destra del testo, con `BbTooltip` che al hover mostra "Prescrizione scaduta il: {data scadenza}";
   - se la prescrizione non è né in scadenza né scaduta: solo la data, senza icona.
3. Per le lavorazioni non attive (vedi Regole), la cella mostra solo la data senza icone, anche se la data è passata o entro 30 giorni.

### B. Detail lavorazione (tutti i 4 ruoli)

1. L'utente apre il dettaglio di una lavorazione.
2. Subito sotto l'header (nell'area già usata da `OperationNotice` per i banner contestuali) il sistema mostra, in via aggiuntiva agli altri eventuali banner attivi:
   - un alert **warning** con testo "⚠️ Attenzione: prescrizione in scadenza il: {data scadenza}" se la lavorazione è attiva e la prescrizione è in scadenza;
   - un alert **error** con testo "⚠️ Attenzione: prescrizione scaduta il: {data scadenza}" se la lavorazione è attiva e la prescrizione è scaduta.
3. Se la lavorazione non è attiva, nessun alert relativo alla scadenza viene mostrato (anche con prescrizione vicina alla scadenza o passata).

## Regole di business

### Definizione di "lavorazione attiva"
Una lavorazione è attiva ai fini di questo meccanismo se e solo se:
- `Operation.canceled_at` è null (non annullata);
- `Operation.status` è uno dei seguenti: `requested` (Richiesta), `in_progress` (In lavorazione), `waiting_approval` (In approvazione).

> Le lavorazioni in `draft`, `production`, `completed` non innescano mai l'alert, anche se la prescrizione è in scadenza o scaduta. Le lavorazioni con `canceled_at` non null non innescano mai l'alert.

### Stato della prescrizione rispetto alla scadenza
Si calcolano due flag derivati su `Prescription` (o, equivalentemente, dalla `latestPrescription` dell'Operation):

- **In scadenza (`is_expiring_soon`)**: `expire_at` valorizzato AND `expire_at >= now()` AND `expire_at <= now()->addDays(30)` (rolling 30 giorni dal momento attuale).
- **Scaduta (`is_expired`)**: `expire_at` valorizzato AND `expire_at < now()`.

Le due condizioni sono mutuamente esclusive. Se `expire_at` è null, entrambi i flag sono `false` (nessun alert).

### Visibilità alert lato Operation
Gli accessor derivati lato Operation (es. `prescription_expiring_status`) restituiscono:
- `expired` se la lavorazione è attiva (come definito sopra) e `latestPrescription.is_expired`;
- `expiring_soon` se la lavorazione è attiva e `latestPrescription.is_expiring_soon`;
- `none` in tutti gli altri casi (incluso lavorazione non attiva e prescrizione senza scadenza).

### Logica calcolata in lettura
Il meccanismo è **calcolato in lettura** (accessor / value resolver), non persistito su colonna. Non servono job di aggiornamento periodico: ogni request rivaluta lo stato sulla base di `now()` e dei dati live.

### Granularità unica
Tutta la finestra "in scadenza" (entro 30 giorni) è warning, senza ulteriori soglie cromatiche. Una volta superata la data di scadenza, l'alert diventa "scaduto" (error).

### Nessun filtro dedicato
La pagina Index non riceve un nuovo filtro "Solo in scadenza"; resta in uso il filtro range su `expire_at` già presente nelle Index admin e workspace customer (le altre Index — agente, fornitore — useranno il filtro range in linea con le scelte della specifica relativa).

### Permessi
Il meccanismo è puramente di visualizzazione e non introduce nuovi gate. Ogni ruolo vede il proprio scope (M&H tutte le Operation, Agente quelle nella sua sfera, Customer le proprie del workspace, Fornitore quelle assegnate alla sua azienda secondo `specifiche-area_fornitore_lavorazioni.md`); su quelle visibili si applicano le stesse regole di evidenziazione.

## Dati e relazioni

### Esistente — riusato senza modifiche
- `prescriptions.expire_at` (datetime nullable) — già esistente come `dateTime('expire_at')` (migration `2026_03_20_120000_add_send_and_expire_at_to_prescriptions_table.php`), già castato a `datetime` nel modello `Prescription`.
- `Operation::latestPrescription()` — relazione HasOne già esistente.
- `Operation.status` (`OperationStatusEnum`) e `Operation.canceled_at` — già usati in altre regole della piattaforma.
- Componente `OperationNotice` — già montato sotto l'header in `resources/js/pages/operations/Show.vue` (admin/agent) e in `resources/js/pages/workspace/operations/Show.vue` (customer) con prop `scope`.
- Componente `BbTooltip` (design system bitboss-ui) — già in uso altrove (es. icona revision status nella workspace Index).
- Componenti tabella `BbTable` con cella "Scade il" (`resources/js/pages/operations/Index.vue` riga ~580 e workspace equivalente).

### Nuovo
- **Accessor `Prescription::isExpiringSoon()`** (o computed property) — restituisce bool. Logica nella sezione Regole di business.
- **Accessor `Prescription::isExpired()`** — restituisce bool.
- **Accessor `Operation::getPrescriptionExpiringStatus()`** (o property derivata) — restituisce string `expired` | `expiring_soon` | `none`. Confluisce nei DTO/payload Inertia inviati alla Index e alla Show di tutti i ruoli.
- **Componente Vue `PrescriptionExpiryIndicator.vue`** (icona + BbTooltip) — riusabile nella cella "Data di scadenza" delle 4 Index. Riceve due prop: `status` (`expiring_soon` | `expired` | `none`) e `expireAt` (Date). Quando `none`, non renderizza nulla.
- **Estensione `OperationNotice.vue`**: aggiunta di due voci nella lista alert items, una per `expiring_soon` e una per `expired`, con i testi indicati. Visibili in entrambi gli scope `admin` e `workspace`. Per il fornitore, lo scope `supplier` (vedi `specifiche-area_fornitore_lavorazioni.md`) include le stesse due voci.
- **Inclusione del nuovo accessor nei resource/payload**: i controller Index e Show dei 4 ruoli devono esporre `prescription_expiring_status` (string) e `prescription_expire_at` (data formattata o ISO) per ogni Operation passata al frontend, in modo che le Vue possano renderizzare l'indicatore senza ricavare la logica lato client.

### File coinvolti (alto livello)

Backend:
- `app/Models/Prescription.php` — aggiungere `isExpiringSoon()` e `isExpired()`.
- `app/Models/Operation.php` — aggiungere accessor/method `prescription_expiring_status`.
- Resource/transformer (oppure il punto in cui Operation viene serializzata per Inertia in Index/Show): aggiungere il campo `prescription_expiring_status`. Il campo `prescription_expire_at` o equivalente è già disponibile (deriva da `latestPrescription.expire_at`).
- Eventuali servizi/repository per Index (admin + workspace + supplier): nessun cambio di logica di filtri, solo accortezza che il nuovo campo arrivi.

Frontend:
- Nuovo `resources/js/components/operations/PrescriptionExpiryIndicator.vue` — icona warning/error + BbTooltip; nessuna icona quando `status === 'none'`.
- `resources/js/pages/operations/Index.vue` — usare il nuovo componente nella cella "Scade il" (admin/agent).
- `resources/js/pages/workspace/operations/Index.vue` — idem per customer.
- `resources/js/pages/workspace/supplier/operations/Index.vue` (in costruzione nella specifica `specifiche-area_fornitore_lavorazioni.md`) — idem per fornitore. Coordinare l'introduzione: questo file viene creato lì, qui ci si limita a fornire il componente da usare e a definire il contratto sui campi.
- `resources/js/components/operations/OperationNotice.vue` — aggiungere i due alert item (warning per `expiring_soon`, error per `expired`), governati dal campo `operation.prescription_expiring_status`. Visibili in tutti gli scope (`admin`, `workspace`, `supplier`).

## Permessi e ruoli

Il meccanismo è solo visualizzazione e non introduce nuovi gate. Si applica agli scope già esistenti:

| Ruolo | Index lavorazioni | Detail lavorazione |
|---|---|---|
| Superadmin / Admin / Agent M&H | Cella "Scade il" con icona warning/error | Alert sotto header (warning/error) |
| Customer (workspace) | Cella "Scade il" con icona warning/error | Alert sotto header (warning/error) |
| Fornitore (admin/membro, workspace supplier) | Cella "Data di scadenza" con icona warning/error | Alert sotto header (warning/error) |

Nessun ruolo è escluso. La visibilità della singola lavorazione resta quella già definita per ogni ruolo dalle policy/gate esistenti.

## Corner case e gestione errori

- **`expire_at` null** → nessun indicatore, nessun alert. La cella mostra "—" o vuoto come oggi.
- **Lavorazione in `draft`** → nessun indicatore, nessun alert, anche se `expire_at` è entro 30 giorni o passato.
- **Lavorazione in `production` o `completed`** → nessun indicatore, nessun alert; la cella mostra solo la data (la riga `completed` resta con line-through come già previsto oggi).
- **Lavorazione con `canceled_at` non null** → nessun indicatore, nessun alert.
- **Prescrizione scaduta da molto tempo (mesi/anni) su lavorazione ancora attiva** → alert error visibile come per qualsiasi prescrizione scaduta. Nessun limite massimo di "vetustà".
- **`expire_at` esattamente al giorno corrente (oggi)** → considerata "in scadenza" (rispetta `expire_at >= now()`), quindi warning. Diventa "scaduta" da `now()` in poi.
- **Lavorazione con più prescrizioni storiche** → si considera solo `latestPrescription` (relazione HasOne già esistente), coerente con il modo in cui la data è già mostrata.
- **Cambio stato Operation in tempo reale** mentre l'utente ha la pagina aperta (es. da `requested` a `production`) → l'evidenziazione si aggiorna al refresh successivo (no ricalcolo client-side, vista la natura derivata e i pattern Inertia in uso).
- **Coesistenza con altri banner di `OperationNotice`** → l'alert "scadenza" si aggiunge alla lista alert items esistenti senza sostituirli; ordine di visualizzazione: prima i banner contestuali allo stato (es. produzione confermata, lavorazione annullata, ecc.), poi l'alert scadenza. Per il fornitore, si applica la stessa logica nello stack di banner definiti in `specifiche-area_fornitore_lavorazioni.md`, mantenendo però sempre visibile l'alert scadenza in aggiunta agli altri (non mutua esclusività).

## UX e feedback

### Index — cella "Data di scadenza"
- Layout cella: `{data formattata}{spazio}{icona}`. Allineamento e formattazione data identici a quelli odierni (`toLocaleDateString('it-IT')`).
- Icona warning ⚠️ con colore warning del design system (giallo/ambra coerente con `OperationNotice` warning).
- Icona error 🔴/⚠️ con colore error del design system (rosso).
- Tooltip su hover/focus dell'icona (non sull'intera cella) per evitare conflitti con il click sulla riga.
- Testo tooltip: "Prescrizione in scadenza il: {data}" / "Prescrizione scaduta il: {data}".
- Mantenuto il `line-through` esistente per le lavorazioni `completed` (nelle quali, comunque, l'icona non appare perché lo stato non è attivo).

### Detail — alert sotto header
- Componente integrato in `OperationNotice` come nuovo alert item.
- Stile warning per "in scadenza", error per "scaduta", coerente con gli stili già adottati da `OperationNotice`.
- Testi:
  - "⚠️ Attenzione: prescrizione in scadenza il: {data}"
  - "⚠️ Attenzione: prescrizione scaduta il: {data}"
- Nessuna call-to-action dentro l'alert (è puramente informativo). Niente pulsanti.
- L'alert non è dismissibile dall'utente: scompare automaticamente quando la lavorazione cambia stato uscendo da quelli attivi, o quando viene annullata, o quando la prescrizione viene aggiornata con una nuova `expire_at`.

### Loading
- Nessun cambiamento al loading delle pagine Index/Show: il dato viaggia con il payload Inertia esistente, non c'è una chiamata aggiuntiva.

## Fasi di implementazione

La feature è piccola e omogenea: backend (accessor + payload) + frontend (1 componente nuovo + estensione `OperationNotice` + uso nelle Index esistenti). Una sola fase.

### Fase 1: Accessor, payload, componente indicator e alert in OperationNotice

- **Scope**:
  - Accessor `Prescription::isExpiringSoon()` e `Prescription::isExpired()`.
  - Accessor `Operation::prescription_expiring_status` (`expired` | `expiring_soon` | `none`) che combina lo stato attivo dell'Operation con lo stato della `latestPrescription`.
  - Esposizione del campo `prescription_expiring_status` (e `prescription_expire_at` se non già presente nel payload) nelle serializzazioni Inertia di:
    - `OperationController::index` (admin/agent),
    - controller workspace customer index,
    - controller workspace supplier index (verrà creato nella specifica `specifiche-area_fornitore_lavorazioni.md`; coordinare),
    - Show admin/customer/supplier.
  - Nuovo componente Vue `PrescriptionExpiryIndicator.vue` (icona warning/error + `BbTooltip`).
  - Integrazione nelle celle "Scade il" / "Data di scadenza" delle 4 Index (admin/agent, customer, supplier — quest'ultima coordinata con la sua specifica).
  - Estensione di `OperationNotice.vue` con due alert items (warning, error) basati su `operation.prescription_expiring_status`, attivi in tutti gli scope (`admin`, `workspace`, `supplier`).
- **File coinvolti**: vedi sezione "File coinvolti (alto livello)".
- **Dipende da**: nulla per i 3 ruoli admin/agent/customer. Per il fornitore: dipende dalla Fase 1 di `specifiche-area_fornitore_lavorazioni.md` (esistenza della pagina Index e Show del workspace supplier) — se questa specifica viene implementata prima, il componente e i campi saranno comunque pronti e l'integrazione lato fornitore avverrà naturalmente quando le sue pagine verranno create.
