<?php
declare(strict_types=1);
$pageTitle = 'Edit Post';
require __DIR__ . '/layouts/header.php';
$id = requestId($_GET['id'] ?? null);
$post = $id === null ? null : findPostById($id);
[$errors, $old] = pullFormFeedback();
if ($post === null) {
    http_response_code(404);
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($post === null): ?>
            <div class="alert alert-warning text-center p-4" role="alert">
                <h1 class="h4">Post not found</h1>
                <p>The requested post does not exist or the link is invalid.</p>
                <a class="btn btn-primary" href="index.php">Back to Blog</a>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-4">Edit Post</h1>
                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= h($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form action="actions/update.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="150" value="<?= h($old['title'] ?? $post['title'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <span class="form-label d-block">Current image</span>
                            <img class="edit-thumbnail rounded border" src="<?= h($post['image_url'] ?? '') ?>" alt="Current post image">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Replace image <span class="text-secondary">(optional)</span></label>
                            <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">Leave empty to keep the current image. Maximum 5 MB.</div>
                        </div>
                        <div class="mb-4">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="8" maxlength="10000" required><?= h($old['content'] ?? $post['content'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Update Post</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
