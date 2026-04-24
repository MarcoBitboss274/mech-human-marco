---
name: review-frontend
description: "Reviews Vue 3 + Inertia + Tailwind + TypeScript frontend code for quality, accessibility, security, and UX patterns. Invoke periodically on new features or before merging."
allowed-tools: Read, Grep, Glob, Bash
argument-hint: "[path-opzionale]"
---

# Frontend Code Review

## Scope

1. Se `$ARGUMENTS` contiene un path, analizza **solo quel path** (ricorsivamente)
2. Altrimenti, esegui `git diff master --name-only --diff-filter=ACMR -- '*.vue' '*.ts' '*.tsx'` per trovare i file frontend modificati rispetto a `master`
3. Se non ci sono file modificati, comunicalo e termina

Per ogni file nello scope, leggi il contenuto completo e analizza secondo la checklist.
Per i composables (`resources/js/composables/`), verifica anche che siano usati correttamente nei componenti che li importano.

## Checklist

### Vue 3 & Composizione
- Composables per logica riusabile — no logica complessa inline nel `<script setup>` (>30 righe di stato + funzioni correlate → estrarre)
- `ref`/`computed` usati correttamente — no `computed` con side effects, no `.value` dimenticati nel template
- Props tipizzate con `defineProps<T>()` — no props senza tipo
- Emit tipizzati con `defineEmits<T>()` — no emit senza tipo
- Single root element nei componenti (requisito Inertia)
- No mutazione diretta delle props — usare emit o pattern locale
- Cleanup di listener/timer/interval in `onUnmounted` se registrati manualmente
- `watch` con `immediate` solo se necessario, `watchEffect` per reattività automatica

### Inertia.js v2
- `router.get/put/post/delete` con `preserveScroll: true` dove il contesto lo richiede
- `onError` callback per feedback utente su ogni mutation (put/post/delete)
- `onFinish` per reset loading state
- `useForm` per form con validazione server-side — no gestione manuale degli errori
- Deferred props con skeleton/loading state (pulsing placeholder)
- No `fetch()` manuale dove Inertia `router` può gestire — `fetch` solo per endpoint JSON puri
- `preserveState: true` dove serve mantenere lo stato locale tra navigazioni

### TypeScript
- Tipi espliciti per `ref<T>`, `computed`, parametri funzione e return type
- No `any` — usare tipi specifici, `unknown`, o type guard
- Tipi condivisi in `resources/js/types/` — no tipi duplicati tra file
- Import type con `type` keyword: `import { type Foo } from '...'`
- Enum e union type preferiti a stringhe magiche

### Tailwind CSS v4
- Utility classes consistenti con componenti fratelli — verifica stile dei file nella stessa directory
- No stili inline (`style=""`) — usare Tailwind utilities
- Responsive design (`sm:`, `md:`, `lg:`) dove il layout lo richiede
- Classi condizionali pulite — ternari leggibili, no concatenazioni complesse (estrarre in computed se necessario)

### Sicurezza Frontend
- `DOMPurify.sanitize()` su **ogni** `v-html` — senza eccezioni
- No `v-html` con dati utente non sanitizzati (form input, risposte API, webhook data)
- Link esterni con `target="_blank"` devono avere `rel="noopener noreferrer"`
- No secrets, token, API key hardcoded nel frontend
- No `eval()`, `innerHTML` manuale, o costruzione dinamica di HTML

### Accessibilità (a11y)
- `aria-label` su bottoni icon-only (bottoni con solo icona senza testo visibile)
- `<Label>` associati ai form input (via `for`/`id` o wrapping)
- Focus management: i dialog/modal devono intrappolare il focus e ripristinarlo alla chiusura
- Stato `disabled` comunicato visivamente e semanticamente
- Stato loading comunicato (spinner + `aria-busy` o testo alternativo)
- Tabella con `<TableHeader>` semantico — no layout table per dati

### Performance & UX
- Debounce (≥200ms) su input di ricerca e filtri testuali
- Loading state visibile su ogni azione asincrona (button spinner, skeleton)
- Feedback visivo su errori — toast o inline error message, mai silenzioso
- Optimistic update con rollback su errore dove appropriato (es. toggle, rating)
- No `console.log`, `console.warn`, `debugger` residui
- Paginazione lato server per liste >50 elementi — no rendering di migliaia di righe

### Qualità Codice
- File Vue non oltre ~500 righe di `<script setup>` — estrarre composables e sotto-componenti
- Nessuna duplicazione logica tra componenti — estrarre in composable o utility
- Naming descrittivo per ref e funzioni (`isLoading`, `submitForm`, non `x`, `fn`)
- Import ordinati (framework → ui components → composables → utils → types → actions)
- Nessun import inutilizzato
- Componenti async (`defineAsyncComponent`) per modali/editor pesanti

## Output

Produci un report strutturato:

```
## Review: Frontend — {data odierna}

### Scope
- Branch: `{branch corrente}` vs `master`
- File analizzati: {N}
- {lista file}

### Critical
- **[File.vue:42](path/to/File.vue#L42)**: Descrizione problema + snippet codice + fix suggerito

### Warning
- **[File.vue:18](path/to/File.vue#L18)**: Descrizione

### Suggestion
- **[File.vue:5](path/to/File.vue#L5)**: Descrizione

### Summary
- Critical: N | Warning: N | Suggestion: N
- Verdict: Approvato / Da rivedere / Bloccante
```

### Classificazione

**Critical** (bloccante):
- `v-html` senza DOMPurify
- Props mutate direttamente
- Secrets nel frontend
- `any` su dati sensibili
- Nessun error handling su mutation

**Warning** (da rivedere):
- File >500 righe senza composables
- Missing `aria-label` su icon buttons
- No loading state su azioni async
- Import inutilizzati
- `console.log` residui

**Suggestion** (migliorativo):
- Estrarre computed/ternari complessi
- Migliorare naming
- Aggiungere skeleton per deferred props
- Ordinare import

Se non ci sono problemi, scrivi "Nessun problema trovato" con il verdict "Approvato".
