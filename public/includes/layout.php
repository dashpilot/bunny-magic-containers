<?php
declare(strict_types=1);

/**
 * @param string $title Page title
 * @param string $active One of: home, about, contact
 * @param callable $content fn(): void
 */
function render_layout(string $title, string $active, callable $content): void
{
    $base = '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · Bunny Demo</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="inner">
            <a class="logo" href="<?= $base ?>/">Bunny Demo</a>
            <nav aria-label="Main">
                <a href="<?= $base ?>/"<?= $active === 'home' ? ' class="active" aria-current="page"' : '' ?>>Home</a>
                <a href="<?= $base ?>/about.php"<?= $active === 'about' ? ' class="active" aria-current="page"' : '' ?>>About</a>
                <a href="<?= $base ?>/contact.php"<?= $active === 'contact' ? ' class="active" aria-current="page"' : '' ?>>Contact</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <?php $content(); ?>
    </main>
    <footer class="site-footer">
        <p>PHP on Apache — ready for <a href="https://bunny.net/magic-containers" rel="noopener noreferrer">Bunny Magic Containers</a>.</p>
    </footer>
</body>
</html>
    <?php
}
