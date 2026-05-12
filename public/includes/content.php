<?php

declare(strict_types=1);

function render_home_content(): void
{
    ?>
    <section class="hero">
        <h1>Php running in a Docker container</h1>
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

/**
 * @param array<int, array{name: string, email: string, message: string, imageUrl: string, createdAt: string}> $comments
 * @param array{name: string, email: string, message: string} $values
 * @param array<string, string> $errors
 */
function render_contact_content(array $comments, array $values, array $errors, bool $success, bool $imageUploadsEnabled): void
{
    ?>
    <section class="hero">
        <h1>Comments</h1>
        <p class="lead">Leave a note below. Comments are saved to a JSON file on the container’s persistent volume; any attached image is uploaded to Bunny Storage.</p>
    </section>
    <div class="card">
        <?php if ($success) { ?>
            <h2>Thanks</h2>
            <p>Your comment was saved.</p>
        <?php } ?>

        <h2>Leave a comment</h2>
        <form method="post" action="/contact" enctype="multipart/form-data" novalidate>
            <label for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                autocomplete="name"
                required
                value="<?= htmlspecialchars($values['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
            <?php if (isset($errors['name'])) { ?>
                <p class="note" role="alert"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php } ?>

            <label for="email">Email (optional)</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                value="<?= htmlspecialchars($values['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
            <?php if (isset($errors['email'])) { ?>
                <p class="note" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php } ?>

            <label for="message">Comment</label>
            <textarea id="message" name="message" required><?= htmlspecialchars($values['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['message'])) { ?>
                <p class="note" role="alert"><?= htmlspecialchars($errors['message'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php } ?>

            <?php if ($imageUploadsEnabled) { ?>
                <label for="image">Image (optional, max 5 MB — JPEG, PNG, GIF, WebP)</label>
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                >
                <?php if (isset($errors['image'])) { ?>
                    <p class="note" role="alert"><?= htmlspecialchars($errors['image'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php } ?>
            <?php } else { ?>
                <p class="note">Image uploads are disabled — set <code>BUNNY_STORAGE_ZONE</code>, <code>BUNNY_STORAGE_KEY</code>, and <code>BUNNY_PULL_ZONE</code> to enable them.</p>
            <?php } ?>

            <button type="submit">Post comment</button>
        </form>
        <p class="note">Comments are stored in <code>/data/comments.json</code> on the persistent volume. Images live in Bunny Storage and are served from the configured Pull Zone.</p>
    </div>

    <div class="card">
        <h2>Recent comments</h2>
        <?php if ($comments === []) { ?>
            <p class="note">No comments yet.</p>
        <?php } else { ?>
            <div class="comments">
                <?php foreach ($comments as $comment) { ?>
                    <article class="comment">
                        <p class="note">
                            <strong><?= htmlspecialchars($comment['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ($comment['email'] !== '') { ?>
                                <span>· <?= htmlspecialchars($comment['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php } ?>
                            <span>· <?= htmlspecialchars($comment['createdAt'], ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                        <p><?= nl2br(htmlspecialchars($comment['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php if (($comment['imageUrl'] ?? '') !== '') { ?>
                            <p class="comment-image">
                                <img
                                    src="<?= htmlspecialchars($comment['imageUrl'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Attachment from <?= htmlspecialchars($comment['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy"
                                >
                            </p>
                        <?php } ?>
                    </article>
                <?php } ?>
            </div>
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
