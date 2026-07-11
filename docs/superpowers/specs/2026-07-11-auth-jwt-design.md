# Authentication with JWT — Design

**Date:** 2026-07-11
**Status:** Approved

## Goal

Add authentication to the course platform: registration, login, and logout. The whole
application (server-rendered Twig pages) authenticates via JWT stored in HttpOnly
cookies — no PHP sessions for auth.

## Decisions

| Decision | Choice |
|---|---|
| Token consumer | The whole app: Twig pages authenticated by JWT in an HttpOnly cookie (stateless firewall, no sessions) |
| User model | Classic Doctrine ORM entity — auth is infrastructure, NOT event-sourced (deliberate exception from the CQRS/ES convention used by Course) |
| JWT library | lexik/jwt-authentication-bundle (RS256 keypair, firewall integration, cookie extractor) |
| Refresh tokens | gesdinet/jwt-refresh-token-bundle: single-use refresh tokens with rotation, stored in the DB |
| Scope v1 | Registration + login + logout; role ROLE_USER; course mutations protected, course list/detail public |
| Token lifecycle | Access JWT ~15 min in cookie `AUTH_TOKEN`; refresh token ~7 days in cookie `REFRESH_TOKEN`; silent rotation |

## Architecture

### Directory layout

```
src/User/
├─ Domain/
│  ├─ User.php                    # ORM entity: id (UUID), email, password (hash), roles
│  └─ UserRepository.php          # interface: byEmail(), add(), emailExists()
├─ Application/
│  └─ Command/
│     ├─ RegisterUser.php         # (userId, email, plainPassword) + validation constraints
│     └─ RegisterUserHandler.php  # hashes password, creates entity, persists
├─ Infrastructure/
│  ├─ DoctrineUserRepository.php  # EntityManager implementation
│  └─ Security/
│     ├─ FormLoginAuthenticator.php   # POST /login: credential check via Security component
│     ├─ JwtCookieSuccessHandler.php  # on success: access JWT + refresh token → HttpOnly cookies
│     ├─ SilentRefreshListener.php    # expired access + valid refresh → rotate in-flight
│     └─ CookieLogout.php             # logout: clear cookies + revoke refresh tokens in DB
└─ Presentation/
   ├─ RegistrationController.php  # GET/POST /register
   └─ SecurityController.php      # GET /login (form), POST /logout
```

Registration goes through the existing command bus (`RegisterUser` command) for
consistency with the Course context, even though the handler persists an ORM entity
rather than appending events.

### New dependencies

- `lexik/jwt-authentication-bundle` — JWT signing/verification, RS256 keypair via
  `bin/console lexik:jwt:generate-keypair`, token read from a cookie (cookie extractor),
  not from the Authorization header.
- `gesdinet/jwt-refresh-token-bundle` — refresh token entity + rotation (single-use).
- Migrations: `user` table (UUID PK, `email` with UNIQUE index, `password`, `roles`),
  `refresh_tokens` table (per gesdinet schema).

### security.yaml

- One `main` firewall, `stateless: true` — auth uses no session.
- Provider: `entity` (User by email property).
- Authenticators: custom `FormLoginAuthenticator` + Lexik `jwt` (cookie extractor for
  `AUTH_TOKEN`).
- `login_throttling` enabled for the login endpoint.
- `access_control`: `/register`, `/login`, GET `/courses` and GET `/courses/{id}`
  public; `/courses/new`, `/courses/{id}/rename`, `/courses/{id}/publish` require
  `ROLE_USER`.
- Forms keep working without sessions because stateless CSRF is already configured
  (`config/packages/csrf.yaml`).

## Flows

### Registration (GET/POST /register)

Form (email, password ×2) → Symfony Form validation (email format, password min length 8,
repeated fields match, email uniqueness via `UserRepository::emailExists()`)
→ `CommandBus->dispatch(new RegisterUser(...))` → handler hashes the password
(`UserPasswordHasherInterface`, `auto` algorithm already configured) and persists the
entity → redirect to `/login` with a success flash. Registration does NOT auto-login.
The UNIQUE index on `email` is the last line of defense against a registration race —
it surfaces as a friendly form error, never a duplicate row.

### Login (POST /login)

`FormLoginAuthenticator` reads email + password; the entity provider loads the user;
password verification happens in the Security component. On success,
`JwtCookieSuccessHandler`:

- issues an access JWT (TTL 15 min; claims: `sub` = user id, `email`, `roles`) into
  cookie `AUTH_TOKEN` (`HttpOnly`, `Secure`, `SameSite=Lax`, path `/`),
- issues a refresh token (TTL 7 days, single-use, stored in `refresh_tokens`) into
  cookie `REFRESH_TOKEN` (`HttpOnly`, `Secure`, `SameSite=Lax`),
- redirects to `_target_path` or `/courses`.

On failure: redirect back to `/login` with one generic flash ("invalid email or
password") — no hint which part was wrong.

### Authenticated requests

The firewall extracts `AUTH_TOKEN` from the cookie; Lexik verifies signature and `exp`.
No database query on the happy path.

### Silent refresh

`SilentRefreshListener` (kernel.request, before the firewall): when `AUTH_TOKEN` is
missing/expired and `REFRESH_TOKEN` is present — validate the refresh token against the
DB, **rotate** it (old one invalidated, new one issued), mint a fresh access JWT, attach
both new cookies to the response, and let the request proceed authenticated. The user
never sees a redirect. If both tokens are dead, the entry point redirects to `/login`
(preserving `_target_path`).

Reuse of an already-rotated refresh token is treated as theft: all refresh tokens of
that user are revoked and the request is redirected to `/login`.

### Logout (POST /logout, CSRF-protected)

Clears both cookies and deletes the user's refresh tokens. The access JWT stays
technically valid for up to 15 minutes — an accepted trade-off of this architecture
(a denylist would be required for a hard kill; out of scope v1).

## Error handling

- Form validation errors render inline at the fields (422 responses, Turbo-compatible).
- Invalid/forged/expired JWT with no refresh → 401 → entry point → redirect to `/login`.
- Login throttling limits brute-force attempts (built-in `login_throttling`).
- Refresh-token reuse → revoke all user refresh tokens, force re-login.

## Testing

- **Unit**: `RegisterUserHandler` (hashing delegated, repository called) against an
  in-memory repository; email-uniqueness validator.
- **Integration**: `DoctrineUserRepository` against test PostgreSQL; refresh-token
  rotation (issue → use → old token rejected).
- **Functional (`WebTestCase`)** — the core of the suite, since auth is mostly wiring:
  - registration end-to-end (form → user row exists),
  - login sets both cookies with HttpOnly flag,
  - request with valid `AUTH_TOKEN` reaches a protected page,
  - request without cookies → redirect to `/login`,
  - expired access + valid refresh → page renders and cookies are rotated,
  - logout clears cookies and revokes refresh tokens,
  - expired access + already-used refresh → redirect to `/login`.
- Test JWT keypair generated for the test env; `when@test` password-hasher costs are
  already lowered.

## Out of scope (v1)

- Password reset, email verification, password change.
- Access-token denylist (instant hard logout).
- ROLE_ADMIN and role-based authorization beyond ROLE_USER.
- Rate limiting beyond login throttling.
- Account lockout, 2FA, OAuth/social login.