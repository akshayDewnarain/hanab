# Sanctum + SPA (cookie session) — Backend API

End-state checklist implemented in this repo:

1. **Sanctum** is installed; `personal_access_tokens` migration is present — run `php artisan migrate`.
2. **`config/sanctum.php`** — `stateful` domains come from `SANCTUM_STATEFUL_DOMAINS` (hostnames only, no `http://`). Defaults include common Vite/React ports (`5173`, `3000`).
3. **`config/auth.php`** — `sanctum` and `api` guards use driver `sanctum` and provider `users`.
4. **`config/sanctum.php`** — `guard` is `['web']` (first-party SPA session).
5. **`bootstrap/app.php`** — `$middleware->statefulApi()` enables `EnsureFrontendRequestsAreStateful` on the **`api`** middleware group (not only `web`).
6. **`config/cors.php`** — `paths` include `api/v1/*`, `api/v1/public/*`, and `sanctum/csrf-cookie`; `supports_credentials` is `true`; `allowed_origins` from `CORS_ALLOWED_ORIGINS` / `FRONTEND_URL` (never `*` when using credentials).
7. **API versioning** — `bootstrap/app.php` sets **`apiPrefix: 'api/v1'`**, so all `routes/api.php` URIs are under **`/api/v1/...`**.
8. **Routes**
   - `POST /api/v1/login` — `Auth::attempt` + session regenerate; optional `device_name` → creates PAT and returns `token` in JSON.
   - `POST /api/v1/logout` — `tokens()->delete()`, `Auth::logout()`, session invalidate + regenerate CSRF token (requires `auth:sanctum`).
   - `GET /api/v1/auth/me` — current user JSON (requires `auth:sanctum`).
   - Employee CRUD and other private APIs stay behind `auth:sanctum` (e.g. `/api/v1/employees`).
9. **Public JSON API** — `routes/public.php` is mounted at **`/api/v1/public/*`** with `withoutMiddleware(EnsureFrontendRequestsAreStateful)` and `withoutMiddleware(ValidateCsrfToken)` (see `bootstrap/app.php` `then:`). Example: `GET /api/v1/public/ping`.

### SPA client

- HTTP client: **`withCredentials: true`** (axios) or `credentials: 'include'` (fetch).
- Axios: enable **`withXSRFToken`** (or send `X-XSRF-TOKEN` from the `XSRF-TOKEN` cookie).
- Before credential-changing requests (login, etc.), call **`GET /sanctum/csrf-cookie`** against the **same API origin** as `/api/v1/*`.
- Align **`.env`**: `APP_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, and dev ports.

### PAT-only note

**`localStorage.token` is not wired** as an automatic Bearer header in this app. Cookie + CSRF carry SPA admin auth. If you want PAT-only clients, add an axios **request interceptor** that sets `Authorization: Bearer …`.

### Policies / gates

A **`Gate::before` super-admin** (if you add one in `AuthServiceProvider`) bypasses normal policy logic — orthogonal to Sanctum but relevant for authorization after login.

### User model

`App\Models\User` uses **`Laravel\Sanctum\HasApiTokens`** for optional PAT creation on login.
