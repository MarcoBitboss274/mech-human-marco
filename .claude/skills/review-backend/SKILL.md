---
name: review-backend
description: "Reviews Laravel backend code for architecture quality, Eloquent patterns, validation, and code smells. Invoke periodically on new features or before merging. Supports follow-up mode: pass a previous review doc as $ARGUMENTS to re-verify its findings against live code."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[path | Docs/review-*.md per follow-up]"
---

# Backend Code Review

## Scope

1. Se `$ARGUMENTS` matcha `Docs/review-*.md` → **modalità follow-up**: leggi il doc, estrai i findings (W/S numerati), **per ciascuno verifica lo stato con Grep/Read diretto sul codice attuale**. Produci il blocco `## Verifica findings precedenti` (vedi Output). In aggiunta, scansiona per nuovi findings e regressioni.
2. Se `$ARGUMENTS` contiene un altro path, analizza **solo quel path** (ricorsivamente) — modalità full-audit o parziale.
3. Altrimenti, esegui `git diff main --name-only --diff-filter=ACMR -- '*.php'` per trovare i file PHP modificati rispetto a `main` — modalità diff.
4. Se non ci sono file modificati in modalità diff, comunicalo e termina.

Per ogni file nello scope, leggi il contenuto completo e analizza secondo la checklist.

## Modalità di esecuzione

- **Full-audit (path con >50 file)**: prima di delegare a subagent, **enumera l'area con Glob** (`app/Http/Controllers/**/*.php` ecc.). I subagent Explore campionano, non enumerano: usarli per smell discovery, non per conteggi.
- **Follow-up**: per ogni finding del doc precedente, **Grep o Read diretto sul file citato**. Non delegare a Explore la verifica "chiuso/aperto" — gli agent tendono a copiare lo stato dal doc invece di guardare il codice live.
- **Conteggi e liste esaustive**: sempre Glob + Grep, mai campione da Explore.

## Checklist

### Architettura & Design
- I controller sono snelli (delegano a Service/Action per logica complessa)
- Single Responsibility Principle rispettato
- Dependency Injection usata correttamente (constructor injection)
- Nessuna dipendenza circolare
- Pattern Laravel rispettati: Form Request per validazione, Resource per API, Policy per authorization
- **Regressioni post-refactor**: se un finding precedente ha prodotto estrazione di classe/metodo, verifica con Grep che gli helper privati spostati non siano stati copiati nei nuovi file. Stesso pattern DRY che il refactor chiudeva può riemergere sul nuovo confine.

### Eloquent & Database
- N+1 query prevenute con eager loading (`with()`, `load()`)
- Uso di `Model::query()` invece di `DB::` dove possibile
- Mass assignment protetto (`$fillable` definito, no `$guarded = []`)
- Relazioni con return type hints
- Query complesse nel query builder, non raw SQL
- Migrazioni backward-compatible (no drop column senza step intermedio)
- Indici sulle colonne usate in WHERE/JOIN
- Foreign keys e cascade definiti

### Validazione
- Form Request per ogni endpoint che riceve input (no validazione inline nel controller)
- Regole coerenti col tipo di dato (email, exists, unique, in)
- Messaggi di errore custom dove utile
- Coerenza nello stile regole (array vs string) — segui la convenzione del progetto

### Pattern Laravel
- `config()` invece di `env()` fuori dai file config
- Named routes usate per URL generation
- `ShouldQueue` per operazioni lunghe (email, notifiche, job)
- Event/Listener per side effects disaccoppiati
- Middleware applicati correttamente sulle route

### Code Quality
- Metodi non più lunghi di ~30 righe
- Nessuna duplicazione di logica
- Naming descrittivo (`isRegisteredForDiscounts`, non `discount()`)
- Return type declarations esplicite su tutti i metodi
- Type hints sui parametri
- PHPDoc con array shapes dove appropriato
- Nessun commento superfluo (solo per logica complessa)
- Constructor property promotion (PHP 8+)

## Output

Produci un report strutturato:

```
## Review: Backend — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `main`
- Modalità: `diff` | `full-audit` | `follow-up di {doc precedente}`
- File analizzati: {N}
- {lista file}
```

### Verifica findings precedenti (solo modalità follow-up)

Tabella obbligatoria in modalità follow-up:

```
| ID | Stato | Verifica |
|---|---|---|
| W1 | chiuso | link [file:L](file#L) + descrizione 1 riga di cosa c'è ora |
| W2 | aperto | finding ancora presente, il codice non è cambiato |
| S3 | residuo consapevole | il piano di remediation esplicitava lo skip; link al piano |
| W4 | falso positivo | il finding v1 era errato — spiegazione |
```

Stati ammessi: `chiuso`, `chiuso-parziale`, `aperto`, `residuo consapevole`, `falso positivo`, `non-applicabile`. Default `aperto` se non c'è evidenza di chiusura.

### Findings attivi

Ogni finding include un tag di provenienza `[nuovo | regressione | residuo]` accanto al titolo:

```
### Critical
- **[file.php:42](path/to/file.php#L42)** [nuovo]: Descrizione + snippet + fix

### Warning
- **[file.php:18](path/to/file.php#L18)** [regressione]: Problema riemerso post-refactor

### Suggestion
- **[file.php:5](path/to/file.php#L5)** [residuo]: Già noto da review precedente, non affrontato
```

### Summary
- Critical: N | Warning: N | Suggestion: N
- In follow-up: chiusi N / totali N v1 | nuovi N | regressioni N
- Verdict: Approvato / Da rivedere / Bloccante

Se non ci sono problemi, scrivi "Nessun problema trovato" con il verdict "Approvato".
