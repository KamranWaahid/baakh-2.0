#!/bin/bash
# Start Laravel server with 100MB upload limit (fixes 413 for Heap Analysis)
cd "$(dirname "$0")"
php -d post_max_size=100M -d upload_max_filesize=100M artisan serve "$@"
