# Multi-stage build para optimizar la imagen
FROM php:8.2-fpm-alpine AS builder

# Instalar dependencias del sistema
RUN apk add --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    git \
    curl \
    postgresql-dev \
    freetds-dev

# Instalar extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pdo_dblib \
    mbstring \
    ctype \
    bcmath

# Instalar composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias PHP con composer
RUN composer install --no-dev --optimize-autoloader --no-progress --no-interaction

# Generar app key si no existe
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Imagen final para producción
FROM php:8.2-fpm-alpine

# Instalar dependencias de build necesarias para compilar extensiones
RUN apk add --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    postgresql-dev \
    freetds-dev \
    supervisor \
    nginx

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pdo_dblib \
    mbstring \
    ctype \
    bcmath

# Remover dependencias de build para reducir tamaño de imagen
RUN apk del --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    freetds-dev

# Instalar solo las librerías de runtime necesarias
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    zlib \
    libzip \
    oniguruma \
    libpq \
    freetds

# Copiar archivos desde el builder
COPY --from=builder /app /app
COPY --from=builder /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Crear directorios necesarios
RUN mkdir -p storage/logs bootstrap/cache /var/log/supervisor && \
    touch /var/log/php-fpm-error.log /var/log/php-fpm-access.log && \
    chmod 666 /var/log/php-fpm-error.log /var/log/php-fpm-access.log && \
    chown -R www-data:www-data /app

# Configurar PHP-FPM
RUN mkdir -p /etc/php-fpm.d
COPY docker/php-fpm.conf /etc/php-fpm.d/www.conf

# Configurar Nginx
RUN mkdir -p /etc/nginx/conf.d
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copiar archivo de supervisord para gestionar múltiples procesos
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exponer puerto
EXPOSE 8000 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php -r 'exit((int)!(http_response_code(file_get_contents("http://localhost:8000/health")) === 200));' || exit 1

# Comando de inicio
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
