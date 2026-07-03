# ============================================================
# Mapbender 2.8 — PHP 8.2 target (Debian 12 / bookworm)
# Multi-stage:
#   - base:  shared system+php install
#   - dev:   adds composer, xdebug-ready, mounts repo at runtime
#   - tools: same as dev, but used only for running rector/phpcs/phpstan
#   - prod:  copies the (migrated) code into the image, minimal layer
# ============================================================
FROM debian:bookworm-slim AS base

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2
ENV APACHE_LOG_DIR=/var/log/apache2

RUN apt-get update && apt-get install -y --no-install-recommends \
        apache2 \
        libapache2-mod-php8.2 \
        php8.2 \
        php8.2-cli \
        php8.2-pgsql \
        php8.2-mysql \
        php8.2-curl \
        php8.2-gd \
        php8.2-mbstring \
        php8.2-xml \
        php8.2-zip \
        php8.2-intl \
        php8.2-bcmath \
        php8.2-imagick \
        php8.2-memcached \
        php8.2-apcu \
        gdal-bin \
        cgi-mapserver \
        imagemagick \
        librsvg2-bin \
        gettext \
        ca-certificates \
        curl \
        less \
        procps \
        postgresql-client \
    && a2enmod rewrite headers deflate expires \
    && a2dismod mpm_event 2>/dev/null || true \
    && a2enmod mpm_prefork php8.2 \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    # Debian's default ImageMagick policy denies SVG; Mapbender's marker
    # generator (createImageFromText.php / mod_getSymbolFromRepository.php)
    # needs to read the bundled .svg files.
    && if [ -f /etc/ImageMagick-6/policy.xml ]; then \
           sed -i 's|<policy domain="coder" rights="none" pattern="SVG" />|<policy domain="coder" rights="read\|write" pattern="SVG" />|' /etc/ImageMagick-6/policy.xml; \
       fi

# Apache + PHP config (shared by all stages)
COPY docker/php8/apache-mapbender.conf /etc/apache2/sites-available/mapbender.conf
COPY docker/php8/php-mapbender.ini /etc/php/8.2/apache2/conf.d/99-mapbender.ini
COPY docker/php8/php-mapbender.ini /etc/php/8.2/cli/conf.d/99-mapbender.ini
COPY docker/php8/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && a2dissite 000-default \
    && a2ensite mapbender

WORKDIR /var/www/html/mapbender
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2", "-D", "FOREGROUND"]


# ------------------------------------------------------------
FROM base AS dev
# Dev stage: composer + vim + git for working inside container
RUN apt-get update && apt-get install -y --no-install-recommends \
        git vim-tiny unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# repo will be bind-mounted at /var/www/html/mapbender


# ------------------------------------------------------------
FROM dev AS tools
# Tools stage: pre-install rector/phpstan/phpcs so we don't re-install per run.
# Composer config files are mounted at /opt/mig-tools, vendor stays here.
WORKDIR /opt/mig-tools
COPY migration-tools/configs/composer.json /opt/mig-tools/composer.json
RUN composer install --no-interaction --prefer-dist --no-progress
ENV PATH="/opt/mig-tools/vendor/bin:${PATH}"
WORKDIR /var/www/html/mapbender


# ------------------------------------------------------------
FROM base AS prod
# Prod stage: bake the (migrated) code into the image.
# Build context is the repo root, but we only want what's needed at runtime.
COPY --chown=www-data:www-data . /var/www/html/mapbender
# Strip dev-only directories
RUN rm -rf \
        /var/www/html/mapbender/docker \
        /var/www/html/mapbender/tests \
        /var/www/html/mapbender/migration-tools \
        /var/www/html/mapbender/docs \
        /var/www/html/mapbender/.git \
        /var/www/html/mapbender/.github
