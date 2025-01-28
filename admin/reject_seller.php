<?php
session_start();
include '../includes/db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Proses reject seller
if (isset($_GET['id'])) {
    $seller_id = $_GET['id'];

    // Update status seller menjadi 'rejected'
    $stmt = $conn->prepare("UPDATE sellers SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $seller_id);

    if ($stmt->execute()) {
        header("Location: dashboard_admin.php?message=rejected");
    } else {
        echo "Terjadi kesalahan, silakan coba lagi.";
    }
} else {
    echo "ID seller tidak ditemukan.";
}
?>
