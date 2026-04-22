# UI Overrides — Componenti BB

Registro di tutti gli override CSS applicati ai componenti `bb` tramite la skill `Gestione_UI`.
Il CSS corrispondente si trova in `resources/css/theming_override.css`.

---

### BbButton — Stile secondary (icon-only automatico + testuali espliciti)

**Data:** 2026-04-22

**Selettori:**
- `.bb-button.bb-button--icon:not(.base-btn--disabled)` (+ `:hover`)
- `.bb-button.bb-button--secondary:not(.bb-button--icon):not(.base-btn--disabled)` (+ `:hover`)
- `.bb-button[variant='secondary']:not(.bb-button--icon):not(.base-btn--disabled)` (+ `:hover`)

**Proprietà modificate:**
- Icon-only a riposo: `--color: transparent; --border-color: var(--color-mix-200); --text-color: var(--color-mix-500)` (border grigio leggero, no bg)
- Icon-only hover: `background-color: var(--color-mix-200); color: var(--bb-text)` (border resta, ma stesso colore del bg → visivamente scompare)
- Testuale secondary a riposo: `--color: transparent; --border-color: var(--color-mix-200); --text-color: var(--bb-text)`
- Testuale secondary hover: `background-color: var(--color-mix-200); color: var(--bb-text)`

**Motivo:** Marco ha chiesto di distinguere un'unica azione primary per schermata (+ Nuova lavorazione, Avanti, Salva) da tutte le altre, che devono essere secondary. Spec Marco: testuali outlined leggero grigio con testo scuro a riposo → hover bg grigio chiaro. Icon button no bg/no border con icona grigia → hover bg grigio chiaro + icona scura. Gli icon-only sono riconosciuti via la classe `.bb-button--icon` che bitboss-ui aggiunge automaticamente quando si usa la prop `icon` del componente. I testuali sono riconosciuti via `class="bb-button--secondary"` o via attribute `variant="secondary"` (quest'ultimo è già presente in 6 punti del codice e veniva passato come prop ignorata ma finisce sul `<button>` root, quindi è agganciabile via CSS).

### bb-input — font-size 13px e centramento verticale perfetto

**Data:** 2026-04-22

**Selettori:**
- `:root` → variabili `--bb-input-font-size`, `--bb-input-prefix`, `--bb-input-compact-h`, `--bb-input-compact-py`
- `.bb-common-input-inner-container input`
- `.bb-common-input-inner-container textarea`

**Proprietà modificate:**
- `--bb-input-font-size: 14px` (impostato a 13px, poi riportato a 14px per la regola "solo numeri pari")
- `--bb-input-prefix: 14px` (allineato al font-size)
- Aggiunte `--bb-input-compact-h: 28px` e `--bb-input-compact-py: 2px` perché `.bb-common-input-inner-container` ridefinisce localmente `--bb-input-h` → `--bb-input-compact-h`; serve coprire anche la variante compact.
- Input/textarea: `line-height: 1.4 !important; margin-top: 0 !important; margin-bottom: 0 !important`

**Motivo:** Marco ha chiesto font-size 13px e centramento verticale perfetto. bb imposta `line-height: initial` sull'`<input>` + margin-top/bottom pari a `--bb-input-py`: l'interazione tra questi margins e il flex `align-items:center` del container può dare mis-centramenti visibili (specialmente con prefix/suffix/icon di dimensioni diverse). Azzerando i margins e fissando un line-height deterministico, il flex del container centra il line-box in modo coerente in tutti i casi (text-input, textarea, date-picker, number-input, ecc. — tutti usano lo stesso container `.bb-common-input-inner-container`). Line-height: 1 per gli `<input>` (line-box = font-size, centramento esatto via flex); 1.4 per le `<textarea>` (multi-line ha bisogno di leading).

### bb-date-picker-input — calendar button senza hover bg, icona grigia più piccola

**Data:** 2026-04-22

**Selettori:**
- `.bb-base-date-picker-input .bb-common-input-inner-container .bb-base-date-picker-input__calendar-btn` (+ `:hover:not(:disabled)`)
- `.bb-base-date-picker-input .bb-common-input-inner-container .bb-base-date-picker-input__calendar-btn svg` (+ hover svg)

**Proprietà modificate:**
- Container button: `background-color: transparent !important` (a riposo e all'hover — prima era `--bb-primary` blu all'hover)
- SVG: `color: var(--bb-icon-color) !important` (grigio, prima era `--bb-primary` blu), `width/height: 16px !important` (ridotto da 18px)

**Motivo:** Marco ha chiesto che le icone dentro gli input si comportino come l'icona search (lens): no background all'hover, vector sempre grigi. L'icona calendario di default era blu con hover bg blu. Ridotta anche leggermente (18→16px) per un aspetto più delicato.

### bb-base-input-container — hint-container margin-top solo se aperto

**Data:** 2026-04-22

**Selettore:** `.bb-base-input-outer-container .bb-base-input-container__hint-container.bb-collapsible--open`

**Proprietà modificate:** `margin-top: 4px !important` (solo quando aperto, prima applicato sempre)

**Motivo:** Il margin-top sul hint-container contribuiva 4px al wrapper input anche quando chiuso (no errore), disallineando gli icon button posizionati via `flex items-end` nelle righe filtri (button Pulisci alzato di 4px rispetto al bottom dell'input). Aggiungendo `.bb-collapsible--open` al selettore, il margin si applica solo quando un errore è effettivamente visibile.

