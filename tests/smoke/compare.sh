#!/usr/bin/env bash
# HTTP-level diff between mapbender-legacy (PHP 7.4 baseline) and
# mapbender-php8 (PHP 8.2 target). Hits each URL on both backends and
# reports status+size+normalized-sha matches/mismatches.
#
# Usage:
#   bash tests/smoke/compare.sh             # summary
#   bash tests/smoke/compare.sh --verbose   # show every probe
#   bash tests/smoke/compare.sh --diff      # show first byte-level diff
set -uo pipefail

LEGACY=http://127.0.0.1:${MB_LEGACY_PORT:-8090}
PHP8=http://127.0.0.1:${MB_PHP8_PORT:-8091}
ERR_LOG=/var/geoportal/log/php-errors.log
MODE="${1:-}"

# Clear the php8 error log so any fatals we see during this run are fresh.
echo "" | sudo tee "$ERR_LOG" >/dev/null 2>&1 || true

# Normalize inherently-variable things so the SHA matches across versions.
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
    local body code sha size_norm
    body=$(/usr/bin/curl -sS --max-time 15 -o "$target" -w "%{http_code}" "$url" 2>/dev/null) || body="ERR"
    code="$body"
    normalize "$target"
    # Use POST-normalization size so cosmetic length differences (genTime
    # precision, Apache version length, port digits) don't mask matches.
    size_norm=$(wc -c < "$target" 2>/dev/null)
    sha=$(sha256sum < "$target" 2>/dev/null | cut -c1-8)
    printf "%s/%s/%s" "$code" "$size_norm" "$sha"
}

# Real Mapbender URLs (files verified to exist in the repo).
URLS=(
    # --- public entry / landing ---
    "/mapbender/"
    "/mapbender/index.php"
    "/mapbender/frames/login.php"
    "/mapbender/frames/login.php?name=admin"
    "/mapbender/frames/home.php"
    "/mapbender/frames/index.php?guiID=Geoportal-RLP"
    "/mapbender/frames/index_ext.php?guiID=Geoportal-RLP"
    # --- public service endpoints (anonymous-callable) ---
    "/mapbender/php/wms.php?REQUEST=GetCapabilities&SERVICE=WMS&VERSION=1.1.1"
    "/mapbender/php/wms.php?REQUEST=GetCapabilities"
    "/mapbender/php/mb_listGUIs.php"
    "/mapbender/php/mb_listGUIs.php?guiID=Geoportal-RLP"
    "/mapbender/php/mb_validateSession.php"
    "/mapbender/php/mb_validateInput.php"
    "/mapbender/php/mb_validatePermission.php"
    "/mapbender/php/mb_checkGuest.php"
    "/mapbender/php/mb_getGUIs.php"
    "/mapbender/php/mb_getWmsData.php"
    "/mapbender/php/mb_listKMLs.php"
    "/mapbender/php/mb_js_exception.php"
    "/mapbender/php/mod_callMetadata.php"
    "/mapbender/php/mod_loadwms.php"
    "/mapbender/php/mod_acceptedTou_server.php"
    "/mapbender/php/mod_activateUserAccount.php"
    "/mapbender/php/mod_button_tooltips.php"
    "/mapbender/php/mod_changeEPSG_dynamic.php"
    "/mapbender/php/mod_layerISOMetadata.php"
    "/mapbender/php/mod_showCapDiff.php"
    "/mapbender/php/mod_monitorCapabilities_read_single_diff.php"
    "/mapbender/php/mod_loadWmcTemplate.php"
    "/mapbender/php/mod_metadataAccess.php"
    "/mapbender/php/mod_abo_show.php"
    "/mapbender/php/mod_pdfQuery.php"
    "/mapbender/php/tagCloud.php"
    "/mapbender/php/createImageFromText.php"
    "/mapbender/php/intersection.php"
    "/mapbender/php/log_error_exec.php"
    "/mapbender/php/mod_addWmsFromFeatureInfo.php"
    "/mapbender/php/mod_validatePassword.php"
    "/mapbender/php/mod_validateUserName.php"
    "/mapbender/php/mod_publish_wmc.php"
    # --- javascripts (PHP-rendered JS) ---
    "/mapbender/javascripts/map.php"
    "/mapbender/javascripts/core.php"
    "/mapbender/javascripts/group.php"
    "/mapbender/javascripts/gui.php"
    "/mapbender/javascripts/mod_confirmLogin.php"
    "/mapbender/javascripts/mod_legend.php"
    "/mapbender/javascripts/mod_coords.php"
    "/mapbender/javascripts/mod_help.php"
    "/mapbender/javascripts/mod_back.php"
    "/mapbender/javascripts/mod_state.php"
    "/mapbender/javascripts/mod_zoomCoords.php"
    "/mapbender/javascripts/mod_changeEPSG.php"
    "/mapbender/javascripts/mod_print1.php"
    "/mapbender/javascripts/mod_displayWmc.php"
    "/mapbender/javascripts/mod_savewmc.php"
    "/mapbender/javascripts/mod_loadwmc.php"
    "/mapbender/javascripts/user.php"
    # --- geoportal-specific paths ---
    "/mapbender/geoportal/gaz.php"
    "/mapbender/geoportal/gaz_wiki.php"
    "/mapbender/geoportal/mod_bplanid.php"
    # --- include/ ---
    "/mapbender/include/dyn_css.php"
)

ok=0
fail=0
first_diff_path=""

for path in "${URLS[@]}"; do
    l=$(probe "${LEGACY}${path}" /tmp/probe.l)
    p=$(probe "${PHP8}${path}"   /tmp/probe.p)
    if [ "$l" = "$p" ]; then
        ok=$((ok+1))
        if [ "$MODE" = "--verbose" ]; then
            printf "  \033[32mOK \033[0m  %s   %s\n" "$l" "$path"
        fi
    else
        fail=$((fail+1))
        printf "  \033[31mDIFF\033[0m  legacy=%-14s  php8=%-14s  %s\n" "$l" "$p" "$path"
        if [ -z "$first_diff_path" ]; then
            first_diff_path="$path"
            cp /tmp/probe.l /tmp/first_diff.l 2>/dev/null
            cp /tmp/probe.p /tmp/first_diff.p 2>/dev/null
        fi
    fi
done

echo
printf "  Total: %d   \033[32mMATCH: %d\033[0m   \033[31mDIFF: %d\033[0m\n" $((ok+fail)) $ok $fail

if [ "$fail" -gt 0 ]; then
    if [ "$MODE" = "--diff" ] && [ -n "$first_diff_path" ]; then
        echo
        echo "  --- first body diff: $first_diff_path ---"
        diff /tmp/first_diff.l /tmp/first_diff.p | head -40
    fi
    echo
    echo "  --- recent PHP fatals on php8 ---"
    sudo tail -200 "$ERR_LOG" 2>/dev/null | grep "PHP Fatal" | sort -u | sed 's/^/  /' | head -15
fi
