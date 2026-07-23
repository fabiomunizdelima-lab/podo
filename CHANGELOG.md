# Changelog

Tutte le modifiche rilevanti a **Podo**. Formato: [Keep a Changelog](https://keepachangelog.com/it/1.1.0/).
Versionamento: [SemVer](https://semver.org/lang/it/). La versione corrente è in `VERSION`.

## [Non rilasciato]

## [0.2.4] - 2026-07-23
### Aggiunto
- Import cartelle cliniche e ortesi da SmartPodos (FileMaker via ODBC): 1.532 visite (podologiche + onicopatie) e 59 ortesi, agganciate ai pazienti per legacy_fm_id; campi clinici cifrati. Comando `podo:import-cliniche` + colonna `legacy_ref` (idempotenza).

## [0.2.3] - 2026-07-23
### Aggiunto
- Migrazione anagrafica da SmartPodos (FileMaker): comando `podo:import-pazienti` + colonna `legacy_fm_id`. Pazienti importati/arricchiti con match sul codice fiscale — 2.013 totali (+970 nuovi; arricchiti con nascita, sesso, telefoni, consenso e note cliniche cifrate). `legacy_fm_id` collega le future cartelle cliniche.
### Note
- Backup in-app (spatie) non operativo: manca `pg_dump` nel container app → usato il `pg_dump` del container db. Da sistemare (aggiungere postgresql-client all'immagine).

## [0.2.2] - 2026-07-23
### Aggiunto
- **Fatture a strutture** dall'interfaccia: nel form si sceglie l'intestatario tra **Paziente** o **Struttura** (denominazione, P.IVA, codice fiscale, indirizzo liberi). Prima il modello lo consentiva ma il form obbligava a un paziente.

## [0.2.1] - 2026-07-23
### Corretto
- **FatturaPA**: l'XML ora include `<DatiRitenuta>` (TipoRitenuta, ImportoRitenuta, AliquotaRitenuta, CausalePagamento) e il flag `<Ritenuta>SI</Ritenuta>` sulle righe quando la ritenuta d'acconto è attiva. Prima veniva calcolata ma non scritta nel file.
### Aggiunto
- Config fatturazione `withholding_type` (default `RT01`) e `withholding_causale` (default `A`).

## [0.2.0] - 2026-07-23
### Aggiunto
- Moduli SmartPodos ricostruiti: **listino prestazioni**, **cartella clinica** (anamnesi cifrata + visite datate con tipi visita: podologica, onicopatie, verruca, diabetico, extra + foto cliniche cifrate su disco), **ortesi/plantari** con stati di lavorazione.
- **Fatturazione**: bozza da visita → emissione numerata → PDF (dompdf), **XML FatturaPA SDI v1.2**, export **Sistema TS**; bollo automatico, ritenuta configurabile; fatture anche **senza paziente** (strutture).
- **Impostazioni** fiscali dello studio da UI (`Setting::billing()`).
- **Aggiornamento applicativo** da Git con controllo versione e barra di avanzamento.
- **Ruoli & Audit**: superadmin / admin / utente (portale paziente in sola lettura).
- **Importatore FatturaPA** (`podo:import-fatturapa`), idempotente: importati **1.043 pazienti** e **2.602 fatture** (nov 2022 → apr 2025).
### Modificato
- Riconciliazione branch: **`main` reso canonico** (contiene il build completo); l'updater punta a `main`.
### Sicurezza
- Campi clinici e contatti cifrati a riposo (cast `encrypted`); header di sicurezza/CSP.

## [0.1.0] - 2026-07-22
### Aggiunto
- Foundation: autenticazione, RBAC, **MFA TOTP** (attualmente disattivata), anagrafica pazienti + consensi GDPR, **agenda** (FullCalendar), sync **Google Calendar** (OAuth2), **promemoria WhatsApp** (Meta Cloud API), audit log, backup cifrato schedulato.
- Deploy **Docker** su Debian/LXC dietro **Nginx Proxy Manager** (HTTP interno, TLS a monte).
