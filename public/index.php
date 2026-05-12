<?php

declare(strict_types=1);

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/bunny_storage.php';

$config = require __DIR__ . '/includes/config.php';
$storageConfig = $config['bunny_storage'];

const DATA_DIR = '/data';
const COMMENTS_FILE = DATA_DIR . '/comments.json';
const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
const IMAGE_EXT_BY_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($rawPath) ? rtrim($rawPath, '/') : '';
$path = $path === '' ? '/' : $path;
if ($path === '/index.php') {
    $path = '/';
}

if ($path === '/about.php') {
    header('Location: /about', true, 301);
    exit;
}
if ($path === '/contact.php') {
    header('Location: /contact', true, 301);
    exit;
}

switch ($path) {
    case '/':
        render_layout('Home', 'home', 'render_home_content');
        break;
    case '/about':
        render_layout('About', 'about', 'render_about_content');
        break;
    case '/comments.json':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (is_file(COMMENTS_FILE)) {
            $raw = @file_get_contents(COMMENTS_FILE);
            if (is_string($raw) && $raw !== '') {
                echo $raw;
                exit;
            }
        }
        echo "[]\n";
        exit;
    case '/contact':
        $strlen = static function (string $value): int {
            return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        };

        $loadComments = static function () use ($storageConfig): array {
            $dir = dirname(COMMENTS_FILE);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!is_file(COMMENTS_FILE)) {
                @file_put_contents(COMMENTS_FILE, "[]\n");
            }

            $raw = @file_get_contents(COMMENTS_FILE);
            if (!is_string($raw) || $raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }

            $out = [];
            foreach ($decoded as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = isset($item['name']) && is_string($item['name']) ? $item['name'] : '';
                $email = isset($item['email']) && is_string($item['email']) ? $item['email'] : '';
                $message = isset($item['message']) && is_string($item['message']) ? $item['message'] : '';
                $imagePath = isset($item['imagePath']) && is_string($item['imagePath']) ? $item['imagePath'] : '';
                $createdAt = isset($item['createdAt']) && is_string($item['createdAt']) ? $item['createdAt'] : '';
                if ($name === '' || $message === '' || $createdAt === '') {
                    continue;
                }

                $imageUrl = $imagePath !== ''
                    ? bunny_storage_public_url($storageConfig, $imagePath)
                    : '';

                $out[] = [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                    'imageUrl' => $imageUrl,
                    'createdAt' => $createdAt,
                ];
            }

            return array_slice(array_reverse($out), 0, 50);
        };

        $appendComment = static function (array $comment): bool {
            $dir = dirname(COMMENTS_FILE);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return false;
            }

            $fp = @fopen(COMMENTS_FILE, 'c+');
            if (!is_resource($fp)) {
                return false;
            }

            try {
                if (!flock($fp, LOCK_EX)) {
                    return false;
                }

                $raw = stream_get_contents($fp);
                $existing = [];
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $existing = $decoded;
                    }
                }

                $existing[] = $comment;
                if (count($existing) > 200) {
                    $existing = array_slice($existing, -200);
                }

                $tmp = COMMENTS_FILE . '.tmp';
                $json = json_encode($existing, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (!is_string($json)) {
                    return false;
                }
                if (@file_put_contents($tmp, $json . "\n") === false) {
                    return false;
                }
                if (!@rename($tmp, COMMENTS_FILE)) {
                    @unlink($tmp);
                    return false;
                }

                return true;
            } finally {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        };

        $errors = [];
        $values = [
            'name' => '',
            'email' => '',
            'message' => '',
        ];
        $success = ($_GET['success'] ?? '') === '1';
        $storageReady = bunny_storage_is_configured($storageConfig)
            && bunny_storage_can_render($storageConfig);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > 0 && $_POST === [] && $_FILES === []) {
                // Whole request body exceeded post_max_size; PHP discarded it.
                $errors['image'] = 'Upload was too large. Please pick a smaller image.';
            }

            $values['name'] = is_string($_POST['name'] ?? null) ? trim((string)$_POST['name']) : '';
            $values['email'] = is_string($_POST['email'] ?? null) ? trim((string)$_POST['email']) : '';
            $values['message'] = is_string($_POST['message'] ?? null) ? trim((string)$_POST['message']) : '';

            if (!is_dir(DATA_DIR) || !is_writable(DATA_DIR)) {
                $errors['form'] = 'Storage is not writable. Ensure a persistent volume is mounted at /data and writable by the web server user.';
            }

            if ($values['name'] === '') {
                $errors['name'] = 'Please enter your name.';
            } elseif ($strlen($values['name']) > 100) {
                $errors['name'] = 'Name is too long.';
            }

            if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address (or leave it empty).';
            } elseif ($strlen($values['email']) > 200) {
                $errors['email'] = 'Email is too long.';
            }

            if ($values['message'] === '') {
                $errors['message'] = 'Please enter a comment.';
            } elseif ($strlen($values['message']) > 2000) {
                $errors['message'] = 'Comment is too long.';
            }

            // Optional image — validate locally before uploading anywhere.
            $pendingImage = null;
            $upload = $_FILES['image'] ?? null;
            if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $err = (int)$upload['error'];
                if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                    $errors['image'] = 'Image is too large (max 5 MB).';
                } elseif ($err === UPLOAD_ERR_PARTIAL) {
                    $errors['image'] = 'Image upload was interrupted. Please try again.';
                } elseif ($err !== UPLOAD_ERR_OK) {
                    $errors['image'] = 'Image upload failed.';
                } elseif (!is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
                    $errors['image'] = 'Image upload failed.';
                } elseif ((int)($upload['size'] ?? 0) > MAX_IMAGE_BYTES) {
                    $errors['image'] = 'Image is too large (max 5 MB).';
                } elseif (!$storageReady) {
                    $errors['image'] = 'Image uploads are not configured on this server.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = '';
                    if ($finfo !== false) {
                        $detected = finfo_file($finfo, $upload['tmp_name']);
                        $mime = is_string($detected) ? $detected : '';
                        finfo_close($finfo);
                    }
                    if (!isset(IMAGE_EXT_BY_MIME[$mime])) {
                        $errors['image'] = 'Only JPEG, PNG, GIF, or WebP images are allowed.';
                    } else {
                        $pendingImage = [
                            'tmp' => $upload['tmp_name'],
                            'ext' => IMAGE_EXT_BY_MIME[$mime],
                            'mime' => $mime,
                        ];
                    }
                }
            }

            if ($errors === []) {
                $imagePath = '';
                if ($pendingImage !== null) {
                    $filename = bin2hex(random_bytes(16)) . '.' . $pendingImage['ext'];
                    $objectKey = bunny_storage_object_key($storageConfig, $filename);
                    if (bunny_storage_put($storageConfig, $objectKey, $pendingImage['tmp'], $pendingImage['mime'])) {
                        $imagePath = $objectKey;
                    } else {
                        $errors['form'] = 'Could not upload image to storage. Please try again.';
                    }
                }

                if ($errors === []) {
                    $ok = $appendComment([
                        'name' => $values['name'],
                        'email' => $values['email'],
                        'message' => $values['message'],
                        'imagePath' => $imagePath,
                        'createdAt' => gmdate('c'),
                    ]);

                    if ($ok) {
                        header('Location: /contact?success=1', true, 303);
                        exit;
                    }
                    if ($imagePath !== '') {
                        // Roll back the storage upload so we don't leak an orphan object.
                        bunny_storage_delete($storageConfig, $imagePath);
                    }
                    $errors['form'] = 'Could not save your comment. Please try again.';
                }
            }
        }

        $comments = $loadComments();
        $imageUploadsEnabled = $storageReady;

        render_layout('Comments', 'contact', static function () use ($comments, $values, $errors, $success, $imageUploadsEnabled): void {
            if (isset($errors['form'])) { ?>
                <div class="card"><p class="note" role="alert"><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></p></div>
            <?php }
            render_contact_content($comments, $values, $errors, $success, $imageUploadsEnabled);
        });
        break;
    default:
        http_response_code(404);
        render_layout('Not found', '', 'render_not_found_content');
        break;
}
