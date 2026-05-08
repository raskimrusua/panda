# Panda — Provisioning Checklist

**One-time developer setup before the UWC pipeline starts P1.** All commands assume `~/Desktop/panda/` as the working directory unless noted. ~1 hour total.

---

## 0. Prerequisites verified

- [ ] `gh` CLI authenticated as `raskimrusua` (`gh auth status`)
- [ ] `wrangler` CLI installed (`wrangler --version` → 3.x)
- [ ] SSH access to Hetzner (`ssh root@178.104.138.140 'hostname'` → `Shira`)
- [ ] Access to Shira's `.env.deploy` at `~/Desktop/farmcore/website/.env.deploy` (contains the joshkim04 CF token)

---

## 1. GitHub repo

```bash
gh repo create raskimrusua/panda \
  --private \
  --description "Panda — horticulture farming OS, sister to Shira"
```

- [ ] Repo created
- [ ] Branch protection set on `main`: require PR + green CI before merge
  ```bash
  gh api repos/raskimrusua/panda/branches/main/protection \
    --method PUT \
    --field required_pull_request_reviews='{"required_approving_review_count":1}' \
    --field required_status_checks='{"strict":true,"contexts":["api-lint","api-test","pwa-typecheck","marketing-build"]}'
  ```

---

## 2. Cloudflare resources

Source the joshkim04 token first:
```bash
cd ~/Desktop/farmcore/website && set -a && . ./.env.deploy && set +a && cd ~/Desktop/panda
```

```bash
# D1
wrangler d1 create panda-marketing-db
# → copy database_id into marketing/wrangler.toml

# R2
wrangler r2 bucket create panda-media

# KV
wrangler kv namespace create PANDA_RATE_LIMIT
# → copy id into marketing/wrangler.toml
wrangler kv namespace create PANDA_SESSION
# → copy id into marketing/wrangler.toml

# Pages projects
wrangler pages project create panda-marketing --production-branch=main
wrangler pages project create panda-pwa --production-branch=main
```

- [ ] D1 created, ID captured
- [ ] R2 bucket created
- [ ] Both KV namespaces created, IDs captured
- [ ] Both Pages projects created

---

## 3. DNS records

Three records under the `shira.farm` zone (CF dashboard or wrangler):

| Type | Name | Content | Proxy |
|---|---|---|---|
| CNAME | `panda` | `panda-marketing.pages.dev` | Proxied (orange) |
| CNAME | `app.panda` | `panda-pwa.pages.dev` | Proxied (orange) |
| A | `api.panda` | `178.104.138.140` | Proxied (orange) |

Via wrangler (requires DNS edit perm on the zone):
```bash
wrangler dns:record create shira.farm --type=CNAME --name=panda --content=panda-marketing.pages.dev --proxied
wrangler dns:record create shira.farm --type=CNAME --name=app.panda --content=panda-pwa.pages.dev --proxied
wrangler dns:record create shira.farm --type=A --name=api.panda --content=178.104.138.140 --proxied
```

Verify:
```bash
dig panda.shira.farm +short            # CF IPs
dig app.panda.shira.farm +short        # CF IPs
dig api.panda.shira.farm +short        # CF IPs (proxy mode = CF IPs, not Hetzner)
```

- [ ] 3 DNS records added
- [ ] All resolve to CF IPs (proxied)

---

## 4. SSL certificates

### a) Cloudflare Advanced Certificate for nested subdomains

`*.shira.farm` (Universal) does NOT cover `*.panda.shira.farm`. Need an Advanced Cert.

CF Dashboard → SSL/TLS → Edge Certificates → "Order Advanced Certificate":
- Hostnames: `panda.shira.farm`, `*.panda.shira.farm`
- CA: Let's Encrypt
- Validity: 1 year (auto-renews)
- Validation: HTTP (auto-completes since DNS is on CF)

Wait ~5 min for status: Active.

- [ ] Advanced Cert ordered
- [ ] Status: Active

### b) Hetzner Origin Certificate re-issue

Existing cert at `/etc/ssl/cloudflare/origin.pem` covers `*.shira.farm` only. Need to re-issue with the new SAN.

CF Dashboard → SSL/TLS → Origin Server → "Create Certificate":
- Hostnames: `shira.farm`, `*.shira.farm`, `panda.shira.farm`, `*.panda.shira.farm`
- Validity: 15 years
- Key type: ECC

