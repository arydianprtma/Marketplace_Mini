<?php  
session_start();  
include 'includes/db.php';  
  
// Cek apakah user sudah login  
if (!isset($_SESSION['user_id'])) {  
    header("Location: login.php");  
    exit();  
}  
  
// Pagination setup  
$limit = 12; // Jumlah produk per halaman  
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  
$page = max(1, $page); // Pastikan halaman minimal 1  
$offset = ($page - 1) * $limit;  
  
// Ambil total jumlah produk untuk pagination  
$total_products_query = $conn->query("SELECT COUNT(*) as total FROM products");  
$total_products = $total_products_query->fetch_assoc()['total'];  
$total_pages = ceil($total_products / $limit);  
  
// Ambil semua produk dengan pagination  
$stmt = $conn->prepare("  
    SELECT p.id, p.name, p.description, p.price, p.image, p.stock, u.username AS seller_name   
    FROM products p  
    JOIN users u ON p.seller_id = u.id  
    LIMIT ? OFFSET ?  
");  
$stmt->bind_param("ii", $limit, $offset);  
$stmt->execute();  
$result = $stmt->get_result();  
  
// Cek apakah user memiliki toko yang sudah di-approve  
$stmt_seller = $conn->prepare("SELECT * FROM sellers WHERE user_id = ? AND status = 'approved'");  
$stmt_seller->bind_param("i", $_SESSION['user_id']);  
$stmt_seller->execute();  
$seller = $stmt_seller->get_result()->fetch_assoc();  
  
// Ambil data foto profil pengguna  
$stmt_user = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");  
$stmt_user->bind_param("i", $_SESSION['user_id']);  
$stmt_user->execute();  
$user = $stmt_user->get_result()->fetch_assoc();  
$profile_picture = $user['profile_picture'] ?? 'default.jpg'; // Gunakan default.jpg jika foto profil tidak ada  
  
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Dashboard Marketplace</title>  
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
  
        html, body {  
            height: 100%;  
            margin: 0;  
        }  
  
        .wrapper {  
            min-height: 100%;  
            display: flex;  
            flex-direction: column;  
        }  
  
        .content {  
            flex: 1;  
        }  
  
        .navbar {  
            background-color: white !important;  
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);  
        }  
  
        .product-card {  
            transition: transform 0.3s ease, box-shadow 0.3s ease;  
            background-color: white;  
            border: none;  
            border-radius: 10px;  
        }  
  
        .product-card:hover {  
            transform: translateY(-10px);  
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);  
        }  
  
        .product-card img {  
            object-fit: cover;  
            height: 250px;  
            border-top-left-radius: 10px;  
            border-top-right-radius: 10px;  
        }  
  
        .badge-stock {  
            position: absolute;  
            top: 10px;  
            right: 10px;  
            background-color: var(--secondary-color) !important;  
        }  
  
        .btn-primary {  
            background-color: var(--primary-color);  
            border-color: var(--primary-color);  
        }  
  
        .btn-primary:hover {  
            background-color: #2980b9;  
            border-color: #2980b9;  
        }  
  
        .text-primary {  
            color: var(--primary-color) !important;  
        }  
  
        footer {  
            background-color: white !important;  
            box-shadow: 0 -2px 4px rgba(0,0,0,0.05);  
        }  
  
        .profile-picture {  
            width: 30px;  
            height: 30px;  
            border-radius: 50%;  
            object-fit: cover;  
            margin-right: 10px;  
        }  
    </style>  
