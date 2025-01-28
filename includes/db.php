<?php
$host = 'localhost'; // Sesuaikan dengan konfigurasi server kamu
$user = 'root';
$password = '';
$dbname = 'marketplace_mini';

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
