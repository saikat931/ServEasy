<?php
/**
 * Split-Domain Session Sync for ServEasy Customer Web Portal
 * Cryptographically verifies the JWT issued by the Central Backend API
 * and establishes the local PHP $_SESSION['user_id'] on the Customer Web domain.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../common/ApiBridge.php';

$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];
$token = trim($_POST['access_token'] ?? ($jsonBody['access_token'] ?? ''));

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing access token']);
    exit;
}

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

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    setcookie('access_token', $token, [
        'expires'  => time() + 3600,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    echo json_encode(['status' => 'success', 'user' => $verifyRes['user']]);
    exit;
}

http_response_code(401);
echo json_encode([
    'status'  => 'error',
    'message' => $verifyRes['message'] ?? 'Could not verify login session with Central API'
]);
exit;
