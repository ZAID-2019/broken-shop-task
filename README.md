# Broken Shop - Technical Assignment

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20)

This repository contains a stabilized implementation of a simplified e-commerce flow built on Laravel (fresh install + overlay).

The assignment objective is to repair and harden the core business cycle:

**Browse Products -> Add to Cart -> Checkout -> Async Payment -> Invoice (mock)**

## Table of Contents

- [Requirements](#requirements)
- [Setup Instructions](#setup-instructions)
- [Core Cycle Status](#core-cycle-status)
- [Architecture Decisions](#architecture-decisions)
- [Security Notes](#security-notes)
- [Performance Notes](#performance-notes)
- [Scope Control (Intentionally Not Implemented)](#scope-control-intentionally-not-implemented)
- [Manual Verification Checklist](#manual-verification-checklist)
- [Project Structure](#project-structure)
- [Contributing](#contributing)

## Priorities

- Correctness
- Payment safety
- Security
- Performance
- Defensive programming
- Clear trade-offs / documentation

## Quick Start

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed && php artisan serve
```

In another terminal:

```bash
php artisan queue:work
```

## Requirements

- PHP 8.2+
- Composer
- SQLite or MySQL
- Node.js + npm (not required for this assignment unless you modify frontend assets)
## Setup Instructions

### 1. Install dependencies

```bash
composer install
```

Optional (only if building frontend assets):

```bash
npm install
npm run build
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Required environment variables example:

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
ADMIN_TOKEN=your-admin-token
```

### 3. Database setup

SQLite (quick setup):

```env
DB_CONNECTION=sqlite
```

```bash
touch database/database.sqlite
php artisan migrate --seed
```

MySQL:

```env
DB_CONNECTION=mysql
DB_DATABASE=broken_shop
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
```

### 4. Run queue worker (required for async payment)

```bash
php artisan queue:work
```

### 5. Start application

```bash
php artisan serve
```

Open: `http://127.0.0.1:8000`

## API Endpoints

| Method | Path                        | Purpose                          | Auth            |
| ------ | --------------------------- | -------------------------------- | --------------- |
| GET    | `/`                         | List products                    | No              |
| GET    | `/products/{id}`            | Product details                  | No              |
| POST   | `/cart/add/{id}`            | Add product to session cart      | No              |
| GET    | `/cart`                     | View cart                        | No              |
| POST   | `/checkout`                 | Create order and dispatch charge | No              |
| GET    | `/checkout/success/{order}` | View latest session order only   | No              |
| GET    | `/admin`                    | Orders + tickets JSON            | `X-ADMIN-TOKEN` |
| POST   | `/ticket`                   | Submit ticket                    | No              |

## Core Cycle Status

End-to-end flow works:

1. Browse paginated products.
2. Add products to a session-based cart.
3. View cart with database-recalculated pricing.
4. Checkout with transactional order creation.
5. Async payment processing via queue worker.
6. Invoice job runs only after payment is marked as paid.
7. Success page is restricted to the latest order in the current session (session-scoped mitigation).

## Architecture Decisions

### Product Listing

- Pagination with bounded `per_page`
- Column selection for efficiency
- Eager loading (`vendor`) to avoid N+1
- Defensive normalization of query parameters

### Cart Design

Cart is stored in session as:

```text
cart = [ product_id => quantity ]
```

Why:

- No Eloquent models stored in session
- No session pricing trust
- Smaller session footprint
- Prices always recalculated from database

### Checkout Safety

- Database is source of truth for prices
- Clean line snapshot (`product_id`, `unit_price`, `qty`, `line_total`)
- Transactional order creation
- `DB::afterCommit()` dispatch
- Session checkout lock to reduce double-submit

### Async Payment Processing

- Guard against duplicate processing (`pending -> processing`)
- Bounded retries
- Progressive retry backoff
- `PaymentAttempt` audit record
- Invoice job dispatched after payment success

## Security Notes

- Success page restricted to latest session order (session-scoped, not full auth)
- Admin endpoint protected via `X-ADMIN-TOKEN`
- Pricing never trusted from session
- Ticket endpoint documented as a potential hardening area

## Performance Notes

- Pagination for large seeded catalog
- Eager loading for vendor
- Bulk `whereIn` loading
- Limited column selection
- Minimal session payload

## Scope Control (Intentionally Not Implemented)

- Full authentication / authorization
- Role-based admin access
- DB-level idempotency keys
- Real payment gateway
- Monetary precision refactor
- Stock locking
- Persistent cart
- Automated feature tests

## Manual Verification Checklist

1. Visit `/` and confirm products load.
2. Add items to cart.
3. Confirm quantity cap (50).
4. Visit `/cart` and verify DB pricing.
5. Checkout and confirm order creation.
6. Run worker and confirm payment becomes `paid`.
7. Confirm invoice job updates order to `invoiced`.
8. Try invalid `/checkout/success/{order}` and confirm restricted behavior.
9. Access `/admin` without token -> `403`.
10. Access `/admin` with token -> JSON response.

## Troubleshooting

- Checkout stays `pending`:
  - Ensure `QUEUE_CONNECTION=database`
  - Run `php artisan queue:work`
- `/admin` returns `403`:
  - Set `ADMIN_TOKEN` in `.env`
  - Send header: `X-ADMIN-TOKEN: <your-token>`
- Session/queue errors after setup:
  - Run `php artisan migrate`
  - Clear config cache: `php artisan config:clear`

## Project Structure

- `routes/web.php` - HTTP routes
- `app/Http/Controllers/` - product, cart, checkout, and admin flow handlers
- `app/Jobs/` - async payment and invoice jobs
- `app/Models/` - domain models (`Order`, `Product`, `PaymentAttempt`, etc.)
- `database/migrations/` - schema definitions
- `resources/views/` - Blade templates

## Final Result

A stable core cycle with improved correctness, safer checkout behavior, basic security hardening, and clear scope boundaries aligned with the assignment.

## Contributing

This repository is prepared as a technical assignment project. Changes should stay aligned with the assignment scope and documented trade-offs.