Save the PEM + key, scp to Hetzner:
```bash
# Locally — paste the new cert content
cat > /tmp/origin-new.pem << 'EOF'
<paste cert>
EOF
cat > /tmp/origin-new.key << 'EOF'
<paste key>
EOF

# Upload + replace + reload
scp /tmp/origin-new.pem root@178.104.138.140:/etc/ssl/cloudflare/origin.pem.new
scp /tmp/origin-new.key root@178.104.138.140:/etc/ssl/cloudflare/origin.key.new
ssh root@178.104.138.140 << 'EOF'
mv /etc/ssl/cloudflare/origin.pem /etc/ssl/cloudflare/origin.pem.bak
mv /etc/ssl/cloudflare/origin.key /etc/ssl/cloudflare/origin.key.bak
mv /etc/ssl/cloudflare/origin.pem.new /etc/ssl/cloudflare/origin.pem
mv /etc/ssl/cloudflare/origin.key.new /etc/ssl/cloudflare/origin.key
chmod 600 /etc/ssl/cloudflare/origin.key
docker compose -f /srv/farmcore/docker-compose.prod.yml restart nginx
EOF

# Cleanup local
rm /tmp/origin-new.{pem,key}
```

Verify:
```bash
echo | openssl s_client -connect api.shira.farm:443 -servername api.shira.farm 2>&1 | grep -E "subject|DNS:"
# Should list panda.shira.farm + *.panda.shira.farm in DNS SAN list
```

- [ ] New Origin Cert generated covering all SANs
- [ ] Uploaded + nginx reloaded
- [ ] SSL still works for existing api.shira.farm
- [ ] SSL ready for future api.panda.shira.farm

---

## 5. Hetzner — Postgres DB + directory

```bash
ssh root@178.104.138.140 << 'EOF'
docker exec farmcore_db psql -U farmcore <<SQL
CREATE DATABASE panda_production;
CREATE USER panda WITH ENCRYPTED PASSWORD 'CHANGEME-rotate-after-deploy';
GRANT ALL PRIVILEGES ON DATABASE panda_production TO panda;
\\c panda_production
GRANT ALL ON SCHEMA public TO panda;
SQL
mkdir -p /srv/panda
chown root:root /srv/panda
EOF
```

- [ ] DB `panda_production` created
- [ ] User `panda` created (rotate password after first deploy)
- [ ] `/srv/panda` directory exists

**Note:** the user password above is a placeholder. Rotate it the moment Laravel `.env` is on the box.

---

## 6. .env.deploy

```bash
cp ~/Desktop/farmcore/website/.env.deploy ~/Desktop/panda/.env.deploy
echo ".env.deploy" >> ~/Desktop/panda/.gitignore
echo "vendor/" >> ~/Desktop/panda/.gitignore
echo "node_modules/" >> ~/Desktop/panda/.gitignore
echo ".env" >> ~/Desktop/panda/.gitignore
echo ".env.local" >> ~/Desktop/panda/.gitignore
```

- [ ] `.env.deploy` copied (same joshkim04 token as Shira)
- [ ] `.gitignore` includes secrets + build artefacts

---

## 7. Pages secrets — defer until P5/P6 launch

These secrets are NOT needed until the marketing site has admin endpoints. **Skip in this provisioning round.** Documented in the plan under "Pages secrets to provision."

When the time comes:
```bash
cd ~/Desktop/panda/marketing
set -a && . ../.env.deploy && set +a
echo "<value>" | wrangler pages secret put ADMIN_PASSWORD --project-name panda-marketing
# ... (full list in plan)
```

---

## 8. Final verification

```bash
# CF resources
wrangler d1 list                     | grep panda-marketing-db
wrangler r2 bucket list              | grep panda-media
wrangler kv namespace list           | grep -E 'PANDA_RATE_LIMIT|PANDA_SESSION'
wrangler pages project list          | grep -E 'panda-marketing|panda-pwa'

# GH repo
gh repo view raskimrusua/panda --json name,visibility,defaultBranch

# DNS
for h in panda app.panda api.panda; do
  echo "$h.shira.farm -> $(dig +short $h.shira.farm | head -1)"
done

# Hetzner readiness
ssh root@178.104.138.140 \
  "docker exec farmcore_db psql -U farmcore -lqt | grep panda_production && ls -la /srv/panda"
```

All five sections should return non-empty results.

---

## You're done. Hand off to the pipeline.

```bash
cd ~/Desktop/panda
# Open a new Claude Code session here
# Paste docs/MASTER_PROMPT.md as the first message
# Then: /new-app panda
# Pipeline does the rest
```

---

## Rollback (if something goes wrong mid-provisioning)

```bash
# CF
wrangler d1 delete panda-marketing-db --skip-confirmation
wrangler r2 bucket delete panda-media
wrangler kv namespace delete --namespace-id=<id>
wrangler pages project delete panda-marketing
wrangler pages project delete panda-pwa

# DNS — manual via CF dashboard

# Hetzner
ssh root@178.104.138.140 "docker exec farmcore_db psql -U farmcore -c 'DROP DATABASE panda_production;'"
ssh root@178.104.138.140 "docker exec farmcore_db psql -U farmcore -c 'DROP USER panda;'"
ssh root@178.104.138.140 "rm -rf /srv/panda"

# GH
gh repo delete raskimrusua/panda --yes
```

Don't roll back unless something is genuinely broken — the listed resources are cheap to keep around.
