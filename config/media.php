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
];

