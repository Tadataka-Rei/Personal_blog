<?php
/**
 * auth.php — Simple session-based authentication
 */

require_once __DIR__ . '/config.php';

/**
 * Check if user is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require authentication — redirects to login if not authenticated.
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        redirect('backend/login.php');
    }
}

/**
 * Attempt login with provided credentials.
 */
function login(string $username, string $password): bool {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout — clear session.
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

