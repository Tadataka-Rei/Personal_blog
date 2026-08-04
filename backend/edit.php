<?php
/**
 * edit.php — Edit an existing post with WYSIWYG editor
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$id = $_GET['id'] ?? '';
$post = getPostById($id);

if (!$post) {
    // Post not found
    $notFound = true;
} else {
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process sections from the form
        $sections = [];
        if (isset($_POST['sections']) && is_array($_POST['sections'])) {
            foreach ($_POST['sections'] as $section) {
                $heading = trim($section['heading'] ?? '');
                $paragraphsRaw = $section['paragraphs'] ?? '';
                
                // If content has HTML tags, treat as single HTML block
                if (strpos($paragraphsRaw, '<') !== false) {
                    $paragraphs = [$paragraphsRaw];
                } else {
                    $paragraphs = array_filter(explode("\n", $paragraphsRaw), function($p) {
                        return trim($p) !== '';
                    });
                    $paragraphs = array_map('trim', $paragraphs);
                    $paragraphs = array_filter($paragraphs, function($p) {
                        return $p !== '';
                    });
                }
                
                if (!empty($heading) && !empty($paragraphs)) {
                    $sections[] = [
                        'heading' => $heading,
                        'paragraphs' => array_values($paragraphs)
                    ];
                }
            }
        }
        
        // Process tags
        $tagsInput = $_POST['tags'] ?? '';
        $tags = array_filter(array_map('trim', explode(',', $tagsInput)), function($t) {
            return $t !== '';
        });
        
        // Build update data
        $updateData = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'lead' => $_POST['lead'] ?? '',
            'image' => $_POST['image'] ?? '',
            'author' => $_POST['author'] ?? 'Admin',
            'tags' => array_values($tags),
            'sections' => $sections
        ];
        
        // Validate
        if (empty($updateData['title'])) {
            $error = 'Title is required.';
        } else {
            if (updatePost($id, $updateData)) {
                redirect('index.php?success=updated');
            } else {
                $error = 'Failed to update post. Please check file permissions.';
            }
        }
    }
    
    // Reload post data after potential update
    $post = getPostById($id);
    
    // Prepare data for form
    $currentTags = isset($post['tags']) && is_array($post['tags']) ? $post['tags'] : [];
    // Get index-level data for description and tags
    $posts = getPosts();
    $indexData = null;
    foreach ($posts as $p) {
        if ($p['id'] === $id) {
            $indexData = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post — Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- TinyMCE WYSIWYG Editor -->
    <script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?: 'no-api-key' ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: '.wysiwyg-editor',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | removeformat',
        menubar: false,
        branding: false,
        promotion: false,
        height: 400,
        content_style: `
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                font-size: 15px; 
                line-height: 1.7; 
                color: #e0e0e0; 
                background: #0d0f1f; 
                padding: 15px; 
            }
            img { max-width: 100%; height: auto; }
            a { color: #00e1ff; }
            code { background: rgba(0,225,255,.1); padding: 2px 6px; border-radius: 3px; }
            blockquote { border-left: 3px solid #00e1ff; margin: 1em 0; padding: .5em 1em; background: rgba(0,225,255,.05); }
        `,
valid_elements: '*[*]',
        extended_valid_elements: 'img[class|src|alt|title|width|height|loading|style],a[class|href|target|rel|title|name],code[class],span[class|style],div[class|style],iframe[src|title|width|height|allowfullscreen|style]',
        valid_children: '+body[style],+div[style]',
        // ── Image upload: drag & drop / paste images → saved as files in frontend/data/uploads ──
        automatic_uploads: true,
        paste_data_images: true,
        images_upload_url: 'upload.php',
        images_upload_handler: undefined,
        images_reuse_filename: true,
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });
    </script>
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

        <!-- Page Title -->
        <h1 class="page-title">Edit: <?= htmlspecialchars($post['title'] ?? '') ?></h1>
        <p class="page-subtitle">Update the post content. HTML tags like <code><img></code>, <code><a></code>, <code><code></code> are fully supported.</p>

        <!-- Error message -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" class="form-card">
            <!-- Title -->
            <div class="form-group">
                <label for="title">Post Title *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
            </div>

            <!-- Author & Image Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" class="form-control" 
                           value="<?= htmlspecialchars($post['author'] ?? 'Admin') ?>">
                </div>
                <div class="form-group">
                    <label for="image">Featured Image URL</label>
                    <input type="url" id="image" name="image" class="form-control" 
                           value="<?= htmlspecialchars($post['image'] ?? '') ?>"
                           placeholder="https://picsum.photos/seed/example/1200/600">
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Short Description (shown on blog listing)</label>
                <textarea id="description" name="description" class="form-control" rows="2"><?= htmlspecialchars($indexData['description'] ?? $post['description'] ?? '') ?></textarea>
            </div>

            <!-- Lead Paragraph -->
            <div class="form-group">
                <label for="lead">Lead Paragraph</label>
                <textarea id="lead" name="lead" class="form-control" rows="3"><?= htmlspecialchars($post['lead'] ?? '') ?></textarea>
            </div>

            <!-- Tags -->
            <div class="form-group">
                <label>Tags</label>
                <div class="tags-input-wrapper">
                    <input type="hidden" name="tags" class="tags-hidden" value="<?= htmlspecialchars(implode(',', $currentTags)) ?>">
                    <input type="text" class="tag-input" placeholder="Type a tag and press Enter...">
                </div>
                <div class="hint">Press Enter or comma to add a tag</div>
            </div>

            <!-- Sections (Content) -->
            <div class="form-group">
                <label>Content Sections</label>
                <div class="hint" style="margin-bottom:1rem;">Each section has a heading and rich content. Use the WYSIWYG editor to add images, links, code, and more.</div>

                <div id="sections-container">
                    <?php if (!empty($post['sections'])): ?>
                        <?php foreach ($post['sections'] as $index => $section): ?>
                            <div class="section-editor">
                                <div class="section-header">
                                    <h4>Section <?= $index + 1 ?></h4>
                                    <?php if ($index > 0): ?>
                                        <button type="button" class="section-remove" onclick="removeSection(this)" title="Remove section">&times;</button>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Heading</label>
                                    <input type="text" class="form-control" name="sections[<?= $index ?>][heading]" 
                                           value="<?= htmlspecialchars($section['heading'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Content (HTML allowed)</label>
                                    <textarea class="form-control wysiwyg-editor" name="sections[<?= $index ?>][paragraphs]" rows="6"><?php
                                        // Join paragraphs back (they may be HTML strings)
                                        $paragraphs = $section['paragraphs'] ?? [];
                                        if (is_array($paragraphs)) {
                                            echo htmlspecialchars(implode("\n\n", $paragraphs));
                                        }
                                    ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="section-editor">
                            <div class="section-header">
                                <h4>Section 1</h4>
                            </div>
                            <div class="form-group">
                                <label>Heading</label>
                                <input type="text" class="form-control" name="sections[0][heading]" placeholder="Section heading" required>
                            </div>
                            <div class="form-group">
                                <label>Content (HTML allowed)</label>
                                <textarea class="form-control wysiwyg-editor" name="sections[0][paragraphs]" rows="6"></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-sm" onclick="addSection()" style="margin-top:.5rem;">+ Add Section</button>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Post</button>
                <a href="index.php" class="btn">Cancel</a>
            </div>
        </form>

        <?php endif; ?>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>

