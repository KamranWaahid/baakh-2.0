# cPanel Deployment Guide (Laravel + Vite)

This project is a Laravel 10 app with Vite frontend assets.  
For cPanel shared hosting, deploy app code outside `public_html` and publish only `public/` into `public_html`.

**Primary shell:** use **cPanel → Advanced → Terminal** (web terminal). All commands below work there; if you use full SSH instead, the same commands apply.

## What is configured

- `.cpanel.yml` now runs `scripts/cpanel-post-deploy.sh` on every cPanel Git deploy.
- Deploy script:
  - syncs repository into `APP_PATH` (default: `/home/<user>/baakh_app`),
  - runs `composer install --no-dev`,
  - runs `npm ci && npm run build` (if npm is available),
  - caches Laravel config/views/events and creates storage symlink,
  - syncs `public/` into `PUBLIC_PATH` (default: `/home/<user>/public_html`),
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

## If npm is unavailable on host

If your host does not provide npm, build locally and upload built assets:

```bash
npm ci
npm run build
```

Then include `public/build` in deployment by uploading/syncing it to `/home/<user>/baakh_app/public/build` before deploy.

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
