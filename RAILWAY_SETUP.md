# Railway Deployment Setup

## Environment Variables Requeridas

Configure las siguientes variables de entorno en tu proyecto de Railway:

### Laravel Configuration
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated-by-docker>
APP_URL=https://msautentificacion-production.up.railway.app
HTTP_HOST=msautentificacion-production.up.railway.app
APP_NAME=MS Autentificacion
```

### Database Configuration (PostgreSQL)
```
DB_CONNECTION=pgsql
DB_HOST=<railway-postgres-host>
DB_PORT=<railway-postgres-port>
DB_DATABASE=railway
DB_USERNAME=despacho
DB_PASSWORD=<tu-password>
```

### Cache & Session
```
CACHE_STORE=redis
SESSION_DRIVER=database
SESSION_LIFETIME=120
BROADCAST_CONNECTION=redis
QUEUE_CONNECTION=redis
```

### Redis (si está disponible)
```
REDIS_CLIENT=phpredis
REDIS_HOST=<railway-redis-host>
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379
```

### Optional: Mail Configuration
```
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<usuario>
MAIL_PASSWORD=<contraseña>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
```

## Pasos para Railway

1. **Conecta tu repositorio** a Railway
2. **Agrega una base de datos PostgreSQL** (si no está ya vinculada)
3. **Configura las variables de entorno** en Settings → Variables
4. **Deploy** - Railway debería automáticamente:
   - Detectar el Dockerfile
   - Construir la imagen
   - Ejecutar los comandos de inicialización (migraciones, cachés)
   - Iniciar la aplicación

## Verificación

Una vez deployado, verifica que funciona:

```bash
# Health check
curl https://msautentificacion-production.up.railway.app/api/health

# GraphQL endpoint
curl -X POST https://msautentificacion-production.up.railway.app/graphql
```

## Troubleshooting

### Error 502
- Revisa los logs: `railway logs`
- Verifica que PHP-FPM está escuchando en 127.0.0.1:9000
- Verifica que Nginx está accesible en puerto 80

### PostgreSQL Connection Failed
- Verifica que `DB_CONNECTION=pgsql` está configurado
- Verifica credenciales de PostgreSQL
- Confirma que las extensiones PDO están instaladas (visible en logs de startup)

### URL Generation Issues
- Verifica que `APP_URL` coincide con tu dominio de Railway
- Verifica que los headers `X-Forwarded-*` están siendo respetados
