# Istruzioni di progetto

## Log esecuzione piani

Il file [Docs/plans/_LOG.md](Docs/plans/_LOG.md) tiene traccia dello stato di esecuzione di tutti i piani in `Docs/plans/`.

**Aggiornarlo automaticamente in questi momenti**:

1. **Quando inizio a eseguire un piano** (Marco mi chiede di implementare un `piano-*.md`):
   - Aggiornare la riga corrispondente: `Stato` → `In corso`, `Avviato` → data odierna (YYYY-MM-DD).
   - Farlo come prima azione, prima di iniziare le modifiche al codice.

2. **Quando l'esecuzione del piano è completata** (tutti i diff applicati, eventuali test verdi):
   - `Stato` → `Completato`, `Completato` → data odierna.
   - In `Note`: hash del commit principale o nome del PR, e qualunque deviazione significativa dal piano.

3. **Quando viene aggiunto un nuovo piano** in `Docs/plans/`:
   - Aggiungere una riga al log con stato `Da fare`.

4. **Quando un piano viene sospeso o abbandonato**:
   - Aggiornare lo stato a `Sospeso` o `Annullato` con motivo in `Note`.

Se non è chiaro se un'azione richiesta da Marco corrisponde all'esecuzione di un piano specifico, chiederglielo prima di toccare il log.
