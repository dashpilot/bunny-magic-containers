<?php

declare(strict_types=1);

/**
 * App configuration.
 *
 * Secrets default to environment variables so they aren't committed.
 * In docker-compose.yml / Bunny Magic Containers, set:
 *   - BUNNY_STORAGE_ZONE
 *   - BUNNY_STORAGE_KEY        (storage zone "Password" / FTP & API Access key)
 *   - BUNNY_STORAGE_HOSTNAME   (optional, default 'storage.bunnycdn.com')
 *   - BUNNY_PULL_ZONE          (e.g. 'your-zone.b-cdn.net' or a custom domain)
 */
return [
    'bunny_storage' => [
        // Subfolder inside the Bunny Storage zone where comment images are uploaded.
        // Use '' to upload to the zone root. No leading or trailing slashes.
        'subfolder' => 'comment-images',

        // Storage zone name (e.g. 'my-app-uploads').
        'zone' => (string) (getenv('BUNNY_STORAGE_ZONE') ?: ''),

        // Storage API hostname. Use a regional one for replicated zones,
        // e.g. 'ny.storage.bunnycdn.com', 'la.storage.bunnycdn.com',
        // 'sg.storage.bunnycdn.com', 'syd.storage.bunnycdn.com'.
        'hostname' => (string) (getenv('BUNNY_STORAGE_HOSTNAME') ?: 'storage.bunnycdn.com'),

        // Storage zone access key (a.k.a. "Password" in the Bunny dashboard
        // under Storage → your zone → FTP & API Access).
        'access_key' => (string) (getenv('BUNNY_STORAGE_KEY') ?: ''),

        // Public Pull Zone hostname used in <img src>. No scheme.
        // e.g. 'my-app.b-cdn.net' or a custom CNAME like 'cdn.example.com'.
        'pull_zone' => (string) (getenv('BUNNY_PULL_ZONE') ?: ''),
    ],
];
