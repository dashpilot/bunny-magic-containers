<?php

declare(strict_types=1);

require __DIR__ . '/includes/layout.php';

render_layout('Home', 'home', static function (): void {
    ?>
    <section class="hero">
        <h1>Does this now auto deploy?</h1>
        <p class="lead">This is a tiny three-page site you can run locally with Docker, then ship to Bunny Magic Containers.</p>
    </section>
    <div class="card">
        <h2>What you get</h2>
        <p>Plain PHP and Apache on port 80 inside the container — the same shape Bunny’s docs use for PHP examples.</p>
        <p>Use the nav above to visit About and Contact.</p>
    </div>
    <?php
});
