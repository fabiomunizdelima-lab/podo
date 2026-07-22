# Installazione su Debian 12 (VPS cloud)

Guida per portare Podo in produzione su una VPS Debian 12.

## 1. Prerequisiti di sistema

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y ca-certificates curl gnupg git ufw fail2ban
```

### Firewall (checklist: firewall, hardening)
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Hardening SSH (consigliato)
- Disabilitare login root e autenticazione password (`/etc/ssh/sshd_config`: `PermitRootLogin no`, `PasswordAuthentication no`), usare solo chiavi.
- `fail2ban` già installato protegge da brute force SSH.

## 2. Docker

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian bookworm stable" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER   # poi ri-login
```

## 3. Deploy applicazione

```bash
git clone https://github.com/fabiomunizdelima-lab/podo.git
cd podo
cp .env.example .env
```

Modifica `.env`:
- `APP_URL` = dominio (es. `https://gestionale.tuostudio.it`)
- `APP_KEY` lascialo vuoto (viene generato all'avvio)
- Password DB e backup robuste
- Credenziali WhatsApp e Google quando pronte

## 4. Certificati TLS (Let's Encrypt)

Opzione consigliata: certbot in modalità standalone/webroot, poi montare i file.

```bash
sudo apt install -y certbot
sudo certbot certonly --standalone -d gestionale.tuostudio.it
# Copia i certificati dove Nginx del compose li cerca:
mkdir -p docker/nginx/certs
sudo cp /etc/letsencrypt/live/gestionale.tuostudio.it/fullchain.pem docker/nginx/certs/
sudo cp /etc/letsencrypt/live/gestionale.tuostudio.it/privkey.pem  docker/nginx/certs/
sudo chown $USER: docker/nginx/certs/*.pem
```

Imposta un rinnovo automatico (cron/systemd timer) che ricopia i file e ricarica Nginx.

## 5. Avvio

```bash
docker compose up -d --build
docker compose exec app php artisan db:seed   # crea superadmin/admin/utente
docker compose logs -f app                    # verifica migrazioni/build
```

Apri `https://gestionale.tuostudio.it` e accedi con le credenziali mostrate dal seeder.

## 6. Operazioni comuni

```bash
# Aggiornare l'app
git pull && docker compose up -d --build

# Backup manuale
docker compose exec app php artisan backup:run

# Log audit
docker compose exec app tail -f storage/logs/audit.log

# Creare un utente da CLI (tinker)
docker compose exec app php artisan tinker
```

## 7. Backup off-site (raccomandato)
Configura in `.env` un disco S3-compatibile (`BACKUP_DISK`) con **object-lock**
per backup immutabili (checklist: backup immutabili/off-site).

## 8. Aggiornamenti di sicurezza
Abilita gli aggiornamenti automatici del sistema operativo:
```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```
