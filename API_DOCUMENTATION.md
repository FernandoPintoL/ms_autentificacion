# MS Autenticación - Documentación API GraphQL

## Descripción General

MS Autenticación es un microservicio basado en Laravel que maneja autenticación de usuarios, control de acceso basado en roles (RBAC) y gestión de permisos para el sistema de despacho de ambulancias. Utiliza:

- **GraphQL** para operaciones de API
- **Laravel Sanctum** para autenticación de API basada en tokens
- **Spatie Laravel Permission** para gestión de roles y permisos
- **SQL Server** para persistencia de datos (con manejo especial para ODBC)

## Arquitectura

### Flujo de Autenticación

1. El usuario envía credenciales (email/contraseña) a la mutación `login`
2. AuthService valida credenciales y estado activo del usuario
3. Sanctum crea un token de acceso personal con los permisos del usuario
4. El token se devuelve al cliente para solicitudes posteriores
5. El cliente incluye el token en el encabezado Autorización: `Bearer <token>`
6. El middleware AttemptAuthentication valida el token en cada solicitud

### Estructura de Roles y Permisos

**Roles:**
- `admin` - Acceso completo al sistema
- `paramedic` - Personal de campo (pacientes, ambulancias, despachos)
- `dispatcher` - Operaciones del centro de despacho
- `hospital` - Personal del hospital (visualización/edición de pacientes)
- `doctor` - Personal médico
- `system` - Sistemas automatizados (n8n, etc.)

**Permisos:** 18 permisos granulares en gestión de usuarios, ambulancias, despachos, pacientes, reportes y configuración.

---

## Consultas GraphQL

### 1. Obtener Usuario Actual

```graphql
query Me {
  me {
    id
    name
    email
    phone
    status
    roles {
      id
      name
    }
    permissions {
      id
      name
    }
  }
}
```

**Respuesta:**
```json
{
  "data": {
    "me": {
      "id": "1",
      "name": "Juan Paramédico",
      "email": "juan@ambulancia.local",
      "phone": "+34612345678",
      "status": "active",
      "roles": [
        {
          "id": "1",
          "name": "paramedic"
        }
      ],
      "permissions": [
        {
          "id": "1",
          "name": "view-patients"
        },
        {
          "id": "2",
          "name": "create-patient"
        }
      ]
    }
  }
}
```

### 2. Obtener Usuario por ID

```graphql
query GetUser($id: ID!) {
  user(id: $id) {
    id
    name
    email
    phone
    status
    created_at
    updated_at
  }
}
```

**Variables:**
```json
{
  "id": "2"
}
```

### 3. Listar Todos los Usuarios (Paginado)

```graphql
query ListUsers($first: Int!, $page: Int!) {
  users(first: $first, page: $page) {
    data {
      id
      name
      email
      phone
      status
    }
    paginatorInfo {
      total
      perPage
      currentPage
      lastPage
      hasMorePages
    }
  }
}
```

**Variables:**
```json
{
  "first": 10,
  "page": 1
}
```

### 4. Obtener Todos los Roles

```graphql
query GetRoles {
  roles {
    id
    name
    description
    permissions {
      id
      name
    }
  }
}
```

### 5. Obtener Todos los Permisos

```graphql
query GetPermissions {
  permissions {
    id
    name
    description
  }
}
```

### 6. Obtener Permisos del Usuario Actual

```graphql
query GetMyPermissions {
  userPermissions {
    id
    name
    description
  }
}
```

### 7. Validar Token

```graphql
query ValidateToken($token: String!) {
  validateToken(token: $token) {
    valid
    message
    user {
      id
      email
    }
    permissions {
      name
    }
  }
}
```

**Variables:**
```json
{
  "token": "1|NFP3U4xprX28BRVQg3yFqVvUuumaqfnf38w8ITcpc0a015ff"
}
```

---

## Mutaciones GraphQL

### 1. Iniciar Sesión de Usuario

```graphql
mutation Login($email: String!, $password: String!) {
  login(email: $email, password: $password) {
    success
    message
    token
    tokenType
    expiresAt
    user {
      id
      name
      email
      roles {
        name
      }
    }
    permissions {
      name
    }
  }
}
```

**Variables:**
```json
{
  "email": "paramedic@ambulancia.local",
  "password": "paramedic@123456"
}
```

**Respuesta:**
```json
{
  "data": {
    "login": {
      "success": true,
      "message": "Autenticación exitosa",
      "token": "1|NFP3U4xprX28BRVQg3yFqVvUuumaqfnf38w8ITcpc0a015ff",
      "tokenType": "Bearer",
      "expiresAt": "2025-10-26T14:30:00.000000Z",
      "user": {
        "id": "2",
        "name": "Juan Paramédico",
        "email": "paramedic@ambulancia.local",
        "roles": [
          {
            "name": "paramedic"
          }
        ]
      },
      "permissions": [
        {
          "name": "view-patients"
        },
        {
          "name": "create-patient"
        }
      ]
    }
  }
}
```

