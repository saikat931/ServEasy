<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_unset();
session_destroy();

$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$pos = strpos($script_path, '/Frontend/Web');
$redirect_url = ($pos !== false) ? substr($script_path, 0, $pos + strlen('/Frontend/Web')) . '/index.php' : './index.php';

header("Location: " . $redirect_url);
exit;
