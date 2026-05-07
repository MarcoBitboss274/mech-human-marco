# Log esecuzione piani

Registro dei piani in `Docs/plans/`: stato di esecuzione, date e note.

**Stati**: `Da fare` · `In corso` · `Completato` · `Sospeso` · `Annullato`

| Piano | Stato | Avviato | Completato | Note |
|---|---|---|---|---|
| [piano-area_fornitore.md](piano-area_fornitore.md) | Completato | 2026-05-06 | 2026-05-07 | Fase 1+2 chiuse. Step 11 risolto con `last_login_at` (active/pending) — commit 774ed17. Step 8 sostituito da modal "Invita membro" su suppliers/Show.vue (no `/users/create` query string). |
| [piano-area_fornitore_lavorazioni.md](piano-area_fornitore_lavorazioni.md) | Da fare | — | — | Dipende da Fase 1 di piano-area_fornitore (`active`/`pending`) |
| [piano-feature_revisione_prescrizione.md](piano-feature_revisione_prescrizione.md) | Da verificare | — | — | — |
| [piano-filtraggio_esportazione.md](piano-filtraggio_esportazione.md) | Da verificare | — | — | — |
| [piano-revisione_aggiornamento.md](piano-revisione_aggiornamento.md) | Da verificare | — | — | — |
| [piano-tab_panoramica.md](piano-tab_panoramica.md) | Da verificare | — | — | — |
| [piano-tab_panoramica_aggiornamento.md](piano-tab_panoramica_aggiornamento.md) | Da verificare | — | — | — |

## Convenzioni

- **Avviato**: data (YYYY-MM-DD) in cui inizia l'implementazione del piano.
- **Completato**: data (YYYY-MM-DD) in cui il piano è eseguito end-to-end (codice + eventuali test).
- **Note**: PR, commit chiave, deviazioni rispetto al piano, follow-up aperti.
- Quando un piano viene aggiunto in `Docs/plans/`, va aggiunta una riga qui con stato `Da fare`.
- Quando un piano viene rimosso/rinominato, aggiornare il link e lo stato (`Annullato` se abbandonato).
