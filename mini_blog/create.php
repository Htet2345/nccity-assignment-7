<?php
declare(strict_types=1);
$pageTitle = 'Create Post';
require __DIR__ . '/layouts/header.php';
[$errors, $old] = pullFormFeedback();
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-4">Create a Post</h1>
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form action="actions/store.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input class="form-control" id="title" name="title" type="text" maxlength="150" value="<?= h($old['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <div class="form-text">JPG, JPEG, PNG, GIF, or WEBP. Maximum 5 MB.</div>
                    </div>
                    <div class="mb-4">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="8" maxlength="10000" required><?= h($old['content'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Upload</button>
                    <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
