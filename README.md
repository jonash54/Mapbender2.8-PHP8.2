# Mapbender 2.8 — PHP 8.2 fork

Fork of [mrmap-community/Mapbender2.8](https://github.com/mrmap-community/Mapbender2.8)
that runs on PHP 8.2 (Debian 12), maintained for the GeoPortal.rlp stack.

The branch `php8-migration` is the deliverable. See
[MIGRATION_NOTES.md](MIGRATION_NOTES.md) for what changed and why,
and how to wire the resulting container into GeoPortal.rlp's
`docker-compose.yml`.

## Quick start

Requirements: Docker + docker-compose plugin.

```bash
git clone --branch php8-migration <this-fork-url> mapbender
cd mapbender
make up                # postgis + legacy (PHP 7.4) + php8 (PHP 8.2)
```

Then open:

- **PHP 8.2 target:** http://127.0.0.1:8091/mapbender/
- **PHP 7.4 baseline** (frozen snapshot for comparison): http://127.0.0.1:8090/mapbender/
- **postgis** (port 5432, user `mapbenderdbuser` / pw `mapbenderdbpassword`)

Default Mapbender admin credentials: `root` / `root`.

## Tests

```bash
make smoke       # curl-based HTTP probe across 50 endpoints
make test        # 33 Playwright specs vs the PHP 8.2 container
bash tests/smoke/bulk.sh        # 419-URL diff between legacy and php8
```

## Project layout

```
.                                  Mapbender source tree (PHP 8.2-compatible)
├── conf/, core/, http/, lib/      Mapbender code (the migration touched ~640 files here)
├── resources/db/                  Mapbender SQL bootstrap (used by postgis init scripts)
├── docker/
│   ├── legacy/                    PHP 7.4 + Apache 2.4 baseline container
│   ├── php8/                      PHP 8.2 + Apache 2.4 target container (dev + prod stages)
│   ├── postgis/                   PostgreSQL 15 + PostGIS 3.3 + Mapbender seed
│   └── wms-mock/                  Static-XML stand-in for external WMS endpoints
├── docker-compose.yml             Dev stack (legacy + php8 + postgis + wms-mock + tools)
├── Makefile                       Wrapper targets — `make help`
├── migration-tools/
│   ├── configs/                   Rector / PHPStan / PHPCompatibility configs
│   └── scripts/                   One-shot bulk-patch helpers used during migration
├── tests/
│   ├── characterization/          Playwright specs (4 suites, 33 tests)
│   └── smoke/                     Shell-based curl diff scripts
├── MIGRATION_NOTES.md             What changed and why, plus the integration snippet
└── README.md                      This file
```

## Building the production image for GeoPortal.rlp

The `php8` Dockerfile has multi-stage targets; the `prod` stage bakes
the Mapbender code into a slim runtime image.

```bash
docker build -t mapbender:php8 -f docker/php8/Dockerfile --target prod .
docker push <your-registry>/mapbender:php8
```

Then add a `mapbender` service to GeoPortal.rlp's `docker-compose.yml`
as documented in [MIGRATION_NOTES.md](MIGRATION_NOTES.md#integrating-into-geoportalrlp).
