# Podo — Gestionale per podologi (cloud)

Rifacimento moderno, web e responsive, del gestionale **SmartPodos** (originariamente
un'applicazione desktop Omnis Studio per Windows). Pensato per girare in cloud su
**Debian 12**, accessibile da smartphone, tablet e desktop, con particolare attenzione
alla **sicurezza** e alla **conformità GDPR** (dati sanitari — categoria particolare art. 9).

> Titolare del trattamento di riferimento: Dott. Fabio Luiz Muniz De Lima (Abbiategrasso, MI).

---

## Funzionalità (Fase 1)

- 🔐 **Autenticazione** con ruoli **Superadmin / Admin / Utente** (RBAC), password Argon2id, **MFA (TOTP)** obbligatoria per gli amministratori.
- 👥 **Anagrafica pazienti** con dati di contatto, codice fiscale, note cliniche cifrate e gestione dei **consensi GDPR**.
- 📅 **Agenda / calendario** responsive (giorno/settimana/mese) con creazione rapida degli appuntamenti.
- 🔄 **Sincronizzazione Google Calendar** (OAuth2, bidirezionale sugli eventi creati dall'app).
- 💬 **Promemoria WhatsApp** automatici tramite **WhatsApp Business Cloud API** (Meta), nel rispetto del consenso del paziente.
- 🧾 **Audit log** completo (chi ha fatto cosa e quando).
- 💾 **Backup** cifrati automatici.

### Fase 2 (pianificata)

- Fatturazione elettronica **FatturaPA / SDI** (già mappato il modello dagli export XML dell'app attuale).
- Invio spese sanitarie al **Sistema TS / Tessera Sanitaria**.
- Importazione dati storici dagli export (XML fatture, XLSX).

---

## Stack tecnologico

| Componente | Tecnologia |
|-----------|-----------|
| Backend | PHP 8.3, **Laravel 11** |
| Database | **PostgreSQL 16** |
| Cache/Code/Sessioni | Redis 7 |
| Frontend | Blade + **Tailwind CSS** + Alpine.js + FullCalendar (build con Vite) |
| Web server | Nginx (TLS 1.2/1.3) |
| Deploy | Docker Compose |

---

## Avvio rapido (Docker, consigliato)

Prerequisiti su Debian 12: `docker` e `docker compose` (vedi [deploy/debian-setup.md](deploy/debian-setup.md)).

```bash
git clone https://github.com/fabiomunizdelima-lab/podo.git
cd podo
cp .env.example .env
# Modifica .env: password DB, dominio, credenziali WhatsApp/Google
# Metti i certificati TLS in docker/nginx/certs/ (fullchain.pem, privkey.pem)
docker compose up -d --build
```

All'avvio il container esegue automaticamente: `composer install`, build asset,
`migrate`, cache di config/route/view. Poi crea gli utenti iniziali:

```bash
docker compose exec app php artisan db:seed
```

Le password generate vengono mostrate in console **una sola volta** — annotatele e
cambiatele al primo accesso. Gli utenti admin/superadmin dovranno attivare l'MFA.

---

## Sviluppo locale (senza Docker)

Richiede PHP 8.3, Composer, Node 20, PostgreSQL, Redis.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev            # in un terminale
php artisan serve      # in un altro
```

---

## Configurazione integrazioni

### WhatsApp (promemoria)
1. Crea un'app su [Meta for Developers](https://developers.facebook.com/) → prodotto **WhatsApp**.
2. Ottieni `Phone Number ID` e un `Access Token` permanente.
3. Crea e fai approvare un **template** di messaggio (es. `promemoria_appuntamento`) con due parametri: `{{1}}` nome, `{{2}}` data/ora.
4. Compila in `.env`: `WHATSAPP_ENABLED=true`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_TEMPLATE_NAME`.

Lo scheduler invia i promemoria automaticamente (default: 24h prima). Serve il consenso WhatsApp del paziente.

### Google Calendar
1. Console Google Cloud → crea credenziali **OAuth 2.0** (tipo *Web application*).
2. Redirect URI: `https://TUO-DOMINIO/oauth/google/callback`.
3. Compila `.env`: `GOOGLE_CALENDAR_ENABLED=true`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`.
4. Ogni operatore collega il proprio account dal menu laterale → *Collega Google Calendar*.

---

## Sicurezza e conformità

Vedi **[docs/SICUREZZA.md](docs/SICUREZZA.md)** per la mappatura puntuale della
*Checklist Sicurezza SaaS/Cloud* fornita, con l'indicazione di cosa è implementato
nel codice, cosa è a carico dell'infrastruttura/VPS e cosa richiede processi
organizzativi o certificazioni esterne.

Documentazione GDPR di base in **[docs/gdpr/](docs/gdpr/)**.

---

## Struttura del progetto

```
app/            Codice applicativo (Models, Controllers, Middleware, Services, Enums)
config/         Configurazioni (hashing Argon2id, podo.php, logging/audit)
database/       Migrazioni e seeder
resources/      Viste Blade + asset frontend (CSS/JS)
routes/         web.php, console.php (scheduler)
docker/         Dockerfile PHP, Nginx, php.ini
deploy/         Guida di installazione su Debian 12
docs/           Sicurezza e GDPR
```

## Origine dati (migrazione da SmartPodos)

Il database originale è il file binario Omnis `VSMP530.smp`, non leggibile senza
Omnis Studio. La migrazione dei dati storici avverrà (Fase 2) tramite gli **export**
disponibili: XML delle fatture elettroniche (contengono anagrafiche e prestazioni),
file XLSX dei fatturati, dati Sistema TS. Il modello dati di Podo è già compatibile
con queste strutture.
