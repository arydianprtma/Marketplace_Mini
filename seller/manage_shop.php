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
$user_profile_picture = isset($_SESSION['profile_pictures']) ? $_SESSION['profile_pictures'] : 'default.png';  

// Ambil data produk
$products_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$products_stmt->bind_param("i", $_SESSION['user_id']);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
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
  
        .navbar {  
            background-color: white !important;  
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);  
        }  

        .shop-logo {  
            max-width: 200px;  
            max-height: 200px;  
            object-fit: cover;  
            border-radius: 10px;  
            margin-bottom: 15px;  
        }  
  
        .profile-picture {  
            width: 30px;  
            height: 30px;  
            border-radius: 50%;  
            object-fit: cover;  
            margin-right: 10px;  
        }   

        footer {  
            background-color: white !important;  
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);  
        }  

        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }

        .product-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: none;
            background: rgba(255,255,255,0.9);
            border-radius: 8px;
            padding: 5px;
        }

        .product-card:hover .product-actions {
            display: flex;
            gap: 5px;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .product-stock {
            font-size: 0.9rem;
            color: #666;
        }

        .tabs-container {
            border-bottom: 2px solid #eee;
            margin-bottom: 2rem;
        }

        .custom-tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .custom-tab.active {
            border-bottom: 3px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 2rem 0;
        }

        .footer {
            background: white;
            padding: 30px 0;
            margin-top: 50px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
    </style>  
</head>  
<body>  
    <!-- Navbar stays the same -->  
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
                        <a class="nav-link active" href="manage_shop.php">  
                            <i class="bi bi-shop-window"></i> Kelola Toko  
                        </a>  
                    </li>  
                </ul>  
                <ul class="navbar-nav">  
                    <li class="nav-item dropdown">  
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">  
                            <img src="../uploads/profile_pictures/<?php echo $store_logo; ?>" alt="Foto Profil" class="profile-picture"> <?php echo $_SESSION['username']; ?>  
                        </a>  
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">  
                            <li><a class="dropdown-item" href="../user/profile.php"><i class="bi bi-person"></i> Profil</a></li>  
                            <!-- <li><a class="dropdown-item" href="../cart.php"><i class="bi bi-cart"></i> Keranjang</a></li>  
                            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-receipt"></i> Pesanan</a></li>   -->
                            <li><a class="dropdown-item" href="../user/settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>  
                            <li><hr class="dropdown-divider"></li>  
                            <li><a class="dropdown-item text-danger" href="logout_seller.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>  
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
            <div class="card shadow-sm mb-4">  
                <div class="card-body text-center">  
                    <?php if (!empty($store_logo)): ?>  
                        <img src="../uploads/shop_logos/<?php echo $store_logo; ?>" alt="Logo Toko" class="shop-logo">  
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

            <!-- Products Section -->
            <div class="tabs-container d-flex justify-content-center mb-4">
                <div class="custom-tab active" data-tab="all">
                    <i class="bi bi-grid me-2"></i>Semua Produk
                </div>
                <div class="custom-tab" data-tab="active">
                    <i class="bi bi-check-circle me-2"></i>Aktif
                </div>
                <div class="custom-tab" data-tab="draft">
                    <i class="bi bi-file-earmark me-2"></i>Draft
                </div>
            </div>

            <?php if ($products_result->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card product-card position-relative">
                                <img src="../uploads/product_images/<?php echo htmlspecialchars($product['image']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                
                                <span class="status-badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>

                                <div class="product-actions">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="product-price mb-1">
                                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                                    </p>
                                    <p class="product-stock mb-2">
                                        Stok: <?php echo $product['stock']; ?> unit
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                                        </span>
                                        <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $product['stock'] > 0 ? 'Tersedia' : 'Habis'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam display-4 mb-3 text-muted"></i>
                    <h3>Belum Ada Produk</h3>
                    <p class="text-muted">Mulai tambahkan produk pertama Anda untuk mulai berjualan!</p>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Produk Pertama
                    </a>
                </div>
            <?php endif; ?>

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
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Tentang Kami</h5>
                    <p class="text-muted">Market adalah platform marketplace yang menghubungkan penjual dan pembeli dalam transaksi yang aman dan nyaman.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Link Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted">Cara Berbelanja</a></li>
                        <li><a href="#" class="text-muted">Cara Berjualan</a></li>
                        <li><a href="#" class="text-muted">Kebijakan Privasi</a></li>
                        <li><a href="#" class="text-muted">Syarat dan Ketentuan</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Hubungi Kami</h5>
                    <ul class="list-unstyled">
                        <li class="text-muted"><i class="bi bi-envelope me-2"></i> support@modernmarket.com</li>
                        <li class="text-muted"><i class="bi bi-telephone me-2"></i> (021) 1234-5678</li>
                        <li class="text-muted"><i class="bi bi-geo-alt me-2"></i> Jakarta, Indonesia</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0 text-muted">
                    &copy; <?php echo date('Y'); ?> Modern Market. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
  
    <!-- Bootstrap JS Bundle with Popper -->  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  

    <!-- Custom JavaScript -->
    <script>
        // Handle product deletion
        function deleteProduct(productId) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                // Send delete request to server
                fetch(`delete_product.php?id=${productId}`, {
                    method: 'DELETE',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page or remove product card
                        location.reload();
                    } else {
                        alert('Gagal menghapus produk. Silakan coba lagi.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                });
            }
        }

        // Handle tab switching
        document.querySelectorAll('.custom-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.custom-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Here you would typically filter the products based on the selected tab
                // You can implement this using AJAX or by adding data attributes to the product cards
                const tabType = tab.dataset.tab;
                // Implementation for filtering products...
            });
        });
    </script>
</body>  
</html>