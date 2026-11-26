#!/usr/bin/env bash
# Static demo of the local site (for client review on GitHub Pages or any static host).
# Not the production site: forms show a "demo" notice, pages carry noindex.
# Usage: ./bin/export-static.sh https://xmeitran.github.io/Blue-Crane
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
SRC="http://localhost:${WP_PORT}"
DEST="${1:?usage: bin/export-static.sh <public base URL>}"
OUT=dist

rm -rf "$OUT" && mkdir -p "$OUT"
# wget exits 8 when any crawled URL 404s (e.g. oEmbed links) — not fatal for a mirror.
wget --mirror --page-requisites --adjust-extension --no-host-directories -e robots=off -q -P "$OUT" \
  --reject-regex '(/wp-json|/feed|xmlrpc|wp-login|wp-admin|/comments/|wp-sitemap|/embed/|\?(service|route|rfq|bcl_rfq|s)=)' \
  "$SRC/" "$SRC/en/" "$SRC/zh/" || [ $? -eq 8 ]
curl -s "$SRC/khong-ton-tai/" > "$OUT/404.html"

# Query-string pages (?p=84 shortlinks…) are duplicates — drop them, or stripping "?…" would overwrite index.html.
find "$OUT" -name '*\?*.html' -delete
# style.css?ver=123.css → style.css (static hosts ignore the query string)
find "$OUT" -name '*\?*' -print0 | while IFS= read -r -d '' f; do mv "$f" "${f%%\?*}"; done
touch "$OUT/.nojekyll"

python3 -I - "$OUT" "$SRC" "$DEST" <<'PY'
import pathlib, sys
out, src, dest = sys.argv[1], sys.argv[2], sys.argv[3].rstrip('/')
demo = """<script>document.addEventListener('submit',function(e){if(!e.target.classList.contains('rfq'))return;e.preventDefault();e.stopPropagation();var l=document.documentElement.lang||'';alert(l.indexOf('zh')===0?'演示版本：网站正式上线后即可提交表单。请致电 0915 713 979。':l.indexOf('en')===0?'Static demo: the form works on the live website. Please call 0915 713 979.':'Bản demo tĩnh: form sẽ gửi được khi website chạy trên máy chủ chính thức. Vui lòng gọi 0915 713 979.');},true);</script>"""
for p in pathlib.Path(out).rglob('*'):
    if p.suffix not in {'.html', '.css', '.js', '.xml', '.json'}:
        continue
    s = p.read_text(encoding='utf-8', errors='ignore')
    s = s.replace(src, dest).replace(src.replace('/', '\\/'), dest.replace('/', '\\/'))
    if p.suffix == '.html':
        s = s.replace('<head>', '<head>\n<meta name="robots" content="noindex, nofollow">', 1).replace('</body>', demo + '</body>', 1)
    p.write_text(s, encoding='utf-8')
print(sum(1 for _ in pathlib.Path(out).rglob('*.html')), 'pages exported to', out)
PY
