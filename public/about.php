<?php

declare(strict_types=1);

require __DIR__ . '/includes/layout.php';

render_layout('About', 'about', static function (): void {
    ?>
    <section class="hero">
        <h1>About</h1>
        <p class="lead">A minimal demo for trying edge-hosted containers.</p>
    </section>
    <div class="card">
        <h2>Why this exists</h2>
        <p>Bunny Magic Containers runs your Docker image close to users. This project keeps PHP simple: no framework, no database, just pages you can extend.</p>
        <p>The image is <code>php:8.3-apache</code> with your files copied into the web root.</p>
    </div>
    <?php
});
