<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const MINI_BLOG_ROOT = __DIR__ . '/..';
const MINI_BLOG_DB = MINI_BLOG_ROOT . '/db.json';
const MINI_BLOG_UPLOADS = MINI_BLOG_ROOT . '/uploads';
const MINI_BLOG_MAX_IMAGE_SIZE = 5 * 1024 * 1024;

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function readPosts(): array
{
    if (!is_file(MINI_BLOG_DB)) {
        return [];
    }

    $contents = file_get_contents(MINI_BLOG_DB);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $posts = json_decode($contents, true);
    return is_array($posts) ? array_values(array_filter($posts, 'is_array')) : [];
}

/**
 * Lock the JSON file, pass its current posts to the callback, then save its result.
 */
function updatePostsFile(callable $callback): bool
{
    $handle = fopen(MINI_BLOG_DB, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return false;
    }

    rewind($handle);
    $contents = stream_get_contents($handle);
    $decoded = $contents !== false && trim($contents) !== '' ? json_decode($contents, true) : [];
    $posts = is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    $updatedPosts = $callback($posts);

    if (!is_array($updatedPosts)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $json = json_encode(array_values($updatedPosts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $success = $json !== false && ftruncate($handle, 0);
    if ($success) {
        rewind($handle);
        $success = fwrite($handle, $json . PHP_EOL) !== false;
        fflush($handle);
    }

    flock($handle, LOCK_UN);
    fclose($handle);
    return $success;
}

function findPostById(int $id): ?array
{
    foreach (readPosts() as $post) {
        if (isset($post['id']) && (int) $post['id'] === $id) {
            return $post;
        }
    }
    return null;
}

function requestId(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }
    $value = (string) $value;
    if ($value === '' || !ctype_digit($value)) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function validCsrfToken(mixed $token): bool
{
    return is_string($token) && hash_equals(csrfToken(), $token);
}

function setFormFeedback(array $errors, array $old = []): void
{
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $old;
}

function pullFormFeedback(): array
{
    $errors = isset($_SESSION['form_errors']) && is_array($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
    $old = isset($_SESSION['old_input']) && is_array($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
    unset($_SESSION['form_errors'], $_SESSION['old_input']);
    return [$errors, $old];
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    $flash = isset($_SESSION['flash']) && is_array($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);
    return $flash;
}

function validatePostText(mixed $titleValue, mixed $contentValue): array
{
    $title = is_string($titleValue) ? trim($titleValue) : '';
    $content = is_string($contentValue) ? trim($contentValue) : '';
    $titleLength = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
    $contentLength = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
    $errors = [];

    if ($title === '') {
        $errors[] = 'The title is required.';
    } elseif ($titleLength > 150) {
        $errors[] = 'The title may not exceed 150 characters.';
    }
    if ($content === '') {
        $errors[] = 'The content is required.';
    } elseif ($contentLength > 10000) {
        $errors[] = 'The content may not exceed 10,000 characters.';
    }

    return [$title, $content, $errors];
}

/**
 * Validate and move an uploaded image. Returns [relative URL, error].
 */
function saveUploadedImage(mixed $file, bool $required = true): array
{
    if (!is_array($file) || !isset($file['error'])) {
        return [null, $required ? 'Please choose an image.' : null];
    }

    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, $required ? 'Please choose an image.' : null];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [null, 'The image upload failed. Please try again.'];
    }

    $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [null, 'The uploaded file is not valid.'];
    }
    if ($size < 1 || $size > MINI_BLOG_MAX_IMAGE_SIZE) {
        return [null, 'The image must be no larger than 5 MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!is_string($mime) || !isset($extensions[$mime]) || @getimagesize($tmpName) === false) {
        return [null, 'Only valid JPG, JPEG, PNG, GIF, or WEBP images are allowed.'];
    }

    if (!is_dir(MINI_BLOG_UPLOADS) && !mkdir(MINI_BLOG_UPLOADS, 0755, true)) {
        return [null, 'The uploads folder could not be created.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmpName, MINI_BLOG_UPLOADS . DIRECTORY_SEPARATOR . $filename)) {
        return [null, 'The image could not be saved.'];
    }

    return ['uploads/' . $filename, null];
}

function deleteStoredImage(mixed $imageUrl): void
{
    if (!is_string($imageUrl) || !preg_match('~^uploads/([a-f0-9]{32}\.(?:jpg|png|gif|webp))$~', $imageUrl, $matches)) {
        return;
    }
    $path = MINI_BLOG_UPLOADS . DIRECTORY_SEPARATOR . $matches[1];
    if (is_file($path)) {
        unlink($path);
    }
}

function newPostId(array $posts): int
{
    $used = [];
    foreach ($posts as $post) {
        if (isset($post['id'])) {
            $used[(string) $post['id']] = true;
        }
    }

    do {
        $id = (int) floor(microtime(true) * 1000000) + random_int(0, 999);
    } while (isset($used[(string) $id]));

    return $id;
}

if (!defined('MINI_BLOG_NO_LAYOUT')):
    $pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Mini Blog';
    $basePath = isset($basePath) ? (string) $basePath : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .post-card { height: 100%; border: 0; box-shadow: 0 .25rem 1rem rgba(0, 0, 0, .08); }
        .post-card img { height: 220px; object-fit: cover; }
        .detail-image { width: 100%; max-height: 520px; object-fit: contain; background: #eef1f4; }
        .edit-thumbnail { width: 180px; height: 120px; object-fit: cover; }
    </style>
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container py-5">
<?php endif; ?>
