\c mapbender

CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;
CREATE EXTENSION IF NOT EXISTS hstore;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder;

-- Ensure mapbender user owns public schema objects it creates later
ALTER SCHEMA public OWNER TO mapbenderdbuser;
GRANT ALL ON SCHEMA public TO mapbenderdbuser;

-- PostGIS 2 -> 3 compatibility shims for Mapbender's bundled SQL update
-- scripts (resources/db/pgsql/UTF-8/update/*.sql). They reference the old
-- unprefixed names that PostGIS 3 dropped.
CREATE OR REPLACE FUNCTION ndims(geometry) RETURNS integer
    AS 'SELECT ST_NDims($1)' LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION srid(geometry) RETURNS integer
    AS 'SELECT ST_SRID($1)' LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION geometrytype(geometry) RETURNS text
    AS 'SELECT GeometryType($1)' LANGUAGE SQL IMMUTABLE;
