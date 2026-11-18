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
setcookie('access_token', '', time() - 3600, '/');
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Logging out...</title></head>
<body>
<script>
try {
    localStorage.removeItem('serveasy_access_token');
    localStorage.removeItem('serveasy_user');
} catch (e) {}
window.location.replace('../index.php');
</script>
</body>
</html>
