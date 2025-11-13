# Missing PHP Extensions & System Dependencies

## Critical Missing Extensions (10 Required)

| # | Extension | Status | Impact | Laravel Component |
|---|-----------|--------|--------|------------------|
| 1 | pdo | MISSING | Cannot connect to database | Database abstraction |
| 2 | json | MISSING | Cannot parse/encode JSON | APIs, GraphQL |
| 3 | openssl | MISSING | Cannot encrypt/decrypt | APP_KEY, HTTPS |
| 4 | filter | MISSING | Cannot validate input | Form validation |
| 5 | hash | MISSING | Cannot hash passwords | Authentication |
| 6 | tokenizer | MISSING | Cannot parse PHP | Service container |
| 7 | session | MISSING | Cannot manage sessions | SESSION_DRIVER=database |
| 8 | iconv | MISSING | Cannot convert encoding | Character encoding |
| 9 | curl | MISSING | Cannot make HTTP requests | Guzzle, APIs |
| 10 | intl | MISSING | Cannot localize (i18n) | APP_LOCALE=es |

## Project-Specific Missing

### ms_autentificacion
- **redis** (php-redis extension)
  - Configuration: REDIS_CLIENT=phpredis
  - Impact: CACHE_STORE=redis will fail
  - Status: NOT INSTALLED

### ms-despacho
- **gmp** (optional but recommended)
  - For GPS calculations with high precision
  - Used by mjaschen/phpgeo

## Missing System Libraries

| Library | Status | Impact | Needed For |
|---------|--------|--------|-----------|
| libxml2 | MISSING | DOM/XML extensions fail | XML processing |
| unixodbc | MISSING from runtime | pdo_dblib fails | SQL Server connectivity |
| icu-libs | MISSING | intl extension fails | Internationalization |

## Database Initialization Missing

### No Migrations Run
Missing 21 critical tables:

**Core (11):**
users, cache, cache_locks, jobs, job_batches, failed_jobs, sessions, migrations

**ms_autentificacion (5):**
permissions, roles, model_has_permissions, model_has_roles, role_has_permissions

**ms-despacho (5):**
ambulancias, personals, despachos, asignacion_personals, historial_rastreos

### No Seeders Run
- ms_autentificacion: AdminUserSeeder, RolePermissionSeeder
- ms-despacho: AmbulanciaSeeder, PersonalSeeder

Result: Application cannot start without these tables = 502 Error

## Configuration Issues

### Missing Initialization Commands
- php artisan migrate --force (not run)
- php artisan db:seed --force (not run)
- php artisan route:cache (not run)
- php artisan view:cache (not run)

### Missing Environment Variables
No validation for:
- DB_HOST, DB_USERNAME, DB_PASSWORD
- REDIS_HOST, REDIS_PASSWORD
- JWT_SECRET
- CORS_ALLOWED_ORIGINS

## PHP-FPM Optimization Missing

Current max_children: 20
Missing: Process idle timeout, slow request logging

## Health Check Issues

Current check only verifies config.php file exists
- Doesn't check database
- Doesn't check tables
- Too short startup time (10s vs needed 20-30s)

## Fix Summary

### Must Add (Priority 1)
1. All 10 critical PHP extensions
2. Database initialization in entrypoint
3. System libraries (libxml2, unixodbc)
4. php-redis for ms_autentificacion

### Should Add (Priority 2)
1. Database seeders
2. Route/view caching
3. Environment validation
4. Better health check

### Nice to Have (Priority 3)
1. OPcache enabling
2. PHP-FPM optimization
3. Slow query logging
4. GMP for ms-despacho

