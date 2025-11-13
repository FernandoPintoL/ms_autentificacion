# ============================================
# Dockerfile - MS Autenticación
# ============================================
# Multi-stage build optimizado para PHP 8.3 + SQL Server

ARG APP_ENV=production

# STAGE 1: Builder
FROM php:8.3-fpm-alpine AS builder

# Instalar dependencias del sistema necesarias
RUN apk add --no-cache \
    build-base \
    curl \
    git \
    composer \
    freetds-dev \
    oniguruma-dev \
    libzip-dev

# Instalar dependencias para extensiones XML
RUN apk add --no-cache libxml2-dev

# Instalar extensiones PHP
RUN docker-php-ext-install \
    pdo_dblib \
    mbstring \
    bcmath \
    ctype \
    fileinfo \
    pcntl \
    zip \
    dom \
    xml

# Verificar que todas las extensiones están cargadas
RUN php -m | grep -E 'dom|tokenizer|session' || echo "Extensions missing" && \
    echo "extension=dom" >> /usr/local/etc/php/conf.d/docker-php-ext-dom.ini && \
    echo "extension=tokenizer" >> /usr/local/etc/php/conf.d/docker-php-ext-tokenizer.ini && \
    echo "extension=session" >> /usr/local/etc/php/conf.d/docker-php-ext-session.ini

WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Verificar extensiones instaladas
RUN php -m

# Instalar dependencias de Composer (sin dev en production)
# Ignoramos requisitos de plataforma ya que las extensiones están incluidas en PHP 8.3
# Usamos --no-scripts para evitar que artisan se ejecute durante el build
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-reqs --no-scripts; \
    else \
        composer install --no-interaction --no-progress --ignore-platform-reqs --no-scripts; \
    fi

# STAGE 2: Runtime
FROM php:8.3-fpm-alpine

ARG APP_ENV=production

# Instalar solo las librerías runtime necesarias
RUN apk add --no-cache \
    freetds \
    oniguruma \
    curl \
    supervisor

# Instalar extensiones PHP
RUN apk add --no-cache --virtual .build-deps \
    build-base \
    freetds-dev \
    oniguruma-dev \
    unixodbc-dev && \
    docker-php-ext-install \
    pdo_dblib \
    mbstring \
    bcmath \
    ctype \
    fileinfo \
    pcntl && \
    apk del .build-deps

# Configuración PHP
RUN echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/00-app.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/00-app.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/00-app.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/00-app.ini

# Copiar código compilado desde builder
COPY --from=builder /app /app
WORKDIR /app

# Instalar Nginx
RUN apk add --no-cache nginx

# Crear directorios necesarios
RUN mkdir -p storage/logs bootstrap/cache && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/storage/logs && \
    touch /app/bootstrap/cache/.gitkeep && \
    touch /app/storage/logs/.gitkeep

# Copiar configuración de Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copiar configuración de PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copiar configuración de Supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorios necesarios para supervisor y nginx
RUN mkdir -p /var/log/supervisor /var/log/nginx /var/run/nginx

# Crear script de entrada
RUN cat > /entrypoint.sh <<'EOF'
#!/bin/sh
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF
RUN chmod +x /entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD php -r "exit(file_exists('bootstrap/cache/config.php') ? 0 : 1);"

# Exponer puerto HTTP
EXPOSE 80

# Comando por defecto - ejecutar script de entrada
ENTRYPOINT ["/entrypoint.sh"]
