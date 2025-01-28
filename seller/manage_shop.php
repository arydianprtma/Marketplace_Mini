<?php  
session_start();  
include '../includes/db.php';  
  
// Cek apakah user sudah login  
if (!isset($_SESSION['user_id'])) {  
    header("Location: ../login.php");  
    exit();  
}  
  
// Ambil data toko berdasarkan user_id dari session  
$stmt = $conn->prepare("SELECT shop_name, shop_description, shop_logo FROM sellers WHERE user_id = ? AND status = 'approved'");  
$stmt->bind_param("i", $_SESSION['user_id']);  
$stmt->execute();  
$result = $stmt->get_result();  
$data = $result->fetch_assoc();  
  
// Validasi data toko  
$store_name = isset($data['shop_name']) ? htmlspecialchars($data['shop_name'], ENT_QUOTES, 'UTF-8') : 'Toko Belum Terdaftar';  
$store_description = isset($data['shop_description']) ? htmlspecialchars($data['shop_description'], ENT_QUOTES, 'UTF-8') : 'Deskripsi belum tersedia';  
$store_logo = isset($data['shop_logo']) ? htmlspecialchars($data['shop_logo'], ENT_QUOTES, 'UTF-8') : '';  
  
// Ambil foto profil dari session  
$user_profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'default_profile.png';  
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Kelola Toko</title>  
    <!-- Bootstrap CSS -->  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>  
        :root {  
            --primary-color: #3498db;  
            --secondary-color: #2ecc71;  
            --dark-color: #2c3e50;  
            --light-color: #ecf0f1;  
        }  
  
        .shop-logo {  
            max-width: 200px;  
            max-height: 200px;  
            object-fit: cover;  
            border-radius: 10px;  
            margin-bottom: 15px;  
        }  
  
        .profile-picture {  
            width: 40px;  
            height: 40px;  
            border-radius: 50%;  
            object-fit: cover;  
            margin-right: 10px;
        }  

        footer {
            background-color: var(--light-color);
            padding: 15px 0;
            text-align: center;
            margin-top: 20px;
        }
    </style>  
</head>  
<body>  
    <!-- Navbar -->  
    <nav class="navbar navbar-expand-lg navbar-light">  
        <div class="container">  
            <a class="navbar-brand" href="../index.php" style="color: var(--primary-color); font-weight: bold;">  
                <i class="bi bi-shop"></i> Marketplace  
            </a>  
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">  
                <span class="navbar-toggler-icon"></span>  
            </button>  
            <div class="collapse navbar-collapse" id="navbarNav">  
                <ul class="navbar-nav me-auto">  
                    <li class="nav-item">  
                        <a class="nav-link" href="../index.php">  
                            <i class="bi bi-house"></i> Beranda  
                        </a>  
                    </li>  
                    <li class="nav-item">  
                        <a class="nav-link active" href="#">  
                            <i class="bi bi-shop-window"></i> Kelola Toko  
                        </a>  
                    </li>  
                </ul>  
                <ul class="navbar-nav">  
                    <li class="nav-item dropdown">  
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">  
                            <img src="../uploads/<?php echo $store_logo; ?>" alt="Foto Profil" class="profile-picture"> <?php echo $_SESSION['username']; ?>  
                        </a>  
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">  
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profil</a></li>  
                            <li><a class="dropdown-item" href="../cart.php"><i class="bi bi-cart"></i> Keranjang</a></li>  
                            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-receipt"></i> Pesanan</a></li>  
                            <li><a class="dropdown-item" href="../user/settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>  
                            <li><hr class="dropdown-divider"></li>  
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>  
                        </ul>  
                    </li>  
                </ul>  
            </div>  
        </div>  
    </nav>  
  
    <!-- Main Content -->  
    <div class="container mt-4">  
        <h1 class="text-center mb-4">Kelola Toko Anda</h1>  
        <?php if ($data): ?>  
            <div class="card shadow-sm">  
                <div class="card-body text-center">  
                    <?php if (!empty($store_logo)): ?>  
                        <img src="../uploads/<?php echo $store_logo; ?>" alt="Logo Toko" class="shop-logo">  
                    <?php endif; ?>  
                    <h2 class="card-title mb-3"><?php echo $store_name; ?></h2>  
                    <p class="card-text text-muted mb-4"><?php echo $store_description; ?></p>  
                    <div class="d-flex justify-content-center gap-3">
                        <a href="edit_shop.php" class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Toko
                        </a>  
                        <a href="add_product.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Produk
                        </a>  
                    </div>
                </div>  
            </div>  
        <?php else: ?>  
            <div class="text-center">  
                <p class="mb-3">Toko Anda belum terdaftar atau belum disetujui.</p>  
                <a href="create_shop.php" class="btn btn-primary">
                    <i class="bi bi-shop me-2"></i>Buat Toko Sekarang
                </a>  
            </div>  
        <?php endif; ?>  
    </div>  
  
    <!-- Footer -->  
    <footer>  
        © <?php echo date('Y'); ?> Marketplace. All Rights Reserved.  
    </footer>  
  
    <!-- Bootstrap JS Bundle with Popper -->  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>  
</html>