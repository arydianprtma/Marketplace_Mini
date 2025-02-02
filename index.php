<?php
session_start();
include 'includes/db.php';

// Mengatur session_id yang unik setiap kali pengguna login
if (!isset($_SESSION['session_id']) && isset($_POST['session_id'])) {
    $_SESSION['session_id'] = $_POST['session_id'];
}

// Mengambil data user berdasarkan session ID
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); 
} else {
    header("Location: login.php");
    exit();
}

// Pengaturan pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Fungsi pencarian dan pengurutan
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Query dasar untuk produk
$base_query = "
    SELECT p.id, p.name, p.description, p.price, p.image, p.stock, p.created_at,
           s.shop_name as seller_name,
           s.shop_logo as seller_avatar,
           AVG(r.rating) as avg_rating, 
           COUNT(r.id) as review_count
    FROM products p
    JOIN users u ON p.seller_id = u.id
    JOIN sellers s ON u.id = s.user_id AND s.status = 'approved'
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE 1=1
";

// Menambahkan kondisi pencarian jika ada
if ($search) {
    $base_query .= " AND (p.name LIKE '%" . $search . "%' OR p.description LIKE '%" . $search . "%')";
}

// Group by clause yang diperbaiki
$base_query .= " GROUP BY p.id, p.name, p.description, p.price, p.image, p.stock, p.created_at, s.shop_name, s.shop_logo";

// Menambahkan pengurutan
switch ($sort) {
    case 'price_low':
        $base_query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $base_query .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $base_query .= " ORDER BY avg_rating DESC, review_count DESC";
        break;
    case 'newest':
    default:
        $base_query .= " ORDER BY p.created_at DESC";
        break;
}

// Query untuk total produk (pagination)
$total_query = "SELECT COUNT(DISTINCT p.id) as total FROM products p 
                JOIN users u ON p.seller_id = u.id
                JOIN sellers s ON u.id = s.user_id AND s.status = 'approved'
                WHERE 1=1";
if ($search) {
    $total_query .= " AND (p.name LIKE '%" . $search . "%' OR p.description LIKE '%" . $search . "%')";
}
$total_products_query = $conn->query($total_query);
$total_products = $total_products_query->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Query final dengan pagination
$query = $base_query . " LIMIT $offset, $limit";
$result = $conn->query($query);

// Mengambil gambar profil user
$stmt_user = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$profile_picture = $user_data['profile_picture'] ?? 'default.jpg';

// Cek status toko user
$stmt_seller = $conn->prepare("SELECT * FROM sellers WHERE user_id = ? AND status = 'approved'");
$stmt_seller->bind_param("i", $_SESSION['user_id']);
$stmt_seller->execute();
$seller = $stmt_seller->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .footer {
            background-color: #f8f9fa;
            padding: 40px 0;
            margin-top: 40px;
        }
        .social-media a {
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        .social-media a:hover {
            color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Marketplace
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <form class="search-bar mx-auto position-relative" method="GET" action="index.php">
                    <input type="search" name="search" class="form-control search-input" 
                           placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-link position-absolute end-0 top-50 translate-middle-y">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                
                <ul class="navbar-nav ms-auto">
                    <?php if ($seller): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="seller/manage_shop.php">
                            <i class="bi bi-shop me-1"></i> Toko Saya
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart me-1"></i> Keranjang
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <img src="uploads/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" 
                                 alt="Profile" 
                                 class="rounded-circle me-1" 
                                 style="width: 24px; height: 24px; object-fit: cover;">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user/profile.php">
                                <i class="bi bi-person me-2"></i> Profil
                            </a></li>
                            <li><a class="dropdown-item" href="user/orders.php">
                                <i class="bi bi-box me-2"></i> Pesanan
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="user/settings.php">
                                <i class="bi bi-gear me-2"></i> Pengaturan
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Keluar
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <?php echo $search ? 'Hasil Pencarian: "'.htmlspecialchars($search).'"' : 'Produk Terbaru'; ?>
            </h2>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php
                    $sort_text = [
                        'newest' => 'Terbaru',
                        'price_low' => 'Harga Terendah',
                        'price_high' => 'Harga Tertinggi',
                        'popular' => 'Terpopuler'
                    ][$sort] ?? 'Urutkan';
                    echo $sort_text;
                    ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item <?php echo $sort == 'newest' ? 'active' : ''; ?>" 
                          href="?sort=newest<?php echo $search ? '&search='.urlencode($search) : ''; ?>">Terbaru</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'price_low' ? 'active' : ''; ?>" 
                          href="?sort=price_low<?php echo $search ? '&search='.urlencode($search) : ''; ?>">Harga Terendah</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'price_high' ? 'active' : ''; ?>" 
                          href="?sort=price_high<?php echo $search ? '&search='.urlencode($search) : ''; ?>">Harga Tertinggi</a></li>
                    <li><a class="dropdown-item <?php echo $sort == 'popular' ? 'active' : ''; ?>" 
                          href="?sort=popular<?php echo $search ? '&search='.urlencode($search) : ''; ?>">Terpopuler</a></li>
                </ul>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if ($result && $result->num_rows > 0): ?>
        <div class="product-grid">
            <?php while ($product = $result->fetch_assoc()): ?>
            <div class="product-card">
                <div class="product-image-wrapper">
                    <img src="uploads/product_images/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="product-image"
                         loading="lazy">
                    <div class="product-badges">
                        <span class="badge-stock">
                            Stok: <?php echo htmlspecialchars($product['stock']); ?>
                        </span>
                        <?php if ($product['avg_rating']): ?>
                        <span class="badge-rating">
                            <i class="bi bi-star-fill me-1"></i>
                            <?php echo number_format($product['avg_rating'], 1); ?>
                            (<?php echo $product['review_count']; ?>)
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-details">
                    <h3 class="product-title">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    
                    <p class="product-description">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    
                    <div class="product-meta">
                        <div class="product-price">
                            Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                        </div>
                        <div class="seller-info">
                        <img src="uploads/shop_logos/<?php echo htmlspecialchars($product['seller_avatar'] ? $product['seller_avatar'] : 'default.jpg'); ?>" 
                                alt="<?php echo htmlspecialchars($product['seller_name']); ?>"
                                class="seller-avatar"
                                onerror="this.src='uploads/shop_logos/default.jpg'">
                            <span class="seller-name">
                                <?php echo htmlspecialchars($product['seller_name']); ?>
                            </span>
                        </div>
                    </div>

                    <form action="add_to_cart.php" method="POST" class="cart-form">
                        <input type="hidden" name="product_id" 
                               value="<?php echo htmlspecialchars($product['id']); ?>">
                        <input type="number" name="quantity" 
                               class="quantity-input"
                               value="1" 
                               min="1" 
                               max="<?php echo htmlspecialchars($product['stock']); ?>" 
                               required>
                        <button type="submit" class="add-to-cart-btn">
                            <i class="bi bi-cart-plus"></i>
                            Tambah
                        </button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Product pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, min($page - 2, $total_pages - 4));
                $end_page = min($total_pages, max(5, $page + 2));

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h3 class="mt-3">Tidak ada produk</h3>
            <p class="text-muted">
                <?php echo $search ? 'Tidak ada produk yang sesuai dengan pencarian Anda.' : 'Belum ada produk yang ditambahkan ke marketplace.'; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer remains the same -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt_seller->close();
$stmt_user->close();
$conn->close();
?>