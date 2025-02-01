<?php
session_start();

include 'includes/db.php';

// Hapus token dari database jika ada cookie
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    // Hapus cookie
    setcookie('remember_me', '', time() - 3600, '/');
}

// Hapus semua session
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>