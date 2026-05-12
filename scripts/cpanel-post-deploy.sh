#!/usr/bin/env bash
# Manual runs: use cPanel Advanced → Terminal or SSH; both are the same shell for these commands.

set -euo pipefail

SOURCE_PATH="${SOURCE_PATH:-$PWD}"
APP_PATH="${APP_PATH:-$HOME/baakh_app}"
PUBLIC_PATH="${PUBLIC_PATH:-$HOME/public_html}"
DEPLOY_MODE="${DEPLOY_MODE:-full}"
COMPOSER_MEMORY_LIMIT="${COMPOSER_MEMORY_LIMIT:--1}"
SKIP_COMPOSER="${SKIP_COMPOSER:-0}"

echo "Deploy source: $SOURCE_PATH"
echo "App path: $APP_PATH"
echo "Public path: $PUBLIC_PATH"
echo "Deploy mode: $DEPLOY_MODE"

sync_application_files() {
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
}

verify_app_boot_files() {
  local missing=0

  for file in \
    "$APP_PATH/bootstrap/vercel.php" \
    "$APP_PATH/bootstrap/app.php" \
    "$APP_PATH/vendor/autoload.php"; do
    if [ ! -f "$file" ]; then
      echo "Missing required app boot file: $file" >&2
      missing=1
    fi
  done

  if [ "$missing" -ne 0 ]; then
    echo "Refusing to publish public/index.php until the target app path is bootable." >&2
    exit 1
  fi
}

verify_vite_build() {
  local public_root="${1:?public root is required}"

  PUBLIC_ROOT="$public_root" php <<'PHP'
<?php
$publicRoot = rtrim(getenv('PUBLIC_ROOT'), '/');
$buildPath = $publicRoot . '/build';
$manifestPath = $buildPath . '/manifest.json';

if (!is_file($manifestPath)) {
    fwrite(STDERR, "Missing Vite manifest: {$manifestPath}" . PHP_EOL);
    exit(1);
}

$manifest = json_decode(file_get_contents($manifestPath), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "Invalid Vite manifest: {$manifestPath}" . PHP_EOL);
    exit(1);
}

$missing = [];
foreach ($manifest as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    foreach (['file', 'css', 'assets'] as $key) {
        foreach ((array) ($entry[$key] ?? []) as $file) {
            if (!is_string($file) || $file === '') {
                continue;
            }
            if (!is_file($buildPath . '/' . ltrim($file, '/'))) {
                $missing[] = $file;
            }
        }
    }
}

if ($missing !== []) {
    fwrite(STDERR, "Vite manifest references missing files:" . PHP_EOL);
    foreach (array_unique($missing) as $file) {
        fwrite(STDERR, " - {$file}" . PHP_EOL);
    }
    exit(1);
}

echo "Verified Vite manifest and built assets." . PHP_EOL;
PHP
}

patch_public_index() {
  php <<'PHP'
<?php
$publicPath = rtrim(getenv('PUBLIC_PATH'), '/');
$app = rtrim(getenv('APP_PATH'), '/');
$file = $publicPath . '/index.php';

if (!is_file($file)) {
    fwrite(STDERR, "index.php not found at {$file}" . PHP_EOL);
    exit(1);
}

$content = file_get_contents($file);

$replacePath = static function (string $pattern, string $target, string $label) use (&$content): void {
    $replacement = var_export($target, true);
    $content = preg_replace($pattern, $replacement, $content, 1, $count);

    if ($count === 0 && strpos($content, $replacement) === false) {
        fwrite(STDERR, "Unable to rewrite {$label} path in public index." . PHP_EOL);
        exit(1);
    }
};

$replacePath('~dirname\(__DIR__\)\s*\.\s*[\'"]/bootstrap/vercel\.php[\'"]|[\'"][^\'"]*/bootstrap/vercel\.php[\'"]~', $app . '/bootstrap/vercel.php', 'bootstrap/vercel.php');
$replacePath('~__DIR__\s*\.\s*[\'"]/\.\./storage/framework/maintenance\.php[\'"]|[\'"][^\'"]*/storage/framework/maintenance\.php[\'"]~', $app . '/storage/framework/maintenance.php', 'maintenance.php');
$replacePath('~__DIR__\s*\.\s*[\'"]/\.\./vendor/autoload\.php[\'"]|[\'"][^\'"]*/vendor/autoload\.php[\'"]~', $app . '/vendor/autoload.php', 'vendor/autoload.php');
$replacePath('~__DIR__\s*\.\s*[\'"]/\.\./bootstrap/app\.php[\'"]|[\'"][^\'"]*/bootstrap/app\.php[\'"]~', $app . '/bootstrap/app.php', 'bootstrap/app.php');

file_put_contents($file, $content);
echo "Rewrote public index paths for cPanel." . PHP_EOL;
PHP
}

publish_build_assets() {
  local app_public="${1:?public root is required}"

  verify_vite_build "$app_public"

  echo "Publishing Vite build assets..."
  mkdir -p "$PUBLIC_PATH/build"
  rsync -a --delete "$app_public/build/" "$PUBLIC_PATH/build/"
}

publish_public_files() {
  local app_public="${1:?public root is required}"

  verify_app_boot_files
  publish_build_assets "$app_public"

  echo "Publishing public files..."
  rsync -a \
    --exclude "build" \
    "$app_public/" "$PUBLIC_PATH/"

  patch_public_index
}

mkdir -p "$APP_PATH" "$PUBLIC_PATH"

case "$DEPLOY_MODE" in
  full)
    ;;
  no-composer|skip-composer)
    SKIP_COMPOSER=1
    ;;
  public|public-only)
    publish_public_files "$APP_PATH/public"
    echo "Public files published successfully."
    exit 0
    ;;
  assets|assets-only)
    publish_build_assets "$SOURCE_PATH/public"
    echo "Build assets published successfully."
    exit 0
    ;;
  *)
    echo "Unknown DEPLOY_MODE: $DEPLOY_MODE" >&2
    echo "Use one of: full, no-composer, skip-composer, public, public-only, assets, assets-only." >&2
    exit 1
    ;;
esac

sync_application_files

cd "$APP_PATH"

if [ "$SKIP_COMPOSER" = "1" ]; then
  echo "Skipping Composer install; verifying existing vendor directory..."
  verify_app_boot_files
else
  echo "Installing PHP dependencies..."
  export COMPOSER_MEMORY_LIMIT
  composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
fi

if command -v npm >/dev/null 2>&1; then
  echo "Building frontend assets..."
  npm ci --no-audit --no-fund
  npm run build
else
  echo "npm not found on server; skipping asset build."
  echo "A valid public/build directory must already exist in the deployment source."
fi

echo "Running Laravel optimize tasks..."
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan event:cache || true
php artisan storage:link || true

publish_public_files "$APP_PATH/public"

echo "Deployment finished successfully."
