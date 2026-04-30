<?php

declare(strict_types=1);

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/content.php';

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
        $commentsFile = '/data/comments.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (is_file($commentsFile)) {
            $raw = @file_get_contents($commentsFile);
            if (is_string($raw) && $raw !== '') {
                echo $raw;
                exit;
            }
        }
        echo "[]\n";
        exit;
    case '/contact':
        $commentsFile = '/data/comments.json';
        $strlen = static function (string $value): int {
            return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        };

        $loadComments = static function () use ($commentsFile): array {
            $dir = dirname($commentsFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!is_file($commentsFile)) {
                @file_put_contents($commentsFile, "[]\n");
            }

            $raw = @file_get_contents($commentsFile);
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
                $createdAt = isset($item['createdAt']) && is_string($item['createdAt']) ? $item['createdAt'] : '';
                if ($name === '' || $message === '' || $createdAt === '') {
                    continue;
                }
                $out[] = ['name' => $name, 'email' => $email, 'message' => $message, 'createdAt' => $createdAt];
            }

            return array_slice(array_reverse($out), 0, 50);
        };

        $appendComment = static function (array $comment) use ($commentsFile): bool {
            $dir = dirname($commentsFile);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return false;
            }

            $fp = @fopen($commentsFile, 'c+');
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

                $tmp = $commentsFile . '.tmp';
                $json = json_encode($existing, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (!is_string($json)) {
                    return false;
                }
                if (@file_put_contents($tmp, $json . "\n") === false) {
                    return false;
                }
                if (!@rename($tmp, $commentsFile)) {
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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $values['name'] = is_string($_POST['name'] ?? null) ? trim((string)$_POST['name']) : '';
            $values['email'] = is_string($_POST['email'] ?? null) ? trim((string)$_POST['email']) : '';
            $values['message'] = is_string($_POST['message'] ?? null) ? trim((string)$_POST['message']) : '';

            if (!is_dir('/data') || !is_writable('/data')) {
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

            if ($errors === []) {
                $ok = $appendComment([
                    'name' => $values['name'],
                    'email' => $values['email'],
                    'message' => $values['message'],
                    'createdAt' => gmdate('c'),
                ]);

                if ($ok) {
                    header('Location: /contact?success=1', true, 303);
                    exit;
                }
                $errors['form'] = 'Could not save your comment. Please try again.';
            }
        }

        $comments = $loadComments();

        render_layout('Comments', 'contact', static function () use ($comments, $values, $errors, $success): void {
            if (isset($errors['form'])) { ?>
                <div class="card"><p class="note" role="alert"><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></p></div>
            <?php }
            render_contact_content($comments, $values, $errors, $success);
        });
        break;
    default:
        http_response_code(404);
        render_layout('Not found', '', 'render_not_found_content');
        break;
}
