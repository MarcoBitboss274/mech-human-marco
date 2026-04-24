---
name: review-security
description: "Security audit of code changes. Checks OWASP top 10, authentication, authorization, SQL injection, XSS, secrets, and rate limiting."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[path-opzionale]"
---

# Security Code Review

## Scope

1. Se `$ARGUMENTS` contiene un path, analizza **solo quel path** (ricorsivamente)
2. Altrimenti, esegui `git diff main --name-only --diff-filter=ACMR` per trovare i file modificati rispetto a `main`
3. Filtra per file rilevanti: `*.php`, `*.vue`, `*.ts`, `*.js`, `*.env*`, `config/*.php`, `routes/*.php`
4. Se non ci sono file modificati, comunicalo e termina

Per ogni file nello scope, leggi il contenuto completo e analizza secondo la checklist.

## Checklist

### Injection (OWASP A03)
- **SQL injection**: cerca `DB::raw()`, `DB::select()` con input non sanitizzato, `whereRaw()` con concatenazione
- **Command injection**: cerca `exec()`, `shell_exec()`, `system()`, `proc_open()`, `passthru()`
- **LDAP/NoSQL injection**: se applicabile

### Broken Authentication (OWASP A07)
- Middleware `auth` su tutte le route protette
- Middleware `verified` dove necessaria verifica email
- Controllo authorization (Policy, Gate, middleware custom) su ogni azione sensibile
- Nessuna logica auth custom che bypassa il framework

### Broken Access Control (OWASP A01)
- Route model binding con scope (utente può accedere solo alle proprie risorse)
- Policy definite per azioni CRUD
- Middleware di autorizzazione applicati nel gruppo route corretto
- Nessun IDOR (Insecure Direct Object Reference)
- Admin-only actions protette

### XSS (OWASP A03)
- In Vue: nessun `v-html` con dati utente non sanitizzati
- In Blade: uso di `{{ }}` (escaped) invece di `{!! !!}` per dati utente
- Content-Security-Policy header dove possibile

### CSRF
- Form Inertia: gestito automaticamente, verificare che non sia disabilitato
- API routes: verificare che usino Sanctum/token auth invece di session

### Secrets & Configurazione
- Nessuna credenziale hardcoded nel codice
- Nessun uso di `env()` fuori dai file `config/*.php`
- `.env` in `.gitignore`
- API keys non committate
- Cerca pattern: password, secret, key, token, credential in stringhe letterali

### Mass Assignment
- Ogni model ha `$fillable` definito esplicitamente
- Nessun `$guarded = []` (guarded vuoto = tutto assegnabile)
- `create()` e `update()` usano solo dati validati dal Form Request

### File Upload
- Validazione tipo MIME e dimensione
- Storage in directory non pubblica o con accesso controllato
- Nome file sanitizzato (no path traversal)

### Rate Limiting
- Endpoint di login/registrazione con rate limiting
- Password reset con rate limiting
- API endpoints sensibili con throttle middleware

### Dipendenze
- Esegui `composer audit 2>&1 || true` per verificare vulnerabilità note
- Verifica che non ci siano pacchetti deprecati con CVE aperti

### Headers di Sicurezza
- Verifica in `bootstrap/app.php` o middleware: CORS, X-Frame-Options, X-Content-Type-Options

## Output

Produci un report strutturato:

```
## Review: Security — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `main`
- File analizzati: {N}
- {lista file}

### Critical
- **[file.php:42](path/to/file.php#L42)**: Vulnerabilità + tipo OWASP + impatto + fix

### Warning
- **[file.php:18](path/to/file.php#L18)**: Rischio potenziale + mitigazione

### Suggestion
- **[file.php:5](path/to/file.php#L5)**: Hardening suggerito

### Dependency Audit
- Output di `composer audit` (se rilevante)

### Summary
- Critical: N | Warning: N | Suggestion: N
- Verdict: Approvato / Da rivedere / Bloccante
```

Se non ci sono problemi, scrivi "Nessuna vulnerabilità trovata" con il verdict "Approvato".
