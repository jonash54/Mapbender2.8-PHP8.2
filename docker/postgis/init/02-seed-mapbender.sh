#!/usr/bin/env bash
# Loads Mapbender 2.8's bundled SQL bootstrap into the 'mapbender' database.
# Mirrors what resources/db/install.sh does (interactively), but runs
# non-interactively at postgres-init time.
#
# Loads the full schema + version-update chain + initial data + GUIs.
#
# Mount points required (via docker-compose):
#   /mapbender-sql/      ->  resources/db/         (Mapbender bundled SQL, read-only)
#   /test-fixtures/      ->  tests/characterization/fixtures/  (optional, read-only)
set -euo pipefail

SQL_DIR="${MAPBENDER_SQL_DIR:-/mapbender-sql}"
DB="mapbender"
USER="mapbenderdbuser"

if [ ! -d "$SQL_DIR" ]; then
    echo "[seed] $SQL_DIR not found — skipping Mapbender bootstrap."
    exit 0
fi

ENCODING="UTF-8"
PG="$SQL_DIR/pgsql"
PGE="$PG/$ENCODING"

run_sql() {
    local f="$1"
    local name="${f##*/}"
    if [ ! -f "$f" ]; then
        echo "[seed]   - $name (missing, skipped)"
        return 0
    fi
    echo "[seed]   + $name"
    psql -v ON_ERROR_STOP=0 -U "$USER" -d "$DB" -f "$f" \
        > "/tmp/seed-${name%.sql}.log" 2>&1 \
        || echo "[seed]     ! errors in /tmp/seed-${name%.sql}.log"
}

# ----- 1) Schema + data + version upgrades (mirrors install.sh line 131-148) -----
echo "[seed] Loading Mapbender schema + update chain..."
SCHEMA_CHAIN=(
    "$PG/pgsql_schema_2.5.sql"
    "$PGE/pgsql_data_2.5.sql"
    "$PG/pgsql_serial_set_sequences_2.5.sql"
    "$PGE/update/update_2.5_to_2.5.1rc1_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.5.1rc1_to_2.5.1_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.5.1_to_2.6rc1_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.6rc1_to_2.6_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.6_to_2.6.1_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.6.1_to_2.6.2_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.6.2_to_2.7rc1_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.7rc1_to_2.7rc2_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.7.1_to_2.7.2_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.7.2_to_2.7.3_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.7.3_to_2.7.4_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.7.4_to_2.8_pgsql_${ENCODING}.sql"
    "$PGE/update/update_2.8_pgsql_${ENCODING}.sql"
    "$PG/pgsql_serial_set_sequences_2.7.sql"
)
for f in "${SCHEMA_CHAIN[@]}"; do
    run_sql "$f"
done

# ----- 2) Additional Mapbender admin/template GUIs ------------------------
echo "[seed] Loading Mapbender admin GUIs..."
ADMIN_GUIS=(
    "$PG/admin_wms_metadata.sql"
    "$PG/admin_wfs_metadata.sql"
    "$PG/admin_ows_scheduler.sql"
    "$PG/gui_digitize.sql"
    "$PG/layout.sql"
    "$PG/layout_with_map.sql"
    "$PG/layout_with_map_and_toolbar.sql"
    "$PG/layout_with_openlayers.sql"
    "$PG/openlayers_application.sql"
    "$PG/template_basic.sql"
    "$PG/template_basic2.sql"
    "$PG/template_basic_zoomBar.sql"
    "$PG/template_openlayers.sql"
    "$PG/template_jquery_ui.sql"
    "$PG/template_wfs.sql"
    "$PG/template_wfs2.sql"
    "$PG/template_print.sql"
    "$PG/template_csw.sql"
    "$PG/wmc_template_extension.sql"
)
for f in "${ADMIN_GUIS[@]}"; do
    run_sql "$f"
done

# ----- 3) GeoPortal-RLP specific GUIs (top-level in resources/db) ---------
echo "[seed] Loading GeoPortal-RLP specific data..."
GEOPORTAL_DATA=(
    "$SQL_DIR/openlayersSql.sql"
    "$SQL_DIR/new_admin_gui.sql"
    "$SQL_DIR/new_admin_gui_de.sql"
    "$SQL_DIR/gui_Administration_DE.sql"
    "$SQL_DIR/gui_admin_metadata.sql"
    "$SQL_DIR/gui_admin_application_metadata.sql"
    "$SQL_DIR/gui_admin_wms_metadata.sql"
    "$SQL_DIR/gui_admin_wfs_metadata.sql"
    "$SQL_DIR/gui_admin_wmc_metadata.sql"
    "$SQL_DIR/gui_admin_ows_scheduler.sql"
    "$SQL_DIR/gui_PortalAdmin_DE.sql"
    "$SQL_DIR/gui_Owsproxy_csv.sql"
    "$SQL_DIR/admin_metadata.sql"
    "$SQL_DIR/Search_extended_DE_open_data.sql"
    "$SQL_DIR/materialize_application_view.sql"
    "$SQL_DIR/materialize_dataset_view.sql"
    "$SQL_DIR/materialize_wms_view.sql"
    "$SQL_DIR/materialize_wfs_view.sql"
    "$SQL_DIR/materialize_wmc_view.sql"
    "$SQL_DIR/module_metadataCarousel.sql"
    "$SQL_DIR/gui_Geoportal-RLP.sql"
    "$SQL_DIR/gui_Geoportal-RLP_2019.sql"
    "$SQL_DIR/gui_Geoportal-RLP_erwSuche2.sql"
)
for f in "${GEOPORTAL_DATA[@]}"; do
    run_sql "$f"
done

# ----- 4) Optional test fixtures ------------------------------------------
if [ -d /test-fixtures ]; then
    echo "[seed] Loading test fixtures..."
    for f in /test-fixtures/*.sql; do
        [ -f "$f" ] || continue
        run_sql "$f"
    done
fi

# ----- 5) Summary --------------------------------------------------------
PUB_TABLES=$(psql -U "$USER" -d "$DB" -tAc "SELECT COUNT(*) FROM pg_tables WHERE schemaname='public';" 2>/dev/null || echo "?")
echo "[seed] Bootstrap complete. public tables: ${PUB_TABLES}"
