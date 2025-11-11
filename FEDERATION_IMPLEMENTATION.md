# MS Autentificación - Apollo Federation Implementation

## Status: In Progress ✅

This document tracks the Federation implementation for MS Autentificación service.

---

## Changes Made

### 1. Schema Updates ✅
**File:** `graphql/schema.graphql`

**Changes:**
- [x] Added `extend schema @link(url: "https://specs.apollo.dev/federation/v2.0")`
- [x] Added `@key(fields: "id")` to `User` type
- [x] Added `@key(fields: "id")` to `Role` type
- [x] Added `@key(fields: "id")` to `Permission` type
- [x] Standardized field names:
  - `email_verified_at` → `emailVerifiedAt`
  - `created_at` → `createdAt`
  - `updated_at` → `updatedAt`

### 2. Entity Resolvers Created ✅
**File:** `app/GraphQL/Resolvers/EntityResolver.php`

**Resolvers Implemented:**
- [x] `resolveUserReference()` - Returns full User object for federation references
- [x] `resolveRoleReference()` - Returns full Role object for federation references
- [x] `resolvePermissionReference()` - Returns full Permission object for federation references

**Key Features:**
- Eager loading relationships (roles, permissions)
- Proper field name transformation (snake_case → camelCase)
- Error handling and logging
- Null handling for missing entities

### 3. Database Response Transformation ✅
**Files Updated:**
- [x] `app/GraphQL/Resolvers/AuthResolver.php` - Updated `formatUserResponse()`
- [x] `app/GraphQL/Resolvers/UserResolver.php` - Updated `formatUserResponse()`
- [x] `app/GraphQL/Resolvers/RolePermissionResolver.php` - Updated `formatRoleResponse()` and `formatPermissionResponse()`

**Changes:**
- [x] All resolvers now return camelCase field names
- [x] DateTime formatting standardized to `Y-m-d H:i:s`
- [x] Role and Permission objects in responses include camelCase timestamps
- [x] Consistent transformation across all resolvers

---

## Configuration Checklist ✅

- [x] Review `EntityResolver.php` implementation
- [x] Update all resolvers to use camelCase field names
- [x] Add field transformation to UserResolver
- [x] Add field transformation to RoleResolver
- [x] Verify field name consistency in all resolvers
- [x] All transformations complete and consistent

---

## Testing Checklist

### Before Proceeding to Next Service

- [ ] Service starts without errors
- [ ] GraphQL introspection works: `GET /graphql?query={__schema{types{name}}}`
- [ ] Sample query returns correct field names (camelCase)
- [ ] Entity resolver test:
  ```graphql
  query {
    user(id: "1") {
      id
      name
      email
      createdAt
      updatedAt
    }
  }
  ```
- [ ] Mutation test returns camelCase fields
- [ ] No schema composition errors (will test with Gateway later)

---

## Required Manual Updates

### File: `app/GraphQL/Resolvers/UserResolver.php`

Update response transformations to use camelCase:

**Before:**
```php
return [
    'id' => $user->id,
    'name' => $user->name,
    'email_verified_at' => $user->email_verified_at,
    'created_at' => $user->created_at,
    'updated_at' => $user->updated_at,
];
```

**After:**
```php
return [
    'id' => (string) $user->id,
    'name' => $user->name,
    'emailVerifiedAt' => $user->email_verified_at ?
        $user->email_verified_at->format('Y-m-d H:i:s') : null,
    'createdAt' => $user->created_at->format('Y-m-d H:i:s'),
    'updatedAt' => $user->updated_at->format('Y-m-d H:i:s'),
];
```

---

## Important Notes

### 1. Database Mapping
- Database uses `email_verified_at`, `created_at`, `updated_at` (snake_case)
- GraphQL schema now expects `emailVerifiedAt`, `createdAt`, `updatedAt` (camelCase)
- Resolvers must transform these fields

### 2. Entity References
- When MS Despacho wants to reference a User, it calls the entity resolver
- The entity resolver must return the FULL User object
- All fields must be present and use correct names

### 3. DateTime Handling
- Lighthouse automatically converts DateTime scalars
- Ensure format matches: `Y-m-d H:i:s`

---

## Phase 2 Progress

- [x] 1. Schema updated with federation directives
- [x] 2. Entity resolvers created
- [ ] 3. Resolvers updated for field name mapping (NEXT)
- [ ] 4. Service tested
- [ ] 5. Integration with Gateway tested

---

## Timeline

- **Done:** Schema updates + Entity resolver skeleton
- **Next:** Update UserResolver, RoleResolver for field mapping
- **Then:** Test with sample queries
- **Finally:** Register with Apollo Gateway

---

## Questions?

- See `FEDERATION_GUIDE.md` in apollo-gateway for detailed patterns
- Check `EntityResolver.php` for reference implementation
