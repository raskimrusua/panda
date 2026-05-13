# Panda — Hetzner deployment runbook

> Co-located on Shira's CX22 (178.104.138.140). Shares Postgres, Redis,
> nginx, Cloudflare Origin Cert. Adds `panda_app` + `panda_horizon`
> containers and a single nginx vhost for `api.panda.shira.farm`.

## Prerequisites

| Need | How |
|---|---|
| SSH access to `root@178.104.138.140` | `ssh root@178.104.138.140` |
| `gh` auth as `raskimrusua` (for git clone over HTTPS, not strictly needed once repo is public) | `gh auth switch -u raskimrusua` |
| The current `REDIS_PASSWORD` from Shira's `/root/farmcore/.env` | `grep REDIS_PASSWORD /root/farmcore/.env` (only Joshua should run this — value is a secret) |
| The current `POSTGRES_PASSWORD` from Shira's `.env` | Same — only Joshua reads this |
| The `RESEND_API_KEY` from Shira's `.env` | Same |

## One-time provisioning order

### 1. Bootstrap `/srv/panda` on the server

```bash
ssh root@178.104.138.140

# Clone the public Panda repo into /srv/panda
git clone https://github.com/raskimrusua/panda.git /srv/panda
cd /srv/panda

# Copy the .env template + fill in the gaps
cp .env.production.example .env
chmod 600 .env

# Generate APP_KEY (Laravel-format)
docker run --rm composer:2 sh -c '
  curl -s https://raw.githubusercontent.com/laravel/laravel/11.x/.env.example > /dev/null;
  echo "APP_KEY=base64:$(openssl rand -base64 32)"
'
# Paste the APP_KEY=... line into /srv/panda/.env

# Generate a fresh DB_PASSWORD (Joshua keeps this in his password manager)
echo "DB_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
# Paste the DB_PASSWORD=... line into /srv/panda/.env

# Joshua: also paste REDIS_PASSWORD + RESEND_API_KEY from Shira's .env into /srv/panda/.env
```

### 2. Create `panda_production` DB + `panda` user on the shared Postgres

```bash
# Read the Shira POSTGRES_PASSWORD ONCE; the variable is local to this shell:
read -s POSTGRES_ADMIN_PASSWORD            # paste Shira's POSTGRES_PASSWORD, hit Enter
read -s NEW_PANDA_DB_PASSWORD              # paste the DB_PASSWORD you generated above

docker exec -e PGPASSWORD="$POSTGRES_ADMIN_PASSWORD" -i farmcore_db \
  psql -U farmcore -d postgres <<SQL
CREATE DATABASE panda_production;
CREATE USER panda WITH ENCRYPTED PASSWORD '$NEW_PANDA_DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE panda_production TO panda;
\c panda_production
GRANT ALL ON SCHEMA public TO panda;
SQL

unset POSTGRES_ADMIN_PASSWORD NEW_PANDA_DB_PASSWORD
```

Verify:
```bash
docker exec -e PGPASSWORD="<panda_password>" farmcore_db \
  psql -h farmcore_db -U panda -d panda_production -c '\conninfo'
```

### 3. Build + start Panda containers

```bash
cd /srv/panda
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# First-run migrations + content cache
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan panda:reload-content
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=DealerSeeder --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=MarketPriceSeeder --force

# Verify both containers healthy
docker compose -f docker-compose.prod.yml ps
```

### 4. Wire the nginx vhost (additive — does NOT modify any Shira block)

The cleanest pattern is to mount Panda's vhost into Shira's nginx via an
`include` directive at the bottom of `/root/farmcore/nginx.conf`'s `http {}`
block, then mount `/srv/panda/infra/nginx/` into the `farmcore_nginx` container.

