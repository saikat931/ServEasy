<?php
/**
 * ServEasy Customer Web Portal - Global Logout Handler
 * Reliably terminates local PHP session, strips 7-day persistent cookies across
 * all scopes (Secure, SameSite, domain root), and clears client-side credentials.
 */

// Anti-cache headers to prevent browser from reusing authenticated page snapshots
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// 1. Invalidate PHPSESSID across root path with both modern array options and legacy parameters
$sessName = session_name();
setcookie($sessName, '', [
    'expires'  => time() - 86400,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
setcookie($sessName, '', time() - 86400, '/');
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie($sessName, '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// 2. Invalidate access_token persistent cookie across root path
setcookie('access_token', '', [
    'expires'  => time() - 86400,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
setcookie('access_token', '', time() - 86400, '/');
unset($_COOKIE['access_token']);

// 3. Destroy PHP session
session_unset();
session_destroy();

$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($pos = stripos($script_path, '/frontend-web')) !== false) {
    $web_root = substr($script_path, 0, $pos + strlen('/frontend-web'));
} elseif (($pos = strpos($script_path, '/Frontend/Web')) !== false) {
    $web_root = substr($script_path, 0, $pos + strlen('/Frontend/Web'));
} else {
    $dir = dirname($script_path);
    $web_root = ($dir === '/' || $dir === '\\') ? '' : rtrim($dir, '/\\');
}
$redirect_url = ($web_root !== '' ? $web_root : '') . '/index.php?logged_out=1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | ServEasy</title>
    <style>
        body { margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #fff; }
        .spinner-box { text-align: center; }
        .spinner { width: 44px; height: 44px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #ef4444; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { color: #94a3b8; font-size: 15px; margin: 0; }
    </style>
</head>
<body>
    <div class="spinner-box">
        <div class="spinner"></div>
        <p>Signing out securely...</p>
    </div>
    <script>
    try {
        localStorage.removeItem('serveasy_access_token');
        localStorage.removeItem('serveasy_user');
        sessionStorage.clear();
        document.cookie = 'access_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
        document.cookie = 'access_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        document.cookie = '<?= $sessName ?>=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
        document.cookie = '<?= $sessName ?>=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    } catch (e) {}
    window.location.replace(<?= json_encode($redirect_url) ?>);
    </script>
</body>
</html>
