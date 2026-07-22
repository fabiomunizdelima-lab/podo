# Documentazione GDPR — bozze

⚠️ **Questi sono documenti di base/bozza** generati come punto di partenza tecnico.
Vanno **rivisti e completati con un consulente privacy / DPO** prima dell'uso reale.
Trattandosi di **dati sanitari** (categoria particolare, art. 9 GDPR) la corretta
impostazione documentale è essenziale.

Contenuto:
- [`informativa-privacy.md`](informativa-privacy.md) — informativa per i pazienti.
- [`registro-trattamenti.md`](registro-trattamenti.md) — registro delle attività di trattamento (art. 30).
- Data breach: procedura da definire (notifica Garante entro 72h).
- DPIA: valutazione d'impatto consigliata per dati sanitari su larga scala.
- DPA: da stipulare con i fornitori (hosting VPS, Meta/WhatsApp, Google) in qualità di responsabili.

## Misure tecniche già adottate nel software
- Cifratura a riposo dei dati sanitari e dei segreti (AES-256).
- Cifratura in transito (TLS).
- Controllo accessi basato su ruoli + MFA.
- Audit log degli accessi e delle modifiche.
- Raccolta esplicita dei consensi (privacy, WhatsApp, marketing) per paziente.
- Minimizzazione e cancellazione logica (soft-delete).
