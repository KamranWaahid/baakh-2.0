# Fix 413 Content Too Large for Heap Analysis

The Heap Analysis Toolkit uploads `.heapsnapshot` files that often exceed 8MB. A 413 error means the request was rejected **before** it reaches Laravel—by PHP or Nginx.

## Quick Fix by Setup

### 1. Using `php artisan serve` (port 8000)

Start the server with increased limits:

```bash
cd Baakh
composer serve
# or
php -d post_max_size=100M -d upload_max_filesize=100M artisan serve
```

Then use `http://127.0.0.1:8000` as usual.

---

### 2. Using Laravel Valet

Nginx enforces `client_max_body_size` (often 1–2MB). Increase it:

1. Open your site config:
   ```bash
   # Valet v4+
   open ~/.config/valet/Nginx/
   # Valet v3
   open ~/.valet/Nginx/
   ```

2. Edit the file for your site (e.g. `baakh.test`).

3. Add this line **inside** the `server { }` block (before `location` blocks):
   ```nginx
   client_max_body_size 100M;
   ```

4. Restart Valet:
   ```bash
   valet restart
   ```

---

### 3. Using Laravel Herd

**Option A – Herd UI (recommended):**

1. Open Herd → **PHP** → **Settings**
2. Set `upload_max_filesize` and `post_max_size` to `100M`
3. Restart Herd

**Option B – Manual Nginx config:**

1. Site configs: `~/Library/Application Support/Herd/config/valet/Nginx/`
2. Add `client_max_body_size 100M;` inside the `server { }` block
3. Restart Herd

---

### 4. Using Nginx directly

Add to your server block:

```nginx
client_max_body_size 100M;
```

Then reload Nginx: `nginx -s reload` or `sudo systemctl reload nginx`.

---

## PHP settings (if request reaches PHP)

If the request reaches PHP but still fails, ensure:

- `post_max_size` ≥ 100M  
- `upload_max_filesize` ≥ 100M  

A `.user.ini` in `public/` is already set for PHP-FPM. For `php artisan serve`, use `composer serve` instead.

---

## Verify

1. Restart your server (Valet/Herd/nginx/artisan serve).
2. Upload a `.heapsnapshot` file > 8MB in the Heap Analysis Toolkit.
3. The upload should succeed and analysis should run.
