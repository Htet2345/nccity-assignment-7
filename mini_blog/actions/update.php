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
if ($id === null || findPostById($id) === null) {
    setFlash('warning', 'The post could not be found.');
    header('Location: ../index.php');
    exit;
}

[$title, $content, $errors] = validatePostText($_POST['title'] ?? null, $_POST['content'] ?? null);
if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
    $errors[] = 'Your session expired. Please submit the form again.';
}

$newImageUrl = null;
if ($errors === []) {
    [$newImageUrl, $imageError] = saveUploadedImage($_FILES['image'] ?? null, false);
    if ($imageError !== null) {
        $errors[] = $imageError;
    }
}

if ($errors !== []) {
    setFormFeedback($errors, ['title' => $title, 'content' => $content]);
    header('Location: ../edit.php?id=' . $id);
    exit;
}

$oldImageUrl = null;
$found = false;
$saved = updatePostsFile(function (array $posts) use ($id, $title, $content, $newImageUrl, &$oldImageUrl, &$found): array {
    foreach ($posts as &$post) {
        if (isset($post['id']) && (int) $post['id'] === $id) {
            $found = true;
            $oldImageUrl = $post['image_url'] ?? null;
            $post['title'] = $title;
            $post['content'] = $content;
            if ($newImageUrl !== null) {
                $post['image_url'] = $newImageUrl;
            }
            break;
        }
    }
    unset($post);
    return $posts;
});

if (!$saved || !$found) {
    if ($newImageUrl !== null) {
        deleteStoredImage($newImageUrl);
    }
    setFlash('danger', 'The post could not be updated.');
    header('Location: ../index.php');
    exit;
}

if ($newImageUrl !== null && $oldImageUrl !== $newImageUrl) {
    deleteStoredImage($oldImageUrl);
}
setFlash('success', 'Post updated successfully.');
header('Location: ../index.php');
exit;