```bash
# 1. Edit Shira's nginx.conf to add the include line
#    (manual — keep all existing Shira blocks untouched)
nano /root/farmcore/nginx.conf
# Inside `http { ... }`, BEFORE the closing brace of http, add:
#     include /etc/nginx/extra-vhosts/*.conf;

# 2. Edit Shira's docker-compose.prod.yml to mount the panda vhost dir
nano /root/farmcore/docker-compose.prod.yml
# In the nginx service's `volumes:` block, append:
#     - /srv/panda/infra/nginx:/etc/nginx/extra-vhosts:ro

# 3. Validate the merged config (test mode — does NOT reload yet)
docker compose -f /root/farmcore/docker-compose.prod.yml \
  run --rm --entrypoint nginx nginx -t \
  -v /srv/panda/infra/nginx:/etc/nginx/extra-vhosts:ro

# 4. Recreate ONLY the nginx container with the new mount + config
cd /root/farmcore
docker compose -f docker-compose.prod.yml up -d nginx

# 5. Smoke test that Shira still works
curl -I https://api.shira.farm/health/        # expect 200
curl -I https://api.panda.shira.farm/up       # expect 200 (after DNS + cert below)
```

### 5. Cloudflare Origin Certificate — reissue with Panda SAN

The current cert at `/etc/ssl/cloudflare/origin.pem` only covers
`shira.farm` and `*.shira.farm`. Panda subdomain `*.panda.shira.farm` is
nested two levels deep and is NOT in the SAN.

In the CF dashboard (joshkim04 account → SSL/TLS → Origin Server):

1. Click "Create Certificate"
2. Hostnames: `shira.farm, *.shira.farm, panda.shira.farm, *.panda.shira.farm`
3. Validity: 15 years (default)
4. Save the new `.pem` and `.key` to the server:
   ```bash
   nano /etc/ssl/cloudflare/origin.pem      # paste new cert
   nano /etc/ssl/cloudflare/origin.key      # paste new key
   chmod 600 /etc/ssl/cloudflare/origin.key
   ```
5. Reload nginx (no recreate needed — same mount path):
   ```bash
   docker exec farmcore_nginx nginx -s reload
   ```

### 6. DNS — `api.panda.shira.farm`

In CF dashboard (or via wrangler if scripted):

| Type | Name | Content | Proxy |
|---|---|---|---|
| A | `api.panda` | `178.104.138.140` | ✅ Proxied |

Verification (~30s after add):
```bash
dig api.panda.shira.farm +short          # should return CF IPs
curl -I https://api.panda.shira.farm/up  # should return 200 from Laravel
```

## Smoke checks

```bash
# Health endpoint
curl -fs https://api.panda.shira.farm/up
# → "{\"status\":\"ok\",\"checks\":[...]}"

# Health endpoint via Laravel HealthController
curl -fs https://api.panda.shira.farm/api/v1/health
# → {"status":"healthy","checks":{...}}

# Crops public catalogue (no auth)
curl -fs https://api.panda.shira.farm/api/v1/crops
# → {"data":[{"slug":"tomato",...}]}

# Horizon dashboard (admin-only — should 401 without superuser session)
curl -I https://api.panda.shira.farm/horizon
# → 302 redirect to login

# Check Shira still works (regression)
curl -I https://api.shira.farm/health/
# → 200 OK
```

## First superuser (Filament admin)

```bash
docker compose -f /srv/panda/docker-compose.prod.yml exec app php artisan tinker
>>> $tenant = App\Models\Tenant::factory()->meru()->create(['name' => 'Demo Farm']);
>>> $u = App\Models\User::factory()->superuser()->create(['tenant_id' => $tenant->id, 'name' => 'Joshua', 'email' => 'joshkim04@gmail.com']);
>>> $u->createToken('demo')->plainTextToken;   // copy this for Bearer auth
>>> exit
```

Then visit `https://api.panda.shira.farm/admin` — Filament panel should
log in with `joshkim04@gmail.com` + the password baked by `UserFactory`
(default: `password`). Change it immediately via the Filament profile page.

## Rollback

If anything breaks Shira:

```bash
# 1. Revert Shira nginx changes
cd /root/farmcore
git checkout nginx.conf docker-compose.prod.yml
docker compose -f docker-compose.prod.yml up -d nginx

# 2. Stop Panda (Shira keeps running independently)
cd /srv/panda
docker compose -f docker-compose.prod.yml down

# 3. (Optional) drop the panda DB
docker exec -e PGPASSWORD="$POSTGRES_ADMIN_PASSWORD" farmcore_db \
  psql -U farmcore -d postgres -c "DROP DATABASE panda_production;"
docker exec -e PGPASSWORD="$POSTGRES_ADMIN_PASSWORD" farmcore_db \
  psql -U farmcore -d postgres -c "DROP USER panda;"
```
