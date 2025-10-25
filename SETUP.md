# MS Autenticación - Setup & Configuración

## ✅ Instalaciones Completadas

### Paquetes PHP instalados:
- **Spatie Laravel Permission** (v6.21.0) - Gestión de roles y permisos
- **Lighthouse GraphQL** (v6.63.1) - API GraphQL
- **Laravel Predis** (v3.2.0) - Cliente Redis
- **Laravel Debugbar** (v3.16.0) - Herramienta de debugging (dev)

### Configuraciones realizadas:
- ✅ Base de datos: SQL Server 1433
- ✅ Cache: File-based (desarrollo)
- ✅ Sanctum: Tokens API autenticación
- ✅ GraphQL: Schema con queries y mutations
- ✅ CORS: Habilitado para múltiples dominios
- ✅ Migraciones: Ejecutadas (roles, permisos)

---

## 🚀 Estructura Creada

```
ms_autentificacion/
├── graphql/
│   ├── schema.graphql          ← Schema principal (queries, mutations, tipos)
│   ├── queries/                ← Queries organizadas
│   ├── mutations/              ← Mutations organizadas
│   └── types/                  ← Tipos GraphQL
├── app/GraphQL/
│   ├── Resolvers/              ← Resolvers para queries/mutations
│   └── Directives/             ← Directivas custom
├── docker/
│   ├── php-fpm.conf            ← Configuración PHP-FPM
│   ├── nginx.conf              ← Configuración Nginx
│   └── supervisord.conf        ← Gestor de procesos
├── k8s/
│   ├── deployment.yaml         ← Deployment K8s (3 réplicas)
│   ├── service.yaml            ← Service (ClusterIP)
│   ├── configmap.yaml          ← Variables de configuración
│   ├── secret.yaml             ← Credenciales sensibles
│   ├── ingress.yaml            ← Ingress (HTTPS)
│   └── hpa.yaml                ← Auto-scaling (3-10 replicas)
├── Dockerfile                  ← Multi-stage build optimizado
├── .dockerignore               ← Archivos ignorados en build Docker
├── routes/
│   └── api.php                 ← Rutas API + health check
└── .env                        ← Variables de entorno

```

---

## 📋 Variables de Entorno Configuradas

```env
# Base de datos
DB_CONNECTION=sqlsrv
DB_HOST=192.168.1.23
DB_PORT=1433
DB_DATABASE=ms_autentificacion
DB_USERNAME=sa
DB_PASSWORD=1234

# Cache
CACHE_STORE=file

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000,127.0.0.1:8000
SANCTUM_AUTH_COOKIE=XSRF-TOKEN

# GraphQL
GRAPHQL_ENDPOINT=/graphql
GRAPHQL_PLAYGROUND_ENABLED=true

# CORS
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
```

---

## 🔗 Endpoints GraphQL

### Queries (Lectura):
```graphql
query {
  me { id, email, name, roles { name } }
  validateToken(token: "abc123") { isValid, expiresAt }
  users(first: 10, page: 1) { data { id, email }, paginatorInfo { total } }
  roles { id, name, permissions { name } }
  permissions { id, name }
  userPermissions { name }
}
```

### Mutations (Escritura):
```graphql
mutation {
  login(email: "user@example.com", password: "password123")
    { token, user { id, email } }

  loginWhatsApp(phone: "+34612345678")
    { token, isNewUser }

  logout
    { success, message }

  createUser(email: "new@example.com", password: "pass123", name: "Juan", roles: ["paramedic"])
    { success, user { id } }

  assignRoleToUser(userId: "1", roleId: "paramedic")
    { success }
}
```

---

## 🐳 Build Docker

```bash
# Build imagen
docker build -t ms-autentificacion:latest .

# Run contenedor
docker run -p 8000:80 -p 9000:9000 \
  -e DB_HOST=host.docker.internal \
  -e DB_USERNAME=sa \
  -e DB_PASSWORD=1234 \
  ms-autentificacion:latest

# Test health check
curl http://localhost:8000/health
```

---

## ☸️ Despliegue en Kubernetes

```bash
# Crear namespace
kubectl create namespace ambulancia-system

# Crear secrets (cambiar valores primero!)
kubectl apply -f k8s/secret.yaml

# Crear configmap
kubectl apply -f k8s/configmap.yaml

# Crear deployment
kubectl apply -f k8s/deployment.yaml

# Crear service
kubectl apply -f k8s/service.yaml

# Crear ingress
kubectl apply -f k8s/ingress.yaml

# Crear HPA
kubectl apply -f k8s/hpa.yaml

# Verificar despliegue
kubectl get pods -n ambulancia-system
kubectl get svc -n ambulancia-system
kubectl logs -f deployment/ms-autentificacion -n ambulancia-system
```

---

## 📊 Acceder a GraphQL Playground (desarrollo)

1. **Localmente:**
   ```
   GET http://localhost:8000/graphql
   ```

2. **En Kubernetes:**
   ```
   GET https://auth.ambulancia.local/graphql
   ```

---

## ⚠️ Próximos Pasos

1. **Crear Resolvers GraphQL**
   - `app/GraphQL/Resolvers/LoginResolver.php`
   - `app/GraphQL/Resolvers/UserResolver.php`
   - `app/GraphQL/Resolvers/RolePermissionResolver.php`

2. **Implementar lógica de autenticación**
   - Servicios de login
   - Validación de tokens
   - Refresh token

3. **Crear seeders para roles y permisos**
   - Paramedic
   - Dispatcher
   - Admin
   - System (n8n)

4. **Testing**
   - Unit tests
   - Feature tests (GraphQL)
   - Integration tests

5. **Documentación API**
   - Postman collection
   - GraphQL schema documentation

6. **Integración MS WebSocket**
   - Validación de tokens
   - Canales de autorización

---

## 🔒 Seguridad

- ✅ Sanctum para API tokens
- ✅ Spatie para roles/permisos
- ✅ CORS configurado
- ✅ Rate limiting (via middleware)
- ⏳ 2FA (próximo paso)
- ⏳ JWT alternativo (opcional)

---

## 📚 Recursos

- [Lighthouse Documentation](https://lighthouse-php.com/)
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
- [Kubernetes Best Practices](https://kubernetes.io/docs/concepts/configuration/overview/)

---

**Última actualización:** 25/10/2025
**Versión:** 1.0.0-alpha
