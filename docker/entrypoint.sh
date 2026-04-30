#!/bin/sh
set -eu

# Bunny mounts a persistent volume at /data. Ensure Apache's www-data can write to it.
mkdir -p /data || true
chown -R www-data:www-data /data 2>/dev/null || true
chmod 775 /data 2>/dev/null || true

exec apache2-foreground

