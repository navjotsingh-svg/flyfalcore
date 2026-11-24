# Falcore — Flight Booking

Laravel travel website for searching and booking flights.

## Stack

- Laravel 12
- PHP 8.2 (XAMPP)
- SQLite (default) — switch to MySQL when XAMPP MySQL is running
- Blade + Tailwind CDN

## Quick start (XAMPP)

1. Start **Apache** in XAMPP.
2. Open: [http://localhost/falcore/public](http://localhost/falcore/public)
3. Or from the project folder:

```bash
php artisan serve
```

Then visit `http://127.0.0.1:8000`

## Database

SQLite is configured by default (`database/database.sqlite`).

Seed sample airlines, airports, and flights:

```bash
php artisan migrate:fresh --seed
```

### Optional: MySQL (XAMPP)

1. Start MySQL in XAMPP.
2. Create database `falcore`.
3. Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=falcore
DB_USERNAME=root
DB_PASSWORD=
```

4. Run `php artisan migrate --seed`

## Live flights (TBO) and payments (PayPal)

Search and ticket issuance use the [TBO Air API](https://www.tboholidays.com/) (Travel Boutique Online / TekTravels). After passenger details, checkout goes to PayPal; on successful capture Falcore books/tickets the fare with TBO.

Add these to `.env`:

```env
APP_URL=http://127.0.0.1:8000

TBO_CLIENT_ID=
TBO_USERNAME=
TBO_PASSWORD=
TBO_END_USER_IP=
TBO_CURRENCY=INR
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_MODE=sandbox
```

1. Ask TBO to whitelist your public server IP for your ClientId, then set `TBO_END_USER_IP` to that IP.
2. Create a PayPal REST app: [developer.paypal.com](https://developer.paypal.com/dashboard/applications) (sandbox).
3. Keep `APP_URL` equal to the site PayPal will return to (`/payments/paypal/{reference}/success`).

Without TBO credentials, Falcore falls back to sample flights. Without PayPal keys, live bookings stay unpaid until you add them.

### Checkout flow

1. Search (TBO Authenticate + Search)
2. Passenger form
3. PayPal order (intent `CAPTURE`)
4. Capture payment
5. Book / ticket with TBO

## Admin panel

Manage every record at [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

- Bookings, flights, passengers, airlines, airports, users
- Search, create, edit, delete on each list

Seeded admin:

- Email: `admin@falcore.test`
- Password: `password`

Only users with `is_admin` can sign in.

## Features

- Live TBO fares (one-way or return)
- PayPal sandbox/live checkout
- Sample flight fallback
- Booking lookup by reference + email
- Traveler cancel / re-issue for paid TBO tickets
- Traveler accounts with trip history at `/account/bookings`

## Demo login user (seeded)

- Email: `admin@falcore.test`
- Password: `password` (Laravel factory default)

## Project structure

| Path | Purpose |
|------|---------|
| `app/Models/` | Airline, Airport, Flight, Booking, Passenger |
| `app/Http/Controllers/` | Home, Flight, Booking |
| `resources/views/` | Blade UI |
| `database/seeders/` | Sample travel data |