### 2. Iniciar Sesión por WhatsApp (Auto-Registro)

```graphql
mutation LoginWhatsApp($phone: String!) {
  loginWhatsApp(phone: $phone) {
    success
    message
    token
    tokenType
    user {
      id
      phone
      status
    }
  }
}
```

**Variables:**
```json
{
  "phone": "612345678"
}
```

**Nota:** Los números de teléfono se formatean automáticamente al formato español (+34) si no se proporcionan.

### 3. Cerrar Sesión del Usuario

```graphql
mutation Logout {
  logout {
    success
    message
  }
}
```

**Encabezados:**
```
Authorization: Bearer <token>
```

### 4. Refrescar Token

```graphql
mutation RefreshToken {
  refreshToken {
    success
    message
    token
    permissions {
      name
    }
  }
}
```

**Encabezados:**
```
Authorization: Bearer <token>
```

### 5. Crear Usuario (Solo Admin)

```graphql
mutation CreateUser(
  $name: String!
  $email: String!
  $phone: String!
  $password: String!
  $role: String!
) {
  createUser(
    name: $name
    email: $email
    phone: $phone
    password: $password
    role: $role
  ) {
    success
    message
    user {
      id
      name
      email
      phone
      status
    }
  }
}
```

**Variables:**
```json
{
  "name": "Juana Doctora",
  "email": "juana.doctora@hospital.local",
  "phone": "+34699999999",
  "password": "ContraseñaSegura123!",
  "role": "doctor"
}
```

### 6. Actualizar Usuario

```graphql
mutation UpdateUser(
  $id: ID!
  $name: String
  $phone: String
  $email: String
  $status: String
) {
  updateUser(
    id: $id
    name: $name
    phone: $phone
    email: $email
    status: $status
  ) {
    success
    message
    user {
      id
      name
      email
      phone
      status
    }
  }
}
```

**Variables:**
```json
{
  "id": "2",
  "name": "Nombre Actualizado",
  "phone": "+34612345680"
}
```

### 7. Eliminar Usuario (Solo Admin)

```graphql
mutation DeleteUser($id: ID!) {
  deleteUser(id: $id) {
    success
    message
  }
}
```

**Variables:**
```json
{
  "id": "5"
}
```

### 8. Asignar Rol a Usuario (Solo Admin)

```graphql
mutation AssignRole($userId: ID!, $role: String!) {
  assignRoleToUser(userId: $userId, role: $role) {
    success
    message
    user {
      id
      roles {
        name
      }
    }
  }
}
```

**Variables:**
```json
{
  "userId": "3",
  "role": "dispatcher"
}
```

### 9. Remover Rol de Usuario (Solo Admin)

```graphql
mutation RemoveRole($userId: ID!, $role: String!) {
  removeRoleFromUser(userId: $userId, role: $role) {
    success
    message
    user {
      id
      roles {
        name
      }
    }
  }
}
```

**Variables:**
```json
{
  "userId": "3",
  "role": "dispatcher"
}
```

---

## Configuración e Instalación

### Requisitos Previos

- PHP 8.2+
- Laravel 12
- SQL Server o base de datos compatible
- Composer

### Pasos de Instalación

1. **Clonar e Instalar Dependencias**
   ```bash
   cd ms_autentificacion
   composer install
   ```

2. **Configurar Entorno**
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales de base de datos
   ```

3. **Generar Clave de Aplicación**
   ```bash
   php artisan key:generate
   ```

4. **Ejecutar Migraciones**
   ```bash
   php artisan migrate
   ```

5. **Configurar Roles y Permisos**
   ```bash
   php artisan auth:setup
   ```

6. **Crear Usuario Admin y Usuarios de Prueba**
   ```bash
   php artisan auth:create-admin
   ```

7. **Limpiar Caché**
   ```bash
   php artisan cache:clear
   ```

8. **Iniciar Servidor de Desarrollo**
   ```bash
   php artisan serve
   ```

El endpoint de GraphQL estará disponible en: `http://localhost:8000/graphql`

---

## Integración con Otros Microservicios

### Autenticación Entre Microservicios

Para validar tokens de otros microservicios, utiliza la consulta `validateToken`:

```graphql
query ValidateToken($token: String!) {
  validateToken(token: $token) {
    valid
    message
    user {
      id
      email
    }
    permissions {
      name
    }
  }
}
```

### Formato de Token

- **Formato:** `ID|CadenaHex` (separada por barra)
- **Ejemplo:** `1|NFP3U4xprX28BRVQg3yFqVvUuumaqfnf38w8ITcpc0a015ff`
- **Duración:** 24 horas
- **Revocación:** Se revoca al cerrar sesión o cambiar contraseña

### Usar Token en Solicitudes

Incluir en el encabezado de Autorización:
```
Authorization: Bearer <token>
```

### Ejemplo de Integración con Node.js

