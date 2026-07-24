# Changelog

Tutte le modifiche rilevanti a **Podo**. Formato: [Keep a Changelog](https://keepachangelog.com/it/1.1.0/).
Versionamento: [SemVer](https://semver.org/lang/it/). La versione corrente è in `VERSION`.

## [Non rilasciato]

## [0.3.1] - 2026-07-24
### Corretto
- **L'agenda non si apriva**: `window.initAgenda` veniva assegnato *dopo* `Alpine.start()` in `resources/js/app.js`, ma lo start percorre il DOM in modo sincrono ed esegue subito `x-init="mount()"` — quindi al momento del mount la funzione era `undefined`, il calendario non veniva creato e il feed degli appuntamenti non veniva mai richiesto. Il difetto era mascherato dal nonce CSP rotto (che bloccava del tutto lo script inline) ed è emerso appena quello è stato sistemato. Ora il mount segnala l'errore in pagina invece di fallire in silenzio.

### Aggiunto
- Agenda: **trascinare su una fascia oraria crea l'appuntamento** (la pagina lo prometteva già, ma la selezione non era abilitata) e **trascinare o ridimensionare un evento ne sposta l'orario**, con ripristino automatico se il salvataggio non riesce.


## [0.3.0] - 2026-07-24
### Aggiunto
- **Impostazioni → Integrazioni**: pagina unica per Google Calendar, email (SMTP) e WhatsApp. Le credenziali si inseriscono dall'interfaccia e finiscono **cifrate** nella tabella `settings` (`Setting::set(..., encrypt: true)`): non serve più toccare il `.env` né ricreare i container. I segreti non tornano mai al browser e un campo lasciato vuoto lascia invariato il valore già salvato.
- **Pulsanti di prova** per i tre servizi: invio di un'email di test, messaggio WhatsApp di test, verifica del collegamento a Google Calendar (legge il calendario configurato).
- **Recupero password via email**: link "Password dimenticata?" in login, collegamento monouso valido 60 minuti (broker standard, tabella `password_reset_tokens`), email in italiano. Le risposte sono identiche a prescindere dall'esito, per non rivelare quali indirizzi esistono; gli account disattivati non ricevono nulla.
- **Promemoria appuntamento via email** (`AppointmentReminderMail`): contiene solo data, ora, prestazione e indirizzo dello studio, nessun dato clinico.
- **Scelta del canale** (WhatsApp / Email / Nessuno) sul form dell'agenda, con pulsante "Invia ora" e stato dell'invio. `podo:send-reminders` smista sul canale scelto per ciascun appuntamento.
- Collegamento Google: parametro `state` anti-CSRF, indirizzo dell'account collegato (colonna additiva `google_tokens.account_email`), pulsante **Scollega**.

### Corretto
- **CSP: il nonce veniva generato dopo il rendering della vista**, quindi nelle pagine restava vuoto e il browser bloccava ogni `<script>` inline — barra di avanzamento degli aggiornamenti, agenda, form fattura e form visita. Ora è generato prima di `$next()`, e il nonce è stato aggiunto ai tre `<script>` che ne erano privi.
- **Agenda**: aprendo un appuntamento esistente, trattamento e note non venivano ricaricati nel modale e il salvataggio li azzerava. Ora il feed li restituisce insieme al canale del promemoria.
- `APP_URL` puntava ancora a `podo.example.it`: erano sbagliati i link nelle email e l'URI di reindirizzamento OAuth.
- Google Calendar: la sincronizzazione richiedeva che fosse *chi ha creato* l'appuntamento ad avere l'account collegato, ora ricade sull'account dello studio. Il `refresh_token`, che Google invia solo al primo consenso, non viene più sovrascritto con `null` ai collegamenti successivi.

### Modificato
- `GoogleCalendarService` e `WhatsAppService` leggono la configurazione da `Setting` (DB) con fallback sul `.env`. `WhatsAppService::sendAppointmentReminder()` ritorna `[esito, messaggio]` invece di un booleano, così l'operatore vede *perché* un invio non è riuscito.
- Nuovo `AppointmentReminderService`: unico punto di smistamento dei promemoria, per invio manuale e schedulato.

## [Non rilasciato]


## [0.2.5] - 2026-07-23
### Aggiunto
- Logo ufficiale "podo" (login, sidebar, favicon).

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
