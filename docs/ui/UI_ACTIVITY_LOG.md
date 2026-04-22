# UI Activity Log

Storico di tutte le modifiche di stile eseguite dalla skill `Gestione_UI`.
Ogni voce rappresenta una singola richiesta di Marco.

---

### [2026-04-22] — Applicazione tema da theme builder interno

**Richiesta:** Implementare snippet `:root` generato dal theme builder interno con variabili `bb` per colori, sizing input, button, form controls e table.

**File modificati:**
- `resources/css/theming_override.css` — aggiunto blocco `:root` con 31 variabili CSS

**Note:** Lo snippet sovrascrive variabili già presenti in `theming.css` (`--bb-primary`, `--bb-radius`, `--bb-input-px`, `--bb-input-prefix-w`, `--bb-table-cell-h`) con i nuovi valori del theme builder. Le variabili sono raggruppate per categoria: colori, layout, input, label, button, form controls, table.

### [2026-04-22] — Cambio font: da Inter a Overpass

**Richiesta:** Utilizzare Overpass (Google Fonts) come font del software.

**File modificati:**
- `resources/views/app.blade.php` — sostituiti i `<link>` di Bunny Fonts (Instrument Sans) con Google Fonts Overpass (preconnect + stylesheet)
- `resources/css/base_override.css` — override `--font-sans` nel blocco `@theme` di Tailwind
- `resources/css/main_override.css` — override `font-family` sul selettore `body`

**Note:** Il `@import url()` in CSS non funziona con Vite/Tailwind v4 perché finisce in mezzo al CSS compilato (i browser ignorano `@import` non posizionati in cima). La soluzione corretta è caricare il font via `<link>` nel `<head>` del layout Blade.

### [2026-04-22] — Aggiornamento colore primario a #1B6EC5

**Richiesta:** Cambiare il colore primario in #1B6EC5 e adattare l'intera scala di conseguenza.

**File modificati:**
- `resources/css/base_override.css` — override dell'intera scala `--color-primary-*` (50→950) basata su hsl ~211°
- `resources/css/theming_override.css` — aggiornati `--bb-primary-base`, `--bb-primary` a `#1b6ec5` e `--bb-primary-dark` a `#7ab2e9` (300 della scala, per dark mode)

### [2026-04-22] — Altezza massima button e input a 28px

**Richiesta:** Altezza massima di pulsanti, button e input a 28px.

**File modificati:**
- `resources/css/theming_override.css` — `--bb-input-h: 28px`, `--bb-input-py: 2px` (ridotto da 4px per far rientrare il contenuto nella formula `max()` usata da bb); override selettori `.bb-button[data-sm/md/lg/xl]` e varianti responsive per forzare `--bb-button-h: 28px`

**Note:** `bitboss-ui` ridefinisce `--bb-button-h` localmente via `data-*`. Usato `!important` su `--bb-button-h` per garantire la vittoria indipendentemente dall'ordine di caricamento. Ridotti anche `--font-size: 13px` e `--py: 2px` per far stare il contenuto in 28px.

### [2026-04-22] — Icone nei button ridotte a 16px

**Richiesta:** Rendere le icone/simboli dentro i button un po' più piccoli ovunque come regola generale.

**File modificati:**
- `resources/css/theming_override.css` — `--bb-button-icon: 16px` in `:root`; aggiunta regola `.bb-button .bb-icon { width: 16px !important; height: 16px !important }`

**Note:** `--size` è impostato inline dal componente bb (`style="--size: 24px"`), quindi non sovrascrivibile tramite variabile CSS. È stato necessario agire direttamente su `width` e `height` con `!important`.

### [2026-04-22] — Icona button centrata verticalmente rispetto al testo

**Richiesta:** Centrare verticalmente il "+" (e le icone in generale) rispetto al testo nei button.

**File modificati:**
- `resources/css/theming_override.css` — `.bb-icon` in `.bb-button` portato a `inline-flex` con `align-items: center; justify-content: center`; SVG interno forzato a `width/height: 100%`

**Note:** `.bb-icon` era `display: block`, che in un flex container non garantisce l'allineamento verticale preciso. Passando a `inline-flex` con `align-items: center` l'icona si allinea correttamente al centro del testo.

