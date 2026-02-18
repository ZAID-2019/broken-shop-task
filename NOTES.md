# Broken Shop – Technical Notes

## Overview
This document summarizes fixes and design decisions made to stabilize the core cycle:

**Browse Products → Add to Cart → Checkout**

Focus areas:
- Correctness
- Payment safety
- Security
- Performance
- Defensive programming

---

## 1) Initial Setup Issues (Overlay Integration)
The repository was provided as an overlay to be applied on top of a fresh Laravel install.

### Issues encountered:
- Missing base `Controller` class
- Project uses database session/queue drivers; required tables exist via migrations and must be migrated.


### Fixes:
- Created fresh Laravel project, then applied overlay (overwrite)
- Ran required migrations:
  - `php artisan migrate`
  - `php artisan queue:table`
  - `php artisan session:table`
---

## 2) Product Listing Improvements

### Problems:
- Loading all products without pagination
- Potential N+1 access for vendor
- Unreachable code after `return`

### Fixes:
- Implemented pagination with safe limits for `page` and `per_page`
- Limited selected columns to reduce payload
- Eager-loaded vendor where needed
- Removed unreachable code
- Normalized input parameters defensively

---

## 3) Session-Based Cart Design

Cart is stored in session as: cart = [ product_id => quantity ]

### Why this approach:
- Avoid storing Eloquent models in session
- Prevent stale pricing
- Reduce session size
- Improve serialization performance
- Avoid trusting session as pricing source

All pricing is recalculated from the database at checkout.

---

## 4) Cart View

### Improvements:
- Loads products from database using `whereIn` (single query)
- Recalculates subtotal and total dynamically
- Skips deleted products safely
- Prevents relying on session for pricing

---

## 5) Checkout Critical Fixes

### Original Risks:
- Total calculated from session data
- Storing raw cart data in order
- Accepting `user_id` from request (security risk)
- Dispatching job before ensuring commit
- No protection against double submission

### Improvements Made:
- Reload products from database during checkout
- Recalculate total using DB prices
- Store clean order snapshot:
  - `product_id`
  - `unit_price`
  - `qty`
  - `line_total`
- Wrap order creation in transaction
- Use `DB::afterCommit()` for payment job dispatch
- Add session-based `checkout_lock` to prevent duplicate submissions

---

## 6) Guest Checkout Handling

The orders table required `user_id`, but no authentication system exists.

### Resolution:
- Made `user_id` nullable
- Enabled guest checkout flow

Future improvement:
- Add authentication and associate orders with users

---

## 7) Security Hardening Notes

- Prevented IDOR on order success page by restricting access to the latest order for the current session.
- Noted that `/admin` route must be protected by proper authentication/authorization.

---

## 8) Payment Safety

To ensure payment correctness:

- Prices are never taken from session
- Prices are always loaded from database
- Payment job dispatch happens after successful commit
- Prevents race conditions and partial state execution

### Improvements:
- Reduced retry attempts in payment job
- Added progressive backoff delays
- Added guard to exit early if order is already `paid`
- Ensured invoice generation runs only when `payment_status = paid`

---

## 9) Abuse Prevention

A maximum quantity limit (50 per product) is enforced at session level.

### Purpose:
- Prevent resource abuse
- Prevent excessive session growth
- Avoid unrealistic cart sizes

Future improvements:
- Make quantity cap configurable
- Provide user-facing validation message

---

## 10) Performance Considerations

- Pagination implemented
- Select clauses limit columns
- Eager loading used where necessary
- Bulk loading via `whereIn` to avoid N+1
- Minimal session footprint
- Defensive input normalization

---

## 11) Future Enhancements

- Remove/update cart items
- Persistent cart in database
- Authentication system
- Order history
- Stock validation
- Idempotency keys
- Payment provider abstraction
- Configurable business rules
- Proper checkout success page
- Better UX feedback handling
- Automated feature tests for core cycle

---

## Final Conclusion

The application core cycle now works correctly and safely.

Security, payment consistency, and database integrity were prioritized while keeping the implementation minimal and aligned with the evaluation requirements.