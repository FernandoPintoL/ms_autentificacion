# MS Autentificación - Federation Testing Guide

## Overview

After implementing Apollo Federation support, test the service to ensure:
1. Schema composition works correctly
2. Entity resolvers function properly
3. Field names are correctly transformed to camelCase
4. Integration with Apollo Gateway works

---

## Pre-Testing Checklist

Before testing, ensure:
- [ ] Service is running on port 8000
- [ ] Database migrations are complete
- [ ] All resolvers are updated
- [ ] Schema file is updated with `@key` directives
- [ ] EntityResolver.php is created

---

## Test 1: GraphQL Introspection

**Purpose:** Verify the schema is correct and federation directives are recognized.

### Command:
```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ __schema { types { name } } }"
  }'
```

### Expected Response:
```json
{
  "data": {
    "__schema": {
      "types": [
        { "name": "User" },
        { "name": "Role" },
        { "name": "Permission" },
        { "name": "Query" },
        { "name": "Mutation" },
        ...
      ]
    }
  }
}
```

**Success Indicator:** User, Role, and Permission types are listed

---

## Test 2: Query Current User (with Authentication)

**Purpose:** Test that field names are correctly transformed to camelCase.

### Prerequisites:
- Generate a valid JWT token from login mutation

### Query:
```graphql
query {
  me {
    id
    name
    email
    status
    createdAt
    updatedAt
    emailVerifiedAt
    roles {
      id
      name
      createdAt
      updatedAt
    }
    permissions {
      id
      name
      createdAt
      updatedAt
    }
  }
}
```

### Expected Response:
```json
{
  "data": {
    "me": {
      "id": "1",
      "name": "John Doe",
      "email": "john@example.com",
      "status": "active",
      "createdAt": "2024-01-15 10:30:00",
      "updatedAt": "2024-01-15 10:30:00",
      "emailVerifiedAt": null,
      "roles": [
        {
          "id": "1",
          "name": "admin",
          "createdAt": "2024-01-01 08:00:00",
          "updatedAt": "2024-01-01 08:00:00"
        }
      ],
      "permissions": [
        {
          "id": "1",
          "name": "users.create",
          "createdAt": "2024-01-01 08:00:00",
          "updatedAt": "2024-01-01 08:00:00"
        }
      ]
    }
  }
}
```

**Success Indicator:** All field names use camelCase (createdAt, updatedAt, emailVerifiedAt)

---

## Test 3: Query Single User by ID

**Purpose:** Test the entity resolver is correctly set up.

### Query:
```graphql
query {
  user(id: "1") {
    id
    name
    email
    createdAt
    updatedAt
    roles {
      id
      name
    }
  }
}
```

### Expected Response:
```json
{
  "data": {
    "user": {
      "id": "1",
      "name": "John Doe",
      "email": "john@example.com",
      "createdAt": "2024-01-15 10:30:00",
      "updatedAt": "2024-01-15 10:30:00",
      "roles": [
        {
          "id": "1",
          "name": "admin"
        }
      ]
    }
  }
}
```

**Success Indicator:** User returned with correct camelCase fields

---

## Test 4: Get All Users (Admin Only)

**Purpose:** Test pagination and list queries with field transformation.

### Prerequisites:
- Must be logged in as admin
- Include Authorization header with Bearer token

### Query:
```graphql
query {
  users(first: 10, page: 1) {
    data {
      id
      name
      email
      createdAt
    }
    paginatorInfo {
      total
      currentPage
      perPage
    }
  }
}
```

### Expected Response:
```json
{
  "data": {
    "users": {
      "data": [
        {
          "id": "1",
          "name": "John Doe",
          "email": "john@example.com",
          "createdAt": "2024-01-15 10:30:00"
        }
      ],
      "paginatorInfo": {
        "total": 5,
        "currentPage": 1,
        "perPage": 10
      }
    }
  }
}
```

**Success Indicator:** Users list with camelCase fields returned with pagination info

---

## Test 5: Get All Roles

**Purpose:** Test Role entity response format.

### Query:
```graphql
query {
  roles {
    id
    name
    description
    createdAt
    updatedAt
  }
}
```

### Expected Response:
```json
{
  "data": {
    "roles": [
      {
        "id": "1",
        "name": "admin",
        "description": "Administrator role",
        "createdAt": "2024-01-01 08:00:00",
        "updatedAt": "2024-01-01 08:00:00"
      }
    ]
  }
}
```

**Success Indicator:** Roles returned with camelCase timestamps (createdAt, updatedAt)

---

## Test 6: Get All Permissions

**Purpose:** Test Permission entity response format.

### Query:
```graphql
query {
  permissions {
    id
    name
    description
    createdAt
    updatedAt
  }
}
```

### Expected Response:
```json
{
  "data": {
    "permissions": [
      {
        "id": "1",
        "name": "users.create",
        "description": "Create new users",
        "createdAt": "2024-01-01 08:00:00",
        "updatedAt": "2024-01-01 08:00:00"
      }
    ]
  }
}
```

**Success Indicator:** Permissions returned with camelCase timestamps

---

## Test 7: Mutation - Create User

**Purpose:** Test that mutations also return camelCase field names.

### Prerequisites:
- Must be logged in as admin

