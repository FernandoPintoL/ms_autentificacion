# Workflow Docker - MS Autentificación (Laravel)

## 🎯 Configuración General

El microservicio de autenticación está dockerizado como un contenedor PHP-FPM + Nginx + Supervisor:

- **Endpoint**: `http://localhost:8000/graphql` (GraphQL)
- **Puerto**: `8000` ✅
- **Framework**: Laravel con Sanctum
- **Base de datos**: Conexión a BD local (PostgreSQL o SQL Server)
- **Proceso Manager**: Supervisor (maneja PHP-FPM y Nginx)

### Características

- ✅ Multi-stage build optimizado (builder → runtime)
- ✅ PHP-FPM con FastCGI Process Manager
- ✅ Nginx para reverse proxy
- ✅ Supervisor para gestionar múltiples procesos
- ✅ Health checks cada 30 segundos
- ✅ Soporte para PostgreSQL y SQL Server
- ✅ Conexión a base de datos local (host.docker.internal)

## 📋 Arquitectura de Contenedor

```
┌─────────────────────────────────────┐
│   Docker Container (Port 8000)      │
├─────────────────────────────────────┤
│  Supervisor (Process Manager)       │
│  ├── PHP-FPM (Backend)              │
│  └── Nginx (Reverse Proxy)          │
└─────────────────────────────────────┘
        ↓ (Puerto 8000)
┌─────────────────────────────────────┐
│   Host Machine                      │
│  Database (PostgreSQL/SQL Server)   │
│  via host.docker.internal           │
└─────────────────────────────────────┘
```

## 🚀 Pasos Iniciales (Solo una vez)

### 1. Construir la imagen del ms_autentificacion

```bash
cd D:\SWII\micro_servicios
docker-compose build ms-autentificacion
```

### 2. Ejecutar el servicio

```bash
# Opción 1: En foreground (ver logs)
docker-compose up ms-autentificacion

# Opción 2: En background
docker-compose up -d ms-autentificacion
```

### 3. Verificar que está corriendo

```bash
# Ver estado del contenedor
docker-compose ps

# Ver logs
docker-compose logs -f ms-autentificacion

# Probar endpoint
curl http://localhost:8000/graphql
```

## 📝 Pasos Posteriores a Cambios

Cada vez que realices cambios en `./ms_autentificacion`:

### Opción A: Reconstrucción Completa (Recomendado)

```bash
docker-compose down
docker-compose build --no-cache ms-autentificacion
docker-compose up -d ms-autentificacion
```

### Opción B: Desarrollo Rápido (Sin Docker)

Para cambios rápidos sin recompilar la imagen:

```bash
cd ./ms_autentificacion
composer install
php -S localhost:8000
```

Esto ejecutará Laravel en `http://localhost:8000` con recargas automáticas.

### Opción C: Desarrollo con Docker Compose (Completo)

```bash
# Terminal 1: Iniciar todo
docker-compose up frontend apollo-gateway ms-autentificacion

# Terminal 2: Tus otros microservicios (si existen)
cd ./ms-despacho
npm run dev
```

## 🗄️ Configuración de Base de Datos

El servicio está configurado para conectarse a una base de datos en tu máquina local, no dockerizada.

### Opción 1: PostgreSQL Local (Predeterminado)

Si tienes PostgreSQL instalado en tu máquina:

```bash
# Variables en docker-compose.yml
DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5432
DB_DATABASE=ms_autentificacion
DB_USERNAME=postgres
DB_PASSWORD=your_local_postgres_password
```

**Pasos:**

1. Crear la base de datos en PostgreSQL:
```sql
CREATE DATABASE ms_autentificacion;
```

2. Actualizar `docker-compose.yml` con tu contraseña real:
```yaml
- DB_PASSWORD=tu_contraseña_postgres_real
```

3. Ejecutar migraciones dentro del contenedor:
```bash
docker-compose exec ms-autentificacion php artisan migrate
```

### Opción 2: SQL Server Local

Si prefieres usar SQL Server:

1. Actualizar `docker-compose.yml` - comentar PostgreSQL y descomentar SQL Server:

```yaml
# Comment out PostgreSQL lines:
# - DB_CONNECTION=pgsql
# - DB_HOST=host.docker.internal
# - DB_PORT=5432

# Uncomment SQL Server lines:
- DB_CONNECTION=sqlsrv
- DB_HOST=host.docker.internal
- DB_PORT=1433
- DB_DATABASE=ms_autentificacion
- DB_USERNAME=sa
- DB_PASSWORD=your_local_sqlserver_password
```

