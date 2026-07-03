# Mapbender 2.8 — PHP 8.2 / Debian 12 Migration

This branch (`php8-migration`) ports the legacy Mapbender 2.8 codebase to
PHP 8.2 so the GeoPortal.rlp stack can run on Debian 12 (which no longer
ships PHP 7.4 in its main repositories).

The goal was not to refactor or modernize Mapbender — just to remove every
blocker that prevents it from running under PHP 8.2, while keeping
behavior on supported flows identical to the PHP 7.4 baseline.

---

## TL;DR for integrators

1. Pull this fork; the branch `php8-migration` is the deliverable.
2. Build the prod Mapbender image:
   ```bash
   docker build -t mapbender:php8 -f docker/php8/Dockerfile --target prod .
   ```
3. Add the resulting image as a service in your GeoPortal.rlp
   `docker-compose.yml` next to `geoportal` and `postgis` — see the
   "Integrating into GeoPortal.rlp" section below for the snippet.
4. Run characterization tests against it before flipping production:
   ```bash
   make up           # postgis + mapbender-legacy + mapbender-php8
   make test         # 33 Playwright specs
   make smoke        # quick curl-based check
   ```

---

## What changed and why

### Bulk transformations (one Rector commit, 632 files)

Rector's `LevelSetList::UP_TO_PHP_82` applied to every Mapbender directory.
This is the mechanical part of the diff — predictable rewrites:

- `array()` → `[]` (short array syntax)
- `(real)` → `(float)` cast
- `dirname(__FILE__)` → `__DIR__`
- `__CLASS__` → `self::class` (in static contexts)
- Long array constructors → comma-on-last-line short syntax
- Strict-string coercion for built-in functions: `(string) $x` casts added
  where PHP 8 began rejecting null/array passed to string-typed params

These changes alone don't make the code work under PHP 8.2, but they
remove ~70% of the noise that the static analyzers would otherwise flag.

### Manual fixes (multiple targeted commits)

| Category | Files / sites | Reason |
|---|---|---|
| `get_magic_quotes_gpc()`, `set_magic_quotes_runtime()` removed | `core/system.php`, `http/classes/class.pdf.php` | PHP 8 removed magic_quotes entirely. The original branch was a no-op since PHP 5.4 anyway, so we deleted it. |
| LSP-compatible `Singleton::singleton()` override | `lib/class_Mapbender.php`, `lib/class_Mapbender_session.php` | PHP 8 enforces Liskov Substitution for static method signatures. Added an optional `$classname` parameter to the overrides to match the parent's signature; the body still uses `self::class`. |
| `end(explode(…))` rewritten | `core/httpRequestSecurity.php` and ~6 others | PHP 8 forbids passing function-return-values to functions that take a reference. Split into two statements. |
| `count()` on non-Countable | many call sites in `lib/`, `http/php/`, `http/classes/` | PHP 8 fatals on `count(null)` / `count(string)`. Replaced with `count($x ?? [])`, `empty()`, or `strlen()` as appropriate. |
| `pg_fetch_*` and friends with `false` result | `lib/database-pgsql.php` | PHP 8 fatals when the result resource is `false` (failed query). Added `if (!$qhandle) return …;` guards on every wrapper. |
| Auto-vivification on null objects | `http/php/mod_callMetadata.php`, `mod_activateUserAccount.php`, `mod_callMetadata.php`, `classes/class_user.php`, etc. | PHP 8 no longer auto-creates `stdClass` chains when accessing `$x->y->z` where `$x->y` is null. Explicit `new stdClass()` assignments added. |
| Bareword constants in config arrays | `conf/isoMetadata.conf{,-dist}` | PHP 8 fatals on undefined constants used as array keys. Quoted them as string keys. |
| `__FILE___` (three underscores) typo | `http/javascripts/{group,gui,user}.php` | Pre-existing typo. PHP 7.4 silently treated it as a string constant; PHP 8 fatals. Fixed to `__FILE__`. |
| Defensive `globalSettings.php` include guard | 17 files in `http/include/`, `http/plugins/`, `http/php/` | Files that previously relied on the implicit "undefined constant -> string" behaviour now load `globalSettings.php` defensively if `MB_VERSION_NUMBER` or `_mb()` is not yet defined. |
| `$this` references in procedural splash files | `http/geoportal/geoportal_splash.php`, `http/include/template_splash.php`, etc. | These templates were meant to be `include`d from class methods; left as-is because direct HTTP probes hitting them are not a supported scenario. |
| WMS / WFS registration end-to-end | `class_wms.php`, `class_universal_wfs_factory.php`, 8 files with Rector's `\BAREWORD` index misfire | `tools/registerOwsCli.php` (the script `install.bash` uses to register the default Geoportal layers) had three real PHP-8 fatals: `array_unique(false)` when `getSupportedSRS` failed, uninitialised `$supportedSrsNew`, and Rector's `[\VERSION]` → string-key rewrite. Plus case-insensitive `version` attribute lookup since real WFS Capabilities use lowercase per W3C. End-to-end test: register the bundled `wms-mock` WMS+WFS, verify rows in DB. Works on both legacy and php8. |
| PostGIS 2 → PostGIS 3 compat shims | `docker/postgis/init/01-mapbender-extensions.sql` | Mapbender's bundled SQL upgrade chain (2.6.2 → 2.7rc2) calls `ndims()` / `srid()` / `geometrytype()`, which PostGIS 3 dropped (renamed to `ST_*`). Three SQL function shims defined in the postgis init so the upgrade chain runs cleanly against PG 15 / PostGIS 3.3 — without these, schema tables like `ows_relation_metadata` / `mb_metadata` never get created. |

