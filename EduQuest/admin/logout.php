<?php
/**
 * Admin Logout
 * Destroys the admin session and redirects to the login page.
 */
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

session_start();

// Only allow logout if an admin session is active
if (!empty($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    session_unset();
    session_destroy();

    // Expire the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
}

header('Location: login.php?logged_out=1');
exit;
