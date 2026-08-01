<?php
/**
 * index.php — Admin dashboard: list all posts
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$posts = getPosts();
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-wrap">
        <!-- Header -->
        <header class="admin-header">
            <a href="./index.php" class="admin-brand">📝 Blog Admin</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="create.php">+ New Post</a>
                <a href="../frontend/public/blog.html" target="_blank">View Blog</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </nav>
        </header>

        <!-- Page Title -->
        <h1 class="page-title">Posts</h1>
        <p class="page-subtitle">Manage your blog posts — <?= count($posts) ?> total</p>

        <!-- Success message -->
        <?php if ($success === 'created'): ?>
            <div class="alert alert-success">✅ Post created successfully!</div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success">✅ Post updated successfully!</div>
        <?php elseif ($success === 'deleted'): ?>
            <div class="alert alert-success">✅ Post deleted successfully!</div>
        <?php endif; ?>

        <!-- Posts Table -->
        <?php if (count($posts) > 0): ?>
            <table class="posts-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Tags</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): 
                        $date = $post['published'] ?? '';
                    ?>
                        <tr>
                            <td class="post-title-cell">
                                <?= htmlspecialchars($post['title']) ?>
                            </td>
                            <td>
                                <?php if (!empty($post['tags'])): ?>
                                    <?php foreach ($post['tags'] as $tag): ?>
                                        <span style="display:inline-block;font-size:.78rem;padding:.15rem .5rem;border-radius:999px;border:1px solid rgba(0,225,255,.32);background:rgba(0,225,255,.08);margin:2px;"><?= htmlspecialchars($tag) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:var(--muted2);font-size:.82rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="post-date-cell"><?= $date ? formatDate($date) : '—' ?></td>
                            <td class="post-actions">
                                <a href="../frontend/public/post.html?id=<?= urlencode($post['id']) ?>" target="_blank" style="color:var(--cyan);">View</a>
                                <a href="edit.php?id=<?= urlencode($post['id']) ?>" class="action-edit">Edit</a>
                                <a href="delete.php?id=<?= urlencode($post['id']) ?>" class="action-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No posts yet</h3>
                <p>Create your first blog post to get started.</p>
                <a href="./create.php" class="btn btn-primary">+ Create Post</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

