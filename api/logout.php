<?php
session_start();
header('Content-Type: application/json');

// Destroy session
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
