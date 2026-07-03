#!/usr/bin/env bash
# Mapbender PHP 8.2 container entrypoint.
# Same shape as the legacy entrypoint so behavior parity is preserved.
set -euo pipefail

MB_ROOT="${MB_ROOT:-/var/www/html/mapbender}"

: "${DBSERVER:=postgis}"
: "${DBPORT:=5432}"
: "${DBNAME:=mapbender}"
: "${DBOWNER:=mapbenderdbuser}"
: "${DBPASSWORD:=mapbenderdbpassword}"

if [ -f "$MB_ROOT/conf/mapbender.conf-dist" ] && [ ! -f "$MB_ROOT/conf/mapbender.conf" ]; then
    echo "[entrypoint] Rendering conf/mapbender.conf from template..."
    sed \
        -e "s|%%DBSERVER%%|${DBSERVER}|g" \
        -e "s|%%DBPORT%%|${DBPORT}|g" \
        -e "s|%%DBNAME%%|${DBNAME}|g" \
        -e "s|%%DBOWNER%%|${DBOWNER}|g" \
        -e "s|%%DBPASSWORD%%|${DBPASSWORD}|g" \
        "$MB_ROOT/conf/mapbender.conf-dist" > "$MB_ROOT/conf/mapbender.conf"
fi

for dist in "$MB_ROOT"/conf/*.conf-dist; do
    [ -f "$dist" ] || continue
    target="${dist%-dist}"
    [ -f "$target" ] || cp "$dist" "$target"
done
for dist in "$MB_ROOT"/conf/*.json-dist "$MB_ROOT"/conf/*.map-dist "$MB_ROOT"/conf/*.js-dist; do
    [ -f "$dist" ] || continue
    target="${dist%-dist}"
    [ -f "$target" ] || cp "$dist" "$target"
done

for d in log http/tmp http/tmp/wmc http/tmp/atomfeeds http/tmp/inspire \
         http/tmp/cache http/tmp/print http/tmp/sld tools/tmp \
         resources/locale; do
    install -d -o www-data -g www-data -m 0775 "$MB_ROOT/$d" 2>/dev/null || true
done

# Compile gettext .mo files from .po if missing (idempotent).
if command -v msgfmt >/dev/null 2>&1; then
    for po in "$MB_ROOT"/resources/locale/*/LC_MESSAGES/Mapbender.po; do
        [ -f "$po" ] || continue
        mo="${po%.po}.mo"
        if [ ! -f "$mo" ] || [ "$po" -nt "$mo" ]; then
            msgfmt "$po" -o "$mo" 2>/dev/null && \
                chown www-data:www-data "$mo" 2>/dev/null || true
        fi
    done
fi

if command -v pg_isready >/dev/null 2>&1; then
    for i in $(seq 1 30); do
        if pg_isready -h "$DBSERVER" -p "$DBPORT" -U "$DBOWNER" >/dev/null 2>&1; then
            break
        fi
        sleep 1
    done
fi

echo "[entrypoint] Starting: $*"
exec "$@"
