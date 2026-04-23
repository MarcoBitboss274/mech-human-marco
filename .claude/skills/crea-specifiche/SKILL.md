---
name: crea-specifiche
description: "Trasforma una bozza di feature in un documento di specifiche completo, facendo domande interattive per chiarire ogni aspetto, corner case e regola di business."
allowed-tools: Read, Grep, Glob, Agent, AskUserQuestion, Edit, Write
argument-hint: "[path al file bozza della feature]"
---

# Crea Specifiche

## Obiettivo

Partendo dal file bozza indicato in `$ARGUMENTS`, guida l'utente attraverso un processo interattivo di raffinamento per produrre un documento di specifiche completo, pronto per l'implementazione.

## Input

`$ARGUMENTS` — path al file che contiene la bozza/descrizione della feature da specificare.

## Processo

### Fase A: Comprensione

1. **Leggi il file bozza** indicato in `$ARGUMENTS`
2. **Esplora il codebase** per capire il contesto tecnico:
   - Usa Agent (Explore) per identificare modelli, controller, componenti, tabelle e pattern esistenti collegati alla feature
   - Identifica cosa esiste già e cosa va creato da zero
3. **Analizza la bozza** per individuare:
   - Aspetti non specificati o ambigui
   - Corner case non coperti
   - Regole di business implicite ma non esplicitate
   - Scelte UX da validare
   - Aspetti di permessi/ruoli non chiariti
   - Gestione errori mancante

### Fase B: Domande iterative

Fai domande all'utente usando `AskUserQuestion` **solo per aspetti che non puoi dedurre dal codebase o dalla bozza**. Regole:

- **Non chiedere ciò che puoi dedurre**: se il codebase mostra già un pattern (es. permessi, struttura dati, componenti UI), segui quel pattern senza chiedere. Chiedi solo quando ci sono ambiguità reali o scelte di prodotto non deducibili dal codice.
- **Max 4 domande per turno** (limite di AskUserQuestion)
- **Raggruppa per area**: prima UX/flusso, poi logica di business, poi edge case
- **Sii specifico**: non chiedere "come vuoi gestire gli errori?" ma "se l'utente prova a [azione] quando [condizione], cosa deve succedere?"
- **Proponi opzioni concrete** con pro/contro quando possibile
- **Itera**: dopo ogni risposta, valuta se servono altre domande. Fermati appena tutto è chiaro — non fare domande per il gusto di farle

Aree da coprire (in ordine):
1. **Flusso utente**: ogni step dell'interazione, da dove parte, dove arriva
2. **Dati**: quali campi, quali relazioni, cosa viene creato/modificato/cancellato
3. **Regole di business**: validazioni, vincoli, calcoli, automazioni
4. **Permessi**: chi può fare cosa, con quali ruoli
5. **UX**: feedback all'utente (toast, redirect, errori inline), stati di loading
6. **Corner case**: cosa succede in scenari limite (dati mancanti, conflitti, concorrenza)
7. **Gestione errori**: come si gestiscono i fallimenti

### Fase C: Generazione documento

Quando tutti gli aspetti sono chiariti, **crea un nuovo file** nella cartella `Docs/specs/` del repo in cui stai lavorando (relativa alla root del repo, ovvero alla working directory corrente). Se la cartella non esiste, creala.

Il nome del nuovo file deve seguire il formato `specifiche-[nome-file-originale].md` (es. se il file di partenza è `bozza-feature-x.md`, il nuovo file sarà `Docs/specs/specifiche-bozza-feature-x.md`; se il file di partenza è `idea.txt`, il nuovo file sarà `Docs/specs/specifiche-idea.md`). **Non sovrascrivere mai il file di partenza** né un file di specifiche esistente con lo stesso nome: in caso di collisione, chiedi all'utente come procedere.

Alla fine della scrittura, comunica all'utente il path del file creato (relativo alla root del repo).

Scrivi il documento di specifiche completo nel nuovo file, usando questa struttura:

```markdown
# [Nome Feature]

## Contesto

[Perché serve questa feature. Quale problema risolve. Cosa ha motivato la richiesta.]

## Flusso utente

[Sequenza numerata di step dell'interazione utente, dall'inizio alla fine. Includi varianti e path alternativi.]

## Regole di business

[Lista puntata di tutte le regole emerse durante le domande. Ogni regola deve essere verificabile e non ambigua.]

## Dati e relazioni

[Tabelle coinvolte, campi nuovi, relazioni. Indicare cosa esiste già e cosa va creato.]

## Permessi e ruoli

[Chi può fare cosa. Quali ruoli hanno accesso. Eventuali restrizioni di visibilità.]

## Corner case e gestione errori

[Ogni edge case identificato con la soluzione scelta. Formato: "Se [condizione] → [comportamento]".]

## UX e feedback

[Messaggi di conferma, errore, loading. Redirect dopo le azioni. Stato della UI durante operazioni async.]

## Fasi di implementazione

[Dividere in fasi SOLO se la feature è troppo grande per una singola sessione di pianificazione. Se può stare in una fase unica, usare una sola fase. Non frammentare inutilmente.]

### Fase 1: [Titolo breve]
- **Scope**: [cosa include questa fase]
- **File coinvolti**: [lista dei file da creare/modificare]
- **Dipende da**: [fasi precedenti, se applicabile]

### Fase N: [Solo se necessario]
...
```

## Regole

- Non inventare requisiti: tutto deve emergere dalla bozza o dalle risposte dell'utente
- Non fare assunzioni su scelte di prodotto — se qualcosa non è chiaro, chiedi. Ma deduci dal codebase tutto ciò che è tecnico (pattern, strutture, convenzioni)
- Dividi in fasi solo se la feature non può essere pianificata e implementata in una singola sessione. La maggior parte delle feature dovrebbe avere una sola fase
- Se servono più fasi, ogni fase deve essere testabile indipendentemente
- Il documento finale deve essere autosufficiente: chi lo legge deve poter implementare senza ulteriori chiarimenti
- Usa il linguaggio dell'utente (italiano se la bozza è in italiano)