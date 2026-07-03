#!/usr/bin/env bash
# Mapbender entrypoint — renders conf/mapbender.conf from -dist template
# using env vars, fixes permissions on writable dirs, then runs CMD.
set -euo pipefail

MB_ROOT="${MB_ROOT:-/var/www/html/mapbender}"

# Environment vars expected (with defaults for dev convenience):
: "${DBSERVER:=postgis}"
: "${DBPORT:=5432}"
: "${DBNAME:=mapbender}"
: "${DBOWNER:=mapbenderdbuser}"
: "${DBPASSWORD:=mapbenderdbpassword}"

# 1) Render mapbender.conf from -dist template
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

# 2) Render any other .conf-dist files that have no .conf sibling yet
for dist in "$MB_ROOT"/conf/*.conf-dist; do
    [ -f "$dist" ] || continue
    target="${dist%-dist}"
    if [ ! -f "$target" ]; then
        cp "$dist" "$target"
    fi
done
# Same for json-dist and map-dist
for dist in "$MB_ROOT"/conf/*.json-dist "$MB_ROOT"/conf/*.map-dist "$MB_ROOT"/conf/*.js-dist; do
    [ -f "$dist" ] || continue
    target="${dist%-dist}"
    [ -f "$target" ] || cp "$dist" "$target"
done

# 3) Ensure writable dirs exist and are owned by www-data
for d in log http/tmp http/tmp/wmc http/tmp/atomfeeds http/tmp/inspire \
         http/tmp/cache http/tmp/print http/tmp/sld tools/tmp \
         resources/locale; do
    install -d -o www-data -g www-data -m 0775 "$MB_ROOT/$d" 2>/dev/null || true
done

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

# 4) Wait briefly for postgis if needed (compose has depends_on but no readiness probe)
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
