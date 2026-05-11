#!/usr/bin/env bash
# Manual runs: use cPanel Advanced → Terminal or SSH; both are the same shell for these commands.

set -euo pipefail

SOURCE_PATH="${SOURCE_PATH:-$PWD}"
APP_PATH="${APP_PATH:-$HOME/baakh_app}"
PUBLIC_PATH="${PUBLIC_PATH:-$HOME/public_html}"

echo "Deploy source: $SOURCE_PATH"
echo "App path: $APP_PATH"
echo "Public path: $PUBLIC_PATH"

mkdir -p "$APP_PATH" "$PUBLIC_PATH"

echo "Syncing application files..."
rsync -a --delete \
  --exclude ".cpanel.yml" \
  --exclude ".git" \
  --exclude ".github" \
  --exclude ".vscode" \
  --exclude "node_modules" \
  --exclude "vendor" \
  --exclude ".env" \
  --exclude "storage/logs/*" \
  --exclude "storage/framework/cache/data/*" \
  "$SOURCE_PATH"/ "$APP_PATH"/

cd "$APP_PATH"

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
  echo "Building frontend assets..."
  npm ci --no-audit --no-fund
  npm run build
else
  echo "npm not found on server; skipping asset build."
  echo "Build assets locally and upload public/build if needed."
fi

echo "Running Laravel optimize tasks..."
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan event:cache || true
php artisan storage:link || true

echo "Publishing public files..."
rsync -a --delete "$APP_PATH/public/" "$PUBLIC_PATH/"

# cPanel shared hosting serves from public_html. The Laravel app is outside it,
# so we rewrite index.php paths after every deploy.
php -r '
$file = getenv("PUBLIC_PATH") . "/index.php";
$app = rtrim(getenv("APP_PATH"), "/");
if (!file_exists($file)) {
    fwrite(STDERR, "index.php not found at " . $file . PHP_EOL);
    exit(1);
}
$content = file_get_contents($file);
$content = str_replace("__DIR__.\x27/../vendor/autoload.php\x27", "\x27" . $app . "/vendor/autoload.php\x27", $content);
$content = str_replace("__DIR__.\x27/../bootstrap/app.php\x27", "\x27" . $app . "/bootstrap/app.php\x27", $content);
file_put_contents($file, $content);
'

echo "Deployment finished successfully."
