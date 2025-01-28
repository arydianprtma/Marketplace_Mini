<?php  
session_start();  
include '../includes/db.php';  
  
// Log PHP errors for debugging  
error_reporting(E_ALL);  
ini_set('display_errors', 1);  
  
// Cek apakah admin sudah login  
if (!isset($_SESSION['admin_id'])) {  
    header("Location: admin_login.php");  
    exit();  
}  
  
// Validasi parameter ID  
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {  
    $_SESSION['error_message'] = "ID toko tidak valid.";  
    header("Location: admin_dashboard.php");  
    exit();  
}  
  
$shop_id = intval($_GET['id']);  
  
// Proses nonaktifkan toko  
try {  
    // Mulai transaksi  
    $conn->begin_transaction();  
  
    // Periksa apakah toko ada dan belum dinonaktifkan  
    $check_stmt = $conn->prepare("SELECT id FROM sellers WHERE id = ? AND status = 'approved'");  
    $check_stmt->bind_param("i", $shop_id);  
    $check_stmt->execute();  
    $check_result = $check_stmt->get_result();  
  
    if ($check_result->num_rows == 0) {  
        throw new Exception("Toko tidak ditemukan atau sudah tidak aktif.");  
    }  
  
    // Update status toko menjadi nonaktif  
    $stmt = $conn->prepare("UPDATE sellers SET status = 'disabled' WHERE id = ?");  
    $stmt->bind_param("i", $shop_id);  
      
    if (!$stmt->execute()) {  
        throw new Exception("Gagal mengupdate status toko: " . $stmt->error);  
    }  
  
    // Nonaktifkan produk-produk milik toko  
    $stmt_products = $conn->prepare("UPDATE products SET status = 'disabled' WHERE seller_id = ?");  
    $stmt_products->bind_param("i", $shop_id);  
      
    if (!$stmt_products->execute()) {  
        throw new Exception("Gagal mengupdate status produk: " . $stmt_products->error);  
    }  
  
    // Commit transaksi  
    $conn->commit();  
  
    // Set pesan sukses  
    $_SESSION['success_message'] = "Toko berhasil dinonaktifkan.";  
  
} catch (Exception $e) {  
    // Rollback transaksi  
    $conn->rollback();  
      
    // Catat kesalahan di log server  
    error_log("Disable shop error: " . $e->getMessage());  
      
    // Set pesan error  
    $_SESSION['error_message'] = "Gagal menonaktifkan toko: " . $e->getMessage();  
}  
  
// Kembali ke halaman dashboard  
header("Location: admin_dashboard.php");  
exit();  
?>  
