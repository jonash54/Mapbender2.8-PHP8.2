#!/usr/bin/env bash
# One-shot helper used during the PHP 8 migration. For files that fatal on
# undefined Mapbender constants (CHARSET, MB_VERSION_NUMBER, LOG_LEVEL_LIST,
# _mb, etc.) when called directly via HTTP, prepend a defensive guard that
# loads core/globalSettings.php (which itself loads conf/mapbender.conf) iff
# it has not been loaded yet.
#
# Idempotent: if the marker comment is already present, the file is skipped.
set -euo pipefail

MARKER="// __MB_PHP8_GUARD__"

FILES=(
    http/php/mod_getSymbolFromRepository.php
    http/php/mod_syncCkan.php
    http/php/mod_wmc_publish.php
    http/javascripts/mod_searchCSW_form.php
    http/javascripts/mod_admin.php
    http/include/dyn_js_object.php
    http/include/dyn_js.php
    http/include/gui1_splash_jquery.php
    http/include/gui1_splash.php
    http/include/mapbender_logo_digitize.php
    http/include/mapbender_logo_splash.php
    http/include/template_splash.php
    http/include/mapbender_logo_new.php
    http/geoportal/geoportal_splash.php
    http/plugins/mb_measure_widget.php
    http/plugins/mb_print.php
    http/plugins/mb_print_woKml_woOv.php
)

guard_block() {
cat <<'EOF'

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
EOF
}

for f in "${FILES[@]}"; do
    path="/var/geoportal/$f"
    if [ ! -f "$path" ]; then
        echo "  - missing: $f"
        continue
    fi
    if /bin/grep -q "$MARKER" "$path"; then
        echo "  = already patched: $f"
        continue
    fi
    # Insert after the opening <?php tag (first occurrence).
    /usr/bin/awk -v guard="$(guard_block)" '
        NR==1 && /^<\?php/ { print; print guard; next }
        { print }
    ' "$path" > "$path.new" && mv "$path.new" "$path"
    echo "  + patched: $f"
done
