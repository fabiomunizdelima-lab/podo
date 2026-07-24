# Podo — Roadmap

Rifacimento moderno di **SmartPodos** (gestionale podologico). Aggiornato al 2026-07-24.

---

## ✅ Fatto

### Fase 1 (foundation preesistente)
- Autenticazione, RBAC, MFA TOTP (**attualmente disattivata**, da riattivare prima della produzione).
- Anagrafica pazienti + consensi GDPR.
- Agenda / appuntamenti (FullCalendar).
- Sincronizzazione Google Calendar (OAuth2).
- Promemoria WhatsApp (Meta Cloud API).

### Integrazioni e notifiche (0.3.0 — 24 lug 2026)
- **Impostazioni → Integrazioni**: Google Calendar, SMTP e WhatsApp configurabili da UI, credenziali cifrate in `settings`, pulsanti di prova per ciascun servizio.
- **Recupero password via email** (link monouso, 60 minuti) e **promemoria appuntamento via email**.
- **Canale del promemoria** scelto sul form dell'agenda (WhatsApp / Email / Nessuno), rispettato anche dall'invio schedulato.
- Correzioni: nonce CSP (gli script inline erano bloccati), `APP_URL`, perdita di trattamento e note in modifica appuntamento, `refresh_token` Google.
- Audit log su file, backup cifrato schedulato (spatie).

### Moduli SmartPodos ricostruiti
- **Prestazioni / Listino** — catalogo con prezzo, IVA/natura FatturaPA, durata.
- **Cartella clinica** — anamnesi unica cifrata + visite datate (campi clinici cifrati) + **tipi di visita** (podologica, onicopatie, verruca, paziente diabetico, extra) + prestazioni erogate da listino + **foto cliniche cifrate su disco**.
- **Ortesi / plantari su misura** — tipo, materiale, specifiche, stati di lavorazione.
- **Fatturazione** — bozza da visita → emissione con numerazione → PDF, XML FatturaPA (SDI v1.2), export Sistema TS; regime flessibile; bollo automatico; ritenuta configurabile.
- **Impostazioni** — dati studio/fatturazione da UI, letti da PDF/XML/TS.
- **Ruoli & Audit** — superadmin / admin (i superadmin gli sono invisibili) / utente = paziente con portale in sola lettura; registro di audit (accessi + modifiche) filtrato per ruolo.
- **Aggiornamenti applicativo** — controllo versione da Git e update con barra di avanzamento (backup → pull → composer → migrazioni → asset → cache).

### Migrazione dati (23 lug 2026)
- **Importatore FatturaPA** (`podo:import-fatturapa`), idempotente e con `--dry-run`:
  importati **1.043 pazienti** e **2.602 fatture** (nov 2022 → apr 2025, € 181.364,50).
- Fatture **senza paziente** ora possibili (lo studio fattura anche a strutture).
- Dati fiscali reali ricavati dalle fatture e impostati: P.IVA, **regime RF01 ordinario**, sede.
- Codice tipologia spesa Sistema TS corretto (**SP**, era SR).

---

## 🕒 Da completare

### A. Funzionalità
- [ ] **Pagamenti / scadenzario** — incassi, stato pagamento, insoluti, metodi tracciati.
- [ ] **Report / statistiche** — fatturato per periodo, prestazioni più frequenti, nuovi pazienti, ortesi in lavorazione.
- [ ] **Fatture a strutture/aziende nell'interfaccia** — il modello le supporta (19 storiche importate) ma il form permette di scegliere solo un paziente: manca il cliente-azienda con denominazione e P.IVA.
- [ ] **`<DatiRitenuta>` nell'XML** — la ritenuta viene calcolata ma non scritta nel file FatturaPA (19 fatture reali la usano).
- [ ] **Appuntamento → visita** — generare una visita clinica da un appuntamento in agenda.
- [ ] Widget dashboard (fatture non pagate, ortesi da consegnare, agenda del giorno).
- [ ] Valutare **Ricevuta vs Fattura**: lo studio è in regime ordinario ed emette TD01, quindi la ricevuta sanitaria potrebbe non servire.

