# Deployment — Docker multi-environment

Three environments run as **isolated Docker Compose stacks on a single server**,
each tracking its own branch and fronted by the host's Nginx reverse proxy.

| Environment | Branch    | APP_ENV | Domain                 | Loopback port | Compose project    |
| ----------- | --------- | ------- | ---------------------- | ------------- | ------------------ |
| Production  | `main`    | `prod`  | mesapplishf.fr         | 127.0.0.1:8080 | `mesapplishf-prod` |
| Test        | `release` | `prod`  | test.mesapplishf.fr    | 127.0.0.1:8081 | `mesapplishf-test` |
| Dev         | `develop` | `dev`   | dev.mesapplishf.fr     | 127.0.0.1:8082 | `mesapplishf-dev`  |

Each stack has its **own database container and volume** — data is never shared
between environments. Pushing to a branch triggers a GitHub Actions deploy of the
matching stack (`.github/workflows/deploy.yml`).

---

## 1. One-time server setup

### 1.1 Install Docker

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER   # log out/in so the deploy user can run docker
docker compose version          # confirm the Compose v2 plugin is present
```

### 1.2 DNS

Add A records pointing the two new subdomains at the server IP (production already exists):

```
test.mesapplishf.fr   A   <server-ip>
dev.mesapplishf.fr    A   <server-ip>
```

### 1.3 Clone each branch into its own directory

The GitHub workflow expects these exact paths:

```bash
sudo mkdir -p /srv/mesapplishf && sudo chown $USER:$USER /srv/mesapplishf
cd /srv/mesapplishf

git clone -b main    git@github.com:<you>/MesApplisHF.git prod
git clone -b release git@github.com:<you>/MesApplisHF.git test
git clone -b develop git@github.com:<you>/MesApplisHF.git dev
```

### 1.4 Create the real env files (secrets) — one per stack

These hold passwords and are **git-ignored**; create them on the server only.

```bash
cd /srv/mesapplishf/prod && cp .env.docker.prod.dist .env.docker.prod
cd /srv/mesapplishf/test && cp .env.docker.test.dist .env.docker.test
cd /srv/mesapplishf/dev  && cp .env.docker.dev.dist  .env.docker.dev
```

Edit each and replace every `CHANGE_ME...`. Generate strong values:

```bash
openssl rand -hex 16   # POSTGRES_PASSWORD
openssl rand -hex 25   # SYMFONY_APP_SECRET
```

Use a **different** password and secret per environment. Set a real `SYMFONY_MAILER_DSN`
on prod (and test if you test email) — otherwise verification emails are silently discarded.

### 1.5 Make the deploy script executable

```bash
chmod +x /srv/mesapplishf/*/bin/deploy-docker.sh
```

---

## 2. Reverse proxy (Nginx on the host)

Each stack listens only on `127.0.0.1:<port>`; Nginx terminates TLS and proxies to it.
**Production currently serves PHP-FPM directly — replace that server block with the
proxy version below** (back up the old config first).

`/etc/nginx/sites-available/mesapplishf` (one `server` block per domain — example for prod):

```nginx
server {
    server_name mesapplishf.fr;

    location / {
        proxy_pass         http://127.0.0.1:8080;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
    # TLS lines (listen 443 / ssl_certificate ...) are managed by certbot below.
}
```

Duplicate the block for `test.mesapplishf.fr` → `8081` and `dev.mesapplishf.fr` → `8082`,
then:

```bash
sudo ln -sf /etc/nginx/sites-available/mesapplishf /etc/nginx/sites-enabled/
sudo certbot --nginx -d mesapplishf.fr -d test.mesapplishf.fr -d dev.mesapplishf.fr
sudo nginx -t && sudo systemctl reload nginx
```

`X-Forwarded-*` headers are required — the app already trusts the private proxy network
via `SYMFONY_TRUSTED_PROXIES` in `compose.yaml`.

---

## 3. GitHub Actions secrets

Already used by the existing workflow — confirm they exist in **Settings → Secrets and
variables → Actions**:

| Secret            | Value                                            |
| ----------------- | ------------------------------------------------ |
| `SSH_HOST`        | server IP / hostname                             |
| `SSH_USER`        | deploy user (must be in the `docker` group)      |
| `SSH_PRIVATE_KEY` | private key whose public key is in `authorized_keys` |

---

## 4. First deploy

Run once per stack on the server (afterwards, pushing the branch auto-deploys):

```bash
cd /srv/mesapplishf/prod && ./bin/deploy-docker.sh prod
cd /srv/mesapplishf/test && ./bin/deploy-docker.sh test
cd /srv/mesapplishf/dev  && ./bin/deploy-docker.sh dev
```

The script fetches the branch, builds the right image target, starts the containers,
and runs migrations. Verify each domain over HTTPS.

---

## 5. Everyday operations

```bash
# Logs for one stack
docker compose -p mesapplishf-prod --env-file /srv/mesapplishf/prod/.env.docker.prod logs -f

# Run a console command
docker compose -p mesapplishf-dev --env-file /srv/mesapplishf/dev/.env.docker.dev exec app php bin/console about

# Manual redeploy / rollback (checkout a tag/commit then re-run)
cd /srv/mesapplishf/prod && ./bin/deploy-docker.sh prod
```

---

## 6. Migrating existing production data (optional)

Your current bare-metal production database is separate from the new prod container.
To carry the data over, dump from the old DB and restore into the prod stack's container:

```bash
# Dump the existing (bare-metal) database
pg_dump -U <old_user> -d <old_db> -F c -f mesapplishf.dump

# Restore into the running prod container's database
docker compose -p mesapplishf-prod --env-file .env.docker.prod cp mesapplishf.dump database:/tmp/d.dump
docker compose -p mesapplishf-prod --env-file .env.docker.prod exec database \
  pg_restore -U mesapplishf -d mesapplishf --clean --if-exists /tmp/d.dump
```

After a data restore you can skip `migrations:migrate` for that first cutover (the dump
already contains the schema). Keep the old setup running until you've verified the
container serves correctly, then switch the Nginx config.
