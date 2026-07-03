-- Mapbender DB owner. Credentials match docker-compose env and
-- the entrypoint's defaults in /docker/{legacy,php8}/entrypoint.sh
CREATE ROLE mapbenderdbuser WITH LOGIN PASSWORD 'mapbenderdbpassword';

-- A dedicated database for Mapbender. The 'mapbender' DB is created
-- from template_postgis so PostGIS is already available.
CREATE DATABASE mapbender OWNER mapbenderdbuser;

GRANT ALL PRIVILEGES ON DATABASE mapbender TO mapbenderdbuser;
