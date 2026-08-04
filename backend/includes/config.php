<?php
/**
 * config.php — Application configuration
 *
 * Supports environment variables via .env file or Docker environment.
 * See .env.example at the project root for all available variables.
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── Load .env file ──
(function () {
    $envPaths = [
        dirname(__DIR__, 2) . '/.env',         // project root
        dirname(__DIR__, 2) . '/backend/.env',  // backend folder I don't have it but just in case
    ];

    foreach ($envPaths as $envPath) {
        if (!file_exists($envPath)) {
            continue;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            // Parse KEY=VALUE (allows quoted values)
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);
                // Strip surrounding quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                    || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                // Only set if not already defined by the actual environment
                if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
        break; // stop after first found .env
    }
})();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Environment-based configuration ─────────────────────────────────────────

// Base paths
$basePath = dirname(__DIR__, 2);
define('BASE_PATH', $basePath);

$defaultDataDir = $basePath . '/frontend/data';
$dataDir = getenv('DATA_DIR') ?: $defaultDataDir;
define('DATA_DIR', $dataDir);
define('POSTS_INDEX', DATA_DIR . '/posts.json');
define('POSTS_DIR', DATA_DIR . '/posts');
define('POST_COUNT_JS', DATA_DIR . '/post-count.js');
define('UPLOADS_DIR', DATA_DIR . '/uploads');

// Admin credentials — from environment variables, fall back to defaults
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'admin123');

// Site URL — from environment or auto-detect
$siteUrl = getenv('SITE_URL') ?: 'http://localhost/testhtml';
define('SITE_URL', rtrim($siteUrl, '/'));
define('ADMIN_URL', SITE_URL . '/backend');

// TinyMCE API key — from environment or empty (self-hosted fallback)
define('TINYMCE_API_KEY', getenv('TINYMCE_API_KEY') ?: '');

// Optional: override the data directory (useful for Docker mounted volumes)
if (getenv('DATA_DIR')) {
    define('DATA_DIR_OVERRIDE', getenv('DATA_DIR'));
}

