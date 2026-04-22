# Tasks per Dev

Attività richieste da Marco che vanno oltre lo scope della skill `Gestione_UI`
e richiedono intervento dello sviluppatore (modifiche strutturali ai template Vue,
logica, routing, store, ecc.).

---

### [2026-04-22] — Spostare il sidebar toggle button nella navbar

**Richiesta:** Spostare `<button class="base-btn sidebar-toggle sidebar-toggle--active">` dalla sidebar alla topbar/navbar.

**Motivo fuori scope:** Richiede di spostare un elemento nel DOM tra due componenti Vue (`LayoutSidebar` e `TopbarDefault`) — modifica strutturale al template, non realizzabile via solo CSS.

**File coinvolti:**
- `resources/js/components/layout/LayoutSidebar.vue` — rimuovere il button
- `resources/js/components/layout/TopbarDefault.vue` — aggiungere il button
- `resources/js/components/layout/workspace/LayoutSidebar.vue` — idem
- `resources/js/components/layout/workspace/TopbarDefault.vue` — idem

### [2026-04-22] — Avatar navbar: mostrare solo 1 lettera invece di 2

**Richiesta:** Mostrare solo la prima iniziale (es. "B" invece di "BB") nell'avatar della navbar.

**Motivo fuori scope:** `::first-letter` non funziona su `display: flex`, che è il layout usato da `.fallback-avatar` per centrare il testo. Modificare il numero di lettere mostrate richiede un cambio nella logica del componente Vue che genera le iniziali.

**File coinvolti:**
- `resources/js/components/layout/TopbarDefault.vue` — ridurre le iniziali a 1 carattere (es. `user.name.charAt(0)` invece delle prime due lettere)
- `resources/js/components/layout/workspace/TopbarDefault.vue` — idem

<!-- Le voci vengono aggiunte qui dalla skill Gestione_UI -->