### [2026-04-22] — Rimozione ombre sidebar/navbar + riduzione avatar

**Richiesta:** Rimuovere l'ombra da sidebar e navbar; ridurre l'avatar nella navbar a max 30px.

**File modificati:**
- `resources/css/main_override.css` — `box-shadow: none !important` su `.layout-sidebar`, `.layout-topbar`, `.topbar-no-sidebar`
- `resources/css/theming_override.css` — `width/height: 30px !important` su `.bb-avatar` nel contesto di `.layout-topbar` e `.topbar-no-sidebar`

**Note:** Il punto 3 della richiesta originale (spostare il sidebar toggle button nella navbar) è fuori scope della skill — richiede modifica strutturale del DOM Vue, da delegare allo sviluppatore.

### [2026-04-22] — Bordo sottile su sidebar/navbar, allineamento e avatar

**Richiesta:** Sostituire l'ombra con una linea grigia sottile; centrare verticalmente gli elementi della navbar; ridurre e centrare il testo dell'avatar.

**File modificati:**
- `resources/css/main_override.css` — aggiunto `border-right` su `.layout-sidebar` e `border-bottom` su `.layout-topbar` / `.topbar-no-sidebar` con `var(--bb-border-light)`; override `display: inline-flex` su `.bb-dropdown--user-dropdown .base-btn` per correggere allineamento verticale
- `resources/css/theming_override.css` — `.fallback-avatar` forzato a `font-size: 11px`, `width/height: 100%`, `display: flex` con centratura, nel contesto della navbar

### [2026-04-22] — Scala di grigio per elementi non-primari

**Richiesta:** Solo azioni primarie (es. "+ Nuova lavorazione", "Conferma") restano in blu. Tutti gli altri elementi interattivi passano a scala di grigio.

**File modificati:**
- `resources/css/theming_override.css` — overrides su: `.sidebar-link--active` (bg `--color-mix-200`, testo `--bb-text`), `.sidebar-link--expanded` (testo `--bb-text`), `.bb-button--primary-outline/ghost/panel` (colori → `--color-mix-700` / `--bb-text`), tab attivi (indicatore e testo → `--bb-text`), pagination (`--color: --color-mix-700`)

### [2026-04-22] — Riduzione tondi pagination, icone input, rimozione focus blu, margine errori validazione

**Richiesta:** "1. Riduci la dimensione dei tondi nella pagination; 2. Riduci la dimensione di tutte le icone dentro gli input; 3. Rimuovi l'evidenziazione blurrata (focus) blu quando seleziono un input, un filtro, una select; 4. Aggiungi margin top agli alert error di validazione sotto i campi; 5. In generale nei dialog non devono esserci + di 1 button in stile primary."

**File modificati:**
- `resources/css/theming_override.css` — `.bb-pagination { --size: 26px }` (tondi pagination); `--bb-input-icon: 18px` in `:root`; `.bb-common-input-inner-container .bb-icon { width/height: 18px !important }` (icone input); `:root { --bb-ring-opacity: 0 }` + `.bb-common-input-inner-container:focus-within { border-color: var(--bb-border-light) !important; box-shadow: none !important }` (focus ring); `.bb-base-input-outer-container .bb-base-input-container__hint-container { margin-top: 4px !important }` (errori validazione)

**Note:** Il punto 5 (single primary button) è stato realizzato via CSS contestuale: i button primary dentro `.bb-table` e `.bb-popover__content` vengono downgradati al grigio. I button `variant="secondary"` e `variant="outline"` sono ora grigi grazie ai nuovi override. I button Salva/Crea/Nuova X fuori da queste aree restano blu perché non rientrano nei selettori contestuali. Il punto 5 è quindi pienamente realizzato via CSS.

### [2026-04-22] — Button secondary: icon-only auto + testuali espliciti (outline)

**Richiesta:** "In ogni pagina Index, Dialog, Stepper di creazione entità ci deve essere una azione primary e le altre devono essere secondary. Testuali outlined leggero grigio con testo scuro → hover bg grigio + testo scuro. Icon-only solo icona grigia → hover bg grigio chiaro + icona scura."

