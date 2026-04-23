# Tab Panoramica — Lavorazione (Operation)

## Contesto

La pagina di dettaglio di una Lavorazione (`Operation`) ha già una struttura a tab ma il primo tab (`overview`) è attualmente un placeholder vuoto ([operations/Show.vue:42-44](resources/js/pages/operations/Show.vue#L42), [workspace/operations/Show.vue](resources/js/pages/workspace/operations/Show.vue)).

Serve realizzarne il contenuto per offrire all'admin e al customer (dentista) **una vista sintetica e complessiva** dello stato della lavorazione e delle sue entità correlate, senza dover navigare fra i tab. La panoramica deve consentire al customer di svolgere le azioni più frequenti (accettare/rifiutare un preventivo, visualizzare una fattura) senza cambiare tab.

Bozza di partenza: [Docs/brief/tab_panoramica.md](Docs/brief/tab_panoramica.md).

## Flusso utente

### Flusso principale (admin o customer)

1. L'utente apre la pagina della Lavorazione.
2. Il tab "Panoramica" è **il primo tab** e **selezionato di default** all'apertura della pagina.
3. La panoramica è divisa in due sezioni verticali in ordine fisso:
   - **Attori coinvolti** (in alto)
   - **Riepilogo lavorazione** (sotto)
4. L'utente legge lo stato delle entità senza cambiare tab. Per qualsiasi dettaglio oltre a quello mostrato, cambia tab manualmente.

### Variante accetta/rifiuta preventivo (admin, agent e customer)

1. Nel "Riepilogo lavorazione" l'utente vede una card **Preventivo** con status `SENT` e bottoni `Accetta` / `Rifiuta`.
2. Click `Accetta` → POST alla rotta appropriata → toast "Preventivo accettato" → reload dati operation → la card aggiorna stato (`ACCEPTED`) e bottoni scompaiono.
3. Click `Rifiuta` → apre dialog con textarea "Nota rifiuto" obbligatoria → submit → POST alla rotta appropriata → toast "Preventivo rifiutato" → reload dati operation.
4. Le rotte utilizzate cambiano in base al contesto:
   - **Admin/agent** (pagina `operations/Show.vue`): `quotes.accept` e `quotes.reject` ([routes/web.php:129-130](routes/web.php#L129)).
   - **Customer** (pagina `workspace/operations/Show.vue`): `workspace.quotes.accept` e `workspace.quotes.reject` ([routes/web.php:203-204](routes/web.php#L203)).

### Variante customer — visualizza fattura

1. Il customer vede una card **Fattura** con status `SENT` o `PAID`.
2. Click sul file allegato → apre il PDF in nuova scheda (link a `media.index`).

## Regole di business

### Sezione "Attori coinvolti"

- Mostra solo **anagrafica statica** (nome, ruolo, eventuali contatti). Nessun timestamp, nessun badge di stato sull'attore.
- Attori visualizzati:
  - **Richiedente** — `operation.latest_prescription.user` (il dentista che ha creato la prescrizione): nome, cognome, email.
  - **Struttura richiedente** — `operation.building`: nome struttura, eventualmente città.
  - **Agente della struttura** — `operation.building.agent`: nome, cognome. *Visibile solo se esiste.*
  - **Fornitore/i** — `operation.suppliers` (tutti o solo `selected_supplier`, vedi sotto). *Non visibile al customer.*

### Sezione "Riepilogo lavorazione"

- Le card **non sono cliccabili** (tranne le azioni inline su Preventivo e Fattura, vedi sotto).
- Ogni card mostra lo stato dell'entità con il badge esistente ([OperationQuoteStatusBadge.vue](resources/js/components/operations/OperationQuoteStatusBadge.vue), ecc.) e dati sintetici.
- Le entità in panoramica sono: **Prescrizione**, **Fornitore/i** (admin only), **Preventivo**, **Produzione**, **Fattura**.
- **`Order` è fuori scope della panoramica.**
- `OperationProgress` **non viene duplicato** nella panoramica — è già visibile sopra i tab ([Show.vue:37](resources/js/pages/operations/Show.vue#L37)).

### Regole "card attiva" quando ci sono più record

Per ogni entità che può avere record multipli, si mostrano solo le istanze "attive", più un contatore testuale sulle altre.

- **Prescrizione**: mostra `operation.latest_prescription` (campo già esistente). Una sola card. Sempre visibile se la lavorazione ha almeno una prescrizione.
- **Fornitore** (admin only):
  - Se esiste `selected_supplier` → card con anagrafica del fornitore selezionato.
  - Se non esiste selected ma esistono fornitori contattati → placeholder "Fornitore in selezione" + contatore "N contattati su M".
  - Se nessun fornitore è stato ancora coinvolto → card non presente.
- **Preventivo**:
  - **Card "attiva"** = ogni preventivo con `status = SENT` (possono essere multipli, uno per fornitore). Una card per ognuno.
  - Se non ci sono `SENT` ma c'è almeno un `ACCEPTED` → card informativa con il preventivo accettato.
  - `DRAFT`, `REJECTED`, `CANCELED` finiscono nel contatore "+N altri preventivi" in fondo alla sezione, non generano card.
- **Produzione**:
  - Mostra l'unica produzione non cancellata (`CONFIRMED`). Se esiste una produzione `CANCELED` e non esistono altre, mostra quella con stato `CANCELED`.
  - Card sempre singola.
- **Fattura**:
  - **Card "attiva"** = ogni fattura con `status = SENT` (possono essere multipli: acconto + saldo). Una card per ognuno.
  - Se non ci sono `SENT` ma esistono `PAID` → card informativa con l'ultima `PAID`.
  - `DRAFT` non compaiono e finiscono nel contatore "+N altre fatture" (solo admin; il customer non vede mai DRAFT perché non gli sono state inviate).

### Stato "vuoto" (lavorazione appena creata)

Quando la `Operation` è in stato `DRAFT` e nessuna entità correlata è stata ancora creata (nessuna prescrizione inviata, niente preventivi, ecc.):

- La sezione **Attori coinvolti** rimane sempre visibile (almeno richiedente, struttura e agente esistono sempre).
- La sezione **Riepilogo lavorazione** viene sostituita da un messaggio globale: "Lavorazione in fase di bozza. Nessun elemento da riepilogare.".

Quando compare almeno un'entità (prescrizione inviata, preventivo creato, ecc.), il messaggio scompare e si mostrano solo le card popolate; le card delle entità non ancora presenti restano nascoste (non placeholder "vuoti").

### Azioni inline

- **Accetta / Rifiuta preventivo**:
  - Visibili a **admin, agent e customer** (stesso comportamento su entrambe le pagine `operations/Show.vue` e `workspace/operations/Show.vue`).
  - Visibili solo per card con `status = SENT`.
  - Le rotte utilizzate dipendono dal contesto:
    - Admin/agent → `quotes.accept` e `quotes.reject` ([routes/web.php:129-130](routes/web.php#L129)).
    - Customer → `workspace.quotes.accept` e `workspace.quotes.reject` ([routes/web.php:203-204](routes/web.php#L203)).
  - Il "Rifiuta" apre un dialog con textarea "Nota rifiuto" obbligatoria (stesso comportamento di [workspace/QuotesTab.vue:149-161](resources/js/pages/workspace/operations/partials/QuotesTab.vue#L149)).
- **Visualizza fattura**:
  - Link al file allegato via `media.index` (stesso pattern di [workspace/InvoicesTab.vue:44-49](resources/js/pages/workspace/operations/partials/InvoicesTab.vue#L44)).
  - Se la fattura non ha allegato → nessun link, solo dato grigio "--".

## Dati e relazioni

Tutto ciò che serve è **già caricato** da `OperationService::getAdminShowData()` ([app/Services/OperationService.php:545](app/Services/OperationService.php#L545)) e `OperationService::getAgentShowData()` ([app/Services/OperationService.php:580](app/Services/OperationService.php#L580)) per la pagina admin/agent, e da `WorkspaceService::getWorkspaceOperationShowData()` ([app/Http/Controllers/WorkspaceController.php:333](app/Http/Controllers/WorkspaceController.php#L333)) per il customer. I dati arrivano già come props `operation` comprensivi di:

- `operation.building` (con `agent`)
- `operation.latest_prescription` (con `user`)
- `operation.quotes[]`
- `operation.productions[]`
- `operation.invoices[]` (con `media`)
- `operation.suppliers[]` + `operation.selected_supplier`

**Non servono nuove colonne DB, nuove rotte o nuovi endpoint.**

Eventuali contatori ("altri N preventivi") si calcolano in frontend filtrando gli array già disponibili nelle props (es. `operation.quotes.filter(q => ['draft','rejected','canceled'].includes(q.status)).length`).

**Verifica da fare in implementazione**: controllare che `WorkspaceService::getWorkspaceOperationShowData()` esponga effettivamente `building.agent` e tutte le relazioni sopra elencate; se manca qualcosa, integrarlo nel service (non nuova rotta).

## Permessi e ruoli

- **Panoramica sempre visibile** a chi può accedere alla pagina della Lavorazione. Nessun nuovo permesso da aggiungere.
- La visibilità della pagina Lavorazione resta governata dai middleware/policy esistenti (`OperationPolicy::view()`, middleware `role:superadmin|admin|agent` per admin area, `role:customer` + `workspace` middleware per workspace).
- Differenze di contenuto tra admin e customer:
  - **Customer non vede** la card Fornitore/Fornitori né il contatore fornitori.
  - **Admin, agent e customer** vedono i bottoni `Accetta` / `Rifiuta` sulle card Preventivo con status `SENT`. Le rotte sottostanti cambiano in base al contesto (admin usa `quotes.*`, customer usa `workspace.quotes.*`). L'autorizzazione effettiva dell'azione è demandata alle policy esistenti lato server.

## Corner case e gestione errori

- **Se la lavorazione è `canceled` o `archived`** → la panoramica mantiene struttura e dati, ma tutte le azioni inline (Accetta/Rifiuta preventivo) sono disabilitate. Il link download fattura resta attivo. Il badge di stato in testa alla pagina ([Show.vue:7-9](resources/js/pages/operations/Show.vue#L7)) è già sufficiente per comunicare lo stato all'utente.
- **Se `latest_prescription` è `null`** → la sezione Attori mostra comunque Struttura + Agente (se esistono) ma non il Richiedente; la card Prescrizione è nascosta.
- **Se `building.agent` è `null`** → attore "Agente" non viene mostrato.
- **Se una card Preventivo SENT genera errore su accetta/rifiuta** → toast di errore ("Si è verificato un errore"), nessun reload, bottone riattivato (stesso pattern di [workspace/QuotesTab.vue:54-60](resources/js/pages/workspace/operations/partials/QuotesTab.vue#L54)).
- **Se esistono più preventivi SENT contemporaneamente** (es. più fornitori hanno inviato preventivi) → si mostrano tutte le card, una per ciascuno; il customer sceglie quale accettare. Dopo accettazione di uno, i restanti tipicamente diventano `CANCELED` (regola business esistente, fuori scope della panoramica).
- **Se esistono più fatture SENT contemporaneamente** (acconto + saldo) → si mostrano tutte le card, una per ciascuna.
- **Fattura senza allegato PDF** → card mostra comunque i dati (codice, importo, descrizione) ma link file = `--`.
- **Se i bottoni Accetta/Rifiuta sono cliccati durante un'operazione in corso** → bottone `Accetta` già disabled via `acceptingQuoteId` ref (stesso pattern esistente); per `Rifiuta` il dialog si chiude solo in `onSuccess` del submit.

## UX e feedback

- **Feedback azione preventivo**: toast di successo/errore usando `useMainToast()` (pattern esistente).
- **Dialog rifiuto preventivo**: usa `BbDialog` con `BbTextarea` required; bottone `Rifiuta` disabled finché la nota è vuota (`rejectForm.notes.trim() === ''`).
- **Reload dati dopo azione**: `router.reload({ only: ['operation'] })` per aggiornare le card senza full page reload (pattern già in [operations/Show.vue:182-186](resources/js/pages/operations/Show.vue#L182)).
- **Badge di stato**: riutilizzare i componenti badge esistenti ([OperationQuoteStatusBadge.vue](resources/js/components/operations/OperationQuoteStatusBadge.vue), [OperationInvoiceStatusBadge.vue](resources/js/components/operations/OperationInvoiceStatusBadge.vue), [OperationOrderStatusBadge.vue](resources/js/components/operations/OperationOrderStatusBadge.vue), [OperationSupplierStatusBadge.vue](resources/js/components/operations/OperationSupplierStatusBadge.vue)).
- **Traduzione label tab**: la label del tab è attualmente `Overview` ([Show.vue:192](resources/js/pages/operations/Show.vue#L192)). Cambiarla in `Panoramica` (coerente con le altre label italiane: Prescrizione, Fornitore, Preventivo, Produzione, Fattura). Aggiornare anche la versione workspace se presente.
- **Tab default all'apertura**: già `overview` ([Show.vue:188](resources/js/pages/operations/Show.vue#L188)). Nessuna modifica.
- **Stato loading azioni**: bottone `Accetta` mostra disabled durante la POST (pattern esistente `acceptingQuoteId`).

## Fasi di implementazione

Una singola fase: la feature è circoscritta a un tab, non tocca controller/DB, e le regole di stato sono tutte deducibili dai dati già caricati.

### Fase 1: Implementazione Tab Panoramica

- **Scope**: costruire il contenuto del tab `overview` per admin/agent e customer, sostituendo i placeholder attuali. Niente refactor dei tab esistenti.
- **File coinvolti**:
  - **Nuovi (admin/agent)**:
    - `resources/js/pages/operations/partials/OverviewTab.vue`
  - **Nuovi (customer/workspace)**:
    - `resources/js/pages/workspace/operations/partials/OverviewTab.vue`
  - **Nuovi componenti condivisi** (usati solo dai due OverviewTab, i tab esistenti restano invariati):
    - `resources/js/components/operations/overview/ActorCard.vue` — card anagrafica attore (nome, ruolo, contatti).
    - `resources/js/components/operations/overview/PrescriptionSummaryCard.vue` — card riepilogo prescrizione.
    - `resources/js/components/operations/overview/SupplierSummaryCard.vue` — card riepilogo fornitore (admin only).
    - `resources/js/components/operations/overview/QuoteSummaryCard.vue` — card preventivo con azioni inline (Accetta/Rifiuta) visibili a tutti i ruoli per card con status `SENT`. Il componente accetta una prop `routeContext: 'admin' | 'workspace'` (o equivalente) per decidere quali route usare.
    - `resources/js/components/operations/overview/ProductionSummaryCard.vue` — card riepilogo produzione.
    - `resources/js/components/operations/overview/InvoiceSummaryCard.vue` — card fattura con link download.
  - **Modificati**:
    - [resources/js/pages/operations/Show.vue](resources/js/pages/operations/Show.vue) — sostituire il `<div class="operations-show__placeholder" />` al tag `#overview` con `<OverviewTab :operation="props.operation" @quote:updated="reloadOperation" />`. Cambiare label tab da `Overview` a `Panoramica`.
    - [resources/js/pages/workspace/operations/Show.vue](resources/js/pages/workspace/operations/Show.vue) — analoga modifica con versione workspace dell'OverviewTab. Cambiare label se presente.
  - **Potenzialmente da verificare/aggiornare**:
    - `app/Services/WorkspaceService.php` — verificare che `getWorkspaceOperationShowData()` carichi tutte le relazioni necessarie (`building.agent`, `latest_prescription.user`, `quotes`, `invoices.media`, `productions`). Se mancano, integrarle.
- **Dipende da**: nessuna fase precedente.
- **Criterio di done**: la panoramica mostra tutte le card richieste con i dati corretti; customer può accettare/rifiutare preventivi SENT e scaricare fatture; admin vede anche la sezione Fornitori; lavorazione canceled/archived ha azioni disabilitate; stato DRAFT mostra il messaggio globale.
