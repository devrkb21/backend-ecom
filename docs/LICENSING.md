# Licensing

This installation checks in with the Coder Zone BD licensing server (the Go/Fiber
backend at `backend/`) using the vendored `coderzonebd/licensing-sdk` package
(`packages/coderzonebd/licensing-sdk/`, required via a local path repository in
`composer.json` — it ships inside this repo so a merchant's server doesn't need
the licensing backend's own repo checked out alongside it).

## How it works

- **Activation** (`php artisan license:activate`, run once during setup) calls the
  licensing server's Sync API to bind this domain to the configured license key.
- **Verification** (`php artisan license:verify`, scheduled every 30 minutes in
  `bootstrap/app.php`) re-checks the license's validity and refreshes the cached
  status/`core_config` in the `settings` table (group `license`). Requests never
  call the licensing server directly — they read this cached status.
- The SDK's own offline grace period (`LICENSE_GRACE_PERIOD_HOURS`, default 72h)
  keeps the last-known-good state if the licensing server is briefly unreachable.

## Setup

1. Set in `.env`:
   ```
   LICENSE_SERVER_URL=https://license.yourdomain.com
   LICENSE_PUBLIC_KEY=<Ed25519 public key printed by the licensing server's startup log>
   LICENSE_PRODUCT_SLUG=backend-ecom
   LICENSE_GRACE_PERIOD_HOURS=72
   ```
2. Either set `LICENSE_KEY` in `.env`, or have the merchant paste it into
   `PUT /api/v1/admin/license` from the admin panel (stored in the `settings`
   table, no restart needed, `settings.manage` permission required).
3. Run `php artisan license:activate` once.
4. Confirm the scheduler is running (`php artisan schedule:work` in dev, or a
   real cron entry calling `php artisan schedule:run` every minute in
   production) so `license:verify` actually ticks.

## What happens when the license expires

Once `license:verify` observes the license as invalid/expired (past the grace
period), this install **degrades instead of locking out**:

- **Storefront is fully unaffected** — browsing, checkout, and order placement
  keep working exactly as before, including payment webhooks and courier
  tracking updates on new orders.
- **No new admin-created resources** — creating a user, category, product, or
  manual order is rejected with a flash error (`{"code": "license_expired"}`
  on the JSON API) via `App\Http\Middleware\EnsureLicenseAllowsCreation`
  (alias `license.create`). This is wired on **both** surfaces — the JSON API
  (`routes/api.php`) and the server-rendered Blade admin panel
  (`routes/web.php`, on `orders.create`/`.store`, `categories.create`/`.store`,
  `products.create`/`.store`, `users.create`/`.store`). Editing/deleting
  existing resources is unaffected.
- **New orders become admin-invisible** — any order placed *after* the moment
  the license expired (`Setting::getValue('license', 'expired_since')`) is
  excluded from the admin order list (and its status/count queries), and
  direct access/status-update/notes/refund/courier-dispatch on one of those
  orders is rejected the same way. This is enforced on **both** the JSON API
  controllers (`Api\OrderController`, `Api\OrderNoteController`,
  `Api\OrderExportController`) and the Blade admin panel's own
  `Admin\OrderController` — the latter via a dedicated
  `App\Http\Middleware\EnsureOrderNotLicenseLocked` (alias
  `license.order-lock`) wrapped around every `admin/orders/{order}/*` route
  (status, source, discount, SMS, payment-status, refund, customer-info,
  items, courier-history-check, SteadFast/Pathao single-send, tracking) plus
  manual filtering in `index()`/`export()`/`bulkAction()` and the courier
  bulk-send controllers. Orders placed *before* that moment remain fully
  visible and editable by admin, indefinitely. Customers can always see and
  manage their own orders regardless of this cutoff — the restriction only
  applies to the admin surface. See `App\Services\LicenseService`
  (`isOrderLocked()`, `shouldBlockCreation()`, `expiredSince()`).

  **Important:** these two Order controllers (`Api\OrderController` and
  `Admin\OrderController`) are separate classes serving separate route files —
  a future admin-facing order action must get its own lock check (or be
  wrapped in `license.order-lock`), it will not inherit enforcement from the
  other controller automatically.
- Renewing the license (a successful `license:verify`) clears the cutoff
  immediately — every order placed during the expired window becomes visible
  and manageable to admin again, with no manual unlock step.

## Admin endpoints

- `GET /api/v1/admin/license` — dedicated license status section. Any admin/
  staff account can read it (e.g. to show a "license expires soon" banner on
  the dashboard) — no extra permission beyond `is_admin`. Returns:
  ```json
  {
    "status": "active",
    "is_valid": true,
    "has_license_key": true,
    "masked_license_key": "************ab12",
    "product_slug": "backend-ecom",
    "server_configured": true,
    "core_config": { "whitelabel_enabled": true },
    "last_verified_at": "2026-08-27T10:00:00+00:00",
    "last_error": "",
    "expired_since": null,
    "grace_period_hours": 72,
    "locked_orders_count": 0
  }
  ```
  `locked_orders_count` is how many orders are currently hidden from admin
  because they were placed after `expired_since` — a direct, at-a-glance
  measure of what renewing would unlock (see `LicenseService::lockedOrdersCount()`).
- `PUT /api/v1/admin/license` — set/rotate the license key; triggers an
  immediate activate + verify attempt. Requires `is_admin` + the
  `settings.manage` permission (unlike the read side, this can change what
  the whole install is licensed as).

## Admin panel (server-rendered)

The Blade admin panel (`resources/views/admin/`) has its own dedicated,
top-level **License** sidebar item (`/admin/license`, route name
`admin.license`) — a standalone page, not a tab buried inside Settings,
right next to Settings in the sidebar (`resources/views/admin/layouts/app.blade.php`).
Gated `settings.manage`, same as the rest of the settings surface (via
explicit `admin.license`/`admin.license.` entries in
`EnsureAdminPermission`'s route-name map — a route not covered by that map
fails closed, so any future license route needs an entry there too).

Shows: status badge, masked license key, product slug, whether
`LICENSE_SERVER_URL`/`LICENSE_PUBLIC_KEY` are configured, last verified time,
grace period, last error, and — when expired — how many orders are currently
locked out of the admin order list. Two actions:

- **Save & Activate** — `PUT admin.license.update`
  (`App\Http\Controllers\Admin\LicenseController::update`): sets the
  key, runs `activate()` then `verify()`, redirects back with a flash message.
- **Verify Now** — `POST admin.license.verify`
  (`::verifyNow`): re-checks without changing the key, e.g. to confirm a
  renewal took effect without waiting for the next scheduled tick.