```javascript
const axios = require('axios');

const token = '1|NFP3U4xprX28BRVQg3yFqVvUuumaqfnf38w8ITcpc0a015ff';

const query = `
  query Me {
    me {
      id
      name
      email
      permissions { name }
    }
  }
`;

axios.post('http://ms-auth:8000/graphql',
  { query },
  {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  }
).then(response => {
  console.log('Usuario:', response.data.data.me);
});
```

---

## Manejo de Errores

### Errores de GraphQL

Todos los errores se devuelven en el formato estándar de GraphQL:

```json
{
  "errors": [
    {
      "message": "Usuario no encontrado",
      "extensions": {
        "debugMessage": "El email proporcionado no existe en nuestros registros",
        "file": "app/Services/AuthService.php",
        "line": 25
      }
    }
  ]
}
```

### Códigos de Error Comunes

| Estado | Mensaje | Causa |
|--------|---------|-------|
| 401 | Sin Autenticación | Falta token o token inválido |
| 403 | No Autorizado | Permisos insuficientes |
| 404 | No Encontrado | Usuario/Rol/Permiso no encontrado |
| 422 | Validación Fallida | Parámetros de entrada inválidos |

---

## Despliegue

### Despliegue con Docker

```dockerfile
FROM php:8.2-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    composer \
    git \
    libpq-dev \
    mssql-tools

# Copiar aplicación
COPY . /app
WORKDIR /app

# Instalar dependencias de PHP
RUN composer install --no-dev

# Ejecutar migraciones
CMD php artisan migrate && php artisan serve --host=0.0.0.0
```

### Despliegue en Kubernetes

El servicio incluye manifiestos de Kubernetes:
- `k8s/deployment.yaml` - 3-10 réplicas de pod
- `k8s/service.yaml` - Servicio ClusterIP
- `k8s/configmap.yaml` - Configuración
- `k8s/hpa.yaml` - Auto-escalado (70% CPU, 80% Memoria)
- `k8s/ingress.yaml` - Ingress HTTPS con cert-manager

---

## Pruebas

Ejecutar el conjunto de pruebas:

```bash
# Todas las pruebas
php artisan test

# Archivo de prueba específico
php artisan test tests/Feature/GraphQL/AuthenticationTest.php

# Con cobertura
php artisan test --coverage
```

Los archivos de prueba incluyen:
- `tests/Feature/GraphQL/AuthenticationTest.php` - Inicio de sesión, cierre de sesión, validación de token
- `tests/Feature/GraphQL/UserManagementTest.php` - CRUD de usuarios y gestión de roles

---

## Rendimiento y Seguridad

### Características de Seguridad

- **Tokens Sanctum**: Tokens de API seguros y revocables
- **Contraseñas Hasheadas**: bcrypt con rondas configurables
- **Limitación de Velocidad**: Acelerar solicitudes por IP
- **Protección CSRF**: Integrada para rutas web
- **Protección contra Inyección SQL**: Consultas parametrizadas y ORM de Laravel

### Consideraciones de Rendimiento

- La validación de token ocurre en cada solicitud
- Los permisos del usuario se cachean en el token
- Las relaciones de rol y permiso utilizan carga anticipada
- Las consultas de GraphQL se optimizan con solucionadores de campos

### Recomendaciones para Producción

1. **Habilitar HTTPS** - Toda comunicación sobre TLS
2. **Configurar SANCTUM_STATEFUL_DOMAINS** - Prevenir CSRF
3. **Configurar CORS** - Restringir a dominios conocidos
4. **Usar Variables de Entorno** - Nunca hacer commit de secretos
5. **Monitorear Base de Datos** - Observar rendimiento de consultas
6. **Configurar Registro** - Rastrear eventos de autenticación

---

## Soporte y Solución de Problemas

### Problemas Comunes

**1. "No role named X for guard Y"**
- Asegúrate de que los roles se crearon con el guard coincidente
- Ejecutar: `php artisan auth:setup`

**2. Errores de Timestamp de ODBC**
- Este servicio utiliza SQL sin formato para operaciones sensibles a timestamp
- No aplicable a otras bases de datos

**3. Token No Funciona**
- Verificar formato de token: `ID|CadenaHex`
- Verificar que el token no haya expirado (24 horas)
- Asegurar formato del encabezado de Autorización: `Bearer <token>`

### Modo de Depuración

Habilitar registro de depuración en `.env`:
```
APP_DEBUG=true
LOG_LEVEL=debug
```

Verificar registros en: `storage/logs/laravel.log`

---

## Historial de Cambios

### Versión 1.0.0 (Lanzamiento Inicial)

- API GraphQL para autenticación
- Control de acceso basado en roles
- Autenticación de API basada en tokens
- Gestión de usuarios
- Gestión de roles y permisos
- Auto-registro basado en número de teléfono WhatsApp

---

## Licencia

Este microservicio es parte del proyecto del sistema de despacho de ambulancias.
