---
name: clean-code
description: "Review and guide Laravel code following Bitboss clean code standards (SRP, fat models, PSR12, DRY, Eloquent best practices, naming conventions, etc.). Supports follow-up mode: pass a previous review doc as $ARGUMENTS to re-verify its findings against live code."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[path | Docs/review-*.md per follow-up]"
---

# Clean Code Review

## Scope

1. Se `$ARGUMENTS` matcha `Docs/review-*.md` → **modalità follow-up**: leggi il doc, estrai i findings, per ciascuno verifica lo stato con Grep/Read diretto sul codice attuale. Produci il blocco `## Verifica findings precedenti` (vedi Output).
2. Se `$ARGUMENTS` contiene un altro path, analizza **solo quel path** (ricorsivamente).
3. Altrimenti, esegui `git diff master --name-only --diff-filter=ACMR -- '*.php'` per trovare i file PHP modificati rispetto a `master`.
4. Se non ci sono file modificati, comunicalo e termina.

Per ogni file nello scope, leggi il contenuto completo e analizza secondo la checklist.

**Nota operativa**: per full-audit o follow-up, i subagent Explore campionano ma non enumerano. Per conteggi e stati di chiusura, usa Grep/Read diretto, non delegare.

## Checklist

### 1. Single Responsibility Principle (SRP)
- Ogni classe e metodo ha UNA sola responsabilità
- Se un metodo fa più di una cosa, estrarre logica in metodi privati/protetti descrittivi
- Metodi più lunghi di ~20 righe sono un code smell

### 2. Fat Models, Skinny Controllers
- La logica DB appartiene ai modelli Eloquent, scope, o classi Action/Service
- I controller orchestrano: chiamano un service/model, restituiscono una risposta
- Nessuna query builder chain dentro un controller

### 3. Business Logic in Service/Action Classes
- Logica complessa (file handling, chiamate esterne, calcoli) in classi `*Service` o `*Action`
- I controller delegano, non implementano logica direttamente

### 4. Validation in Request Classes
- Mai `$request->validate([...])` inline nel controller
- Sempre classi `*Request` che estendono `FormRequest`

### 5. DRY — Don't Repeat Yourself
- Condizioni di query duplicate → suggerire Eloquent scopes (`scopeActive`, ecc.)
- Frammenti Blade ripetuti → suggerire partial o componenti
- **Post-refactor check**: dopo un'estrazione di classe/metodo, fai `Grep` del nome degli helper privati spostati. Se appaiono in ≥2 file nuovi, estrai subito in trait/scope prima di considerare il refactor chiuso. Lo split-without-consolidation ricrea il pattern DRY che stava chiudendo.

### 6. Eloquent over Query Builder / Raw SQL
- Preferire relazioni Eloquent e scopes rispetto a query raw
- `Model::query()` invece di `DB::table()`
- Metodi `Collection` invece di `array_*`

### 7. N+1 Queries
- Nessuna query dentro loop `@foreach` in Blade
- Sempre eager loading: `User::with('profile')->get()`
- Mai `Model::all()` — sempre vincoli o paginazione

### 8. Query Performance
- `User::all()->count()` → `User::count()`
- `foreach(Model::where(...)->get() as $m) { $m->update(...) }` → `Model::where(...)->update([...])`

### 9. PSR-12 Code Style
- Metodi e variabili in camelCase
- Classi in PascalCase
- Indentazione e spaziatura corretta
- Nessun tag di chiusura `?>`

### 10. Laravel Naming Conventions
| Cosa | Convenzione | Esempio |
|------|-------------|---------|
| Controller | Singolare | `ArticleController` |
| Model | Singolare | `User` |
| Route | plurale, kebab-case | `articles/1` |
| Named route | snake_case + dot | `users.show_active` |
| Tabella | plurale, snake_case | `article_comments` |
| Foreign key | `{model}_id` | `article_id` |
| Metodo | camelCase | `getAll` |
| Variabile | camelCase | `$articlesWithAuthor` |
| Trait | aggettivo | `Notifiable` |

- **Pre-creazione**: prima di creare un file nuovo (Request, Action, Service, Enum), fai `Glob` di esempi dello stesso tipo nel progetto (es. `app/Http/Requests/**/*Request.php`) per allineare nome e firma. Non adottare l'idiom generale Laravel se il progetto ha una convention locale più stringente.

