# Podo — istruzioni di progetto

Gestionale per podologi in cloud. È il rifacimento web di **SmartPodos**, il gestionale
desktop che lo studio usa da anni. Su questa macchina lavorano **due sviluppatori con i
propri agenti**: prima di modifiche invasive, verifica lo stato invece di dare per scontato
di essere solo.

---

## Ambiente

- Macchina `podo-dev` (`192.168.0.83`) — **container LXC Proxmox**, non una VM
  (`systemd-detect-virt` = lxc). Niente `/dev/net/tun`, moduli kernel dall'host.
- Progetto in `/root/podo`, di proprietà `www-data` (serve all'auto-update dell'app).
- Stack: **PHP 8.3 / Laravel 11** + Livewire, **PostgreSQL 16**, Redis 7,
  Blade + Tailwind + Alpine + FullCalendar (Vite), Nginx. Tutto in **Docker Compose**.
- L'app resta in HTTP sulla `:8080`, il TLS lo gestisce Nginx Proxy Manager a monte.

Tutti i comandi applicativi girano **dentro il container**:

```bash
docker compose exec -T app php artisan <comando> </dev/null
```

---

## ⚠️ Trappole verificate sul campo

Sono costate tempo: leggile prima di lavorare.

1. **`docker compose exec -T` consuma lo stdin.** Se lo usi dentro uno script passato via
   stdin (es. `ssh host 'bash -s' <<'EOF'`), tutto ciò che segue viene mangiato e lo script
   si tronca in silenzio. Aggiungi **sempre** `</dev/null`.

2. **Modificare il `.env` non ha effetto senza ricreare i container.** Docker Compose inietta
   le variabili di `env_file` all'avvio, e Laravel dà precedenza all'ambiente di sistema sul
   file. `config:cache` non basta: serve `docker compose up -d`.
   Verifica sempre con `docker compose exec -T app printenv NOME_VAR </dev/null`.

3. **`sed` mangia i backslash** su questa configurazione (sia con apici singoli che doppi):
   un `\App\Models\X` diventa `AppModelsX`. Il lint PHP **passa lo stesso** (è sintatticamente
   valido) ma a runtime la classe non esiste. Per inserire codice con namespace usa `awk`
   (li preserva) oppure riscrivi il file con un heredoc.

4. **Il repository è PUBBLICO.** Vedi la sezione sui dati qui sotto.

---

## 🔴 Dati dei pazienti — regole non negoziabili

Il database contiene **anagrafiche reali di oltre mille pazienti** di uno studio sanitario
(dati personali, contesto sanitario → categoria particolare art. 9 GDPR).

- **Mai `git add -A`.** Aggiungi i singoli path sorgente e controlla sempre
  `git diff --cached --name-only` prima di committare.
- **Mai committare** `.env` o sue copie, contenuto di `storage/app/import*`,
  `storage/app/clinical` (foto cliniche), log, cache compilate. Sono già in `.gitignore`:
  non aggirarlo.
- Gli export temporanei di dati vanno **cancellati a fine lavorazione** (l'importatore lo fa
  da solo).
- Prima di qualsiasi scrittura massiva sul DB: `docker compose exec -T app php artisan backup:run --only-db </dev/null`

---

## Cosa esiste già (non ricostruirlo)

- **Pazienti** — anagrafica, consensi GDPR, soft delete.
- **Cartella clinica** — anamnesi unica (1:1) + **visite** datate con *tipo visita*
  (podologica, onicopatie, verruca, diabetico, extra) + prestazioni erogate dal listino +
  **foto cliniche cifrate** su disco privato, servite solo via controller autenticato.
- **Listino prestazioni** — prezzo, IVA/natura FatturaPA, durata.
- **Ortesi / plantari su misura** — con stati di lavorazione.
- **Fatturazione** — bozza da visita → emissione numerata → PDF (dompdf), **XML FatturaPA
  SDI v1.2**, export Sistema TS. Bollo automatico, ritenuta configurabile.
  Le fatture possono essere **senza paziente** (lo studio fattura anche a strutture).
- **Impostazioni** (superadmin) — dati fiscali dello studio in tabella `settings`, letti da
  PDF/XML/TS via `Setting::billing()`. **Non leggere `config('podo.billing')` direttamente.**
