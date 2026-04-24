---
name: review-feature
description: "End-to-end completeness review of a new feature. Checks backend, frontend, routing, database, tests, types, and docs."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[nome-feature-o-path]"
---

# Feature Completeness Review

## Scope

1. Se `$ARGUMENTS` contiene un path o nome feature, usa quello come contesto per la ricerca
2. Altrimenti, esegui `git diff main --name-only --diff-filter=ACMR` per trovare **tutti** i file modificati rispetto a `main`
3. Raggruppa i file per area: backend (`app/`), frontend (`resources/js/`), database (`database/`), routes (`routes/`), tests (`tests/`), config (`config/`), docs (`docs/`)
4. Se non ci sono file modificati, comunicalo e termina

## Checklist

### Backend
- [ ] Controller presente con metodi CRUD necessari
- [ ] Form Request per ogni endpoint che riceve input
- [ ] Service/Action per logica complessa (no business logic nel controller)
- [ ] Policy/middleware per authorization
- [ ] Eloquent Resource per response API (se applicabile)
- [ ] Notifiche/mail con `ShouldQueue`

### Database
- [ ] Migrazione presente per nuove tabelle/colonne
- [ ] Model con `$fillable`, `casts()`, relazioni tipizzate
- [ ] Factory con stati utili (es. `expired()`, `suspended()`)
- [ ] Indici su colonne usate in query
- [ ] Foreign keys con cascade appropriato

### Routing
- [ ] Route definita in `routes/web.php` o `routes/app.php`
- [ ] Middleware applicati (auth, verified, resolve-impresa, ensure-admin)
- [ ] Named route per URL generation
- [ ] Wayfinder rigenerato (`npm run build` include il plugin)

### Frontend
- [ ] Pagina Vue in `resources/js/pages/`
- [ ] Props TypeScript coerenti con i dati passati dal controller
- [ ] Layout corretto (`AppLayout` per app, `AuthLayout` per auth)
- [ ] Breadcrumbs definiti
- [ ] Gestione errori (flash message, form errors)
- [ ] Empty states per liste vuote
- [ ] Loading states per operazioni async

### TypeScript Types
- [ ] Tipi in `resources/js/types/` aggiornati
- [ ] Props interface definita nel componente
- [ ] Tipi shared in `global.d.ts` se necessario (flash, shared props)

### Tests
- [ ] Feature test per happy path di ogni endpoint
- [ ] Test authorization (403 per utenti non autorizzati)
- [ ] Test validazione (errori per input invalido)
- [ ] Test edge cases (duplicati, scaduti, sospesi)
- [ ] Factory usate, assertions specifiche
- [ ] Esegui `php artisan test --compact` e verifica che passi

### Code Quality
- [ ] `vendor/bin/pint --dirty --format agent` senza errori
- [ ] `npm run build` senza errori
- [ ] Nessun `console.log` rimasto nel codice Vue
- [ ] Nessun `dd()` o `dump()` rimasto nel codice PHP
- [ ] Import inutilizzati rimossi

### Documentazione
- [ ] Se esiste `docs/`, verificare se serve aggiornamento
- [ ] Commenti in codice solo dove la logica è complessa

## Cross-Check Coerenza

Verifica che i dati fluiscano correttamente end-to-end:
1. **Controller → Vue**: le props passate da `Inertia::render()` corrispondono a `defineProps<Props>()`
2. **Form → Controller**: i campi del form (`useForm({})`) corrispondono alle regole del Form Request
3. **Wayfinder**: gli import `@/actions/` esistono e i parametri corrispondono alla route
4. **Database → Model → Controller**: i campi selezionati/filtrati corrispondono alle colonne della tabella

## Output

Produci un report strutturato:

```
## Review: Feature — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `main`
- Feature: {descrizione breve della feature rilevata dai file}
- File totali: {N}

### Inventory
| Area | File | Stato |
|---|---|---|
| Backend | app/Http/Controllers/FooController.php | Presente |
| Form Request | app/Http/Requests/StoreFooRequest.php | Presente |
| Migration | database/migrations/xxx_create_foo_table.php | Presente |
| Model | app/Models/Foo.php | Presente |
| Factory | database/factories/FooFactory.php | MANCANTE |
| Frontend | resources/js/pages/foo/Index.vue | Presente |
| Types | resources/js/types/index.ts | Aggiornato |
| Test | tests/Feature/FooControllerTest.php | Presente |
| Docs | docs/foo.md | Non necessario |

### Critical
- **Componente mancante**: Factory per Foo — necessaria per i test

### Warning
- **[Controller.php:25](path#L25)**: Props passate non corrispondono a defineProps

### Suggestion
- **[Index.vue:10](path#L10)**: Aggiungere empty state

### Cross-Check
- Controller → Vue: OK / Mismatch su campo X
- Form → Request: OK / Campo Y mancante nelle regole
- Wayfinder: OK / Import non trovato
- DB → Model: OK / Colonna Z non in $fillable

### Summary
- Critical: N | Warning: N | Suggestion: N
- Completezza: {N}/{M} componenti presenti
- Verdict: Approvato / Da rivedere / Bloccante
```
