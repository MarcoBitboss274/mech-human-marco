---
name: pianifica-da-specifiche
description: "Trasforma un documento di specifiche in un piano di implementazione snello, focalizzato su diff vs codebase, sequenza e rischi — senza duplicare le specifiche."
allowed-tools: Read, Grep, Glob, Agent, Write
argument-hint: "[path al file di specifiche]"
---

# Pianifica da Specifiche

## Obiettivo

Partendo dal file di specifiche indicato in `$ARGUMENTS`, produci un piano di implementazione che **aggiunge valore rispetto alle specifiche** invece di ripeterle. Le specifiche sono la fonte di verità per "cosa fare"; il piano risponde a "come farlo nel codice di questo progetto, in che ordine, con quali rischi".

## Input

`$ARGUMENTS` — path al documento di specifiche (tipicamente generato da `/crea-specifiche`). Se il path non è fornito, chiedi quale file di specifiche leggere e fermati.

## Principio cardine

**Il piano NON ripete il contenuto delle specifiche.** Se un'informazione è già nelle specifiche (shape payload, regole di business, flussi utente, lista completa dei file, testo copy UX, schema tabelle), il piano la **linka** con `[riferimento](path/al/doc.md)` — non la copia.

Se ti trovi a incollare un blocco che è già nelle specifiche, fermati e sostituiscilo con un link.

## Processo

### Fase A: Lettura specifiche

1. Leggi il documento di specifiche indicato.
2. Estrai mentalmente: entità/tabelle coinvolte, file dichiarati come nuovi/modificati, vincoli tecnici espliciti, fasi già suddivise.
3. Se le specifiche sono ambigue o incomplete, **non riempire i buchi da solo** — segnalali nel piano come "Domande aperte" da risolvere prima dell'implementazione.

### Fase B: Verifica codebase

Usa Agent (Explore) o Grep/Glob per verificare cosa **esiste già** nel codebase rispetto a quanto pianificato dalle specifiche:

- File/classi citati come "da modificare": esistono davvero a quel path? Hanno la forma assunta dalle specifiche?
- Pattern riutilizzabili: esistono già trait, componenti, helper, factory state che le specifiche propongono di creare da zero?
- Convenzioni: come sono strutturati oggi i file analoghi (controller, form request, pagine Vue, test)?
- Dipendenze implicite: composable, seeder, fixture, command che useranno la feature e che le specifiche potrebbero non citare.

**Questa verifica è il valore principale del piano.** Se le specifiche dicono "crea componente X" ma esiste già `ComponentX.vue` con la stessa API, il piano lo dichiara esplicitamente.

### Fase C: Scrittura piano

Crea un nuovo file nella cartella `Docs/plans/` del repo in cui stai lavorando (path relativo alla root del repo, ovvero alla working directory corrente). Se la cartella non esiste, creala.

Il nome del file deriva dal nome del file di specifiche rimuovendo il prefisso `specifiche-` e aggiungendo `piano-` (es. `Docs/specs/specifiche-refactor-valutazioni.md` → `Docs/plans/piano-refactor-valutazioni.md`). Non sovrascrivere mai le specifiche né un file di piano esistente con lo stesso nome: in caso di collisione, chiedi all'utente come procedere.

**Perché in `Docs/plans/`**: il piano è un artefatto del progetto, vive accanto alle specifiche (`Docs/specs/`) ed è versionato con il repo. Chi clona il repo trova specifiche e piano nello stesso posto.

Alla fine della scrittura, comunica all'utente il path del piano creato (relativo alla root del repo), così può passarlo esplicitamente nelle sessioni successive.

Usa **esattamente** questa struttura, con sezioni fisse e brevi. Se suna sezione non ha contenuto, scrivi "—" e vai avanti.

```markdown
# Piano: [Nome feature]

**Specifiche**: [link al doc di specifiche]

## Stato del codebase

Cosa esiste già e va riutilizzato (path + file + una riga di nota).
Cosa non esiste e va creato da zero.
Pattern/convenzioni da seguire già in uso nel progetto.

Niente flussi utente, niente regole di business — quelli sono nelle specifiche.

## Diff rispetto alle specifiche

Elenco puntato di divergenze tra quanto assumono le specifiche e lo stato reale del codebase. Per ogni voce: "Specifiche dicono X — realtà: Y — implicazione per il piano: Z".

Se non ci sono divergenze: "Nessuna divergenza rilevata."

## Step ordinati

Sequenza numerata di step di implementazione. Raggruppali in 3-6 gruppi logici (es. "Schema + Model", "Validation", "Controller", "Frontend", "Test"). Ogni step:

- Azione concreta (verbo + oggetto).
- Gate di verifica (comando da eseguire, test da far passare, check visivo).

Niente lista completa dei file per ogni step — la lista è nelle specifiche. Qui conta solo l'**ordine** e il **criterio di done**.

## Rischi e punti di attenzione

Cose che possono rompersi o essere dimenticate, non deducibili dalle specifiche:

- Migrazioni destructive, side-effect su seeder/fixture, dipendenze su composable/trait non citati.
- N+1, eager loading, indici mancanti.
- File lunghi o duplicati che conviene refactorare prima.
- Test esistenti che vanno riscritti (non solo aggiunti).
- Ordine di deploy / rollback se rilevante.

## Domande aperte

Solo se le specifiche lasciano ambiguità che devono essere risolte prima di implementare. Ogni domanda ha: cosa non è chiaro + perché blocca + opzioni concrete se ne vedi.

Se le specifiche sono complete: "Nessuna."

## Ordine di commit suggerito

Come spezzare l'implementazione in commit atomici, verdi uno per uno. 3-8 commit al massimo.
```

## Cosa NON includere nel piano

Regola della scrematura: se una di queste cose ti viene spontanea, cancellala e metti un link alle specifiche.

- Riassunto del contesto o del rationale della feature.
- Elenco completo dei file nuovi/modificati/rimossi (le specifiche lo hanno già).
- Shape del payload JSON o dello schema DB ripetuta verbatim.
- Flussi utente o regole di business.
- Copy UX, messaggi toast, testi inline.
- Spiegazioni generali su pattern Laravel/Vue ben noti.

## Regole di scrittura

- Linguaggio dell'utente (italiano se le specifiche sono in italiano).
- Piano stretto: target ~150 righe max per feature di media complessità. Se sfori le 250, probabilmente stai duplicando le specifiche.
- Ogni step del piano deve avere un gate di verifica misurabile (comando, test, check UI) — no "verifica generale" o "testa il flusso".
- Nessun blocco di codice lungo. Snippet ammessi solo per mostrare una convenzione locale non deducibile. Per tutto il resto: link al file reale con riga.
- Usa link markdown `[path](path#Lnnn)` per tutti i riferimenti a codice esistente.

## Verifica finale

Prima di salvare il file, rileggi e chiediti:

1. Se cancello tutto ciò che è già nelle specifiche, quanto resta? Deve restare la maggior parte del piano.
2. Uno sviluppatore che legge *solo* il piano senza le specifiche riesce a lavorare? No — e va bene così: deve leggere entrambi.
3. Ogni step ha un comando/test che dice "ok, fatto"? Se no, riscrivi lo step.

Se anche uno di questi controlli fallisce, rivedi il piano prima di consegnarlo.
