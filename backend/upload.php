<?php
/**
 * upload.php — TinyMCE image upload endpoint
 *
 * Accepts image uploads from the TinyMCE WYSIWYG editor (drag & drop, paste,
 * or file picker) and saves them as files in the uploads directory
 * (frontend/data/uploads). Returns a TinyMCE-compatible JSON response:
 *
 *   { "location": "/data/uploads/<filename>" }
 *
 * Only authenticated admin users may upload.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'Method not allowed.']);
    exit;
}

// Define the uploads directory (frontend/data/uploads).
$uploadsDir = defined('UPLOADS_DIR') ? UPLOADS_DIR : DATA_DIR . '/uploads';

// Create the directory if it does not exist.
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0777, true) && !is_dir($uploadsDir)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['location' => null, 'error' => 'Could not create uploads directory.']);
        exit;
    }
}

// TinyMCE sends the file field named "file".
$file = $_FILES['file'] ?? null;

if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'No file uploaded.']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'Upload failed with error code ' . $file['error'] . '.']);
    exit;
}

// Enforce a maximum file size (keep in sync with nginx client_max_body_size).
define('MAX_UPLOAD_BYTES', 25 * 1024 * 1024); // 25 MB
if ($file['size'] > MAX_UPLOAD_BYTES) {
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'File too large. Maximum allowed size is 25 MB.']);
    exit;
}

// Validate the file is actually an image.
$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];

$realMime = mime_content_type($file['tmp_name']);
$ext = $allowedMime[$realMime] ?? null;

if ($ext === null) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'Only image files (JPG, PNG, GIF, WEBP, SVG) are allowed.']);
    exit;
}

// Generate a unique filename (keep original extension).
$base = pathinfo($file['name'], PATHINFO_FILENAME);
$base = preg_replace('/[^a-zA-Z0-9_-]/', '-', $base);
$base = trim($base, '-') ?: 'image';

$filename = $base . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $uploadsDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['location' => null, 'error' => 'Failed to move uploaded file.']);
    exit;
}

// Optionally set permissive permissions so the static host can serve it.
@chmod($destPath, 0644);

// Return the public URL for the uploaded image.
// The frontend nginx serves /data/ -> frontend/data/, so the URL is /data/uploads/<filename>.
header('Content-Type: application/json');
echo json_encode(['location' => '/data/uploads/' . $filename]);
exit;
