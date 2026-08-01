<?php
/**
 * functions.php — CRUD operations for monthly JSON post data
 *
 * Storage layout (frontend/data):
 *   posts-MM-YYYY.json        → list of post summaries for that month (newest first)
 *   posts-data-MM-YYYY.json   → list of full post data for that month
 *   post-count.js             → generated JS catalog (window.POST_COUNT) for fast frontend pagination
 */

require_once __DIR__ . '/config.php';

/* ────────────────────────────────────────────────────────────────────────────
 * Path & month helpers
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Build the month key for a date string, e.g. "2026-07-28" → "07-2026".
 */
function getMonthKey(string $date = null): string {
    $date = $date ?: date('Y-m-d');
    $ts = strtotime($date);
    return date('m-Y', $ts);
}

/**
 * Full path to the month summary file (posts-MM-YYYY.json).
 */
function getSummaryFilePath(string $monthKey): string {
    return DATA_DIR . '/posts-' . $monthKey . '.json';
}

/**
 * Full path to the month data file (posts-data-MM-YYYY.json).
 */
function getDataFilePath(string $monthKey): string {
    return DATA_DIR . '/posts-data-' . $monthKey . '.json';
}

/* ────────────────────────────────────────────────────────────────────────────
 * Read / write month files
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Get summaries for a single month (newest first as stored).
 */
function getMonthPosts(string $monthKey): array {
    $path = getSummaryFilePath($monthKey);
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? array_values($data) : [];
}

/**
 * Get full post data for a single month.
 */
function getMonthData(string $monthKey): array {
    $path = getDataFilePath($monthKey);
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? array_values($data) : [];
}

/**
 * Save a month's summary list.
 */
function saveMonthPosts(string $monthKey, array $posts): bool {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    $json = json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(getSummaryFilePath($monthKey), $json) !== false;
}

/**
 * Save a month's full data list.
 */
function saveMonthData(string $monthKey, array $posts): bool {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    $json = json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(getDataFilePath($monthKey), $json) !== false;
}

/**
 * Discover all existing month keys, newest month first.
 */
