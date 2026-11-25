#!/usr/bin/env bash
# End-to-end checks against the local stack: pages, SEO basics, RFQ form (happy path + abuse cases), email, file protection.
# Usage: ./bin/test.sh   (after ./bin/setup.sh)
set -uo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
BASE="http://localhost:${WP_PORT}"
MAIL="http://localhost:${MAIL_UI_PORT}"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
pass=0; fail=0
ok()  { echo "  ✓ $1"; pass=$((pass+1)); }
ko()  { echo "  ✗ $1"; fail=$((fail+1)); }
check() { if eval "$2"; then ok "$1"; else ko "$1"; fi; }
wp() { docker compose run --rm -T cli wp "$@" 2>/dev/null; }

echo "Pages (3 languages)"
for u in / /en/ /zh/ /gioi-thieu/ /en/about-us/ /services/ /en/services/ /zh/services/ /routes/ /en/routes/ /zh/routes/ /tin-tuc/ /en/news/ /yeu-cau-bao-gia/ /en/request-a-quote/ /lien-he/ /en/contact/ /wp-sitemap.xml; do
  code=$(curl -s -o "$TMP/p.html" -w '%{http_code}' "$BASE$u")
  check "$u → 200" '[ "$code" = 200 ] && ! grep -qiE "fatal error|<b>warning</b>|<b>notice</b>" "$TMP/p.html"'
done
check "unknown URL → 404" '[ "$(curl -s -o /dev/null -w "%{http_code}" "$BASE/khong-ton-tai/")" = 404 ]'

echo "SEO"
curl -s "$BASE/en/" > "$TMP/en.html"
check "hreflang vi/en/zh on /en/" 'grep -q "hreflang=\"vi\"" "$TMP/en.html" && grep -q "hreflang=\"en\"" "$TMP/en.html" && grep -qi "hreflang=\"zh" "$TMP/en.html"'
check "meta description" 'grep -q "<meta name=\"description\"" "$TMP/en.html"'
check "JSON-LD Organization" 'grep -q "\"@type\":\"Organization\"" "$TMP/en.html"'
check "html lang=en-US on /en/" 'grep -q "<html lang=\"en-US\"" "$TMP/en.html"'

echo "RFQ"
wp transient delete --all >/dev/null   # reset the per-IP rate limit for repeatable runs
before=$(wp post list --post_type=bcl_rfq --format=count)
fetch_form() { curl -s -c "$TMP/c" "$BASE/yeu-cau-bao-gia/" > "$TMP/f.html"; TOKEN=$(grep -o 'name="bcl_token" value="[^"]*"' "$TMP/f.html" | sed 's/.*value="//;s/"$//'); SERVICE=$(grep -o 'name="rfq\[services\]\[\]" value="[0-9]*"' "$TMP/f.html" | head -1 | grep -o '[0-9]*"$' | tr -d '"'); }
post() { # $1 = extra curl args
  curl -s -o "$TMP/r.html" -D "$TMP/h.txt" -b "$TMP/c" "$BASE/yeu-cau-bao-gia/?bcl_rfq=full" \
    --form-string "bcl_token=$TOKEN" --form-string "bcl_redirect=$BASE/yeu-cau-bao-gia/" \
    --form-string "rfq[company]=Test Co" --form-string "rfq[contact_name]=Nguyen Test" --form-string "rfq[email]=buyer@example.com" \
    --form-string "rfq[phone]=0912 345 678" --form-string "rfq[cargo]=Electronic parts" --form-string "rfq[origin]=Long An" \
    --form-string "rfq[destination]=Phnom Penh" --form-string "rfq[vehicle]=40hc" --form-string "rfq[ship_date]=2026-12-15" \
    --form-string "rfq[services][]=$SERVICE" --form-string "rfq[consent]=1" "$@"
}
printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF\n' > "$TMP/invoice.pdf"
printf '<?php echo "pwned"; ?>' > "$TMP/evil.pdf"

fetch_form; sleep 4
post -F "rfq_files[]=@$TMP/invoice.pdf;type=application/pdf"
REF=$(grep -i '^location:' "$TMP/h.txt" | grep -o 'RFQ-[0-9]\{6\}-[0-9]\{4,\}')
check "valid RFQ with PDF → redirect with reference ($REF)" '[ -n "$REF" ]'
check "RFQ saved in admin" '[ "$(wp post list --post_type=bcl_rfq --format=count)" -eq $((before+1)) ]'
sleep 2
mails=$(curl -s "$MAIL/api/v1/search?query=$REF")
check "email to BCL + confirmation to customer" '[ "$(echo "$mails" | grep -o "\"Subject\"" | wc -l)" -ge 2 ]'
FILE=$(wp eval 'echo (get_posts(["post_type"=>"bcl_rfq","numberposts"=>1])[0] ?? null) ? get_post_meta(get_posts(["post_type"=>"bcl_rfq","numberposts"=>1])[0]->ID,"_bcl_rfq_files",true)[0]["path"] : "";')
check "attachment stored with random name" '[ -n "$FILE" ]'
check "attachment not reachable from the web (403)" '[ "$(curl -s -o /dev/null -w "%{http_code}" "$BASE/wp-content/uploads/bcl-rfq/$FILE")" = 403 ]'

fetch_form
post -F "rfq_files[]=@$TMP/invoice.pdf;type=application/pdf"
check "submitted too fast → rejected" 'grep -q "rfq__alert" "$TMP/r.html" && ! grep -qi "^location:" "$TMP/h.txt"'

fetch_form; sleep 4
post -F "rfq_files[]=@$TMP/evil.pdf;type=application/pdf"
check "fake PDF (PHP code) → rejected" 'grep -q "field--file has-error" "$TMP/r.html"'

fetch_form; sleep 4
count=$(wp post list --post_type=bcl_rfq --format=count)
post --form-string "rfq[hp_url]=http://spam.example"
check "honeypot → fake success, nothing saved" 'grep -qi "RFQ-000000-0000" "$TMP/h.txt" && [ "$(wp post list --post_type=bcl_rfq --format=count)" -eq "$count" ]'

fetch_form; sleep 4
curl -s -o "$TMP/r.html" -b "$TMP/c" "$BASE/yeu-cau-bao-gia/?bcl_rfq=full" --form-string "bcl_token=$TOKEN" --form-string "rfq[email]=not-an-email"
check "missing/invalid fields → field errors" '[ "$(grep -o "field__error" "$TMP/r.html" | wc -l)" -ge 5 ]'

echo
echo "Passed: $pass  Failed: $fail"
[ "$fail" -eq 0 ]
