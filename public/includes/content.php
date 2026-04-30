<?php

declare(strict_types=1);

function render_home_content(): void
{
    ?>
    <section class="hero">
        <h1>And how about now?</h1>
        <p class="lead">This is a tiny three-page site you can run locally with Docker, then ship to Bunny Magic Containers.</p>
    </section>
    <div class="card">
        <h2>What you get</h2>
        <p>Plain PHP and Apache on port 80 inside the container — the same shape Bunny’s docs use for PHP examples.</p>
        <p>Use the nav above to visit About and Contact.</p>
    </div>
    <?php
}

function render_about_content(): void
{
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
}

function render_contact_content(bool $submitted): void
{
    ?>
    <section class="hero">
        <h1>Contact</h1>
        <p class="lead">Demo form — nothing is stored or sent.</p>
    </section>
    <div class="card">
        <?php if ($submitted) { ?>
            <h2>Thanks</h2>
            <p>Your message was not saved (this is a static demo). Hook this up to email or an API when you are ready.</p>
            <p><a href="/contact">Send another</a></p>
        <?php } else { ?>
            <h2>Get in touch</h2>
            <form method="post" action="/contact">
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
}

function render_not_found_content(): void
{
    ?>
    <section class="hero">
        <h1>Not found</h1>
        <p class="lead">No page at this URL.</p>
    </section>
    <div class="card">
        <p><a href="/">Back to home</a></p>
    </div>
    <?php
}