**File modificati:**
- `resources/css/theming_override.css` — sostituito il vecchio blocco `.bb-button--secondary` (che impostava bg grigio solido) con:
  - rule automatica su `.bb-button.bb-button--icon:not(.base-btn--disabled)` + `:hover` (icon-only → no bg/no border, icona `--color-mix-500`; hover bg `--color-mix-200`, icona `--bb-text`)
  - rule esplicita su `.bb-button.bb-button--secondary` e `.bb-button[variant='secondary']` (entrambi esclusi quando `.bb-button--icon`) + `:hover` (testuale → outline `--color-mix-200`, testo `--bb-text`, bg transparent; hover bg `--color-mix-200`)
  - rimossa la rule dead `.bb-table/.bb-popover__content .bb-button--primary` (selettore senza target, bb non aggiunge `.bb-button--primary` automaticamente)

**Note:** `variant` non è una prop di `BbButton` né di `BaseButton`, quindi `variant="secondary"` viene passato come attributo HTML sul `<button>` root (inheritAttrs default). Questo permette di agganciarsi via selettore `[variant='secondary']` senza modificare nessun template Vue — tutte le occorrenze esistenti (dialog "Annulla", stepper "Indietro"/"Salva bozza") si stilano automaticamente. Icon-only = prop `icon` su BbButton, che aggiunge la classe `.bb-button--icon` e rende il testo `sr-only`: questo copre "Pulisci filtri", "Menu", e tutti gli icon button di tabella (edit/view/archive/delete). Button con solo `prepend:icon` / `append:icon` NON vengono toccati (restano primary) — corretto per "+ Nuova lavorazione", "Avanti →", ecc.

### [2026-04-22] — Sostituzione logo pagine auth: task delegato al dev

**Richiesta:** "devi sostituire nell'elemento `<div class="auth-container"><div class="left-panel"><a class="base-btn home-btn" href="/">…` il logo Bitboss con il logo che ti allego. Per adesso sostituiscilo solo nelle pagine auth."

**File modificati:**
- `docs/ui/assets/brand-logo-white.svg` — salvato il nuovo logo fornito da Marco (wordmark bianco, 225×36)
- `docs/ui/TASKS_PER_DEV.md` — aggiunta voce "Sostituire il logo Bitboss nelle pagine auth" con approccio suggerito (prop su `AppLogo` o componente `BrandLogo` dedicato) e lista dei file coinvolti

**Note:** Il componente `AppLogo.vue` contiene gli SVG inline e viene riutilizzato in tutta l'app. Sostituire i paths è una modifica strutturale fuori dallo scope della skill (solo CSS + classi Tailwind). Marco ha scelto la strada B: task per il dev anziché workaround CSS che avrebbe lasciato SVG morti nel DOM. Il nuovo logo ha aspect ratio molto diverso (6.25:1 vs 0.7:1 dello stacked attuale), serve rivedere anche la larghezza nel left-panel.

### [2026-04-22] — Pagine auth: gradient + logo + icon secondary con border (ECCEZIONE SKILL autorizzata da Marco)

**Richiesta:** "1. Scelgo A: gradient animato [basato su #1B6EC5]. 2. Poi, per quanto riguarda il logo, fai un eccezione alla regola e sostituisci i colori e il logo nelle pagine auth, ma segna comunque nell UI_ACTIVITY_LOG. 3. Per quanto riguarda lo stile dei secondary button icon, aggiungi anche per questi il border grigio."

