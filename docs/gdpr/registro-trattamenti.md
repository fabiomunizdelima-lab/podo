# Registro delle attività di trattamento (BOZZA — art. 30 GDPR)

> Bozza tecnica da completare con il consulente privacy.

## Titolare
- **Titolare:** [Dott. Nome Cognome] — [P.IVA] — [contatti]
- **DPO:** [se nominato]

## Trattamento 1 — Gestione clinica pazienti
| Campo | Valore |
|-------|--------|
| Finalità | Erogazione prestazioni podologiche, gestione cartella clinica |
| Base giuridica | Art. 9.2.h GDPR (finalità di cura) |
| Categorie interessati | Pazienti |
| Categorie dati | Anagrafici, contatto, codice fiscale, **dati sanitari** |
| Destinatari | Personale autorizzato dello studio |
| Trasferimenti extra-UE | No |
| Termini cancellazione | Secondo obblighi di conservazione documentazione sanitaria |
| Misure di sicurezza | Cifratura AES-256, RBAC, MFA, audit log, backup cifrati, TLS |

## Trattamento 2 — Fatturazione e adempimenti fiscali
| Campo | Valore |
|-------|--------|
| Finalità | Emissione fatture elettroniche (SDI), invio Sistema TS |
| Base giuridica | Art. 6.1.c (obbligo legale) |
| Categorie dati | Anagrafici, fiscali, importi prestazioni |
| Destinatari | Agenzia delle Entrate, Sistema TS, commercialista |
| Termini | Termini di legge (fiscali) |

## Trattamento 3 — Promemoria appuntamenti (WhatsApp/Email)
| Campo | Valore |
|-------|--------|
| Finalità | Invio promemoria appuntamento |
| Base giuridica | Art. 6.1.a (consenso) |
| Categorie dati | Nome, numero di telefono, data/ora appuntamento |
| Responsabili | Meta Platforms Ireland (WhatsApp) — DPA |
| Trasferimenti extra-UE | Possibili (SCC) |
| Misure | Invio solo con consenso registrato; log invii |

## Trattamento 4 — Sincronizzazione agenda (Google Calendar)
| Campo | Valore |
|-------|--------|
| Finalità | Sincronizzazione appuntamenti sul calendario dell'operatore |
| Base giuridica | Legittimo interesse organizzativo / consenso operatore |
| Responsabili | Google Ireland — DPA |
| Dati | Titolo evento, data/ora (minimizzati) |
