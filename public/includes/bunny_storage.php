<?php

declare(strict_types=1);

/**
 * Thin wrapper around the Bunny Storage HTTP API.
 *
 * Storage zones are addressed as:
 *     https://<hostname>/<zone>/<path>
 * with an `AccessKey` header carrying the zone password.
 *
 * Public reads happen via a Pull Zone hostname (e.g. <name>.b-cdn.net).
 *
 * @phpstan-type StorageConfig array{
 *     subfolder: string,
 *     zone: string,
 *     hostname: string,
 *     access_key: string,
 *     pull_zone: string,
 * }
 */

/**
 * @param StorageConfig $config
 */
function bunny_storage_is_configured(array $config): bool
{
    return $config['zone'] !== ''
        && $config['access_key'] !== ''
        && $config['hostname'] !== '';
}

/**
 * @param StorageConfig $config
 */
function bunny_storage_can_render(array $config): bool
{
    return $config['pull_zone'] !== '';
}

/**
 * Build the relative object key inside the storage zone for a given filename,
 * combining the configured subfolder with the file.
 *
 * @param StorageConfig $config
 */
function bunny_storage_object_key(array $config, string $filename): string
{
    $sub = trim($config['subfolder'], "/ \t\n\r\0\x0B");
    return $sub === '' ? $filename : $sub . '/' . $filename;
}

/**
 * Upload a local file to Bunny Storage at `<zone>/<relativePath>`.
 *
 * @param StorageConfig $config
 */
function bunny_storage_put(array $config, string $relativePath, string $localPath, string $mime): bool
{
    if (!bunny_storage_is_configured($config)) {
        return false;
    }
    if (!is_file($localPath)) {
        return false;
    }

    $url = bunny_storage_api_url($config, $relativePath);
    $fp = @fopen($localPath, 'rb');
    if (!is_resource($fp)) {
        return false;
    }

    $size = @filesize($localPath);
    if ($size === false) {
        fclose($fp);
        return false;
    }

    $ch = curl_init($url);
    if ($ch === false) {
        fclose($fp);
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_UPLOAD => true,
        CURLOPT_INFILE => $fp,
        CURLOPT_INFILESIZE => $size,
        CURLOPT_HTTPHEADER => [
            'AccessKey: ' . $config['access_key'],
            'Content-Type: ' . $mime,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $ok = curl_exec($ch) !== false;
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    return $ok && $status === 201;
}

/**
 * Delete an object from Bunny Storage. Returns true on success or if the
 * object did not exist.
 *
 * @param StorageConfig $config
 */
function bunny_storage_delete(array $config, string $relativePath): bool
{
    if (!bunny_storage_is_configured($config)) {
        return false;
    }

    $url = bunny_storage_api_url($config, $relativePath);
    $ch = curl_init($url);
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => ['AccessKey: ' . $config['access_key']],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $ok = curl_exec($ch) !== false;
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $ok && ($status === 200 || $status === 204 || $status === 404);
}

/**
 * Public CDN URL for a stored object (via the Pull Zone hostname).
 * Returns '' when no pull zone is configured.
 *
 * @param StorageConfig $config
 */
function bunny_storage_public_url(array $config, string $relativePath): string
{
    if (!bunny_storage_can_render($config)) {
        return '';
    }
    $host = preg_replace('#^https?://#i', '', trim($config['pull_zone']));
    $host = rtrim((string) $host, '/');
    if ($host === '') {
        return '';
    }
    return 'https://' . $host . '/' . bunny_storage_encode_path(ltrim($relativePath, '/'));
}

/**
 * @param StorageConfig $config
 */
function bunny_storage_api_url(array $config, string $relativePath): string
{
    return sprintf(
        'https://%s/%s/%s',
        rtrim($config['hostname'], '/'),
        rawurlencode($config['zone']),
        bunny_storage_encode_path(ltrim($relativePath, '/'))
    );
}

function bunny_storage_encode_path(string $path): string
{
    $parts = array_map('rawurlencode', explode('/', $path));
    return implode('/', $parts);
}