2. Crear la base de datos en SQL Server:
```sql
CREATE DATABASE ms_autentificacion;
```

3. Reconstruir el contenedor:
```bash
docker-compose down
docker-compose build --no-cache ms-autentificacion
docker-compose up -d ms-autentificacion
```

4. Ejecutar migraciones:
```bash
docker-compose exec ms-autentificacion php artisan migrate
```

## 🔧 Variables de Entorno

Las siguientes variables se inyectan en tiempo de ejecución desde `docker-compose.yml`:

```bash
# Aplicación
APP_NAME=MS Autenticacion
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:M2h47SJxWQ2HYegzdg4w+Wqd6ZchBJoiU+dJW2HWK/M=

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Base de Datos (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5432
DB_DATABASE=ms_autentificacion
DB_USERNAME=postgres
DB_PASSWORD=your_local_postgres_password

# Laravel
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log

# Sanctum (Autenticación)
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000,127.0.0.1:8000
SANCTUM_AUTH_COOKIE=XSRF-TOKEN

# GraphQL
GRAPHQL_ENDPOINT=/graphql
GRAPHQL_PLAYGROUND_ENABLED=true

# CORS
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=*
```

## 📂 Estructura del Contenedor

```
Dockerfile
├── Stage 1: Builder (PHP 8.2)
│   ├── Instalar extensiones PHP
│   ├── Instalar Composer
│   ├── Copiar código
│   └── Instalar dependencias (composer install)
│
└── Stage 2: Runtime (PHP 8.2 Alpine)
    ├── Instalar runtime dependencies
    ├── Instalar PHP extensions
    ├── Copiar código desde builder
    ├── Configurar PHP-FPM
    ├── Configurar Nginx
    ├── Configurar Supervisor
    └── Exponer puerto 8000
```

## 🔌 Puntos de Integración

### Con Apollo Gateway

El Apollo Gateway automáticamente descubre este servicio cuando está corriendo:

```
Apollo Gateway (4000)
    ↓
MS Autentificación (8000)
    ↓ (via host.docker.internal)
BD Local (PostgreSQL/SQL Server)
```

**Verificar integración:**

```bash
# Acceder a Apollo Sandbox
http://localhost:4000/sandbox

# Ejecutar una query de autenticación
query {
  users {
    id
    email
    name
  }
}
```

### Con Frontend React

El frontend se conecta a través del Apollo Gateway:

```
Frontend (3000)
    ↓
Apollo Gateway (4000) - GraphQL Proxy
    ↓
MS Autentificación (8000) - Subgraph
```

## 🔍 Monitoreo

### Ver logs en tiempo real

```bash
docker-compose logs -f ms-autentificacion
```

### Ver últimas 50 líneas

```bash
docker-compose logs --tail=50 ms-autentificacion
```

### Acceder al contenedor

```bash
docker-compose exec ms-autentificacion sh
```

### Verificar estado

```bash
# Estado del contenedor
docker-compose ps

# Health check
curl http://localhost:8000/health

# GraphQL Playground
http://localhost:8000/graphql
```

## 🛠️ Tareas Comunes en el Contenedor

### Ejecutar migraciones

```bash
docker-compose exec ms-autentificacion php artisan migrate
```

### Crear usuario de prueba

```bash
docker-compose exec ms-autentificacion php artisan tinker
```

### Ver logs de Laravel

```bash
docker-compose exec ms-autentificacion tail -f storage/logs/laravel.log
```

### Ejecutar seeders

```bash
docker-compose exec ms-autentificacion php artisan db:seed
```

### Ejecutar tests

```bash
docker-compose exec ms-autentificacion php artisan test
```

## 🌐 Endpoints Disponibles

| Endpoint | Descripción | Método |
|----------|-------------|--------|
| `http://localhost:8000` | Health check | GET |
| `http://localhost:8000/graphql` | GraphQL Endpoint | POST |
| `http://localhost:4000/sandbox` | Apollo Sandbox (para probar) | GET |
| `http://localhost:3000` | Frontend integrado | GET |

## 🐛 Troubleshooting

### El contenedor no inicia

```bash
docker-compose logs ms-autentificacion

# Errores comunes:
# - "Connection refused" en BD: Verificar que PostgreSQL/SQL Server está corriendo
# - "Port already in use": Otro proceso usa puerto 8000
# - "Permission denied": Ejecutar con sudo si es necesario
```

