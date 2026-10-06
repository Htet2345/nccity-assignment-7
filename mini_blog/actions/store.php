<?php
declare(strict_types=1);
define('MINI_BLOG_NO_LAYOUT', true);
require __DIR__ . '/../layouts/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

[$title, $content, $errors] = validatePostText($_POST['title'] ?? null, $_POST['content'] ?? null);
if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
    $errors[] = 'Your session expired. Please submit the form again.';
}

$imageUrl = null;
if ($errors === []) {
    [$imageUrl, $imageError] = saveUploadedImage($_FILES['image'] ?? null);
    if ($imageError !== null) {
        $errors[] = $imageError;
    }
}

if ($errors !== [] || $imageUrl === null) {
    setFormFeedback($errors, ['title' => $title, 'content' => $content]);
    header('Location: ../create.php');
    exit;
}

$saved = updatePostsFile(function (array $posts) use ($title, $content, $imageUrl): array {
    $posts[] = [
        'id' => newPostId($posts),
        'title' => $title,
        'content' => $content,
        'image_url' => $imageUrl,
    ];
    return $posts;
});

if (!$saved) {
    deleteStoredImage($imageUrl);
    setFormFeedback(['The post could not be saved. Please try again.'], ['title' => $title, 'content' => $content]);
    header('Location: ../create.php');
    exit;
}

setFlash('success', 'Post created successfully.');
header('Location: ../index.php');
exit;
