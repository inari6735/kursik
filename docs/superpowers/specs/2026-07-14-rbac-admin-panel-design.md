# Roles & Permissions (RBAC) + Admin Panel — Design

**Date:** 2026-07-14
**Status:** Approved

## Goal

A role-based access-control system with an admin panel: a fixed permission catalog in
code, roles as editable entities (permission matrix), user–role assignment, and
authorization decisions made from JWT claims (no DB on the happy path).

## Decisions

| Decision | Choice |
|---|---|
| Granularity | Full RBAC: permission catalog as a PHP enum, roles as ORM entities with a permission set; app code checks permissions, never role names (single exception: the `^/admin` gate) |
| Panel scope v1 | Users (role assignment) + roles (CRUD) + permission matrix |
| Effect of changes | Everything from the token: roles AND computed permissions embedded in JWT claims at token creation; changes apply on the next token (silent rotation ≤ ~15 min, or re-login) |
| Panel tech | Own controllers at `/admin/**` using the project's Twig Component architecture; commands/queries via existing buses |
| Context | New bounded context `src/Access` (authorization), separate from `src/User` (identity) |

## Data model (`src/Access`)

```
src/Access/
├─ Domain/
│  ├─ Permission.php            # string enum — THE permission catalog:
│  │                            #   CourseCreate='course.create', CourseRename='course.rename',
│  │                            #   CoursePublish='course.publish', AccessManage='access.manage'
│  ├─ Role.php                  # ORM entity: id (UUID), name (unique), permissions (JSON list<string>)
│  ├─ RoleRepository.php        # interface: all(), byName(), byNames(), add(), remove(), nameExists()
│  └─ Exception/                # RoleNotFound, RoleNameTaken, ProtectedRole, LastAdminWouldBeLost
├─ Application/
│  ├─ Command/                  # CreateRole, UpdateRolePermissions, DeleteRole, AssignUserRoles
│  └─ Query/                    # ListUsersWithRoles, ListRoles, FindRole (+ DTOs)
├─ Infrastructure/
│  ├─ DoctrineRoleRepository.php
│  └─ Security/                 # TokenUser, PermissionVoter, JWT claims listener
└─ Presentation/                # AdminUserController, AdminRoleController + admin templates
```

- `Permission` is a PHP enum — adding a permission = adding a case and using it in code.
  The panel never creates permissions; the matrix renders checkboxes from
  `Permission::cases()` (grouped by prefix). `Role.permissions` stores enum values,
  validated on write.
- `User.roles` (existing JSON column) now stores **role entity names** (`["admin"]`).
  `User::getRoles()` maps them to the Symfony convention (`admin` → `ROLE_ADMIN`) and
  always appends `ROLE_USER`. No join table — the unique role name is the natural key.
- `DeleteRole` also removes the name from every user's `roles` array (single handler
  operation). Role renaming is out of scope (delete + create).
- **Seed**: a migration creates the `roles` table and inserts an `admin` role with all
  permissions; console command `app:user:promote <email>` assigns it (bootstrap of the
  first admin without UI).

## Security & JWT integration

- **Claims at token creation**: a listener on Lexik's `JWTCreatedEvent` (login and
  silent rotation share this path) loads the user's role entities, computes the union
  of their permissions, and adds a `permissions` claim next to the standard `roles`.
- **Claims-based request user**: new `TokenUser` (implements Lexik's
  `JWTUserInterface`; identifier = email, roles + permissions from the payload) and a
  `lexik_jwt` provider for the firewall's `jwt` authenticator. Happy path = zero SQL
  (today the entity provider queries the DB per request — that changes).
  `FormLoginAuthenticator` gets an explicit user loader (`UserRepository::byEmail`) in
  its `UserBadge`, so form login keeps using the entity.
- **`PermissionVoter`** — the only permission guard: supports attributes that are
  `Permission` enum values; for `TokenUser` it checks the claim (no DB); for a `User`
  entity (rare path) it computes from `RoleRepository`.
- **Changes to existing code**:
  - `access_control`: course paths are removed; `CourseController` actions call
    `denyAccessUnlessGranted(Permission::CourseCreate->value)` etc. The `^/admin` gate
    requires `ROLE_ADMIN`; panel controllers additionally require `access.manage`.
  - Templates show actions based on `is_granted('course.create')` etc. instead of
    `app.user`.
  - Anonymous denial → entry point → `/login`; authenticated denial → 403.
- **Staleness semantics (accepted)**: matrix/assignment changes take effect on the next
  token — automatically at silent rotation, at the latest after ~15 min; immediately
  after re-login. Emergency revocation = delete the user's refresh tokens (their
  session dies with the current access token).

## Admin panel

Layout with tabs (Users / Roles) under `/admin`, gated by `ROLE_ADMIN` + `access.manage`.

| Screen | Content | Actions |
|---|---|---|
| `/admin/users` | table: email, role chips, created date | "Edit roles" → role-checkbox form → `AssignUserRoles` |
| `/admin/roles` | table: name, permission count, user count | "New role" (name → `CreateRole`), edit, delete (POST + CSRF → `DeleteRole`) |
| `/admin/roles/{id}` | permission matrix: checkboxes from `Permission::cases()` grouped by prefix | save → `UpdateRolePermissions` |

**Lockout guards (in handlers):**
- the `admin` role is protected: it cannot be deleted and `access.manage` cannot be
  removed from it,
- `AssignUserRoles` refuses to leave the system with zero users holding the `admin`
  role,
- violations throw domain exceptions surfaced as flashes.

**New UI components** (utilities only inside them, per the frontend architecture):
`Badge` (role/status chip, variants), `Table` (styled wrapper, `head`/`body` blocks),
`CheckboxField` (checkbox + label), `Layout:AdminNav` (panel tabs). Panel pages are
pure component composition — zero CSS classes.

## Error handling

- Taken role name → inline form error; unknown role/user → 404.
- Role name validated against `[a-z][a-z0-9_]{1,30}`.
- All mutations are POSTs with CSRF tokens.
- Domain guard violations (protected role, last admin) → flash messages.

## Verification (no tests, per project rule)

- `lint:container`, `lint:twig`, `doctrine:schema:validate`, `tailwind:build` all pass.
- Grep: zero `class="` in admin page templates.
- Functional: `app:user:promote` against the live DB; kernel-level smoke of commands
  and queries.

## Out of scope (v1)

- Pagination, search, audit log of admin actions.
- Per-user permissions (only via roles).
- Role renaming.
- Forced token refresh on permission change (accepted ≤15 min lag).
- Managing courses from the panel.