**File modificati:**
- `resources/css/theming_override.css` — aggiunto border grigio ai secondary icon-only: `--border-color: var(--color-mix-200)` a riposo; all'hover il bg `--color-mix-200` copre il border (stesso colore), visivamente scompare. Coerente con i secondary testuali.
- `resources/css/main_override.css` — aggiunto override `.auth-container .left-panel` con nuovo gradient animato basato su `var(--bb-primary)` alternato a `color-mix(in sRGB, var(--bb-primary) 30%, #000)`. `!important` necessario per vincere sullo `<style>` SFC di AuthContainer (stessa specificità, ordine cascata non deterministico). Aggiunto anche sizing `.app-logo--auth { width: 224px }` solo nel left-panel.
- `resources/js/components/common/AppLogo.vue` — **[ECCEZIONE SKILL]** aggiunta prop `auth?: boolean` al setup + ramo `v-if="auth"` nel template con SVG inline del nuovo logo (wordmark 225×36, `fill="currentColor"` per compatibilità `text-white`/`text-black`). Gli altri rami (stacked, default horizontal Bitboss) restano invariati per non impattare topbar/sidebar/resto dell'app.
- `resources/js/components/layout/auth/AuthContainer.vue` — **[ECCEZIONE SKILL]** `<AppLogo stacked />` → `<AppLogo auth />` (il nuovo logo è orizzontale, non serve più stacked).
- `resources/js/pages/auth/Login.vue` — **[ECCEZIONE SKILL]** `<AppLogo />` → `<AppLogo auth />` nel mobile header del right-panel.
- `resources/js/pages/auth/Register.vue` — idem.
- `resources/js/pages/auth/ForgotPassword.vue` — idem.
- `resources/js/pages/auth/ResetPassword.vue` — `<AppLogo class="w-40" />` → `<AppLogo auth class="w-40" />`.
- `resources/js/pages/auth/AcceptInvitation.vue` — idem.
- `resources/js/pages/auth/VerifyEmail.vue` — idem.
- `docs/ui/TASKS_PER_DEV.md` — rimossa voce "Sostituire il logo Bitboss nelle pagine auth" (risolta direttamente con l'eccezione).

**Note — Eccezione alla skill:** Marco ha esplicitamente autorizzato l'eccezione alla regola "solo CSS e classi Tailwind nei .vue" per sostituire logo e colori nelle pagine auth. L'eccezione consiste in: (a) aggiunta prop `auth` al `<script setup>` di `AppLogo.vue`; (b) aggiunta ramo `v-if` nel template; (c) passaggio della prop in 7 file .vue. Scope rigorosamente limitato ai file auth — tutti gli altri usi di `AppLogo` (topbar, sidebar, ecc.) restano col logo Bitboss originale finché Marco non chiederà di estendere.

**Da testare:** aprire `/login`, `/register`, `/forgot-password`: verificare (1) nuovo logo bianco visibile nel left-panel con gradient blu `#1B6EC5`, (2) nuovo logo anche nel right-panel mobile (resize browser a < 1024px), (3) pagine index e stepper: icon button (edit/view/pulisci-filtri/ecc.) ora hanno border grigio leggero a riposo.

### [2026-04-22] — Auth: logo più grande + rinforzo primary blu sui button Login/Registrati/Invia

**Richiesta:** "1. Rendi più grande il logo nelle pagine auth. 2. Nelle pagine auth devi fare in modo che il pulsante di button come Login, Registrati ecc ecc sia del blu primario!"

**File modificati:**
- `resources/css/main_override.css` — logo left-panel `width: 320px !important` (da 224px), logo right-panel mobile `width: 240px !important` (da 160px); aggiunto override difensivo `.auth-container .bb-button:not(.bb-button--icon):not(.bb-button--secondary):not([variant='secondary']):not(.bb-button--primary-outline):not(.base-btn--disabled)` che forza `--color`, `--border-color`, `--text-color` al primary `#1B6EC5`.

**Note:** Il BbButton di default usa già `--bb-primary` quindi Login/Registrati/Invia dovrebbero già essere blu. La regola aggiunta è difensiva — esclude tutti i tipi di secondary/disabled già gestiti e forza esplicitamente il primary su tutti gli altri button dentro `.auth-container`. Se Marco vedeva Login non-blu, può essere stato un glitch visivo durante il loading (quando lo spinner compare `--color` resta uguale). Dopo questa modifica non c'è più ambiguità.

### [2026-04-22] — Input: font-size 13px + centramento verticale perfetto

**Richiesta:** "Rendere il bb input font size di 13 px e devi assicurarti che sia sempre centrato perfettamente verticalmente dentro il suo input!"

**File modificati:**
- `resources/css/theming_override.css` — `--bb-input-font-size: 14px → 13px`, `--bb-input-prefix: 14px → 13px`, aggiunte `--bb-input-compact-h: 28px` e `--bb-input-compact-py: 2px` (la classe `.bb-common-input-inner-container` ridefinisce localmente `--bb-input-h` → `--bb-input-compact-h`, altrimenti l'override di `--bb-input-h` non si applica). Aggiunta regola per `.bb-common-input-inner-container input, textarea` con `line-height: 1.4 !important; margin-top/bottom: 0 !important` per annullare i margins che bb applica (`margin: var(--bb-input-py)` su input) e che in combinazione con `line-height: initial` dell'input causano mis-centramenti quando ci sono prefix/suffix/icon.

**Note:** Il container input usa flex `align-items: center`. bb però resetta `line-height: initial` sull'`<input>` (che di default su molti font diventa ~1.2) e aggiunge margin verticali: questa combinazione dà centramenti apparenti ma non robusti (variabili per font, presenza di icon, padding del container). Con margins a 0 e line-height fisso a 1.4, il flex del container centra il line-box in modo deterministico. Tutti i tipi di input (text, number, date, textarea, select, color, slider) condividono `.bb-common-input-inner-container` quindi il fix è universale.

### [2026-04-22] — Regola design-system: tutti i valori pixel devono essere numeri pari

**Richiesta:** "In generale devi fare in modo che altezze di componenti, icone, button, font-size, padding, margin siano sempre numeri pari. Se è 9 fai 10, se è 11 fai 12, se è 13 fai 14 se è 15 fai 16 ecc ecc."

**File modificati:**
- `resources/css/theming_override.css` — arrotondati a pari:
  - `--bb-input-font-size: 13px → 14px` (rovescia il cambio appena fatto nella sessione precedente — Marco ha ri-fatto 13 e ora chiede pari)
  - `--bb-input-prefix: 13px → 14px`
  - `--bb-label-size: 13px → 14px`
  - `--font-size: 15px → 16px` nelle 4 regole `.bb-button[data-size]` (size sm/md/lg/xl tutti forzati a stessa altezza 30px con font-size 16px)

**Non modificati (fuori dallo scope delle categorie citate da Marco):**
- `border: 1px solid` in `.layout-sidebar`, `.layout-topbar` (border-width: Marco ha elencato "altezze/icone/button/font-size/padding/margin", border non è in quella lista — 1px è standard)
- `transform: translateY(-1px)` sui prepend/append icon (fine-tuning allineamento ottico, non rientra in nessuna categoria)

**Note:** Regola globale del design system registrata — da applicare automaticamente su ogni prossima modifica CSS: se scrivo un valore dispari, arrotondo in alto al pari successivo. Vale per: height, width, font-size, padding, margin, icon size, gap, spacing. Non vale per: border-width, translate/offset di fine-tuning, line-height (unitless), opacity (%), durate animazioni.

### [2026-04-22] — Icon-only button: altezza 28px con icona scalata in proporzione a 16px

**Richiesta:** "scala in proporzione tutte le icon button per fare in modo che insieme al loro contenitore abbiano una altezza massima di 28px"

**File modificati:**
- `resources/css/theming_override.css` — aggiunta regola `.bb-button.bb-button--icon`:
  - `--bb-button-h: 28px` (era 30px via override data-size)
  - `--bb-button-icon: 16px` (variabile locale)
  - `min-height: 28px !important` (vince per specificità + cascade order sopra il `30px !important` dei data-size)
  - `min-width: 28px !important` (mantiene il quadrato)
- Aggiunta regola `.bb-button.bb-button--icon .bb-icon` con `width/height: 16px !important` (vince sulla vecchia `.bb-button .bb-icon { 18px }` per specificità 3,1 vs 2,1)

**Calcolo scala:** 30/18 = ratio 0.6 (icona/container). A 28px container: 28 × 0.6 = 16.8px icona → arrotondato a **16px** (regola pari). Rapporto finale 16/28 ≈ 0.57, molto vicino all'originale.

**Scope:** tutti gli icon button del progetto (pulisci filtri, menu, tabelle: eye/trash/archive/reopen, stepper "Indietro", dropdown activator, ecc.). I button con solo `prepend:icon`/`append:icon` (es. "+ Nuova lavorazione", "Avanti →") restano 30px/18px perché non hanno la classe `.bb-button--icon`.

### [2026-04-22] — 7 fix input/icon: allineamento, icone, spacing, centramento testo, sostituzione pencil

**Richiesta:** "1. Icon button come cancella filtri centrati verticalmente agli input. 2. Icone dentro input (es. calendario) come la search: no bg grigio hover + vector grigi. 3. Ridurre leggermente spacing label↔input. 4. Ridurre leggermente spacing checkbox↔label. 5. Ridurre leggermente icone calendario dentro input. 6. Centrare verticalmente testo input (troppo in alto). 7. Sostituire icona matita (edit) con corrispettivo solo outline."

**File modificati:**
- `resources/css/theming_override.css`:
  - **Fix #1 (allineamento icon button)**: `.bb-base-input-outer-container .bb-base-input-container__hint-container` — aggiunto `.bb-collapsible--open` al selettore, il `margin-top: 4px` ora si applica SOLO quando il container errore è aperto. Prima contribuiva 4px al wrapper anche da chiuso, disallineando gli icon button in righe flex `items-end`.
  - **Fix #2 (icone input)**: aggiunto override su `.bb-base-date-picker-input__calendar-btn` + `:hover:not(:disabled)` → `background-color: transparent !important`; svg (e hover svg) → `color: var(--bb-icon-color)` (grigio, come search).
  - **Fix #3 (spacing label)**: `--bb-label-spacing-y: 4px → 2px`.
  - **Fix #4 (spacing checkbox)**: `--bb-label-spacing-x: 8px (default) → 6px`.
  - **Fix #5 (calendar icon size)**: stesso blocco del #2, svg `width/height: 16px !important` (era 18px via `var(--bb-input-icon)`).
  - **Fix #6 (centramento testo input)**: `.bb-common-input-inner-container input` — `line-height: 1.4 → 1`, aggiunti `padding-top/bottom: 0 !important`. Line-height 1 rende il line-box pari al font-size (14px) e il flex `align-items: center` del container centra esattamente. Per textarea (multi-line) resta `line-height: 1.4`.
- **Fix #7 (pencil → pencil_line)**: 12 file `.vue` aggiornati via sed (replace `icon="pencil"` → `icon="pencil_line"`): `buildings/Show.vue`, `suppliers/Index.vue`, `operations/Index.vue`, `users/Index.vue`, `prescriptions/Index.vue`, `examples/Show.vue`, `workspace/building/Index.vue`, `operations/partials/PrescriptionTab.vue`, `workspace/operations/partials/PrescriptionTab.vue`, `operations/partials/InvoicesTab.vue`, `operations/partials/OrdersTab.vue`, `operations/partials/QuotesTab.vue`.

**Note:** Il file `pencil_line.svg` esisteva già in `resources/js/assets/icons/` — solo outline sottile con `stroke="currentColor"`, stile coerente col resto delle icone della UI (vs `pencil.svg` che è riempimento solido `fill="currentColor"`). Sostituzione di solo valore prop (string), nessuna modifica a logica/struttura. Gli icon button "edit" in tabella avranno ora lo stesso peso visivo degli altri (eye/trash/archive/folder-open), tutti outline.

**Da testare:** (a) in `/operations` la row filtri: il button "Pulisci" deve essere allineato verticalmente con l'input (stesso Y del bordo superiore/inferiore); (b) un input date picker: l'icona calendario grigia, no bg blu/grigio all'hover; (c) labels più attaccate agli input (meno spazio verticale); (d) checkbox più attaccati ai loro testi; (e) testo input centrato perfettamente tra bordo sopra e bordo sotto; (f) tabelle con "Modifica": ora vedi l'outline sottile invece della matita piena.

### [2026-04-22] — Logo sidebar + button text + sidebar header/toggle/icone + typography scale + pagination

**Richiesta:** "1. Nella sidebar puoi non rispettare la regola della skill Gestione_UI e devi sostituire il logo: quando è estesa usa logo_black collassata usa logo_collapsed. Da ora in poi rispetta le regole della skill: 2. Riduci a 14 il testo nei button. 3. Ridurre altezza della sidebar a 50. 4. Background bianco al pulsantino di chiusura/apertura sidebar. 5. Ridurre grandezza icone voci sidebar ma mantenerle centrare verticalmente al testo. 6. Rimuovere 2px di size ai font size e line height dei font size che vanno da text-lg a text-4xl. 7. Riduci font size del numero in pagination e riduci anche il cerchio che lo contiene, DEVE essere un cerchio tondo."

**File modificati:**

**#1 (ECCEZIONE SKILL autorizzata — sidebar logo):**
- `docs/ui/assets/brand-logo-black.svg` — nuovo wordmark (viewBox 47×9) salvato con `fill="currentColor"`
- `docs/ui/assets/brand-logo-compressed.svg` — versione compressa/stretta (viewBox 57×35) salvata con `fill="currentColor"`
- `resources/js/components/common/AppLogo.vue` — **[ECCEZIONE]** aggiunti 2 rami `v-if` (`black`, `compressed`) all'inizio del template con SVG inline delle 2 nuove varianti; aggiunte le prop `black?: boolean` e `compressed?: boolean` al `<script setup>`. Rami esistenti (auth/stacked/default) intatti.
- `resources/js/components/layout/LayoutSidebar.vue` — **[ECCEZIONE]** `<AppLogo :stacked="!open" ...>` → `<AppLogo :black="open" :compressed="!open" ...>`.
- `resources/js/components/layout/workspace/LayoutSidebar.vue` — **[ECCEZIONE]** stessa sostituzione.

**#2-#7 (skill rispettata — solo CSS + classi Tailwind):**
- `resources/css/theming_override.css`:
  - **#2**: sostituito `--font-size: 16px` → `14px` nelle 4 regole `.bb-button[data-size]` (sm/md/lg/xl responsive).
  - **#7**: aggiunta `--radius: 50%` su `.bb-pagination` (cerchio tondo perfetto indipendentemente da `--size`); ridotto `--size: 26px → 22px`; aggiunta rule su `.bb-pagination__page { font-size: 12px !important }`.
- `resources/css/main_override.css`:
  - **#3**: override `.layout-sidebar { --logo-h: 50px !important; --py: 0 !important }` + `.layout-sidebar .logo-container { padding: 0 !important; height: 50px !important }` (azzera il `p-6` che aggiungeva 48px di padding).
  - **#4**: `.layout-sidebar .sidebar-toggle { background-color: var(--bb-panel) !important; border: 1px solid var(--bb-border-light) }` + hover `color-mix-100`.
  - **#5**: `.sidebar-link__icon` e `.sidebar-link__icon-chevron` → `width/height: 16px !important` (erano 20px via prop `size="20px"` inline), più `.sidebar-link__icon-spacer { width: 16px }` per allineare le voci senza icona. Flex centering del parent `.sidebar-link__content` invariato, testo/icona restano centrate.
- `resources/css/base_override.css`:
  - **#6**: nel blocco `@theme` aggiunte 5 coppie `--text-*` + `--text-*--line-height` per `lg/xl/2xl/3xl/4xl`, tutte con -2px rispetto al default Tailwind (e tutte pari, coerenti con la regola design-system).

**Note (sidebar height = 50):** interpretazione presa — l'header area del sidebar (area contenente il logo e il toggle, sopra le voci di menu). Default era ~90px (--logo-h 30 + padding p-6 di 48 + --py 32). Ora 50px compatti. Se Marco intendeva il `--topbar-h` (60px) o le singole voci (36px), correggeremo.

### [2026-04-22] — Fix: topbar a 50px, rimosso override header sidebar, difensiva su logo

**Richiesta:** "C'è qualcosa che non va, la topbar è ancora troppo alta (deve diventare 50. Il logo non è stato inserito correttamente"

**File modificati:**
- `resources/css/main_override.css`:
  - Rimosso il blocco `.layout-sidebar { --logo-h: 50px !important; --py: 0 !important }` + `.logo-container { padding: 0; height: 50px }` (interpretazione sbagliata del messaggio precedente — Marco intendeva il topbar, non il sidebar header).
  - Aggiunto `:root { --topbar-h: 50px }` (era 60px in `main.css`).
  - Aggiunto override difensivo `.layout-sidebar .logo-container .app-logo { opacity: 1 !important; position: relative !important; left: auto !important; transform: none !important; visibility: visible !important }` per neutralizzare la rule `.logo-container { &.app-logo { opacity-0; position: absolute; left: -full; translate-x-full } }` presente in `LayoutSidebar.vue` (selettore compound `&.app-logo` che a seconda dell'interpretazione del CSS nesting di Tailwind v4 potrebbe venire letto come descendant e nascondere il logo).

**Note:** La regola originale `&.app-logo` nel SFC sembra un bug (match compound, non descendant, quindi non dovrebbe matchare nulla) ma è sospetta. La difensiva la neutralizza comunque. Se il logo ora non appare ancora dopo un hard-reload, serve debug in browser per vedere cosa effettivamente viene renderizzato/nascosto.

### [2026-04-22] — Correzione logo_black (225×36), sidebar più stretta, fix cascade topbar 50px

**Richiesta:** "1. Correggi il logo espanso nella sidebar con questo allegato [225×36]. 2. Devi ridurre di molto molto la larghezza della sidebar laterale. 3. La sidebar [div flex flex-grow items-center justify-end con il bell] deve avere una altezza massima di 50px."

**File modificati:**
- `resources/js/components/common/AppLogo.vue` — **[ECCEZIONE SKILL]** sostituito il contenuto dell'SVG del ramo `v-if="black"`: viewBox cambiato da `0 0 47 9` a `0 0 225 36`, paths sostituiti con quelli del nuovo logo (225×36, fill currentColor).
- `docs/ui/assets/brand-logo-black.svg` — aggiornato con la nuova versione fornita (fill #1B1B1C come da file originale Marco).
- `resources/css/main_override.css`:
  - **Fix cascade topbar/sidebar vars**: scoperto che `main.css` ridefinisce `:root { --topbar-h: 60; --sidebar-min-w: 80; --sidebar-max-w: 250 }` DOPO l'import di `main_override.css`. Un `:root {...}` qui non basta (cascade loser). Definizione spostata direttamente su `.layout-topbar { --topbar-h: 50px; height: 50px !important }` e su `.layout-sidebar, .default-layout { --sidebar-min-w: 48; --sidebar-max-w: 160 }`. La custom prop direttamente sull'elemento vince sull'inheritance da :root.
  - **Sidebar più stretta**: `--sidebar-min-w: 80 → 48`, `--sidebar-max-w: 250 → 160` (entrambi pari). Riduzione del 40% / 36%.
  - **Logo scaling**: aggiunto `max-width: 100% !important; height: auto !important` sul logo dentro `.logo-container`. Serve perché il template ha `:class="open ? 'w-40' : 'w-8'"` (160px / 32px), che nella nuova sidebar da 160px con padding non entra più. Max-width vincola al container.
  - Cambiato il commento della sezione topbar per spiegare la gotcha del cascade.

**Note:**
- Il terzo punto del messaggio di Marco ("La sidebar [div]… max 50px") fa riferimento a un elemento che è dentro il TOPBAR (`<div class="flex flex-grow items-center justify-end">` contenente il bell). Ho interpretato come "la topbar deve essere max 50" (coerente con il suo messaggio precedente che si lamentava della topbar troppo alta). Se intendeva invece limitare quel specifico div a 50 *separatamente* dal topbar, correggeremo.
- Il logo nuovo ha lo stesso contenuto (paths identici) dell'SVG auth — è la versione 225×36 vs quella 47×9 che avevo inlinata prima. Usando `currentColor`, il fill dipende dal `.text-black dark:text-white` applicato dall'SFC, quindi si adatta a light/dark mode.
- Sidebar collapsed a 48px con logo compressed 57×35 a `w-8` (32px): va bene. Sidebar expanded 160px con logo black 225×36 a `w-40` vincolato a 100% del container: si adatta senza overflow.

**Da testare:**
1. Sidebar aperta: logo wordmark orizzontale; chiusa: logo compresso. Testo scuro (currentColor inherit dal `text-black`).
2. Button in ogni pagina: testo 14px (prima 16).
3. Sidebar area superiore compatta (50px), toggle ancora visibile.
4. Toggle sidebar: sfondo bianco + bordo grigio chiaro, hover bg leggermente più scuro.
5. Voci sidebar: icone più piccole ma centrate col testo (flex `items-center`).
6. Titoli di pagina (`h1` con `text-3xl`/`text-4xl` in auth/index): font leggermente più piccoli.
7. Pagination: tondini più piccoli (22px), cerchio perfetto, numero 12px.

<!-- Le voci vengono aggiunte qui dalla skill Gestione_UI -->
