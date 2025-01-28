<?php
session_start();
include '../includes/db.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil ID seller dari URL
if (isset($_GET['id'])) {
    $seller_id = $_GET['id'];

    // Update status seller menjadi 'approved' dan atur notifikasi menjadi 1
    $stmt = $conn->prepare("UPDATE sellers SET status = 'approved', notification = 1 WHERE id = ?");
    $stmt->bind_param("i", $seller_id); // Menggunakan $seller_id di sini
    $execute_success = $stmt->execute();

    if ($execute_success) {
        // Redirect ke dashboard admin setelah berhasil approve
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Terjadi kesalahan saat memproses approval.";
    }

    $stmt->close();
} else {
    echo "ID seller tidak ditemukan.";
}

$conn->close();
?>
