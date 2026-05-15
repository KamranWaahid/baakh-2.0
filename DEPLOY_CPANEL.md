# cPanel Deployment Guide (Laravel + Vite)

This project is a Laravel 10 app with Vite frontend assets.  
For cPanel shared hosting, deploy app code outside `public_html` and publish only `public/` into `public_html`.

**Primary shell:** use **cPanel → Advanced → Terminal** (web terminal). All commands below work there; if you use full SSH instead, the same commands apply.

## What is configured

- `.cpanel.yml` now runs `scripts/cpanel-post-deploy.sh` on every cPanel Git deploy.
- Deploy script:
  - syncs repository into `APP_PATH` (default: `/home/<user>/baakh_app`),
  - runs `composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader`,
  - runs `npm ci && npm run build` (if npm is available),
  - verifies `public/build/manifest.json` references files that actually exist,
  - caches Laravel config/views/events and creates storage symlink,
  - publishes Vite assets with delete limited to `public_html/build`,
  - publishes other `public/` files without deleting the rest of `public_html`,
  - rewrites `public_html/index.php` so it boots Laravel from `APP_PATH`.

## One-time cPanel setup

1. Create/import a MySQL database and user in cPanel.
2. In **Domains**, ensure your site document root is `public_html`.
3. In **Software > Setup Node.js App** (optional), do nothing unless your host requires Node app setup. This app runs on PHP/Apache; Node is only used at build time.
4. In **Software > MultiPHP INI Editor**, set PHP to a Laravel-compatible version (8.1+).
5. In **Git Version Control**, create or pull this repository.
6. In repository settings, enable **Deployment** and make sure it uses project `.cpanel.yml`.

### Verify PHP and Node (Terminal)

Open **Advanced → Terminal**, then:

```bash
php -v
which php
node -v 2>/dev/null || echo "node not in PATH"
npm -v 2>/dev/null || echo "npm not in PATH"
```

## Environment variables (.env)

In **Terminal**, replace `YOUR_CPANEL_USER` with your cPanel username (or use `$HOME` as below).

Create `.env` from the example and edit values:

```bash
cd "$HOME/baakh_app"
cp -n .env.example .env
nano .env
```

Minimum production-oriented values (adjust DB and URL):

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Then in **Terminal**:

```bash
cd "$HOME/baakh_app"
php artisan key:generate
php artisan migrate --force
```

## Deploying updates

For each code update:

1. Push to the branch connected in cPanel Git.
2. Click **Deploy HEAD Commit** in cPanel Git UI.
3. Confirm deploy logs end with `Deployment finished successfully.`

### Optional: rerun deploy script manually (Terminal)

If Git deploy ran but you want to repeat the same steps (e.g. after fixing `.env` on disk), open **Terminal**, `cd` to the directory cPanel uses as the deploy source for this repo (often under `~/repositories/` — check **Git Version Control** for the clone path), then:

```bash
export APP_PATH="$HOME/baakh_app"
export PUBLIC_PATH="$HOME/public_html"
cd /path/to/your/repo/checkout
bash scripts/cpanel-post-deploy.sh
```

### Deploy modes

Use these from the cPanel repository checkout, for example `cd "$HOME/repositories/baakh-2.0"`:

```bash
export APP_PATH="$HOME/baakh_app"
export PUBLIC_PATH="$HOME/public_html"

# Full deploy: sync app, run Composer, build assets, publish public files.
bash scripts/cpanel-post-deploy.sh

# Full deploy without Composer: use only when APP_PATH/vendor is already complete.
DEPLOY_MODE=no-composer bash scripts/cpanel-post-deploy.sh

# Publish only built Vite assets from the repo checkout; does not touch public_html/index.php.
DEPLOY_MODE=assets-only bash scripts/cpanel-post-deploy.sh

# Publish public files from APP_PATH/public and rewrite public_html/index.php.
DEPLOY_MODE=public-only bash scripts/cpanel-post-deploy.sh
```

Do not run `rsync -a --delete public/ "$HOME/public_html/"` manually. It can overwrite the cPanel-adjusted `public_html/index.php` with the raw repo version and delete unrelated files in `public_html`.

## Recovering a production 500 after a raw public sync

If assets return `200` but `/` returns `500` after copying raw `public/`, first restore `public_html/index.php` so it points at a bootable Laravel app. The raw repo `public/index.php` expects `../vendor/autoload.php` relative to `public_html`, which is wrong on cPanel when the app lives elsewhere.

### Quick recovery: boot from the cPanel repo checkout

Use this if `php artisan` works in `$HOME/repositories/baakh-2.0` and that checkout has a usable `vendor/` directory:

```bash
export APP_PATH="$HOME/repositories/baakh-2.0"
export PUBLIC_PATH="$HOME/public_html"
cd "$APP_PATH"
DEPLOY_MODE=public-only bash scripts/cpanel-post-deploy.sh
```

After this, `public_html/index.php` should contain absolute paths like:

```php
require '/home/YOUR_CPANEL_USER/repositories/baakh-2.0/vendor/autoload.php';
$app = require_once '/home/YOUR_CPANEL_USER/repositories/baakh-2.0/bootstrap/app.php';
```

### Preferred repair: restore APP_PATH vendor and boot from baakh_app

Use this when Composer OOM left `$HOME/baakh_app/vendor` incomplete, but the repository checkout has working dependencies:

```bash
export REPO_PATH="$HOME/repositories/baakh-2.0"
export APP_PATH="$HOME/baakh_app"
export PUBLIC_PATH="$HOME/public_html"

mkdir -p "$APP_PATH/vendor"
rsync -a "$REPO_PATH/vendor/" "$APP_PATH/vendor/"

cd "$REPO_PATH"
DEPLOY_MODE=no-composer bash scripts/cpanel-post-deploy.sh
```

If you must retry Composer on cPanel, the deploy script already uses low-noise production flags and sets `COMPOSER_MEMORY_LIMIT=-1`. That bypasses PHP's memory limit, but it cannot bypass the hosting account's OS memory cap; if `mmap() failed: [12] Cannot allocate memory` continues, copy a complete `vendor/` from a machine or checkout where Composer succeeds and use `DEPLOY_MODE=no-composer`.

## If npm is unavailable on host

If your host does not provide npm, build locally and upload built assets:

```bash
npm ci
npm run build
```

Then include `public/build` in deployment by uploading/syncing it with the deploy source; the deploy script will stop before publishing if Vite assets are missing or incomplete.

## Troubleshooting (Terminal)

Clear config cache after `.env` changes:

```bash
cd "$HOME/baakh_app"
php artisan config:clear
php artisan cache:clear
```

If uploads or logs fail with permission errors:

```bash
cd "$HOME/baakh_app"
chmod -R u+rwX storage bootstrap/cache
```

Re-check PHP:

```bash
php -v
```

## Notes

- Deploy script keeps `.env` untouched on server.
- If your cPanel username is not `$USER` at deploy runtime, hardcode paths in `.cpanel.yml`.
- If first deploy fails on permissions, set writable permissions for `storage` and `bootstrap/cache` (see **Troubleshooting** above).
