#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x run-worker.sh`

# This command runs the queue worker
php artisan queue:work --tries=3 --max-time=0