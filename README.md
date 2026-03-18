# SpendSmart (PHP + JSON)

SpendSmart is a lightweight finance tracking web app built with PHP and vanilla JavaScript. It runs locally on Apache (XAMPP) and stores data in JSON files.

## Features

- Authentication
  - Register / Login / Logout
  - Guest mode (try the app without saving to disk)
  - Password rules: min 6 chars, must include letters (not numbers-only)

- Transactions
  - Add / edit / delete income and expenses
  - Per-transaction currency selection
  - Categories validation by transaction type (income/expense)

- Categories
  - Add / edit / delete categories
  - Per-user category storage (one user’s edits don’t affect others)

- Account
  - Update profile (name/email/password)
  - Choose default currency (also selectable during registration)
  - Delete account (removes profile + transactions + per-user categories)

- Currency
  - Supported currencies are defined by the system in `data/currencies.json`
  - Real-time FX conversion when changing default currency
    - Uses online exchange rates with a small cache
    - Stores cache in `data/exchange_rates_cache.json`

## Tech Stack

- PHP (API endpoints under `app/`)
- Vanilla JS (dashboard logic in `js/app.js`, auth in `js/auth.js`)
- JSON files for persistence (`data/`)

## Project Structure

- `index.php`, `login.php`, `register.php`, `dashboard.php` — pages
- `app/` — JSON API endpoints
  - `app/auth.php` — session + shared helpers
  - `app/register.php`, `app/login.php`, `app/logout.php` — auth endpoints
  - `app/account.php` — profile update + delete account
  - `app/transactions.php` — transaction CRUD
  - `app/categories.php` — category CRUD
  - `app/Currency.php` — supported currency list + live FX conversion
- `data/` — JSON storage
  - `categories.json` — default categories
  - `currencies.json` — supported currencies
  - Generated at runtime: `users.json`, `transactions.json`, `categories_<userId>.json`, `exchange_rates_cache.json`

## Run Locally (Windows / XAMPP)

1. Install XAMPP.
2. Copy this project folder to: `C:\xampp\htdocs\spendsmart`
3. Open XAMPP Control Panel and start **Apache**.
4. Open your browser:
   - `http://localhost/spendsmart/`

## Notes on Data & Privacy

- This app stores user accounts and transactions in JSON files under `data/`.
- The repository should **not** publish real `data/users.json` or personal transaction data.
  - A `.gitignore` is included to prevent committing these files.

## Troubleshooting

- If pages don’t load: confirm Apache is running and the project is inside `htdocs`.
- If currency conversion fails: confirm the machine has internet access. The app will use cached rates when available.
