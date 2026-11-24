# 🚀 Riwaj Grand — Hostinger Production Deployment Guide

A step-by-step guide to deploying this Laravel + Livewire project on **Hostinger (Shared, Cloud, or VPS Hosting)**.

> **Payments are cash-only.** No Stripe/JazzCash/EasyPaisa keys, gateway URLs or
> webhook secrets are needed or supported. Do not copy any such variables into
> `.env`. Cash is physically verified by authorized staff in the ERP.
>
> **Demo seeding is disabled in production.** `hostinger:setup` no longer offers
> a `--seed` option, and the demo seeders refuse to run when `APP_ENV=production`.
> Create the first Super Admin explicitly with `php artisan` after deployment.

---

## 📋 Pre-Deployment Checklist (Already Completed in Codebase)

- [x] **Production Assets Compiled:** Built with Vite into `public/build/assets/` (`app-*.css` and `app-*.js`). No Node.js required on Hostinger.
- [x] **Root `.htaccess` Configured:** Automatically redirects web traffic to `/public/` and blocks direct access to `.env`, `.git`, `storage/`, etc.
- [x] **Public `.htaccess` Optimized:** Includes HTTPS redirect, Gzip compression, LiteSpeed/Apache browser caching, and security headers.
- [x] **Production Environment Template:** `.env.production.example` prepared with Hostinger MySQL defaults.
- [x] **One-Command Setup:** `php artisan hostinger:setup` handles migrations, storage symlinks, and caching.

---


## Production operations correction (current)

This section supersedes older snippets in this guide where they differ.
- Do not use `--seed` with `hostinger:setup`; production demo seeding is
  disabled.
- On Hostinger Shared/Cloud without Redis and persistent workers, use the
  database cache/lock/session/queue drivers, not file cache or sync queues.
- Configure both Laravel cron entries for `schedule:run` and
  `queue:work database --stop-when-empty` as documented in
  [deploy/PRODUCTION_OPERATIONS.md](deploy/PRODUCTION_OPERATIONS.md).
- Use hPanel SSL for shared hosting. The Caddy automatic TLS stack is only for
  a VPS where Docker can bind public ports 80 and 443.
- The production go-live checks, encrypted VPS backup scripts, staging restore
  drill, Uptime Kuma setup, SMTP and WhatsApp live verification are documented
  in [deploy/PRODUCTION_OPERATIONS.md](deploy/PRODUCTION_OPERATIONS.md).

## Step 1: Set PHP Version in Hostinger hPanel

1. Log into your **Hostinger hPanel**.
2. Go to **Websites** &rarr; click **Manage** on your domain.
3. In the search bar or left menu, find **PHP Configuration** (under *Advanced*).
4. Select **PHP 8.2** or **PHP 8.3** (Recommended).
5. Click **Save**.
6. Under the **PHP Extensions** tab, ensure the following are enabled (they are enabled by default on Hostinger):
   - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `bcmath`, `intl`, `gd`.

---

## Step 2: Create MySQL Database in Hostinger

1. In hPanel, go to **Databases** &rarr; **MySQL Databases**.
2. Create a new Database:
   - **MySQL Database Name:** e.g., `riwaj_grand` (Hostinger will prefix it, e.g., `u123456789_riwaj_grand`)
   - **MySQL Username:** e.g., `riwajuser` (Hostinger will prefix it, e.g., `u123456789_riwajuser`)
   - **Password:** Generate a strong password.
3. Click **Create**.
4. Save the **Database Name**, **Username**, and **Password** for the next step.

---

## Step 3: Upload Project Files to Hostinger

### Option A: Via Hostinger File Manager (ZIP Upload — Quickest)
1. On your computer, create a ZIP of your project:
   - **Include:** `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `composer.json`, `composer.lock`, `.htaccess`, `.env.production.example`.
   - **Exclude:** `node_modules`, `.git` (and optionally `vendor` if running composer install on server).
2. In hPanel, open **File Manager** (`public_html`).
3. Upload your ZIP file into `public_html` and **Extract** it.
4. Rename `.env.production.example` to `.env`.

### Option B: Via Hostinger Git (Recommended for continuous updates)
1. In hPanel, search for **Git**.
2. Paste your repository Git URL and select branch `main`.
3. Set directory to `/public_html` and click **Create & Deploy**.

---

## Step 4: Configure `.env` File

Open the `.env` file in Hostinger File Manager and set:

```ini
APP_NAME="Riwaj Grand"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_riwaj_grand
DB_USERNAME=u123456789_dbuser
DB_PASSWORD="your_database_password"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
# Shared reservation mutex. `database` uses the cache_locks table and is safe
# on a single host; use `redis` when multiple app servers share Redis.
BOOKING_LOCK_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
```

---

## Step 5: Run Production Setup & Optimization

### If you have SSH Terminal access (Hostinger Premium, Business, Cloud):
1. In hPanel, search for **SSH Access** &rarr; Enable SSH and copy the connection command.
2. In your terminal (or Web Terminal in hPanel), navigate to your project:
   ```bash
   cd public_html
   ```
3. If `vendor` folder wasn't uploaded, install dependencies:
   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```
4. Generate application key (if not already set):
   ```bash
   php artisan key:generate
   ```
5. Run the automated Hostinger deployment command:
   ```bash
   php artisan hostinger:setup
   ```

   Demo seeders are intentionally disabled in production. Create the first
   Super Admin through the documented secure provisioning workflow; never use
   known demo credentials on a public site.

---

## Step 6: Verify Domain & SSL

1. In hPanel &rarr; **Security** &rarr; **SSL**: Ensure your Free Lifetime SSL certificate is **Active**.
2. Visit **`https://yourdomain.com`** in your browser.
3. Test key pages:
   - Homepage: `https://yourdomain.com/`
   - Venues Catalog: `https://yourdomain.com/venues`
   - Login: `https://yourdomain.com/login`

---

## 💡 Troubleshooting on Hostinger

| Issue | Solution |
| :--- | :--- |
| **403 Forbidden or 500 Error** | Check file permissions: Directories should be `0755`, files `0644`. `storage` and `bootstrap/cache` must be writable (`0775`). |
| **Images in `/storage` 404** | Run `php artisan storage:link`. If SSH is disabled, create a symlink in File Manager pointing `public_html/public/storage` to `public_html/storage/app/public`. |
| **Styles not updating** | Run `php artisan optimize:clear` or clear browser cache (`Ctrl + F5`). Assets in `public/build` are cached for 1 year by LiteSpeed. |
