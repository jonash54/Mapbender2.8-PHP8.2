FROM postgis/postgis:15-3.3

# Initialization scripts run on first start (empty volume).
# Order matters: alphabetical by filename.
COPY docker/postgis/init/ /docker-entrypoint-initdb.d/

# The Mapbender-bundled SQL files (read-only mount at runtime) live in
# /mapbender-sql/ inside the container so the seed script can find them.
# We bake a marker here; the actual files are mounted via docker-compose.
ENV MAPBENDER_SQL_DIR=/mapbender-sql
