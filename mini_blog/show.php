<?php
declare(strict_types=1);
$pageTitle = 'View Post';
require __DIR__ . '/layouts/header.php';
$id = requestId($_GET['id'] ?? null);
$post = $id === null ? null : findPostById($id);
if ($post === null) {
    http_response_code(404);
}
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <?php if ($post === null): ?>
            <div class="alert alert-warning text-center p-4" role="alert">
                <h1 class="h4">Post not found</h1>
                <p>The requested post does not exist or the link is invalid.</p>
                <a class="btn btn-primary" href="index.php">Back to Blog</a>
            </div>
        <?php else: ?>
            <article class="card border-0 shadow-sm overflow-hidden">
                <img class="detail-image" src="<?= h($post['image_url'] ?? '') ?>" alt="Image for <?= h($post['title'] ?? 'post') ?>">
                <div class="card-body p-4 p-md-5">
                    <h1 class="mb-4"><?= h($post['title'] ?? 'Untitled') ?></h1>
                    <div class="fs-5 lh-lg"><?= nl2br(h($post['content'] ?? '')) ?></div>
                    <div class="mt-4 d-flex gap-2">
                        <a class="btn btn-secondary" href="edit.php?id=<?= (int) $post['id'] ?>">Edit</a>
                        <a class="btn btn-outline-primary" href="index.php">Back to Blog</a>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
