#!/usr/bin/env bash
# Enumerate every .php file under http/ that we expect to be reachable from
# the web, and compare legacy vs php8 response. Designed to surface mass
# breakage from migration work (e.g. one buggy include kills a whole tree).
#
# This is the broad-coverage companion to compare.sh — runs on hundreds of
# URLs and reports only the failing ones.
set -uo pipefail

LEGACY=http://127.0.0.1:${MB_LEGACY_PORT:-8090}
PHP8=http://127.0.0.1:${MB_PHP8_PORT:-8091}
ERR_LOG=/var/geoportal/log/php-errors.log

echo "" | sudo tee "$ERR_LOG" >/dev/null 2>&1 || true

normalize() {
    sed -i -E \
        -e 's#Apache/[0-9.]+ \(Debian\)#Apache/X (Debian)#g' \
        -e 's#Port [0-9]+#Port NNNN#g' \
        -e 's#("genTime"|"generation_time"|"gen_time"):[0-9.eE+-]+#\1:0#g' \
        -e 's#PHPSESSID=[A-Za-z0-9]+#PHPSESSID=X#g' \
        -e 's#Set-Cookie: [^\r\n]+##g' \
        "$1" 2>/dev/null
}

probe() {
    local url="$1" target="$2"
    local body code sha
    body=$(/usr/bin/curl -sS --max-time 10 -o "$target" -w "%{http_code}" "$url" 2>/dev/null) || body="ERR"
    code="$body"
    normalize "$target"
    sha=$(sha256sum < "$target" 2>/dev/null | cut -c1-8)
    printf "%s/%s" "$code" "$sha"
}

mapfile -t paths < <(
    /usr/bin/find /var/geoportal/http -maxdepth 3 -name "*.php" \
        -not -path "*/extensions/*" \
        -not -path "*/fpdf/*" \
        -not -path "*/print/*" \
        -not -path "*/sld/*" \
        -not -path "*/widgets/*" \
        -not -path "*/classes/*" \
        -not -path "*/tmp/*" \
        2>/dev/null \
        | /bin/sed 's|/var/geoportal/http|/mapbender|g' \
        | /usr/bin/sort -u
)

total=${#paths[@]}
ok=0
fail=0
fatal_paths=()

echo "Probing $total URLs..."

for path in "${paths[@]}"; do
    l=$(probe "${LEGACY}${path}" /tmp/probe.l)
    p=$(probe "${PHP8}${path}"   /tmp/probe.p)
    if [ "$l" = "$p" ]; then
        ok=$((ok+1))
    else
        fail=$((fail+1))
        # 500 only on php8 → migration regression (always worth investigating)
        # both 500 → pre-existing bug (interesting but lower prio)
        if [[ "$p" == 500/* && "$l" != 500/* ]]; then
            printf "  \033[31mREGR\033[0m  legacy=%-14s  php8=%-14s  %s\n" "$l" "$p" "$path"
            fatal_paths+=("$path")
        elif [[ "$p" != "$l" ]]; then
            printf "  \033[33mdiff\033[0m  legacy=%-14s  php8=%-14s  %s\n" "$l" "$p" "$path"
        fi
    fi
done

echo
printf "  Total: %d   \033[32mMATCH: %d\033[0m   \033[31mDIFF: %d\033[0m\n" $total $ok $fail

if [ "${#fatal_paths[@]}" -gt 0 ]; then
    echo
    echo "  --- PHP fatals (unique) ---"
    sudo tail -1000 "$ERR_LOG" 2>/dev/null \
        | grep "PHP Fatal" \
        | sed -E 's#^\[[^]]+\] ##' \
        | sort -u \
        | head -30 \
        | sed 's/^/  /'
fi
