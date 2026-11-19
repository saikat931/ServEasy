<?php
/**
 * ServEasy Customer Web Portal - Global Session & Authentication Manager
 * Handles persistent 7-day session cookie parameters, automatic token-based session
 * hydration from cookies / central JWT, and cross-subpath session stability.
 */

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Explicitly configure session cookie parameters to root path '/' so session is shared across /auth, /pages, and root
    session_set_cookie_params([
        'lifetime' => 604800, // 7 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// If explicit logout flag was passed in URL, enforce clean unauthenticated state
if (isset($_GET['logged_out'])) {
    $_SESSION = [];
    unset($_COOKIE['access_token']);
}

// Automatic Session Rehydration from access_token cookie if session was cleared or expired
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['access_token']) && !isset($_GET['logged_out'])) {
    $token = trim($_COOKIE['access_token']);
    $bridgePath = __DIR__ . '/ApiBridge.php';
    if (file_exists($bridgePath)) {
        require_once $bridgePath;
        try {
            $verifyRes = \Frontend\ApiBridge::callApi('auth-api.php', 'POST', [
                'action'       => 'verify_token',
                'access_token' => $token
            ]);
            if (!empty($verifyRes['status']) && $verifyRes['status'] === 'success' && !empty($verifyRes['user']['id'])) {
                $_SESSION['user_id']      = (int)$verifyRes['user']['id'];
                $_SESSION['full_name']    = $verifyRes['user']['full_name'] ?? '';
                $_SESSION['email']        = $verifyRes['user']['email'] ?? '';
                $_SESSION['phone']        = $verifyRes['user']['phone'] ?? '';
                $_SESSION['access_token'] = $token;
            } else {
                // Token invalid or revoked - expire cookie immediately
                $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                setcookie('access_token', '', [
                    'expires'  => time() - 86400,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                unset($_COOKIE['access_token']);
            }
        } catch (\Throwable $t) {
            error_log("Session auto-rehydration error: " . $t->getMessage());
        }
    }
}
