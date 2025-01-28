<?php  
session_start();  
include '../includes/db.php';  
  
// Cek apakah admin sudah login  
if (!isset($_SESSION['admin_id'])) {  
    header("Location: admin_login.php");  
    exit();  
}  
  
// Query untuk seller approved  
$stmt = $conn->prepare("SELECT s.*, u.username, u.email FROM sellers s JOIN users u ON s.user_id = u.id WHERE s.status = 'approved'");  
$stmt->execute();  
$results = $stmt->get_result();  
  
// Query untuk seller pending  
$stmt_pending = $conn->prepare("SELECT s.*, u.username, u.email FROM sellers s JOIN users u ON s.user_id = u.id WHERE s.status = 'pending'");  
$stmt_pending->execute();  
$pending_results = $stmt_pending->get_result();  
?>  
<!DOCTYPE html>  
<html lang="id">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Admin Dashboard - Toko Online</title>  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">  
    <style>  
        body { background-color: #f4f6f9; }  
        .dashboard-container {  
            background-color: white;  
            border-radius: 10px;  
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);  
            padding: 30px;  
            margin-top: 30px;  
        }  
    </style>  
</head>  
<body>  
    <div class="container dashboard-container">  
        <?php  
        // Tampilkan pesan sukses atau error  
        if (isset($_SESSION['success_message'])) {  
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .   
                 htmlspecialchars($_SESSION['success_message']) .   
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';  
            unset($_SESSION['success_message']);  
        }  
        if (isset($_SESSION['error_message'])) {  
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .   
                 htmlspecialchars($_SESSION['error_message']) .   
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';  
            unset($_SESSION['error_message']);  
        }  
        ?>  
  
        <div class="page-header d-flex justify-content-between align-items-center mb-4">  
            <h2 class="mb-0">Admin Dashboard</h2>  
            <a href="../logout.php" class="btn btn-outline-danger">  
                <i class="bi bi-box-arrow-right me-2"></i>Logout  
            </a>  
        </div>  
  
        <!-- Daftar Toko yang Disetujui -->  
        <h4 class="mb-3">Toko Online</h4>  
        <?php if ($results->num_rows > 0): ?>  
            <div class="table-responsive">  
                <table class="table table-hover table-striped">  
                    <thead class="table-light">  
                        <tr>  
                            <th>ID</th>  
                            <th>Nama Toko</th>  
                            <th>Pemilik</th>  
                            <th>Email</th>  
                            <th>No. HP</th>  
                            <th>Alamat</th>  
                            <th>Status</th> <!-- Tambahkan kolom status -->  
                            <th class="text-center">Actions</th>  
                        </tr>  
                    </thead>  
                    <tbody>  
                        <?php while ($row = $results->fetch_assoc()): ?>  
                            <tr>  
                                <td><?= htmlspecialchars($row['id']) ?></td>  
                                <td><?= htmlspecialchars($row['shop_name']) ?></td>  
                                <td><?= htmlspecialchars($row['username']) ?></td>  
                                <td><?= htmlspecialchars($row['email']) ?></td>  
                                <td><?= htmlspecialchars($row['phone_number']) ?></td>  
                                <td><?= htmlspecialchars($row['shop_address']) ?></td>  
                                <td><?= htmlspecialchars($row['status']) ?></td> <!-- Tampilkan status -->  
                                <td class="text-center">  
                                    <a href='shop_details.php?id=<?= $row['id'] ?>' class='btn btn-info btn-sm btn-action'>  
                                        <i class="bi bi-eye me-1"></i>Detail  
                                    </a>  
                                    <a href='disable-shop.php?id=<?= $row['id'] ?>' class='btn btn-warning btn-sm btn-action' onclick="return confirm('Apakah Anda yakin ingin menonaktifkan toko ini?')">  
                                        <i class="bi bi-slash-circle me-1"></i>Nonaktifkan  
                                    </a>  
                                </td>  
                            </tr>  
                        <?php endwhile; ?>  
                    </tbody>  
                </table>  
            </div>  
        <?php else: ?>  
            <div class="alert alert-info">Tidak ada toko online saat ini.</div>  
        <?php endif; ?>  
  
        <!-- Daftar Seller Pending -->  
        <h4 class="mt-5 mb-3">Seller Menunggu Persetujuan</h4>  
        <?php if ($pending_results->num_rows > 0): ?>     
            <div class="table-responsive">  
                <table class="table table-hover table-striped">  
                    <thead class="table-light">  
                        <tr>  
                            <th>ID</th>  
                            <th>Nama Toko</th>  
                            <th>Pemilik</th>  
                            <th>Email</th>  
                            <th>No. HP</th>  
                            <th>Alamat</th>  
                            <th class="text-center">Actions</th>  
                        </tr>  
                    </thead>  
                    <tbody>  
                        <?php while ($row = $pending_results->fetch_assoc()): ?>  
                            <tr>  
                                <td><?= htmlspecialchars($row['id']) ?></td>  
                                <td><?= htmlspecialchars($row['shop_name']) ?></td>  
                                <td><?= htmlspecialchars($row['username']) ?></td>  
                                <td><?= htmlspecialchars($row['email']) ?></td>  
                                <td><?= htmlspecialchars($row['phone_number']) ?></td>  
                                <td><?= htmlspecialchars($row['shop_address']) ?></td>  
                                <td class="text-center">  
                                    <a href='approve_seller.php?id=<?= $row['id'] ?>' class='btn btn-success btn-sm btn-action'>  
                                        <i class="bi bi-check-circle me-1"></i>Approve  
                                    </a>  
                                    <a href='reject_seller.php?id=<?= $row['id'] ?>' class='btn btn-danger btn-sm btn-action'>  
                                        <i class="bi bi-x-circle me-1"></i>Tolak  
                                    </a>  
                                </td>  
                            </tr>  
                        <?php endwhile; ?>  
                    </tbody>  
                </table>  
            </div>  
        <?php else: ?>  
            <div class="alert alert-warning">Tidak ada seller yang menunggu persetujuan.</div>  
        <?php endif; ?>  
    </div>  
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>  
</html>  
