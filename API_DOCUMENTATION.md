# MS Autenticación - API GraphQL Documentation

## Overview

MS Autenticación is a Laravel-based microservice that handles user authentication, role-based access control (RBAC), and permission management for the ambulance dispatch system. It uses:

- **GraphQL** for API operations
- **Laravel Sanctum** for token-based API authentication
- **Spatie Laravel Permission** for role and permission management
- **SQL Server** for data persistence (with special handling for ODBC)

## Architecture

### Authentication Flow

1. User sends credentials (email/password) to `login` mutation
2. AuthService validates credentials and user active status
3. Sanctum creates a personal access token with user's permissions
4. Token is returned to client for subsequent requests
5. Client includes token in Authorization header: `Bearer <token>`
6. AttemptAuthentication middleware validates token on each request

### Role and Permission Structure

**Roles:**
- `admin` - Full system access
- `paramedic` - Field personnel (patients, ambulances, dispatches)
- `dispatcher` - Dispatch center operations
- `hospital` - Hospital staff (patient viewing/editing)
- `doctor` - Medical personnel
- `system` - Automated systems (n8n, etc.)

**Permissions:** 18 granular permissions across users, ambulances, dispatches, patients, reports, and settings management.

---

## GraphQL Queries

### 1. Get Current User

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

**Response:**
```json
{
  "data": {
    "me": {
      "id": "1",
      "name": "John Paramedic",
      "email": "john@ambulancia.local",
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

### 2. Get User by ID

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

### 3. List All Users (Paginated)

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

### 4. Get All Roles

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

### 5. Get All Permissions

```graphql
query GetPermissions {
  permissions {
    id
    name
    description
  }
}
```

### 6. Get Current User's Permissions

```graphql
query GetMyPermissions {
  userPermissions {
    id
    name
    description
  }
}
```

### 7. Validate Token

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

## GraphQL Mutations

### 1. User Login

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

**Response:**
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
        "name": "John Paramedic",
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

### 2. WhatsApp Login (Auto-Registration)

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

**Note:** Phone numbers are automatically formatted to Spanish format (+34) if not provided.

### 3. User Logout

```graphql
mutation Logout {
  logout {
    success
    message
  }
}
```

**Headers:**
```
Authorization: Bearer <token>
```

### 4. Refresh Token

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

**Headers:**
```
Authorization: Bearer <token>
```

### 5. Create User (Admin Only)

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
  "name": "Jane Doctor",
  "email": "jane.doctor@hospital.local",
  "phone": "+34699999999",
  "password": "SecurePassword123!",
  "role": "doctor"
}
```

### 6. Update User

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
  "name": "Updated Name",
  "phone": "+34612345680"
}
```

### 7. Delete User (Admin Only)

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

### 8. Assign Role to User (Admin Only)

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

### 9. Remove Role from User (Admin Only)

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

## Setup and Installation

### Prerequisites

- PHP 8.2+
- Laravel 12
- SQL Server or compatible database
- Composer

### Installation Steps

1. **Clone and Install Dependencies**
   ```bash
   cd ms_autentificacion
   composer install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

4. **Run Migrations**
   ```bash
   php artisan migrate
   ```

5. **Setup Roles and Permissions**
   ```bash
   php artisan auth:setup
   ```

6. **Create Admin and Test Users**
   ```bash
   php artisan auth:create-admin
   ```

7. **Clear Cache**
   ```bash
   php artisan cache:clear
   ```

8. **Start Development Server**
   ```bash
   php artisan serve
   ```

The GraphQL endpoint will be available at: `http://localhost:8000/graphql`

---

## Integration with Other Microservices

### Cross-Microservice Authentication

To validate tokens from other microservices, use the `validateToken` query:

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

### Token Format

- **Format:** `ID|HexString` (separated by pipe)
- **Example:** `1|NFP3U4xprX28BRVQg3yFqVvUuumaqfnf38w8ITcpc0a015ff`
- **Duration:** 24 hours
- **Revocation:** Revoked on logout or password change

### Using Token in Requests

Include in Authorization header:
```
Authorization: Bearer <token>
```

### Example Node.js Integration

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
  console.log('User:', response.data.data.me);
});
```

---

## Error Handling

### GraphQL Errors

All errors are returned in the standard GraphQL format:

```json
{
  "errors": [
    {
      "message": "User not found",
      "extensions": {
        "debugMessage": "The email provided does not exist in our records",
        "file": "app/Services/AuthService.php",
        "line": 25
      }
    }
  ]
}
```

### Common Error Codes

| Status | Message | Cause |
|--------|---------|-------|
| 401 | Unauthenticated | Missing or invalid token |
| 403 | Unauthorized | Insufficient permissions |
| 404 | Not Found | User/Role/Permission not found |
| 422 | Validation Failed | Invalid input parameters |

---

## Deployment

### Docker Deployment

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    composer \
    git \
    libpq-dev \
    mssql-tools

# Copy application
COPY . /app
WORKDIR /app

# Install PHP dependencies
RUN composer install --no-dev

# Run migrations
CMD php artisan migrate && php artisan serve --host=0.0.0.0
```

### Kubernetes Deployment

The service includes Kubernetes manifests:
- `k8s/deployment.yaml` - 3-10 pod replicas
- `k8s/service.yaml` - ClusterIP service
- `k8s/configmap.yaml` - Configuration
- `k8s/hpa.yaml` - Auto-scaling (70% CPU, 80% Memory)
- `k8s/ingress.yaml` - HTTPS ingress with cert-manager

---

## Testing

Run the test suite:

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/GraphQL/AuthenticationTest.php

# With coverage
php artisan test --coverage
```

Test files include:
- `tests/Feature/GraphQL/AuthenticationTest.php` - Login, logout, token validation
- `tests/Feature/GraphQL/UserManagementTest.php` - User CRUD and role management

---

## Performance and Security

### Security Features

- **Sanctum Tokens**: Secure, revocable API tokens
- **Password Hashing**: bcrypt with configurable rounds
- **Rate Limiting**: Throttle requests per IP
- **CSRF Protection**: Built-in for web routes
- **SQL Injection Protection**: Parameterized queries and Laravel ORM

### Performance Considerations

- Token validation happens on every request
- User permissions are cached in token
- Role and permission relationships use eager loading
- GraphQL queries are optimized with field resolvers

### Production Recommendations

1. **Enable HTTPS** - All communication over TLS
2. **Set SANCTUM_STATEFUL_DOMAINS** - Prevent CSRF
3. **Configure CORS** - Restrict to known domains
4. **Use Environment Variables** - Never commit secrets
5. **Monitor Database** - Watch query performance
6. **Set Up Logging** - Track authentication events

---

## Support and Troubleshooting

### Common Issues

**1. "No role named X for guard Y"**
- Ensure roles were created with matching guard
- Run: `php artisan auth:setup`

**2. ODBC Timestamp Errors**
- This service uses raw SQL for timestamp-sensitive operations
- Not applicable to other databases

**3. Token Not Working**
- Verify token format: `ID|HexString`
- Check token hasn't expired (24 hours)
- Ensure Authorization header format: `Bearer <token>`

### Debug Mode

Enable debug logging in `.env`:
```
APP_DEBUG=true
LOG_LEVEL=debug
```

Check logs in: `storage/logs/laravel.log`

---

## Changelog

### Version 1.0.0 (Initial Release)

- GraphQL API for authentication
- Role-based access control
- Token-based API authentication
- User management
- Role and permission management
- WhatsApp phone-based auto-registration

---

## License

This microservice is part of the ambulance dispatch system project.
