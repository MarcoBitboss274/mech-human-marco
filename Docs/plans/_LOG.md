# Log esecuzione piani

Registro dei piani in `Docs/plans/`: stato di esecuzione, date e note.

**Stati**: `Da fare` · `In corso` · `Completato` · `Sospeso` · `Annullato`

| Piano | Stato | Avviato | Completato | Note |
|---|---|---|---|---|
| [piano-area_fornitore.md](piano-area_fornitore.md) | Completato | 2026-05-06 | 2026-05-07 | Fase 1+2 chiuse. Step 11 risolto con `last_login_at` (active/pending) — commit 774ed17. Step 8 sostituito da modal "Invita membro" su suppliers/Show.vue (no `/users/create` query string). |
| [piano-area_fornitore_lavorazioni.md](piano-area_fornitore_lavorazioni.md) | Completato | 2026-05-07 | 2026-05-07 | Fase 1+2 chiuse. Default: `documents_sent` = qualunque upload conta; nuova colonna `production_canceled_at` cablata in `cancelProduction`/`confirmProduction`. Chat fornitore: nuove tabelle `operation_supplier_chat_*`, `SupplierChatService`, eventi broadcast, policy, controller admin + endpoint workspace; ChatSlider refactor con `chatScope` (+ embed mode), wrapper 2 tab `OperationChatTabbed`. 4 Notification class + `NotificationService::sendToSupplier` aggiornato (mail anonima + bell ai user `last_login_at != null`). Hook su `attachSupplier`/`swapSupplier`/`confirmProduction`/`cancelProduction`/`cancel`. Follow-up aperti: (a) unread tracking pinia store per chat fornitore (oggi solo customer), (b) sezione "Documenti del caso" lato supplier vuota finché Prescription non registra una media collection. |
| [piano-feature_revisione_prescrizione.md](piano-feature_revisione_prescrizione.md) | Da verificare | — | — | — |
| [piano-filtraggio_esportazione.md](piano-filtraggio_esportazione.md) | Da verificare | — | — | — |
| [piano-revisione_aggiornamento.md](piano-revisione_aggiornamento.md) | Da verificare | — | — | — |
| [piano-tab_panoramica.md](piano-tab_panoramica.md) | Da verificare | — | — | — |
| [piano-tab_panoramica_aggiornamento.md](piano-tab_panoramica_aggiornamento.md) | Da verificare | — | — | — |
| [piano-stati_fornitore.md](piano-stati_fornitore.md) | Completato | 2026-05-08 | 2026-05-08 | Refactor stati 6→3 + supplier_completed_at su pivot + endpoint POST complete + activity log custom (8 eventi) + reset auto su cancelProduction + alert "Prescrizione in revisione" + badge "Completata dal fornitore" su ProductionsTab M&H. Notifica admin via `NotificationService::sendToAdmins` (solo role=admin v1; superadmin/agent specifico Operation rimasti fuori scope, da estendere se richiesto). 13 test Pest nuovi verdi (`SupplierVisibleStatusTest`). 5 fallimenti residui in test suite erano preesistenti su `main` (Chat/ProductionCrud/ProductionStatusTimestamps), non regressioni. |
| [piano-nuovi_stati_fornitore.md](piano-nuovi_stati_fornitore.md) | Completato | 2026-05-12 | 2026-05-12 | Inversione parziale del refactor precedente. Lato Fornitore: rimosso `case_status` (UI/colonna/filtro/badge/bottoni), rimossi endpoint `close-case`/`reopen-case`, rimossa ability `SUPPLIER_OPERATIONS_COMPLETE`. Index ha 1 sola colonna "Stato" (= production_status). Lato Admin: aggiunte 2 azioni `markCaseCompleted`/`reopenCase` con route `operations.case.complete`/`operations.case.reopen`, mini-sezione "Stato lavorazione (Fornitore)" in ProductionsTab. Service: rinominati metodi in `markCaseCompletedByAdmin`/`reopenCaseByAdmin` (firma `(Operation, User $causer)`, supplier dedotto). Rimosse notifiche `SupplierCaseCompletedForAdmin`/`SupplierCaseReopenedForAdmin`. `case_status` rimosso da `$appends` Operation, esposto esplicitamente nei payload admin/agent. Whitelist activity log pulita (no `case_*` per fornitore). 22/22 test Pest verdi. |
| [piano-stati-lavorazione-produzione.md](piano-stati-lavorazione-produzione.md) | Completato | 2026-05-12 | 2026-05-12 | Refactor a 2 dimensioni: case status (Aperta/Completata, solo Fornitore) + production status (Null/Confermata/Annullata/Completata, solo Admin). Drop di `production_canceled_at`; rollback storico `Production.status = completed → confirmed`. Nuovo `CaseStatusEnum`, rimosso `SupplierVisibleStatusEnum`. Nuovi service methods: `markCaseCompleted`, `reopenCase`, `markProductionCompleted`, `reopenProduction`. 4 notifiche: `SupplierCaseCompletedForAdmin` (rinomina), `SupplierCaseReopenedForAdmin`, `OperationProductionCompletedForSupplierNotification`, `OperationProductionReopenedForSupplierNotification`. Index fornitore: 2 colonne (Stato + Produzione) + 2 filtri indipendenti. Show fornitore: 2 badge + Chiudi/Riapri senza vincoli. ProductionsTab admin: 4 pulsanti (Conferma/Annulla/Completa/Riapri). 20 test Pest verdi (`CaseAndProductionStatusTest`); fallimenti residui su Auth/Dashboard/Inertia/Chat sono preesistenti. |

## Convenzioni

- **Avviato**: data (YYYY-MM-DD) in cui inizia l'implementazione del piano.
- **Completato**: data (YYYY-MM-DD) in cui il piano è eseguito end-to-end (codice + eventuali test).
- **Note**: PR, commit chiave, deviazioni rispetto al piano, follow-up aperti.
- Quando un piano viene aggiunto in `Docs/plans/`, va aggiunta una riga qui con stato `Da fare`.
- Quando un piano viene rimosso/rinominato, aggiornare il link e lo stato (`Annullato` se abbandonato).