- **Aggiornamenti applicativo** — controllo versione da Git + update con barra di avanzamento.
- **Ruoli e audit** — superadmin / admin (i superadmin gli sono invisibili) / utente = il
  paziente stesso, con portale in sola lettura. Registro audit di accessi e modifiche.
- **Importatore storico** — `php artisan podo:import-fatturapa <cartella> [--dry-run]`,
  idempotente, legge gli archivi mensili di fatture elettroniche.

---

## Convenzioni di codice

- **Italiano** per commenti, label, messaggi utente e nomi di rotte (`/fatture`, `/listino`,
  `/ortesi`, `/impostazioni`). Nomi di classi e colonne in inglese.
- Blade con le utility già definite: `.card`, `.input`, `.label`, `.btn-primary`,
  `.btn-secondary`, `.badge`. Tabella su desktop + lista su mobile.
- **Enum PHP** per gli stati (`InvoiceStatus`, `OrthosisStatus`, `VisitType`, `Role`), con
  `label()` e `color()`.
- Dati clinici **cifrati a riposo** con il cast `encrypted`. Se aggiungi campi clinici,
  cifrali.
- RBAC via middleware `role:admin` / `role:superadmin` (gerarchico: admin include superadmin).
- Audit: i model rilevanti usano `LogsActivity` di spatie. Eventi custom con
  `activity('nome')->causedBy(...)->event(...)->log(...)`.
- Migrazioni **additive**: il DB contiene dati reali, non ricreare tabelle.

---

## Migrazione dallo storico

**SmartPodos è un applicativo FileMaker Pro 12**, non Omnis (vecchi documenti dicono il
contrario: sono sbagliati). Il database `VSMP530.smp` ha firma `HBAM7` e tiene i dati in
blocchi compressi: non è leggibile con string-scraping, serve FileMaker o l'export dal
runtime.

Già importato dagli XML FatturaPA ufficiali: **anagrafiche e fatturato**.
Mancano **agenda storica, cartelle cliniche e il legame foto↔paziente**, che vivono solo
nel FileMaker.

⚠️ **Coordinatevi prima di importare.** Il database non è vuoto: due import sovrapposti
producono anagrafiche duplicate e conflitti di numerazione fatture. Verifica i conteggi
prima di scrivere e preferisci passaggi **additivi** con match sul **codice fiscale**
(deterministico) invece che su nome/telefono (ambiguo: con oltre mille pazienti gli omonimi
sono certi).

---

## Stato e prossimi passi

Il lavoro residuo è tracciato in **`docs/ROADMAP.md`** — leggilo prima di proporre attività.
I punti bloccanti per la produzione: recupero password + SMTP (assente), backup off-site,
riattivazione MFA, dati fiscali completi e validazione XML contro lo schema SDI, HTTPS,
pulizia dei dati demo.

---

## Versioning e rilascio (agg. 2026-07-23)

Versione in `VERSION` (SemVer). Attuale **0.2.0**. Schema: patch `0.2.1 … 0.2.99` → minor `0.3.0`,
fino a `1.0.0` alla messa in produzione. **Mai** diminuire la versione: l'updater usa
`version_compare` e non rileverebbe l'aggiornamento.

Branch canonico: **`main`** (l'updater punta lì). Non creare più branch paralleli:
al momento lavora **un solo agente**.

Procedura di rilascio:
1. aggiorna `VERSION` col nuovo numero;
2. sposta le voci da `## [Non rilasciato]` a `## [x.y.z] - AAAA-MM-GG` in `CHANGELOG.md`;
3. commit (path espliciti, mai `git add -A`);
4. `git tag vX.Y.Z` sul commit;
5. `git push origin main && git push origin vX.Y.Z`.

L'aggiornamento in-app (Impostazioni → Aggiornamenti) rileva la nuova `VERSION` su
`origin/main` ed esegue: backup DB → pull → composer → migrate → assets → cache.

> Proprietà file: il repo è in `/root/podo` (dir 700) e i file sono di `www-data` (serve al
> `git pull` dell'updater, che gira come www-data nel container). Se esegui git come root
> sull'host, **ripristina** dopo: `chown -R www-data:www-data /root/podo`.
