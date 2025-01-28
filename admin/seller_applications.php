<?php
session_start();
include 'includes/db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil semua pengajuan seller
$stmt = $conn->prepare("SELECT * FROM seller_applications");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Pengajuan Menjadi Seller</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Toko</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($application = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $application['store_name']; ?></td>
                    <td><?php echo $application['store_description']; ?></td>
                    <td><?php echo ucfirst($application['status']); ?></td>
                    <td>
                        <a href="approve_seller.php?id=<?php echo $application['id']; ?>" class="btn btn-success btn-sm">Setujui</a>
                        <a href="reject_seller.php?id=<?php echo $application['id']; ?>" class="btn btn-danger btn-sm">Tolak</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
