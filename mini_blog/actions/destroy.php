<?php
declare(strict_types=1);
define('MINI_BLOG_NO_LAYOUT', true);
require __DIR__ . '/../layouts/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$id = requestId($_POST['id'] ?? null);
if ($id === null || !validCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'The delete request was invalid.');
    header('Location: ../index.php');
    exit;
}

$deletedImageUrl = null;
$found = false;
$saved = updatePostsFile(function (array $posts) use ($id, &$deletedImageUrl, &$found): array {
    $remaining = [];
    foreach ($posts as $post) {
        if (!$found && isset($post['id']) && (int) $post['id'] === $id) {
            $found = true;
            $deletedImageUrl = $post['image_url'] ?? null;
            continue;
        }
        $remaining[] = $post;
    }
    return $remaining;
});

if ($saved && $found) {
    deleteStoredImage($deletedImageUrl);
    setFlash('success', 'Post deleted successfully.');
} elseif ($saved) {
    setFlash('warning', 'The post was already removed.');
} else {
    setFlash('danger', 'The post could not be deleted.');
}

header('Location: ../index.php');
exit;