### Mutation:
```graphql
mutation {
  createUser(
    email: "newuser@example.com"
    name: "New User"
    phone: "1234567890"
    password: "password123"
    roleId: "2"
  ) {
    success
    message
    user {
      id
      name
      email
      createdAt
      updatedAt
      roles {
        id
        name
      }
    }
  }
}
```

### Expected Response:
```json
{
  "data": {
    "createUser": {
      "success": true,
      "message": "Usuario creado correctamente",
      "user": {
        "id": "6",
        "name": "New User",
        "email": "newuser@example.com",
        "createdAt": "2024-01-20 14:30:00",
        "updatedAt": "2024-01-20 14:30:00",
        "roles": [
          {
            "id": "2",
            "name": "dispatcher"
          }
        ]
      }
    }
  }
}
```

**Success Indicator:** Created user returned with camelCase fields

---

## Test 8: Login Mutation

**Purpose:** Test authentication response format.

### Mutation:
```graphql
mutation {
  login(email: "john@example.com", password: "password123") {
    success
    message
    token
    tokenType
    expiresAt
    user {
      id
      name
      email
      createdAt
    }
    permissions {
      id
      name
    }
  }
}
```

### Expected Response:
```json
{
  "data": {
    "login": {
      "success": true,
      "message": "Login successful",
      "token": "123|abc...",
      "tokenType": "Bearer",
      "expiresAt": "2024-01-21 14:30:00",
      "user": {
        "id": "1",
        "name": "John Doe",
        "email": "john@example.com",
        "createdAt": "2024-01-15 10:30:00"
      },
      "permissions": [
        {
          "id": "1",
          "name": "users.create"
        }
      ]
    }
  }
}
```

**Success Indicator:** AuthResponse includes correct camelCase fields and user data

---

## Test 9: Federation Introspection (Apollo Gateway)

**Purpose:** Test that Apollo Gateway can introspect this service correctly.

### When Running with Apollo Gateway:

```bash
curl -X POST http://localhost:4000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ __schema { types { name } } }"
  }'
```

**Success Indicator:** Apollo Gateway successfully introspects and includes User, Role, Permission types

---

## Test 10: Entity Resolution (Federation)

**Purpose:** Test entity resolver works when called by Apollo Gateway.

**Simulated Request** (what Gateway will send):
```graphql
query {
  _entities(representations: [
    {
      __typename: "User"
      id: "1"
    }
  ]) {
    ... on User {
      id
      name
      email
      createdAt
    }
  }
}
```

**Expected Response:**
```json
{
  "data": {
    "_entities": [
      {
        "id": "1",
        "name": "John Doe",
        "email": "john@example.com",
        "createdAt": "2024-01-15 10:30:00"
      }
    ]
  }
}
```

**Success Indicator:** Entity resolver returns correct object with all fields

---

## Testing Commands

### Run all tests with curl:
```bash
# Test 1: Introspection
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{__schema{types{name}}}"}'

# Test 2: Query me (requires auth token in header)
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"query":"{me{id name email createdAt}}"}'

# Test 3: Query user
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{user(id:\"1\"){id name email}}"}'
```

### Using GraphQL Client (Postman/Insomnia):

1. Create POST request to `http://localhost:8000/graphql`
2. Set header: `Content-Type: application/json`
3. Add Authorization header if needed: `Authorization: Bearer <token>`
4. Copy queries from above and test

---

## Troubleshooting

### Issue: Field names still appear in snake_case
**Solution:**
- Verify `AuthResolver.php`, `UserResolver.php`, and `RolePermissionResolver.php` are updated
- Check that `formatUserResponse()` methods use camelCase
- Restart the service

### Issue: Schema composition fails
**Solution:**
- Verify `extend schema @link(url: "...")` is at the top of schema.graphql
- Verify `@key(fields: "id")` is on User, Role, Permission types
- Check for syntax errors in schema file

### Issue: Entity resolver returns null
**Solution:**
- Verify `EntityResolver.php` file exists
- Check that User, Role, Permission models exist and have data
- Verify relationships are correctly loaded with `.with()`

### Issue: Roles or Permissions missing timestamps
**Solution:**
- Verify `formatRoleResponse()` and `formatPermissionResponse()` in RolePermissionResolver include `createdAt` and `updatedAt`
- Check that Role and Permission models have timestamps

---

## Success Criteria

All tests should pass with:
- ✅ camelCase field names (createdAt, updatedAt, emailVerifiedAt)
- ✅ Correct DateTime format (Y-m-d H:i:s)
- ✅ All relationships included (roles, permissions)
- ✅ Entity resolvers returning full objects
- ✅ No GraphQL errors in responses

---

## Next Steps After Testing

Once all tests pass:

1. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat: Add Apollo Federation support to MS Autentificación"
   ```

2. **Integration with Apollo Gateway**
   - Update Apollo Gateway configuration to include this service
   - Register as subgraph: `autentificacion` on `http://localhost:8000/graphql`
   - Test cross-service queries

3. **Move to Next Service**
   - Begin conversion of MS Despacho
   - Follow same pattern

---

## Reference

- Schema file: `graphql/schema.graphql`
- Entity Resolver: `app/GraphQL/Resolvers/EntityResolver.php`
- Auth Resolver: `app/GraphQL/Resolvers/AuthResolver.php`
- User Resolver: `app/GraphQL/Resolvers/UserResolver.php`
- Role/Permission Resolver: `app/GraphQL/Resolvers/RolePermissionResolver.php`
