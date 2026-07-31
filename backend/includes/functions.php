<?php
/**
 * functions.php — CRUD operations for posts JSON data
 */

require_once __DIR__ . '/config.php';

/**
 * Get all posts from the index file.
 */
function getPosts(): array {
    if (!file_exists(POSTS_INDEX)) {
        return [];
    }
    $json = file_get_contents(POSTS_INDEX);
    $data = json_decode($json, true);
    return $data['posts'] ?? [];
}

/**
 * Get a single post by its ID.
 */
function getPostById(string $id): ?array {
    $posts = getPosts();
    foreach ($posts as $post) {
        if ($post['id'] === $id) {
            // Load full detail
            $detailPath = BASE_PATH . '/frontend/' . $post['detail'];
            if (file_exists($detailPath)) {
                $detailJson = file_get_contents($detailPath);
                $detail = json_decode($detailJson, true);
                return $detail;
            }
            return $post;
        }
    }
    return null;
}

/**
 * Save the posts index (list of all posts).
 */
function savePostsIndex(array $posts): bool {
    $data = ['posts' => array_values($posts)];
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    return file_put_contents(POSTS_INDEX, $json) !== false;
}

/**
 * Save a single post detail to its file.
 */
function savePostDetail(array $post): bool {
    $id = $post['id'] ?? '';
    if (empty($id)) return false;

    if (!is_dir(POSTS_DIR)) {
        mkdir(POSTS_DIR, 0777, true);
    }

    $path = POSTS_DIR . '/post-' . $id . '.json';
    $json = json_encode($post, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json) !== false;
}

/**
 * Generate a URL-friendly slug from a title.
 */
function createSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Generate a unique ID (slug + random suffix if needed).
 */
function generateUniqueId(string $title): string {
    $base = createSlug($title);
    $posts = getPosts();
    $existingIds = array_column($posts, 'id');
    
    $id = $base;
    $counter = 1;
    while (in_array($id, $existingIds)) {
        $id = $base . '-' . $counter;
        $counter++;
    }
    return $id;
}

/**
 * Create a new post.
 */
function createPost(array $data): bool {
    $id = $data['id'] ?? generateUniqueId($data['title'] ?? 'untitled');
    
    // Build index entry
    $indexEntry = [
        'id' => $id,
        'title' => $data['title'] ?? 'Untitled',
        'description' => $data['description'] ?? '',
        'image' => $data['image'] ?? 'https://picsum.photos/seed/' . $id . '/600/400',
        'tags' => $data['tags'] ?? [],
        'detail' => 'data/posts/post-' . $id . '.json'
    ];
    
    // Build detail entry
    $detailEntry = [
        'id' => $id,
        'title' => $data['title'] ?? 'Untitled',
        'author' => $data['author'] ?? 'Admin',
        'published' => $data['published'] ?? date('Y-m-d'),
        'updated' => date('Y-m-d'),
        'image' => $data['image'] ?? 'https://picsum.photos/seed/' . $id . '/1200/600',
        'lead' => $data['lead'] ?? '',
        'sections' => $data['sections'] ?? [
            [
                'heading' => 'Introduction',
                'paragraphs' => ['Content coming soon...']
            ]
        ],
        'tags' => $data['tags'] ?? []
    ];
    
    // Add to index
    $posts = getPosts();
    $posts[] = $indexEntry;
    
    if (!savePostsIndex($posts)) return false;
    if (!savePostDetail($detailEntry)) return false;
    
    return true;
}

/**
 * Update an existing post.
 */
function updatePost(string $id, array $data): bool {
    $posts = getPosts();
    $found = false;
    
    // Update index
    foreach ($posts as &$post) {
        if ($post['id'] === $id) {
            $post['title'] = $data['title'] ?? $post['title'];
            $post['description'] = $data['description'] ?? $post['description'];
            $post['image'] = $data['image'] ?? $post['image'];
            $post['tags'] = $data['tags'] ?? $post['tags'];
            $found = true;
            break;
        }
    }
    unset($post);
    
    if (!$found) return false;
    if (!savePostsIndex($posts)) return false;
    
    // Update detail
    $detail = getPostById($id);
    if ($detail) {
        $detail['title'] = $data['title'] ?? $detail['title'];
        $detail['author'] = $data['author'] ?? $detail['author'];
        $detail['image'] = $data['image'] ?? $detail['image'];
        $detail['lead'] = $data['lead'] ?? $detail['lead'];
        $detail['sections'] = $data['sections'] ?? $detail['sections'];
        $detail['tags'] = $data['tags'] ?? $detail['tags'];
        $detail['updated'] = date('Y-m-d');
        
        return savePostDetail($detail);
    }
    
    return true;
}

/**
 * Delete a post by ID.
 */
function deletePost(string $id): bool {
    $posts = getPosts();
    $detailPath = null;
    
    foreach ($posts as $key => $post) {
        if ($post['id'] === $id) {
            $detailPath = BASE_PATH . '/frontend/' . $post['detail'];
            unset($posts[$key]);
            break;
        }
    }
    
    if (!savePostsIndex($posts)) return false;
    
    // Delete detail file
    if ($detailPath && file_exists($detailPath)) {
        unlink($detailPath);
    }
    
    return true;
}

/**
 * Redirect helper.
 */
function redirect(string $url): void {
    if ($url === '' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
        $target = $url;
    } else {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestDir = dirname($requestUri);
        if ($requestDir === '.' || $requestDir === '/') {
            $target = '/' . ltrim($url, '/');
        } else {
            $target = rtrim($requestDir, '/') . '/' . ltrim($url, '/');
        }
    }

    header('Location: ' . $target);
    exit;
}

/**
 * Get a human-readable date.
 */
function formatDate(string $dateStr): string {
    $date = new DateTime($dateStr);
    return $date->format('F j, Y');
}

