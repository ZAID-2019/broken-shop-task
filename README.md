# Broken Shop - Technical Assignment

This repository contains a stabilized implementation of a simplified e-commerce system built on Laravel 12.

The assignment objective is to repair and harden the core business cycle:

Browse Products -> Add to Cart -> Checkout -> Async Payment -> Invoice

The implementation prioritizes:

- Correctness
- Payment safety
- Security
- Performance
- Defensive programming
- Clear trade-off documentation

## Core Cycle Status

The following flow works end-to-end:

1. Browse paginated products
2. Add products to a session-based cart
3. View cart with database-recalculated pricing
4. Checkout with transactional order creation
5. Async payment processing via queue worker
6. Invoice generation after successful payment
7. Restricted success-page access (basic IDOR mitigation)

## Setup Instructions

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Set required values in `.env`:

```dotenv
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
ADMIN_TOKEN=your-admin-token
```

If using SQLite, create the database file:

```bash
touch database/database.sqlite
```

### 3) Run migrations and seed data

```bash
php artisan migrate --seed
```

### 4) Run queue worker

```bash
php artisan queue:work
```

### 5) Start application

```bash
php artisan serve
```

Application URL:

`http://127.0.0.1:8000`

## Architecture Decisions

### Product Listing

- Pagination with bounded `per_page`
- Column selection for efficiency
- Eager loading (`vendor`) to reduce query overhead
- Defensive normalization of query parameters

### Cart Design

Cart is stored in session with a minimal structure:

`cart = [ product_id => quantity ]`

Why this design:

- No Eloquent models stored in session
- No pricing data trusted from session
- Lower memory/serialization footprint
- Pricing always recalculated from database

### Checkout Safety

Checkout flow includes:

- Product reload from database
- Clean line-item snapshot (`product_id`, `unit_price`, `qty`, `line_total`)
- Transactional order creation
- `DB::afterCommit()` dispatch for payment job
- Session checkout lock to reduce duplicate submissions

This preserves pricing correctness and avoids common race conditions.

### Async Payment Processing

Payment is handled via `ChargePaymentJob` with:

- Guard against duplicate processing (`pending -> processing` transition)
- Bounded retry attempts
- Progressive retry backoff
- `PaymentAttempt` audit record creation
- Invoice job dispatch after payment success

## Security Considerations

- Basic IDOR mitigation on checkout success page (latest session order only)
- Admin endpoint protected with `X-ADMIN-TOKEN` header
- No trust in session-held pricing
- Ticket endpoint security considerations documented
- Controlled retry behavior in async jobs

## Performance Considerations

- Pagination for large seeded catalog (5,000 products)
- Eager loading for vendor relationship in listing
- Bulk `whereIn` loading for cart and checkout
- Limited column selection on critical queries
- Minimal session footprint

## Scope Control

The assignment does not require fixing everything. The following are intentionally left as future improvements:

- Full authentication and authorization model
- Role-based admin access controls
- Database-level idempotency keys for checkout/payment
- Real payment provider integration
- Monetary precision model (decimal/minor units)
- Stock reservation and locking
- Persistent cart model
- Complete automated test coverage for the core flow
- Ticket schema/controller alignment (`tickets` migration vs controller payload)

## Manual Verification Checklist

1. Visit `/` and confirm products load with pagination.
2. Add multiple items to cart.
3. Confirm quantity caps at 50.
4. Visit `/cart` and verify totals reflect database pricing.
5. Checkout once and confirm order creation.
6. Run queue worker and confirm payment processing to `paid`.
7. Confirm invoice job transitions order status to `invoiced`.
8. Attempt invalid `/checkout/success/{order}` URL and verify restricted access.
9. Access `/admin` without token and verify `403`.
10. Access `/admin` with valid token and verify JSON response.

## Key Paths

- `routes/web.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Jobs/ChargePaymentJob.php`
- `app/Jobs/GenerateInvoiceJob.php`
- `database/migrations/`

## Final Result

The current implementation delivers a stable core cycle with improved payment consistency, safer checkout behavior, and explicit scope boundaries aligned with the assignment.
