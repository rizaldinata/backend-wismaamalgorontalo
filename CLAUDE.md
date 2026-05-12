# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 11 **modular monolithic** backend for a dormitory/boarding house (wisma) management system, using `nwidart/laravel-modules`. Current modules: Room, Resident, Rental, Finance, Maintenance, Inventory, Guest, Notification, Auth, Setting.

> **Active Refactor:** This project is undergoing a major architectural refactor toward true event-driven modularity. Read `CATATAN_ARSITEKTUR.md` for the full architectural vision, and `ROADMAP_REFACTOR.md` for the step-by-step task list with progress tracking.

## Key Documents

These two files are the authoritative source of truth for all refactor work. **Read them at the start of every refactor session.**

| File | Isi | Kapan Dibaca |
|---|---|---|
| [`CATATAN_ARSITEKTUR.md`](CATATAN_ARSITEKTUR.md) | Visi arsitektur akhir, alasan perubahan, daftar event penting, dan detail setiap modul bisnis | Saat membuat keputusan desain, menambah fitur baru, atau ada pertanyaan "kenapa struktur ini?" |
| [`ROADMAP_REFACTOR.md`](ROADMAP_REFACTOR.md) | Daftar task per fase dengan progress tracker (`[ ]`/`[x]`), aturan wajib, dan perintah siap pakai | Di awal setiap sesi refactor — baca, lihat task mana yang masih `[ ]`, lanjutkan dari sana |

**Cara mulai sesi refactor:**
```
"Lanjut refactor. Baca ROADMAP_REFACTOR.md dan lanjutkan dari task yang belum selesai."
```

## Common Commands

```bash
# Development
composer dev                        # Start server, queue, pail logs, and Vite concurrently
php artisan serve                   # Web server only (port 8000)

# Database
php artisan migrate                 # Run pending migrations
php artisan migrate:fresh --seed    # Drop all tables, re-run, and seed

# New module
php artisan module:make FeatureName # Scaffold a new module under Modules/

# Testing (uses actual DB, not in-memory — see phpunit.xml)
php artisan test                    # All tests (Unit + Feature + Modules)
php artisan test --filter=TestName  # Single test by name
php artisan test --testsuite=Modules # Module tests only

# Code style
./vendor/bin/pint                   # Format PHP (PSR-12, auto-fix)
./vendor/bin/pint --test            # Check without fixing

# Production / Docker
docker compose up -d
docker compose exec app php artisan migrate
```

## Architecture

### Module Structure

Every feature lives in `Modules/<Name>/` with this layered structure:

```
Http/Controllers/   ← request/response only, calls Service
Http/Requests/      ← FormRequest validation
Services/           ← all business logic, calls Repository
Repositories/
  Contracts/        ← interfaces (Dependency Inversion)
  Eloquent/         ← concrete Eloquent implementations
Models/             ← Eloquent models and relations
Transformers/       ← API Resources (JSON presentation layer)
Enums/              ← typed status constants (e.g. LeaseStatus)
Events/             ← domain events fired by this module
Listeners/          ← listeners for events from other modules
database/           ← module-specific migrations and seeders
routes/api.php      ← module API routes
```

Repository interfaces are bound to implementations in each module's `ServiceProvider`.

### Request Flow

```
HTTP → Nginx → routes/api.php → Controller (validate via FormRequest)
    → Service (business logic, DB::transaction for multi-step ops)
    → Repository (Eloquent queries)
    → Transformer (Resource)
    → ApiResponse trait → JSON
```

### Target Architecture (refactor in progress)

The goal is strict separation into three tiers so any business module can be toggled ON/OFF without breaking others:

```
INFRASTRUCTURE (always on)   Auth, Setting
CORE (always on)             Room, Schedule (replaces Rental + Resident)
BUSINESS MODULES (optional)  Finance, Maintenance, Guest, Inventory, Notification
```

Business modules must not call other modules' services or repositories directly — they communicate only via **Laravel Events**. See `CATATAN_ARSITEKTUR.md` for the full event catalog and `ROADMAP_REFACTOR.md` for the phase-by-phase plan.

### Current Cross-Module Communication (pre-refactor)

Modules currently call each other's **Services** directly (tight coupling that the refactor will eliminate):
- `RentalService` → `FinanceService::createInvoice()` on new lease
- `FinanceService` → `RentalService` to activate lease on payment
- `InventoryService` → `FinanceService` to record purchase as expense
- `GuestService` injects `LeaseRepositoryInterface` from Rental
- `MaintenanceService` injects `ResidentRepositoryInterface` from Resident

### API Response Convention

Always use the `App\Traits\ApiResponse` trait in controllers:

```php
$this->apiSuccess($data, $message, $statusCode);
$this->apiError($message, $statusCode, $errors);
```

### Auth & Permissions

- **Laravel Sanctum** — token-based API auth
- **Spatie Permission** — RBAC stored in global DB tables
- Middleware: `->middleware('permission:create-room')`
- Super-admin bypasses all permission checks via Gate
- Always use `Modules\Auth\Models\User` (not `App\Models\User`) inside modules

### Module Activation

`modules_statuses.json` controls which modules are loaded. Set a module to `false` to disable its routes and providers without deleting code.

## Key Integrations

- **Midtrans** — payment gateway in `Modules/Finance`. Env: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`.
- **Fonnte** — SMS/WhatsApp notifications via `FONNTE_TOKEN`.
- **Scramble** — auto-generates OpenAPI docs from type-hints/doc-blocks at `/docs/api`.
- **Intervention Image** — centralized in `App\Services\ImageService`.

## Environment

Copy `.env.example` to `.env` and set:
- Local dev uses **SQLite** by default (`database/database.sqlite`).
- Production uses **MariaDB** (`DB_CONNECTION=mysql`, `DB_HOST=db`).
- Force HTTPS is enabled automatically when `APP_ENV=production` or `staging`.

## Database Migrations

- Global: `database/migrations/` — users, permissions, tokens, jobs
- Per-module: `Modules/<Name>/database/migrations/`

`php artisan migrate` discovers module migrations automatically when the module is active.

## Refactor Rules (must follow during active refactor phases)

> See `ROADMAP_REFACTOR.md` for the full task list and `CATATAN_ARSITEKTUR.md` for the target architecture.

1. One phase = one git branch. Never mix changes from two phases.
2. API response shapes must not change — Flutter clients consume these endpoints.
3. Database changes are additive — add new columns/tables first, migrate data, then drop old ones.
4. Always run `php artisan test` after completing each task.
5. No direct service calls across module boundaries — use Events.
6. Test on `staging` before merging to `main`.
