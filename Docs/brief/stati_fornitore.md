Devi aiutarmi a ragionare sulla questione Stato lavoraizone lato Forntiore vs Stato lavorazione lato M&H.
Quanto abbiamo implementato adesso sul concetto di Stato lavorazione nel workspace fornitore non va bene.
Innanzi tutto vorrei slegare lo stato della lavorazione lato fornitore dallo stato della lavorazione lato M&H (ma magari sbaglio).

Gli stati di una lavorazione lato workspace fornitore devono seguire quste logiche:
- Quando M&H sceglie il Fornitore, lato fornitore il forntiore vede la lvorazione in stato "Nuovo caso";
- Quando M&H conferma l'avvio Produzione, lato fornitore il forntiore vede la lvorazione in stato "Produzione confermata";
- Quando il Fornitore finisce la produzione e clicca su "Produzione completata", lato fornitore il forntiore vede la lvorazione in stato "Completato", lato M&H lo stato della lavorazione NON cambia. Il fatto che il fornitore abbia completato la produzione al massimo aggiunge un badge alla produzione con scritto "Completata";

Edge case da studiare:
- Se il Fornitore segna la produzione come Completata ma M&H annulla la "Produzione" che succede?
- Se il Fornitore segna la produzione come Completata ma M&H annulla la "Lavorazione" che succede?
- Se il Fornitore vede che la lavorazione è in stato "Produzione confermata" ma M&H annulla la "Lavorazione" che succede?

