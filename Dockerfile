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
    postgresql-dev \
    oniguruma-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    openssl-dev

# Instalar extensiones PHP
# Nota: iconv está compilado en PHP 8.3 y se habilitará en docker-php-ext-enable
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    ctype \
    fileinfo \
    pcntl \
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
    postgresql-libs \
    oniguruma \
    libzip \
    libxml2 \
    icu-libs \
    curl \
    supervisor

# Instalar solo las librerías de desarrollo necesarias (temporal para habilitar extensiones)
RUN apk add --no-cache --virtual .build-deps \
    build-base \
    postgresql-dev \
    oniguruma-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    openssl-dev && \
    # Instalar extensiones
    docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    ctype \
    fileinfo \
    pcntl \
    zip \
    dom \
    xml \
    intl && \
    # Habilitar extensiones compiladas
    docker-php-ext-enable pdo pdo_pgsql mbstring bcmath ctype fileinfo pcntl zip dom xml intl && \
    # json, openssl, filter, hash, tokenizer son built-in en PHP 8.3 - no es necesario habilitarlos explícitamente
    # Limpiar dependencias de compilación
    apk del .build-deps

# TODO: Instalar redis desde PECL (requiere compilación de phpize)
# Será instalado en una stage separada cuando sea necesario

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

# Crear directorios necesarios y establecer permisos
RUN mkdir -p storage/logs bootstrap/cache && \
    chown -R www-data:www-data /app && \
    chmod -R 755 /app/public && \
    chmod -R 755 /app/bootstrap && \
    chmod -R 755 /app/storage && \
    touch /app/bootstrap/cache/.gitkeep && \
    touch /app/storage/logs/.gitkeep && \
    chown www-data:www-data /app/bootstrap/cache/.gitkeep /app/storage/logs/.gitkeep

# Crear archivo .env vacío para que Laravel lo encuentre
# Las variables de entorno serán inyectadas por Railway en tiempo de ejecución
RUN touch /app/.env && chmod 644 /app/.env && chown www-data:www-data /app/.env

# Copiar configuración de Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copiar configuración de PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copiar configuración de Supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorios necesarios para supervisor y nginx
RUN mkdir -p /var/log/supervisor /var/log/nginx /var/run/nginx /tmp/nginx && \
    mkdir -p /tmp/nginx/client_body /tmp/nginx/proxy /tmp/nginx/fastcgi /tmp/nginx/uwsgi /tmp/nginx/scgi && \
    chown -R www-data:www-data /var/log/nginx /var/run/nginx /tmp/nginx && \
    chmod -R 755 /var/log/nginx /var/run/nginx /tmp/nginx

# Crear script de entrada que inicializa la BD y cachés
RUN cat > /entrypoint.sh <<'EOF'
#!/bin/sh

echo "=== Iniciando aplicación Laravel ==="

# Esperar a que la base de datos esté lista (máximo 30 segundos)
if [ -n "$DB_HOST" ]; then
    echo "[1/6] Esperando a que la base de datos esté disponible..."
    max_attempts=30
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if php -r "new PDO('pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; then
            echo "Base de datos lista"
            break
        fi
        attempt=$((attempt + 1))
        sleep 1
    done
fi

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    echo "[2/6] Generando APP_KEY..."
    APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    export APP_KEY
else
    echo "[2/6] APP_KEY ya está configurada"
fi

# Generar config cache
echo "[3/6] Generando config cache..."
php /app/artisan config:cache 2>/dev/null || true

# Ejecutar migraciones
echo "[4/6] Ejecutando migraciones..."
php /app/artisan migrate --force 2>/dev/null || echo "Migraciones completadas o fallos ignorados"

# Generar route cache
echo "[5/6] Generando route cache..."
php /app/artisan route:cache 2>/dev/null || true

# Generar view cache
echo "[6/6] Generando view cache..."
php /app/artisan view:cache 2>/dev/null || true

echo "=== Aplicación lista, iniciando Supervisord ==="

# Diagnostico antes de iniciar supervisord
echo ""
echo "=== Diagnostico del Sistema ==="
echo "PHP version: $(php -v | head -1)"
echo "PHP PDO drivers: $(php -r 'echo implode(", ", PDO::getAvailableDrivers());')"
php -m | grep -E "pgsql|pdo" || echo "PostgreSQL extensions: NOT FOUND"
echo "Nginx config test:"
nginx -t 2>&1 || true
echo "Listening ports:"
netstat -tlnp 2>/dev/null | grep -E "LISTEN|PID" || echo "netstat not available"
echo ""

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
