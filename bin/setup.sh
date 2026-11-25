#!/usr/bin/env bash
# One-command local install: WordPress + Polylang + BCL theme/plugin + 3-language seed content.
# Safe to re-run (seed is idempotent).
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  printf 'DB_PASSWORD=%s\nADMIN_USER=bcladmin\nADMIN_PASSWORD=%s\nADMIN_EMAIL=admin@bcl.local\nWP_PORT=8090\nMAIL_UI_PORT=8025\n' \
    "$(openssl rand -hex 16)" "$(openssl rand -base64 18 | tr -d '/+=')" > .env
  chmod 600 .env
  echo "Created .env with random passwords."
fi
set -a; . ./.env; set +a
URL="http://localhost:${WP_PORT}"

docker compose up -d db wordpress mailpit
wp() { docker compose run --rm -T cli wp "$@"; }

echo "Waiting for WordPress files…"
until docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; do sleep 2; done

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="$URL" --title="BCL – Blue Crane Logistics" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASSWORD" --admin_email="$ADMIN_EMAIL" --skip-email
fi

wp option update timezone_string 'Asia/Ho_Chi_Minh'
wp option update date_format 'd/m/Y'
wp option update blog_public 1
wp rewrite structure '/%postname%/'
wp language core install vi zh_CN
wp site switch-language vi
wp user update "$ADMIN_USER" --locale=vi >/dev/null

wp plugin is-installed polylang || wp plugin install polylang
wp plugin activate polylang bcl-core
wp language plugin install polylang vi zh_CN || true
wp theme activate bcl
wp plugin delete akismet hello >/dev/null 2>&1 || true

wp eval-file /seed/seed.php
wp rewrite flush

echo
echo "Done."
echo "  Website : $URL   (EN: $URL/en/  ZH: $URL/zh/)"
echo "  Admin   : $URL/wp-admin  — user/password in .env (ADMIN_USER / ADMIN_PASSWORD)"
echo "  Mailbox : http://localhost:${MAIL_UI_PORT} (Mailpit, catches RFQ emails)"
