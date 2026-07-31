<?php
/**
 * delete.php — Delete a post
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$id = $_GET['id'] ?? '';
$post = getPostById($id);

if (!$post) {
    $notFound = true;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    if (deletePost($id)) {
        redirect('./index.php?success=deleted');
    } else {
        $error = 'Failed to delete post. Please check file permissions.';
    }
}

// Reload index data for display
$posts = getPosts();
$indexData = null;
foreach ($posts as $p) {
    if ($p['id'] === $id) {
        $indexData = $p;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Post — Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-wrap">
        <!-- Header -->
        <header class="admin-header">
            <a href="index.php" class="admin-brand">📝 Blog Admin</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="create.php">+ New Post</a>
                <a href="../frontend/public/blog.html" target="_blank">View Blog</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </nav>
        </header>

        <?php if (isset($notFound) && $notFound): ?>
            <div class="alert alert-danger">Post not found. <a href="index.php" style="color:var(--cyan);">Back to dashboard.</a></div>
        <?php else: ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Delete Confirmation -->
        <div class="delete-card">
            <div class="delete-icon">⚠️</div>
            <h2>Delete Post</h2>
            <p>Are you sure you want to delete "<strong><?= htmlspecialchars($indexData['title'] ?? $post['title'] ?? '') ?></strong>"?<br>
            This action cannot be undone.</p>

            <div style="margin-bottom:1.5rem;padding:.8rem;background:rgba(255,71,87,.06);border:1px solid rgba(255,71,87,.2);border-radius:.7rem;font-size:.85rem;color:var(--muted);display:inline-block;">
                🗂️ Post ID: <code style="color:var(--cyan);"><?= htmlspecialchars($id) ?></code>
            </div>

            <form method="POST" action="" onsubmit="return confirmDelete(this)">
                <input type="hidden" name="confirm" value="yes">
                <div class="delete-actions">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="index.php" class="btn">Cancel</a>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>

