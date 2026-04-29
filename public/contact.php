<?php

declare(strict_types=1);

require __DIR__ . '/includes/layout.php';

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

render_layout('Contact', 'contact', static function () use ($submitted): void {
    ?>
    <section class="hero">
        <h1>Contact</h1>
        <p class="lead">Demo form — nothing is stored or sent.</p>
    </section>
    <div class="card">
        <?php if ($submitted) { ?>
            <h2>Thanks</h2>
            <p>Your message was not saved (this is a static demo). Hook this up to email or an API when you are ready.</p>
            <p><a href="contact.php">Send another</a></p>
        <?php } else { ?>
            <h2>Get in touch</h2>
            <form method="post" action="">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" autocomplete="name" required>

                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required>

                <label for="message">Message</label>
                <textarea id="message" name="message" required></textarea>

                <button type="submit">Send</button>
            </form>
            <p class="note">POST requests work the same on Magic Containers once deployed.</p>
        <?php } ?>
    </div>
    <?php
});