### B. Migrazione dati storici (resto)
SmartPodos è un applicativo **FileMaker Pro 12** (non Omnis): il database `VSMP530.smp`
tiene i dati in blocchi compressi, quindi serve FileMaker (o l'export dal runtime)
per estrarre ciò che non è nelle fatture.
- [x] Anagrafiche e fatturato dagli XML FatturaPA.
- [ ] **Coordinare l'export del cliente** (823 pazienti, 1.555 fatture, 124 appuntamenti):
      capire la fonte e decidere se integra o sostituisce l'import attuale.
- [ ] **Agenda storica** (gli appuntamenti non sono ricavabili dalle fatture).
- [ ] **Cartelle cliniche / anamnesi** — solo dentro il FileMaker.
- [ ] **Foto cliniche**: 148 immagini in `Files/Images/Secure`, ma il legame foto↔paziente è nel DB.
- [ ] Importatore XLSX fatturati e storico Sistema TS (110 CSV + 233 XML).

### C. Pre-produzione (bloccanti)
- [x] **Reset password + SMTP** (0.3.0) — recupero password via email e configurazione SMTP da Impostazioni → Integrazioni.
      Resta da inserire le credenziali del mittente reale e provare l'invio.
- [ ] **Backup off-site** — schedulato, ma va verificata la destinazione: i dati sanitari non devono restare solo su questa macchina.
- [ ] **Riattivare MFA** (`MFA_REQUIRED_FOR_ADMINS=true` + **ricreare i container**, non basta `config:cache`).
- [ ] **Completare i dati fiscali** (codice fiscale e indirizzo mancanti) e **validare l'XML** contro lo schema SDI ufficiale.
- [ ] **HTTPS / dominio** su Nginx Proxy Manager con certificato.
- [ ] **Pulizia dati demo** (paziente "Demo Cartella", fattura 1/2026, account paziente.demo, righe audit di test).

### D. Adempimenti fiscali reali
- [ ] **Invio allo SDI** — l'app genera l'XML ma non lo trasmette: serve un canale (intermediario/commercialista).
- [ ] **Conservazione a norma** delle fatture elettroniche (10 anni).
- [ ] **Invio Sistema TS reale** — servono credenziali e certificati TS (quelli del cliente sono in `SistemaTS/Dati`).

### E. GDPR / sicurezza
- [ ] **Bonifica GitHub**: chiedere a GitHub Support la rimozione del commit orfano `1e8f7dd`, che conteneva dati di pazienti; valutare di rendere **privato** il repository.
- [ ] Cifrare anche **CF, email, telefono, indirizzo** (oggi solo le note cliniche) con blind-index sul codice fiscale.
- [ ] **Export dati paziente** (portabilità) e **cancellazione definitiva** (oblio).
- [ ] **Retention**: cancellazione automatica oltre i termini di conservazione.
- [ ] **DPIA** — trattamento su larga scala di dati sanitari.
- [ ] Audit trail verso un **SIEM esterno**.
- [ ] **Test automatici** sui moduli nuovi (phpunit è configurato).

### F. Esercizio / manutenzione
- [ ] **Monitoraggio e alerting** (uptime, spazio disco, esito backup).
- [ ] **Advisory**: `composer audit` segnala 3 advisory su dompdf — mitigare o sostituire.
- [ ] Procedura documentata di **restore** del backup (un backup non testato non è un backup).

---

## Note operative
- Macchina dev: `192.168.0.83` — **container LXC** Proxmox (non una VM), Debian 12.
  Accesso anche da remoto via Tailscale (`podo-dev`).
- Stack: Laravel 11 / PostgreSQL 16 / Redis 7 / Docker Compose, dietro Nginx Proxy Manager.
- Modifiche al `.env` richiedono `docker compose up -d`: le variabili sono iniettate
  all'avvio del container e hanno precedenza sul file.
