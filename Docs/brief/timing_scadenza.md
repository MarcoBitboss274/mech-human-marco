
Bisogna creare un meccanismo che evidenzi le lavorazioni attive che hanno una prescrizione in scadenza.

- Il meccanismo deve attivarsi un mese prima della scadenza;
- Così come anche il concetto di scadenza, il meccanismo è valido solo per le lavorazioni attive, e quindi non anullate, che si trovano in stato = Richiesta, In lavorazione, In approvazione.

Quando il meccanismo si attiva:
- deve apparire un alert ⚠️ con tooltip "Prescrizione in scadenza il: data scadenza" di fianco alla data di scadenza nella riga di una lavorazione nella index lavorazioni di admin M&H, Customer, Agente, Fornitore.
- deve apparire un alert con testo "⚠️ Attenzione: prescrizione in scadenza il: data scadenza" sotto l'header nel dettaglio di una Lavorazione nel dettaglio lavorazioni di admin M&H, Customer, Agente, Fornitore.