</head>  
<body>  
    <div class="wrapper">  
        <!-- Navbar -->  
        <nav class="navbar navbar-expand-lg navbar-light shadow-sm">  
            <div class="container">  
                <a class="navbar-brand" href="index.php" style="color: var(--primary-color); font-weight: bold;">    
                    <i class="bi bi-shop"></i> Marketplace    
                </a>   
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">  
                    <span class="navbar-toggler-icon"></span>  
                </button>  
                <div class="collapse navbar-collapse" id="navbarNav">  
                    <ul class="navbar-nav me-auto">  
                        <li class="nav-item">    
                            <a class="nav-link" href="index.php">    
                                <i class="bi bi-house-door-fill me-2"></i> Beranda    
                            </a>    
                        </li>    
                        <li class="nav-item">    
                            <a class="nav-link" href="#">    
                                <i class="bi bi-shop me-2"></i> Produk    
                            </a>    
                        </li>  
                        <?php if ($seller): ?>  
                        <li class="nav-item">    
                            <a class="nav-link" href="seller/manage_shop.php">    
                                <i class="bi bi-gear me-2"></i> Kelola Toko    
                            </a>    
                        </li>  
                        <?php endif; ?>  
                    </ul>  
                    <ul class="navbar-nav">  
                        <li class="nav-item dropdown">    
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">    
                                <img src="uploads/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto Profil" class="profile-picture"> <?php echo htmlspecialchars($_SESSION['username']); ?>    
                            </a>    
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">    
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profil</a></li>    
                                <li><a class="dropdown-item" href="cart.php"><i class="bi bi-cart"></i> Keranjang</a></li>    
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-receipt"></i> Pesanan</a></li>    
                                <li><a class="dropdown-item" href="user/settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>    
                                <li><hr class="dropdown-divider"></li>    
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>    
                            </ul>    
                        </li>    
                    </ul>  
                </div>  
            </div>  
        </nav>  
  
        <!-- Main Content -->  
        <div class="content container mt-4">  
            <div class="row">  
                <div class="col-12">  
                    <h2 class="mb-4 text-center" style="color: var(--dark-color);">Selamat Datang di Marketplace</h2>  
                </div>  
            </div>  
  
            <div class="row g-4">  
                <?php  
                // Menampilkan semua produk yang ada di marketplace  
                if ($result->num_rows > 0) {  
                    while ($product = $result->fetch_assoc()) {  
                        ?>  
                        <div class="col-md-4 col-lg-3 col-sm-6">  
                            <div class="card product-card position-relative">  
                                <span class="badge bg-secondary badge-stock">Stok: <?php echo $product['stock']; ?></span>  
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">  
                                <div class="card-body">  
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>  
                                    <p class="card-text text-muted mb-2"><?php echo htmlspecialchars($product['description']); ?></p>  
                                    <div class="d-flex justify-content-between align-items-center">  
                                        <h6 class="mb-0 text-primary">Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></h6>  
                                        <small class="text-muted">Penjual: <?php echo htmlspecialchars($product['seller_name']); ?></small>  
                                    </div>  
                                    <form action="add_to_cart.php" method="POST" class="mt-3">  
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">  
                                        <div class="input-group">  
                                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>" required>  
                                            <button type="submit" class="btn btn-primary">  
                                                <i class="bi bi-cart-plus"></i> Keranjang  
                                            </button>  
                                        </div>  
                                    </form>  
                                </div>  
                            </div>  
                        </div>  
                        <?php  
                    }  
                } else {  
                    echo '<div class="col-12 text-center"><p>Tidak ada produk yang tersedia.</p></div>';  
                }  
                ?>  
            </div>  
  
            <!-- Pagination -->  
            <nav aria-label="Pagination" class="mt-4">  
                <ul class="pagination justify-content-center">  
                    <?php if ($page > 1): ?>  
                        <li class="page-item">  
                            <a class="page-link" href="?page=<?php echo htmlspecialchars($page - 1); ?>">Sebelumnya</a>  
                        </li>  
                    <?php endif; ?>  
  
                    <?php   
                    // Tampilkan nomor halaman  
                    for ($i = 1; $i <= $total_pages; $i++) {  
                        $active = ($i == $page) ? 'active' : '';  
                        echo "<li class='page-item $active'><a class='page-link' href='?page=" . htmlspecialchars($i) . "'>" . htmlspecialchars($i) . "</a></li>";  
                    }  
                    ?>  
  
                    <?php if ($page < $total_pages): ?>  
                        <li class="page-item">  
                            <a class="page-link" href="?page=<?php echo htmlspecialchars($page + 1); ?>">Berikutnya</a>  
                        </li>  
                    <?php endif; ?>  
                </ul>  
            </nav>  
        </div>  
  
        <!-- Footer -->  
        <footer class="text-center text-lg-start mt-4">  
            <div class="container p-4">  
                <div class="text-center p-3" style="color: var(--dark-color);">  
                    © <?php echo date('Y'); ?> Marketplace. All Rights Reserved.  
                </div>  
            </div>  
        </footer>  
    </div>  
  
    <!-- Bootstrap JS Bundle with Popper -->  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>  
</html>  
  
<?php  
$stmt->close();  
$stmt_seller->close();  
$stmt_user->close();  
$conn->close();  
?>  
