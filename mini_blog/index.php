<?php
declare(strict_types=1);
$pageTitle = 'Mini Blog';
require __DIR__ . '/layouts/header.php';
$posts = readPosts();
$flash = pullFlash();
?>
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= h($flash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= h($flash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Latest Posts</h1>
</div>

<?php if ($posts === []): ?>
    <div class="text-center bg-white rounded-3 shadow-sm p-5">
        <h2 class="h4">No posts yet</h2>
        <p class="text-secondary mb-4">Create your first post and share something interesting.</p>
        <a class="btn btn-primary" href="create.php">Create Post</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach (array_reverse($posts) as $post): ?>
            <?php
            $id = requestId($post['id'] ?? null);
            if ($id === null) {
                continue;
            }
            ?>
            <div class="col-sm-6 col-lg-4">
                <article class="card post-card overflow-hidden">
                    <img src="<?= h($post['image_url'] ?? '') ?>" class="card-img-top" alt="Image for <?= h($post['title'] ?? 'post') ?>">
                    <div class="card-body d-flex flex-column">
                        <h2 class="card-title h5 mb-4"><?= h($post['title'] ?? 'Untitled') ?></h2>
                        <div class="d-flex flex-wrap gap-2 mt-auto">
                            <a class="btn btn-primary btn-sm" href="show.php?id=<?= $id ?>">View More</a>
                            <a class="btn btn-secondary btn-sm" href="edit.php?id=<?= $id ?>">Edit</a>
                            <form action="actions/destroy.php" method="post" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/layouts/footer.php'; ?>