### 11. No Magic Numbers / Variabili oscure
- `$a`, `$u`, `$k` → nomi descrittivi
- Stringhe letterali nelle condizioni → costanti o config (`Article::TYPE_NORMAL`)
- Numeri magici → costanti con nome

### 12. Config, non `.env` diretto
- `env('API_KEY')` nel codice → `config('api.key')`
- I valori `.env` si leggono solo dentro `config/*.php`

### 13. Sintassi Laravel concisa
- `$request->session()->get('cart')` → `session('cart')`
- `->orderBy('created_at', 'desc')` → `->latest()`
- `->orderBy('created_at', 'asc')` → `->oldest()`
- `->first()->name` → `->value('name')`
- `Carbon::now()` → `now()`

### 14. IoC / Dependency Injection
- `new Service()` dentro un metodo → iniettare via costruttore o `app(Service::class)`

### 15. No Logic in Routes
- Nessuna closure con business logic nei file route
- Le route mappano solo a metodi dei controller

### 16. No JS/CSS in Blade, No HTML in PHP
- Nessun blocco `<script>` o `<style>` nei template Blade
- Nessun `echo '<div>...'` nelle classi PHP

### 17. Date
- Salvare date in formato standard
- Usare accessors/mutators Eloquent per la formattazione
- Mai formattare date inline con `Carbon::createFromFormat(...)`

### 18. API vs Ajax
- Route API (token-auth, client esterni) → `routes/api.php`
- Route Ajax (session-auth, same-origin) → `routes/web.php`

### 19. Convention-first, idiom-second
Quando crei nuovo codice (action, request, service, enum, helper), la priorità è **coerenza col pattern del progetto**; lo stile "idiomatico generale" è subordinato. Esempi di errore tipico:
- `AddCompanyMemberRequest` in un progetto che usa `StoreX`/`UpdateX` ovunque → preferire `StoreCompanyMemberRequest` o `AttachCompanyMemberRequest`.
- `DashboardStatsLoader::load(string $table)` in un progetto dove i nomi tabella stanno in enum → accettare un enum, non string magic.
- Label user-facing `'Creativita'` (ASCII del case PHP) quando il progetto usa testi italiani accentati → `'Creatività'`.

## Output

Produci un report strutturato:

```
## Review: Clean Code — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `master`
- Modalità: `diff` | `path-scan` | `follow-up di {doc precedente}`
- File analizzati: {N}
- {lista file}
```

### Verifica findings precedenti (solo modalità follow-up)

Tabella obbligatoria in modalità follow-up:

```
| ID | Stato | Verifica |
|---|---|---|
| W1 | chiuso | link [file:L](file#L) + cosa c'è ora |
| S3 | residuo consapevole | link al piano di remediation che esplicita lo skip |
| W4 | falso positivo | il finding v1 era errato — spiegazione |
```

Stati ammessi: `chiuso`, `chiuso-parziale`, `aperto`, `residuo consapevole`, `falso positivo`, `non-applicabile`. Default `aperto` se non c'è evidenza di chiusura.

### Findings attivi

Ogni finding include un tag di provenienza `[nuovo | regressione | residuo]`:

```
### Critical
- **[file.php:42](path/to/file.php#L42)** [nuovo]: Violazione + regola (#N) + snippet + fix

### Warning
- **[file.php:18](path/to/file.php#L18)** [regressione]: Violazione + regola (#N) + fix

### Suggestion
- **[file.php:5](path/to/file.php#L5)** [residuo]: Miglioramento + regola (#N)
```

### Summary
- Critical: N | Warning: N | Suggestion: N
- In follow-up: chiusi N / totali N v1 | nuovi N | regressioni N
- Verdict: Approvato / Da rivedere / Bloccante

### Criteri di classificazione

| Livello | Quando usarlo |
|---------|---------------|
| **Critical** | Viola SRP in modo grave, introduce N+1, business logic nel controller, SQL injection, `$guarded = []` |
| **Warning** | Codice duplicato (DRY), naming non convenzionale, metodo troppo lungo, query non ottimale, **nuovo codice che rompe naming/pattern del resto del progetto** (vedi #19) |
| **Suggestion** | Sintassi semplificabile, commento superfluo, piccolo miglioramento di leggibilità, **duplicazione emersa da refactor recente** (regressione DRY post-split) |

Se non ci sono problemi, scrivi "Nessun problema trovato" con il verdict "Approvato".
