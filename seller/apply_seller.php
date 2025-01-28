<?php
session_start();
include 'includes/db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data form
$store_name = $_POST['store_name'];
$store_description = $_POST['store_description'];
$user_id = $_SESSION['user_id'];

// Cek apakah user sudah menjadi seller
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user['role'] === 'seller') {
    // Jika sudah seller, redirect ke halaman settings dengan pesan
    header("Location: settings.php?error=Sudah menjadi seller.");
    exit();
}

// Simpan pengajuan menjadi seller di database
$stmt = $conn->prepare("INSERT INTO seller_applications (user_id, store_name, store_description, status) VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("iss", $user_id, $store_name, $store_description);
$stmt->execute();
$stmt->close();

// Redirect ke halaman settings dengan pesan sukses
header("Location: settings.php?success=Pengajuan menjadi seller berhasil. Tunggu konfirmasi.");
exit();
?>