function getAllMonthKeys(): array {
    $files = glob(DATA_DIR . '/posts-*.json');
    $keys = [];
    foreach ($files as $file) {
        $base = basename($file);
        // matches posts-MM-YYYY.json
        if (preg_match('/^posts-(\d{2}-\d{4})\.json$/', $base, $m)) {
            $keys[] = $m[1];
        }
    }
    // Sort descending so the newest month comes first.
    usort($keys, function ($a, $b) {
        $ta = strtotime(str_replace('-', '-', '01-' . $a));
        $tb = strtotime(str_replace('-', '-', '01-' . $b));
        return $tb - $ta;
    });
    return $keys;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Aggregated read helpers (across all months, newest first)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Get ALL post summaries across every month (newest first).
 */
function getPosts(): array {
    $all = [];
    foreach (getAllMonthKeys() as $key) {
        foreach (getMonthPosts($key) as $post) {
            $all[] = $post;
        }
    }
    return $all;
}

/**
 * Get a single post summary by ID (from the summary index).
 */
function getPostSummary(string $id): ?array {
    foreach (getAllMonthKeys() as $key) {
        foreach (getMonthPosts($key) as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }
    }
    return null;
}

/**
 * Get a single post by its ID, including full detail.
 * Returns the full post data (detail file content).
 */
function getPostById(string $id): ?array {
    foreach (getAllMonthKeys() as $key) {
        $posts = getMonthPosts($key);
        foreach ($posts as $idx => $post) {
            if ($post['id'] === $id) {
                $data = getMonthData($key);
                if (isset($data[$idx]) && is_array($data[$idx])) {
                    return $data[$idx];
                }
                return $post;
            }
        }
    }
    return null;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Write helpers (create / update / delete)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Create a new post. The summary is stored under posts-MM-YYYY.json and the
 * full post under posts-data-MM-YYYY.json, based on the published date.
 */
function createPost(array $data): bool {
    $id = $data['id'] ?? generateUniqueId($data['title'] ?? 'untitled');
    $published = $data['published'] ?? date('Y-m-d');
    $monthKey = getMonthKey($published);

    // Build summary entry
    $summary = [
        'id' => $id,
        'title' => $data['title'] ?? 'Untitled',
        'description' => $data['description'] ?? '',
        'image' => $data['image'] ?? 'https://picsum.photos/seed/' . $id . '/600/400',
        'tags' => $data['tags'] ?? [],
        'published' => $published,
        'month' => $monthKey
    ];

    // Build full post entry
    $detail = [
        'id' => $id,
        'title' => $data['title'] ?? 'Untitled',
        'author' => $data['author'] ?? 'Admin',
        'published' => $published,
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

    // Prepend to the month's summary + data files (newest first)
    $summaries = getMonthPosts($monthKey);
    $details   = getMonthData($monthKey);
    array_unshift($summaries, $summary);
    array_unshift($details, $detail);

    if (!saveMonthPosts($monthKey, $summaries)) return false;
    if (!saveMonthData($monthKey, $details)) return false;

    regeneratePostCount();
    return true;
}

/**
 * Update an existing post (summary + full data).
 */
function updatePost(string $id, array $data): bool {
    $oldMonthKey = null;
    $oldIdx = null;
    $oldSummary = null;

    // Locate the post across all months.
    foreach (getAllMonthKeys() as $key) {
        $summaries = getMonthPosts($key);
        foreach ($summaries as $idx => $s) {
            if ($s['id'] === $id) {
                $oldMonthKey = $key;
                $oldIdx = $idx;
                $oldSummary = $s;
                break 2;
            }
        }
    }

    if ($oldMonthKey === null || $oldIdx === null) return false;

    $summaries = getMonthPosts($oldMonthKey);
    $details   = getMonthData($oldMonthKey);
    $detail    = $details[$oldIdx] ?? $oldSummary;

    // New published date (defaults to old).
    $newPublished = $data['published'] ?? ($oldSummary['published'] ?? date('Y-m-d'));
    $newMonthKey  = getMonthKey($newPublished);

    // Update summary fields.
    $newSummary = $summaries[$oldIdx];
    $newSummary['title']       = $data['title'] ?? $oldSummary['title'];
    $newSummary['description'] = $data['description'] ?? $oldSummary['description'];
    $newSummary['image']       = $data['image'] ?? $oldSummary['image'];
    $newSummary['tags']        = $data['tags'] ?? $oldSummary['tags'];
    $newSummary['published']   = $newPublished;
    $newSummary['month']       = $newMonthKey;

    // Update detail fields.
    $detail['title']     = $data['title'] ?? $detail['title'] ?? $oldSummary['title'];
    $detail['author']    = $data['author'] ?? $detail['author'] ?? 'Admin';
    $detail['image']     = $data['image'] ?? $detail['image'] ?? $oldSummary['image'];
    $detail['lead']      = $data['lead'] ?? $detail['lead'] ?? '';
    $detail['sections']  = $data['sections'] ?? $detail['sections'] ?? [];
    $detail['tags']      = $data['tags'] ?? $detail['tags'] ?? [];
    $detail['published'] = $newPublished;
    $detail['updated']   = date('Y-m-d');

    // If the month didn't change, update in place.
    if ($newMonthKey === $oldMonthKey) {
        $summaries[$oldIdx] = $newSummary;
        $details[$oldIdx]   = $detail;
        if (!saveMonthPosts($oldMonthKey, $summaries)) return false;
        if (!saveMonthData($oldMonthKey, $details)) return false;
    } else {
        // Remove from old month, prepend to new month.
        array_splice($summaries, $oldIdx, 1);
        array_splice($details, $oldIdx, 1);
        if (!saveMonthPosts($oldMonthKey, $summaries)) return false;
        if (!saveMonthData($oldMonthKey, $details)) return false;

        $newSummaries = getMonthPosts($newMonthKey);
        $newDetails   = getMonthData($newMonthKey);
        array_unshift($newSummaries, $newSummary);
        array_unshift($newDetails, $detail);
        if (!saveMonthPosts($newMonthKey, $newSummaries)) return false;
        if (!saveMonthData($newMonthKey, $newDetails)) return false;
    }

    regeneratePostCount();
    return true;
}

/**
 * Delete a post by ID (removes from both month summary + data files).
 */
function deletePost(string $id): bool {
    $found = false;
    foreach (getAllMonthKeys() as $key) {
        $summaries = getMonthPosts($key);
        $details   = getMonthData($key);
        $removed = false;

        foreach ($summaries as $idx => $s) {
            if ($s['id'] === $id) {
                array_splice($summaries, $idx, 1);
                if (isset($details[$idx])) {
                    array_splice($details, $idx, 1);
                }
                $removed = true;
                break;
            }
        }

        if ($removed) {
            // If the month files became empty, delete them entirely.
            if (empty($summaries)) {
                @unlink(getSummaryFilePath($key));
                @unlink(getDataFilePath($key));
            } else {
                if (!saveMonthPosts($key, $summaries)) return false;
                if (!saveMonthData($key, $details)) return false;
            }
            $found = true;
            break;
        }
    }

    if (!$found) return false;
    regeneratePostCount();
    return true;
}

/* ────────────────────────────────────────────────────────────────────────────
 * post-count.js catalog generator
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Generate the frontend catalog file post-count.js:
 *
 *   window.POST_COUNT = {
 *     "total": 12,
 *     "files": [
 *       { "key": "07-2026", "summary": "posts-07-2026.json", "data": "posts-data-07-2026.json", "count": 5 },
 *       ...
 *     ],
 *     "byId": {
 *       "my-post": { "key": "07-2026", "index": 2 },
 *       ...
 *     }
 *   };
 */
function regeneratePostCount(): bool {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    $files = [];
    $byId  = [];
    $total = 0;

    foreach (getAllMonthKeys() as $key) {
        $summaries = getMonthPosts($key);
        $count = count($summaries);
        if ($count <= 0) continue;

        $files[] = [
            'key' => $key,
            'summary' => 'posts-' . $key . '.json',
            'data' => 'posts-data-' . $key . '.json',
            'count' => $count,
        ];

        foreach ($summaries as $idx => $s) {
            $byId[$s['id']] = ['key' => $key, 'index' => $idx];
        }
        $total += $count;
    }

    $payload = json_encode(
        ['total' => $total, 'files' => $files, 'byId' => $byId],
        JSON_UNESCAPED_SLASHES
    );

    $js = 'window.POST_COUNT = ' . $payload . ';';
    return file_put_contents(POST_COUNT_JS, $js) !== false;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Legacy migration (best-effort)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * If the old single-file layout exists (posts.json + posts/post-*.json) and the
 * new layout has not been initialized yet, migrate the data into monthly files
 * and generate post-count.js. Safe to call on every request.
 */
function migrateLegacyData(): void {
    // Only run if the old index exists and the new catalog doesn't.
    if (!file_exists(POSTS_INDEX) || file_exists(POST_COUNT_JS)) return;

    $json = file_get_contents(POSTS_INDEX);
    $data = json_decode($json, true);
    $oldPosts = $data['posts'] ?? [];
    if (empty($oldPosts)) return;

    foreach ($oldPosts as $entry) {
        $id = $entry['id'] ?? '';
        if ($id === '') continue;

        // Load full detail if available.
        $detailPath = isset($entry['detail']) ? BASE_PATH . '/frontend/' . $entry['detail'] : null;
        $detail = null;
        if ($detailPath && file_exists($detailPath)) {
            $detail = json_decode(file_get_contents($detailPath), true);
        }

        $published = $detail['published'] ?? $entry['published'] ?? date('Y-m-d');
        $monthKey = getMonthKey($published);

        $summary = [
            'id' => $id,
            'title' => $entry['title'] ?? 'Untitled',
            'description' => $entry['description'] ?? '',
            'image' => $entry['image'] ?? 'https://picsum.photos/seed/' . $id . '/600/400',
            'tags' => $entry['tags'] ?? [],
            'published' => $published,
            'month' => $monthKey,
        ];

        $full = $detail ?: [
            'id' => $id,
            'title' => $summary['title'],
            'author' => 'Admin',
            'published' => $published,
            'updated' => date('Y-m-d'),
            'image' => $summary['image'],
            'lead' => '',
            'sections' => [['heading' => 'Introduction', 'paragraphs' => ['Content coming soon...']]],
            'tags' => $summary['tags'],
        ];
        $full['id'] = $id;
        $full['title'] = $summary['title'];
        $full['image'] = $summary['image'];
        $full['tags'] = $summary['tags'];
        $full['published'] = $published;

        $summaries = getMonthPosts($monthKey);
        $details   = getMonthData($monthKey);
        array_unshift($summaries, $summary);
        array_unshift($details, $full);

        saveMonthPosts($monthKey, $summaries);
        saveMonthData($monthKey, $details);
    }

    regeneratePostCount();
}

/* ────────────────────────────────────────────────────────────────────────────
 * Generic helpers
 * ────────────────────────────────────────────────────────────────────────── */

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

// Auto-migrate legacy single-file layout (posts.json + posts/post-*.json) if
// present and the new post-count.js catalog has not been generated yet.
migrateLegacyData();

