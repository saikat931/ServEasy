<?php
// ServEasy Customer Profile Alias
require_once __DIR__ . '/common/auth_session.php';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: pages/user-profile.php" . $query);
exit;
