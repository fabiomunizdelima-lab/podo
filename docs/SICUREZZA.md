# Sicurezza e conformità — mappatura della checklist

Questo documento mappa la *Checklist Sicurezza SaaS/Cloud – Dati Sensibili* fornita
rispetto a ciò che è **implementato nel codice** di Podo, ciò che è a carico
dell'**infrastruttura/VPS** (configurazione da fare in fase di deploy) e ciò che
richiede **processi organizzativi o certificazioni** esterne al software.

Legenda: ✅ implementato nel codice · 🛠️ da configurare in infrastruttura · 📋 processo/organizzativo · 🔜 Fase 2

---

## Autenticazione
| Voce | Stato | Note |
|------|-------|------|
| MFA | ✅ | TOTP obbligatorio per admin/superadmin (`EnforceMfa`, `MfaController`) |
| Argon2id | ✅ | `config/hashing.php` driver `argon2id`, 64 MiB |
| RBAC | ✅ | Ruoli Superadmin/Admin/Utente (`EnsureRole`, enum `Role`) |
| Least Privilege | ✅ | Rotte protette per ruolo minimo; hard-delete solo admin+ |
| Session Management | ✅ | Sessioni Redis cifrate, `SameSite=Strict`, secure cookie, rigenerazione su login |
| SSO / SAML / OIDC / SCIM | 🔜 | Predisposto (Sanctum); SSO enterprise valutabile in seguito |

## Crittografia
| Voce | Stato | Note |
|------|-------|------|
| TLS 1.3 | ✅/🛠️ | Nginx TLS 1.2/1.3, HSTS, forza HTTPS; certificati da fornire (Let's Encrypt) |
| AES-256 | ✅ | Cast `encrypted` su note cliniche, segreti MFA, token Google (chiave `APP_KEY`) |
| Database Encryption | ✅/🛠️ | Campi sensibili cifrati a livello applicativo; cifratura volume DB lato VPS |
| Backup Encryption | ✅ | `spatie/laravel-backup` con password di cifratura |
| Key Rotation / KMS/HSM | 🛠️ | Rotazione `APP_KEY` e gestione segreti via vault/KMS del provider |
| Certificate Management | 🛠️ | Let's Encrypt / rinnovo automatico |

## Sicurezza applicativa
| Voce | Stato | Note |
|------|-------|------|
| OWASP Top 10 | ✅ | Eloquent (anti-SQLi), escaping Blade (anti-XSS), CSRF token Laravel |
| Protezione CSRF/XSS/SQLi | ✅ | Middleware CSRF, output encoding, query parametrizzate |
| Content Security Policy | ✅ | `SecurityHeaders` con nonce. **TODO**: rimuovere `'unsafe-eval'` migrando Alpine alla build CSP |
| Rate Limiting | ✅ | Login (`throttle:login`) e API |
| Security headers | ✅ | HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| API Security | ✅ | Sanctum, validazione input su ogni controller |
| Secure SDLC / Code Review | 📋 | Processo: PR review su GitHub, branch protetti |
| SAST / DAST / Dependency/Secret/Container Scanning | 🛠️ | Pipeline CI (larastan, `security-checker`, `npm audit`, Trivy). Config CI da aggiungere |

## Logging e monitoraggio
| Voce | Stato | Note |
|------|-------|------|
| Audit Log | ✅ | Canale `audit` + `spatie/activitylog` su pazienti/appuntamenti/utenti/accessi |
| Logging amministratori | ✅ | Azioni admin tracciate (creazione/modifica utenti, login) |
| Conservazione log | ✅ | Retention configurabile (audit 2 anni) |
| Centralizzazione / SIEM / Alerting / H24 | 🛠️📋 | Spedire i log a un SIEM esterno; monitoraggio è servizio gestito |
| Log immutabili | 🛠️ | Storage append-only / WORM lato infrastruttura |

## Backup e continuità
| Voce | Stato | Note |
|------|-------|------|
| Backup automatici / cifrati | ✅ | Scheduler giornaliero, cifrati |
| Backup off-site / immutabili | 🛠️ | Destinazione S3/object storage con object-lock |
| Disaster Recovery / BC / RTO / RPO | 📋 | Piano documentato + test di ripristino periodici |

## Sicurezza infrastrutturale (🛠️ VPS/cloud)
Firewall, WAF, IDS/IPS, segmentazione rete, VPN amministrative, bastion host, Zero Trust,
hardening OS, patch/vulnerability/configuration management, asset inventory: **da configurare
sul VPS**. Il `docker-compose` non espone la porta del DB (segmentazione di base) e i container
girano con utente non-root.

## Compliance GDPR (parziale ✅, resto 📋)
- Privacy by Design & Default: ✅ minimizzazione, cifratura, consensi espliciti, soft-delete.
- Informativa privacy, registro trattamenti, DPIA, DPA, procedura data breach: bozze in `docs/gdpr/` (📋 da completare con il DPO/consulente).
- Nomina DPO, NIS2: 📋 valutazione caso per caso.

## Certificazioni e test (📋 esterni)
ISO 27001/27017/27018/27701, SOC 2, CSA STAR, PCI DSS, penetration test, red/purple team,
bug bounty, security assessment: **non realizzabili via codice** — richiedono auditor/fornitori
esterni e budget dedicato. Il software è progettato per **superare** questi audit sul piano tecnico.

## Formazione (📋)
Security awareness, simulazioni phishing, formazione GDPR/NIS2, secure coding: attività per il personale.

---

## Hardening TODO prioritari (prima del go-live in produzione)
1. Certificati TLS reali (Let's Encrypt) + rinnovo automatico.
2. Migrare Alpine.js alla **build CSP** per eliminare `'unsafe-eval'` dalla CSP.
3. Backup off-site su object storage con **object-lock** (immutabilità).
4. Pipeline **CI** con SAST (larastan), dependency scan, secret scan, container scan (Trivy).
5. Spedizione log a **SIEM** esterno + alerting.
6. Firewall/WAF davanti all'app (es. reverse proxy gestito o Cloudflare).
7. Completare la documentazione GDPR con il consulente privacy.
