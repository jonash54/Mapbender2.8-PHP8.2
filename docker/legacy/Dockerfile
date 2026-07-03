# ============================================================
# Mapbender 2.8 — Legacy baseline (PHP 7.4 on Debian 11)
# Used for recording characterization tests against the
# original behavior. NOT for production.
# ============================================================
FROM debian:bullseye-slim

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2
ENV APACHE_LOG_DIR=/var/log/apache2

RUN apt-get update && apt-get install -y --no-install-recommends \
        apache2 \
        libapache2-mod-php7.4 \
        php7.4 \
        php7.4-cli \
        php7.4-pgsql \
        php7.4-mysql \
        php7.4-curl \
        php7.4-gd \
        php7.4-mbstring \
        php7.4-xml \
        php7.4-zip \
        php7.4-intl \
        php7.4-bcmath \
        php-imagick \
        php-memcached \
        php-apcu \
        php-pear \
        gdal-bin \
        cgi-mapserver \
        imagemagick \
        librsvg2-bin \
        gettext \
        ca-certificates \
        curl \
        less \
        procps \
        vim-tiny \
    && a2enmod rewrite headers deflate expires \
    && a2dismod mpm_event 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && if [ -f /etc/ImageMagick-6/policy.xml ]; then \
           sed -i 's|<policy domain="coder" rights="none" pattern="SVG" />|<policy domain="coder" rights="read\|write" pattern="SVG" />|' /etc/ImageMagick-6/policy.xml; \
       fi

# Apache site config and PHP overrides
COPY docker/legacy/apache-mapbender.conf /etc/apache2/sites-available/mapbender.conf
COPY docker/legacy/php-mapbender.ini /etc/php/7.4/apache2/conf.d/99-mapbender.ini
COPY docker/legacy/php-mapbender.ini /etc/php/7.4/cli/conf.d/99-mapbender.ini
COPY docker/legacy/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && a2dissite 000-default \
    && a2ensite mapbender

WORKDIR /var/www/html/mapbender

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2", "-D", "FOREGROUND"]
