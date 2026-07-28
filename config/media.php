<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Storage Disk
    |--------------------------------------------------------------------------
    |
    | Use "local" to store inside public/assets/images (legacy behavior),
    | or set to a cloud disk like "s3" to store media on object storage.
    |
    */
    'disk' => env('MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Base Media Path
    |--------------------------------------------------------------------------
    |
    | Relative root folder for uploaded media objects.
    |
    */
    'base_path' => env('MEDIA_BASE_PATH', 'assets/images'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Media Path
    |--------------------------------------------------------------------------
    |
    | Relative root folder when storing on S3/R2 (legacy CDN layout).
    |
    */
    'cloud_base_path' => env('MEDIA_CLOUD_BASE_PATH', 'Images'),

    /*
    |--------------------------------------------------------------------------
    | Extra Web Root (cPanel public_html)
    |--------------------------------------------------------------------------
    |
    | When the app boots from APP_PATH but Apache serves PUBLIC_PATH
    | (e.g. ~/public_html), set this so uploads are also written where
    | the browser can fetch them. DOCUMENT_ROOT is used automatically
    | when present; this is an explicit override/fallback.
    |
    | Example: /home/USER/public_html
    |
    */
    'web_root' => env('MEDIA_WEB_ROOT', ''),

    /*
    |--------------------------------------------------------------------------
    | Max Image Edge (pixels)
    |--------------------------------------------------------------------------
    |
    | Downscale uploads so the longest side does not exceed this value
    | before WebP encoding. Helps shared hosting memory limits.
    |
    */
    'max_edge' => (int) env('MEDIA_MAX_EDGE', 2000),
];
