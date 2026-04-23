---
name: explain-feature
description: "Genera un diagramma di flusso testuale completo di una funzionalità, mostrando tutti i flussi possibili con la catena azione utente → controller → service → AI → DB."
allowed-tools: Read, Grep, Glob, Write, Bash(php artisan route:list *)
argument-hint: "[nome-feature, path, o descrizione]"
---

# Diagramma di Flusso Funzionalità

## Obiettivo

Produci un diagramma di flusso testuale completo della funzionalità indicata, coprendo **tutti** i flussi possibili.

## Input

`$ARGUMENTS` — può essere un nome di funzionalità, un path a un controller/service, o una descrizione libera.

## Come procedere

1. **Identifica i file coinvolti**: controller, service, model, enum, migration, pagina Vue
2. **Mappa tutti i flussi** dell'utente: ogni bottone, ogni azione che attiva un endpoint
3. **Traccia la catena completa** per ogni flusso: azione utente → controller::metodo → service::metodo → AI (se coinvolta) → operazioni DB (create, update, upsert, soft-delete)
4. **Documenta le logiche di match/upsert**: chiavi di ricerca, fallback, normalizzazioni
5. **Includi i side-effect**: aggiornamenti automatici su entità correlate, cascade

## Formato output

Per ogni flusso, usa un blocco di codice con frecce (↓) per ogni step. Indica classi e metodi reali del codebase.

```
### N. Nome del flusso

Azione utente (bottone, dialog, ecc.)
        ↓
Controller::metodo()
  - Validazione: cosa viene validato
        ↓
Service::metodo()
        ↓
AI estrae → campi strutturati (elencarli)
         → blob Markdown (descrizione contenuto)
        ↓
Logica di match/upsert:
  1. Cerca per chiave primaria
  2. Fallback su chiave secondaria
  3. Se trovato → update
  4. Se non trovato → create con attributi X
        ↓
Operazioni DB collaterali (soft-delete precedenti, aggiornamento campi correlati)
```

## Output e salvataggio

Al termine, **salva il diagramma** in un file Markdown nella cartella `Docs/explanations/` del repo in cui stai lavorando (path relativo alla root del repo, ovvero alla working directory corrente). Se la cartella non esiste, creala.

Nome file: `explain-[nome-feature].md`, dove `[nome-feature]` è una versione kebab-case del nome della funzionalità (derivata da `$ARGUMENTS`: se è già un nome, usalo; se è un path, prendi il basename senza estensione; se è una descrizione libera, riassumila in 2-4 parole kebab-case).

Esempi:
- `$ARGUMENTS = "gestione valutazioni"` → `Docs/explanations/explain-gestione-valutazioni.md`
- `$ARGUMENTS = "app/Http/Controllers/ValutazioneController.php"` → `Docs/explanations/explain-valutazione-controller.md`

Non sovrascrivere file esistenti con lo stesso nome: in caso di collisione, chiedi all'utente come procedere.

Alla fine, comunica all'utente il path del file creato (relativo alla root del repo) e mostra anche il contenuto in chat.

## Regole

- Copri **tutti** i flussi, inclusi quelli manuali, batch, e di retrocompatibilità
- Mostra i nomi reali di classi, metodi, tabelle, enum dal codebase
- Per ogni flusso indica la **provenienza dei dati** (input utente, estrazione AI, default DB)
- Se ci sono normalizzazioni (uppercase, strip, parse), indicale
- Alla fine aggiungi una sezione "Gerarchia chiave di match" se la funzionalità usa logica di upsert
- Alla fine aggiungi una sezione con il riepilogo delle **entità dati** (tabelle, campi chiave, relazioni)
