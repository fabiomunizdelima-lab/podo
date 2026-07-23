# Podo — Roadmap

Rifacimento moderno di **SmartPodos** (gestionale podologico). Stato aggiornato al 2026-07-23.

---

## ✅ Fatto

### Fase 1 (foundation preesistente)
- Autenticazione, RBAC, MFA TOTP (attualmente **disattivata in dev**).
- Anagrafica pazienti + consensi GDPR.
- Agenda / appuntamenti (FullCalendar).
- Sincronizzazione Google Calendar (OAuth2).
- Promemoria WhatsApp (Meta Cloud API).
- Audit log su file, backup cifrato schedulato (spatie).

### Moduli SmartPodos ricostruiti
- **Prestazioni / Listino** — catalogo con prezzo, IVA/natura FatturaPA (N4 esente art.10), durata.
- **Cartella clinica** — anamnesi unica cifrata + visite datate (campi clinici cifrati) + **tipi di visita** (podologica, onicopatie, verruca, paziente diabetico, extra) + prestazioni erogate da listino + **foto cliniche cifrate su disco**.
- **Ortesi / plantari su misura** — tipo, materiale, specifiche, stati di lavorazione (prescritto → in lavorazione → pronto → consegnato).
- **Fatturazione (Ricevute sanitarie)** — bozza da visita → emissione con numerazione → **PDF**, **XML FatturaPA (SDI v1.2)**, **export Sistema TS**; regime flessibile forfettario/ordinario; bollo automatico; ritenuta configurabile.
- **Impostazioni** — dati studio/fatturazione da UI (tabella settings), letti da PDF/XML/TS.
- **Ruoli & Audit**:
  - Superadmin (tutto), Admin (tutto ma i superadmin sono invisibili/intoccabili), Utente = paziente (portale sola-lettura della propria cartella).
  - Registro di audit (accessi + modifiche) con filtri; admin non vede le attività dei superadmin.

---

## 🕒 Da completare

### A. Funzionalità
- [ ] **Pagamenti / scadenzario** — incassi, stato pagamento fatture, insoluti, metodi di pagamento tracciati.
- [ ] **Report / statistiche** — fatturato per periodo, prestazioni più frequenti, nuovi pazienti, ortesi in lavorazione.
- [ ] **Ricevuta vs Fattura** — tipo-documento distinto con numerazione separata (tipico del forfettario).
- [ ] **Appuntamento → visita** — generare una visita clinica direttamente da un appuntamento in agenda.
- [ ] Widget dashboard (fatture non pagate, ortesi da consegnare, agenda del giorno).

### B. Migrazione dati storici da SmartPodos
Il DB originale è il file binario Omnis `VSMP530.smp`, non leggibile senza Omnis Studio:
la migrazione avviene tramite gli **export** già estratti dal cliente.
- [ ] **Importatore anagrafiche** dai dati estratti (pazienti storici).
- [ ] **Importatore fatture/prestazioni** dagli XML di fattura elettronica.
- [ ] **Importatore fatturati XLSX** e dati Sistema TS.
- [ ] Riconciliazione: match pazienti per codice fiscale, gestione duplicati, report di import.
- [ ] Storico clinico: valutare cosa è recuperabile dagli export (le cartelle cliniche potrebbero non essere negli export).

### C. Pre-produzione (bloccanti)
- [ ] **Reset password + SMTP** — oggi NON esiste recupero password; configurare mail e flusso "password dimenticata" (serve al portale paziente).
- [ ] **Backup off-site** — il backup è schedulato ma va verificata la destinazione: i dati sanitari (art. 9) non devono restare solo sulla stessa VM (es. S3 su .43 o host .73).
- [ ] **Riattivare MFA** per gli amministratori (`MFA_REQUIRED_FOR_ADMINS=true`).
- [ ] **Dati fiscali reali** in Impostazioni + **validazione XML** contro lo schema SDI ufficiale prima del primo invio.
- [ ] **HTTPS / dominio** su Nginx Proxy Manager con certificato.
- [ ] **Pulizia dati demo** (paziente "Demo Cartella", fattura 1/2026, account paziente.demo, righe audit di test).

### D. Adempimenti fiscali reali (oltre la generazione file)
- [ ] **Invio allo SDI** — l'app genera l'XML ma non lo trasmette: serve un canale (intermediario/commercialista o accredito SDI).
- [ ] **Conservazione a norma** delle fatture elettroniche (obbligo di conservazione digitale a 10 anni).
- [ ] **Invio Sistema TS reale** — oggi c'è l'export CSV: servono credenziali TS, tracciato ufficiale e codice tipologia spesa corretto.

### E. Rafforzamenti GDPR / sicurezza
- [ ] Cifrare anche **CF, email, telefono, indirizzo** (oggi cifrate solo le note cliniche) con blind-index sul codice fiscale per la ricerca.
- [ ] **Export dati paziente** (portabilità) e **cancellazione definitiva** (diritto all'oblio).
- [ ] **Retention**: cancellazione automatica dei dati oltre i termini di conservazione.
- [ ] **DPIA** (valutazione d'impatto) — trattamento su larga scala di dati sanitari.
- [ ] Invio dell'audit trail a un **SIEM esterno** (previsto dal commento in config/logging).
- [ ] **Test automatici** sui moduli nuovi (phpunit è configurato).

### F. Esercizio / manutenzione
- [ ] **Monitoraggio e alerting** (uptime, spazio disco, esito backup).
- [ ] **Aggiornamenti e advisory**: `composer audit` segnala 3 advisory su dompdf — valutare mitigazione o alternativa.
- [ ] Procedura documentata di **restore** del backup (un backup non testato non è un backup).

---

## Note
- Accesso VM dev: `192.168.0.83` (podo-dev), root via chiave SSH.
- Stack: Laravel 11 / PostgreSQL 16 / Redis 7 / Docker Compose, dietro Nginx Proxy Manager.
