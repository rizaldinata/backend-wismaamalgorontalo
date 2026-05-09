# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 11 **modular monolithic** backend for a dormitory/boarding house (wisma) management system, using `nwidart/laravel-modules`. Modules: Room, Resident, Finance, Rental, Maintenance, Inventory, Auth, Notification, Guest, Setting.

## Common Commands

```bash
# Development
composer dev                        # Start server, queue, pail logs, and Vite concurrently
php artisan serve                   # Web server only (port 8000)

# Database
php artisan migrate                 # Run pending migrations
php artisan migrate:fresh --seed    # Drop all tables, re-run, and seed

# Testing
php artisan test                    # All tests
php artisan test --filter=TestName  # Single test

# Code style
./vendor/bin/pint                   # Format PHP (PSR-12, auto-fix)
./vendor/bin/pint --test            # Check without fixing

# Production / Docker
docker compose up -d                # Start all services
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

### Cross-Module Communication

Modules call each other's **Services**, never each other's repositories or tables directly.  
Example: `RentalService` → `FinanceService::createInvoice()` when a new lease is created.  
Example: `InventoryService` → `FinanceService` to sync purchase prices as expense records.

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
- Resident-only features guarded by `Gate::define('resident-access', ...)`

### Module Activation

`modules_statuses.json` controls which modules are loaded. Set a module to `false` to disable its routes and providers without deleting code.

## Key Integrations

- **Midtrans** — payment gateway, configured in `Modules/Finance`. Requires `.env` keys: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`.
- **Fonnte** — SMS notifications via `FONNTE_TOKEN` env var.
- **Scramble** — auto-generates OpenAPI docs from type-hints/doc-blocks. Available at `/docs/api`.
- **Intervention Image** — centralized in `App\Services\ImageService`.

## Environment

Copy `.env.example` to `.env` and set:
- Local dev uses **SQLite** by default.
- Production uses **MariaDB** (`DB_CONNECTION=mysql`, `DB_HOST=db`).
- Force HTTPS is enabled automatically when `APP_ENV=production` or `staging`.

## Database Migrations

- Global migrations: `database/migrations/` (users, permissions, tokens, jobs)
- Module migrations: `Modules/<Name>/database/migrations/`

Run `php artisan migrate` — Laravel discovers module migrations automatically when the module is active.
