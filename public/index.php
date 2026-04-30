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
    case '/contact':
        $submitted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        render_layout('Contact', 'contact', static function () use ($submitted): void {
            render_contact_content($submitted);
        });
        break;
    default:
        http_response_code(404);
        render_layout('Not found', '', 'render_not_found_content');
        break;
}
