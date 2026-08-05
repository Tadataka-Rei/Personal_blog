# =============================================================================
# Dockerfile — PHP 8.1 FPM + nginx backend for Blog Admin
# =============================================================================

FROM php:8.1-fpm

# ── Install system packages & PHP extensions ────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        libzip-dev \
        zip \
        unzip \
        git \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# ── Copy application ────────────────────────────────────────────────────────
COPY . /var/www/html/

# ── nginx configuration ────────────────────────────────────────────────────
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf
COPY backend/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── PHP configuration (override upload limits) ─────────────────────────────
COPY docker/php.ini /usr/local/etc/php/conf.d/99-overrides.ini

# ── Permissions ─────────────────────────────────────────────────────────────
RUN mkdir -p /var/www/html/frontend/data /var/www/html/frontend/data/posts /run/nginx \
    && chown -R www-data:www-data /var/www/html/frontend/data /var/www/html/backend /var/lib/nginx /var/log/nginx /run/nginx \
    && chmod -R 0777 /var/www/html/frontend/data /var/www/html/backend \
    && usermod -u 1000 www-data 2>/dev/null || true

# ── Working directory ───────────────────────────────────────────────────────
WORKDIR /var/www/html

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]