### Conexión a BD rechazada

```bash
# Verificar que la BD está corriendo en tu máquina
# Para PostgreSQL:
psql -U postgres -h localhost -d postgres -c "SELECT 1"

# Para SQL Server:
sqlcmd -S localhost -U sa -P your_password -Q "SELECT 1"

# Si funciona, es un problema de configuración en docker-compose.yml
# Asegúrate de:
# 1. host.docker.internal está correcto
# 2. Credenciales son correctas
# 3. Base de datos existe
```

### Puerto 8000 ya está en uso (Windows)

```powershell
netstat -ano | findstr :8000
taskkill /PID <PID> /F
```

### Build falla

```bash
# Limpiar caché y reconstruir
docker-compose build --no-cache ms-autentificacion

# Verificar que Dockerfile es válido
docker build --help

# Verificar que composer.json es válido
docker-compose exec ms-autentificacion composer validate
```

### Error "host.docker.internal not found"

Esto significa que estás en Linux. Soluciones:

```bash
# En Linux, usar la IP del host o "172.17.0.1"
# Actualizar docker-compose.yml:
- DB_HOST=172.17.0.1  # En lugar de host.docker.internal
```

## 📊 Workflow Completo

### Terminal 1: Iniciar infraestructura Docker

```bash
cd D:\SWII\micro_servicios
docker-compose up frontend apollo-gateway ms-autentificacion
```

### Terminal 2: Iniciar otros microservicios (opcional)

```bash
# Si tienes ms-despacho u otros servicios
cd D:\SWII\micro_servicios\ms-despacho
npm run dev
```

### Acceder a la aplicación

```
Frontend: http://localhost:3000
Apollo Sandbox: http://localhost:4000/sandbox
MS Autentificacion GraphQL: http://localhost:8000/graphql
```

## ✅ Checklist de Configuración Inicial

- [ ] Tengo PostgreSQL o SQL Server instalado en mi máquina
- [ ] He creado la base de datos `ms_autentificacion`
- [ ] He actualizado `docker-compose.yml` con mis credenciales
- [ ] He ejecutado `docker-compose build ms-autentificacion`
- [ ] Puedo conectar a la BD desde mi máquina local
- [ ] He ejecutado `docker-compose up ms-autentificacion`
- [ ] El contenedor está en estado "healthy"
- [ ] Puedo acceder a `http://localhost:8000/graphql`

## 🚀 Consideraciones de Producción

Para producción, actualizar:

1. **Dockerfile**:
   - Cambiar `FROM php:8.2-fpm-alpine` a versión estable fija
   - Usar `--no-dev` en composer install

2. **docker-compose.yml**:
   - Cambiar `APP_ENV` a `production`
   - Cambiar `APP_DEBUG` a `false`
   - Usar BD remota en lugar de `host.docker.internal`
   - Usar secrets para contraseñas
   - Cambiar `APP_KEY` a una clave real

3. **Seguridad**:
   - Configurar SSL/TLS en Nginx
   - Limitar CORS a dominios específicos
   - Usar variables de entorno para secretos
   - Ejecutar contenedor como usuario no-root

4. **Performance**:
   - Aumentar workers de PHP-FPM
   - Configurar Redis para caché
   - Usar CDN para assets estáticos
   - Implementar rate limiting

## 📚 Documentación Relacionada

- **Docker Setup General**: Ver `DOCKER_SETUP_SUMMARY.md`
- **Apollo Gateway**: Ver `DOCKER_WORKFLOW.md`
- **Frontend**: Ver `FRONTEND_DOCKER_WORKFLOW.md`
- **Laravel**: https://laravel.com/docs
- **Sanctum**: https://laravel.com/docs/sanctum

## 📝 Notas Importantes

1. **host.docker.internal**: Solo funciona en Docker Desktop (Windows/Mac). En Linux usar `172.17.0.1`
2. **Network Mode**: Se usa `host` para acceso directo a localhost
3. **Health Checks**: Se ejecutan cada 30 segundos, importante para monitoreo
4. **Multi-stage Build**: Reduce el tamaño de imagen final (~200MB vs 800MB)
5. **Supervisor**: Maneja PHP-FPM y Nginx en un solo contenedor

---

**Última actualización**: 11/11/2025
**Estado**: Production-Ready
**Autor**: Docker Setup Automation