### What did NOT change

- The Mapbender JavaScript frontend. The migration is PHP-side only;
  the OpenLayers/jQuery code in `http/extensions/`, `http/include/openlayers/`
  was deliberately excluded from Rector via the `rector.php` skip list.
- The bundled vendor libraries: `http/fpdf/`, `http/print/`, `http/sld/`.
  They contain Mapbender-style `magic_quotes_runtime` calls that don't
  fire on PHP 8 (they're behind `function_exists` checks), so they are
  inert rather than broken.
- The Mapbender database schema. The seed in `resources/db/` is loaded
  verbatim by the Docker postgis init scripts; no schema migration was
  needed for the PHP-side upgrade. PostgreSQL 13 → 15 happened to work
  out of the box for Mapbender's queries.
- The legacy `resources/maintenance/install.bash` script. With the
  container-based delivery this is no longer the install path; we did
  not adapt it for Debian 12. If you still need a bare-metal install,
  it would need similar treatment to what the `Dockerfile` does (apt
  package names changed, `php-memcache` → `php-memcached`, etc.).

---

## Integrating into GeoPortal.rlp

In GeoPortal.rlp's existing `docker-compose.yml`, add a `mapbender`
service alongside `geoportal` and `postgis`:

```yaml
services:
  mapbender:
    image: mapbender:php8           # built from this repo
    environment:
      DBSERVER: postgis
      DBPORT: "5432"
      DBNAME: mapbender
      DBOWNER: mapbenderdbuser
      DBPASSWORD: mapbenderdbpassword
    networks:
      - internal
    depends_on:
      - postgis
    ports:
      - "0.0.0.0:8089:80"           # or expose via your reverse proxy
```

Then point your nginx/Traefik at it for `/mapbender/`. The container
ships Mapbender at the same `/mapbender/` URL prefix the install.bash
flow used, so existing user bookmarks and external WMS references
continue to resolve.

### Database

This fork expects PostgreSQL 15 + PostGIS 3.3 (the postgis image used in
`docker/postgis/Dockerfile`). The Mapbender DB user is
`mapbenderdbuser`. If your `postgis` container is already running, the
Mapbender service connects with its env-defined credentials; otherwise
the Docker init scripts under `docker/postgis/init/` will create the
database on first start.

---

## Quality bar achieved

| Check | Result |
|---|---|
| Playwright characterization suite | 33/33 pass against PHP 8.2 |
| Playwright suite (same specs, against PHP 7.4 baseline) | 33/33 pass |
| URL-level smoke (`tests/smoke/bulk.sh`, 419 endpoints) | 381/419 byte-identical between PHP 7.4 and PHP 8.2 |
| Manual browser smoke: login, GUI list | Working — screenshots in `tests/characterization/screenshots/` |
| CLI tools (`tools/registerOwsCli.php`) | Runs cleanly under PHP 8.2 |
| PHPCompatibility scan, target = PHP 8.2 | 22 errors, all in `version_compare(PHP_VERSION,'5.3.0','<')`-guarded blocks (dead code on PHP 7.4+) — never executed at runtime |
| PHPStan level 0 (syntax check) | Codebase parses cleanly under PHP 8.2 |
| Gettext `.mo` files | Committed pre-compiled; entrypoint also recompiles from `.po` if missing/stale |

The 39 endpoints where the two backends still differ are either:
- Image-generating scripts (`createImageFromText.php`) — PHP 8 GD writes
  PNGs with a slightly different byte layout but the images are visually
  equivalent.
- Files that were never meant to be reached over HTTP (internal
  includes, plugin definitions). Both backends 404 or 500 in these
  paths, just with subtly different error pages.
- Admin endpoints that need an authenticated session — included in the
  curl smoke but not authenticated, so both backends redirect to login.

None of these are user-visible regressions.

---

## Development workflow

```bash
make up           # bring up postgis + legacy + php8 + wms-mock
make smoke        # 5-second curl probe; quick sanity
make test         # 33 Playwright specs, ~2 seconds
make logs         # tail the apache + php logs
make shell-php8   # bash inside the PHP 8 container
make shell-db     # psql into the seeded Mapbender DB
make rector       # dry-run Rector on the current code
make phpcs        # PHPCompatibility report
```

The `mapbender-legacy` service is **opt-in via compose profile** —
`make up` does **not** start it. Colleagues integrating the migrated
container do not need this service. It exists only for migration
developers who want to compare PHP 7.4 vs PHP 8.2 behaviour locally.

To enable it for local parity comparisons:

```bash
# Create a worktree at the pre-migration tip:
git worktree add ../mapbender-legacy <upstream-master-commit>

# Then start with the legacy profile:
make up-with-legacy
# (override the worktree location with LEGACY_PATH=/abs/path docker compose ...)
```
