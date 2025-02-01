<?php
session_start();
include 'includes/db.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data foto profil pengguna
$stmt_user = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$profile_picture = $user['profile_picture'] ?? 'default.jpg';

// Proses aksi pada keranjang
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        switch ($_POST['action']) {
            case 'update':
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                if ($quantity > 0) {
                    // Cek stok produk
                    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $product = $stmt->get_result()->fetch_assoc();
                    
                    if ($quantity <= $product['stock']) {
                        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                        $stmt->execute();
                        $_SESSION['message'] = "Jumlah produk berhasil diperbarui";
                    } else {
                        $_SESSION['error'] = "Jumlah melebihi stok yang tersedia!";
                    }
                }
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
                $_SESSION['message'] = "Produk berhasil dihapus dari keranjang";
                break;
        }
    }

    // Redirect untuk menghindari post resubmission
    header("Location: cart.php");
    exit();
}

// Ambil data keranjang dengan informasi produk dan penjual
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, u.username as seller_name, u.profile_picture as seller_avatar
               FROM cart c 
               JOIN products p ON c.product_id = p.id
               JOIN users u ON p.seller_id = u.id
               WHERE c.user_id = ?
               ORDER BY u.username, c.added_at DESC";

$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Kelompokkan produk berdasarkan penjual
$sellers = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $seller_name = $row['seller_name'];
    if (!isset($sellers[$seller_name])) {
        $sellers[$seller_name] = [
            'avatar' => $row['seller_avatar'],
            'products' => []
        ];
    }
    $sellers[$seller_name]['products'][] = $row;
    $total_price += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
        }

        .seller-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }

        .seller-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .seller-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .cart-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
        }

        .sticky-summary {
            position: sticky;
            top: 20px;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-picture {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .stock-warning {
            color: #dc3545;
            font-size: 0.875rem;
        }

        .btn-checkout {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            width: 100%;
        }

        .btn-checkout:hover {
            background-color: #2980b9;
            color: white;
        }

        .select-item {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        #select-all {
            cursor: pointer;
        }

        .form-check-label {
            cursor: pointer;
            user-select: none;
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
                            <a class="nav-link" href="products.php">  
                                <i class="bi bi-shop me-2"></i> Produk  
                            </a>  
                        </li>  
                    </ul>  
                    <ul class="navbar-nav">  
                        <li class="nav-item dropdown">  
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">  
                                <img src="uploads/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto Profil" class="profile-picture"> <?php echo $_SESSION['username']; ?>  
                            </a>  
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">  
                                <li><a class="dropdown-item" href="../user/profile.php">  
                                    <i class="bi bi-person me-2"></i> Profil  
                                </a></li>  
                                <li><a class="dropdown-item" href="cart.php">  
                                    <i class="bi bi-cart me-2"></i> Keranjang  
                                </a></li>  
                                <li><a class="dropdown-item" href="user/orders.php">  
                                    <i class="bi bi-list-check me-2"></i> Pesanan  
                                </a></li>  
                                <li><a class="dropdown-item" href="user/settings.php">  
                                    <i class="bi bi-gear me-2"></i> Pengaturan  
                                </a></li>  
                                <li><hr class="dropdown-divider"></li>  
                                <li><a class="dropdown-item text-danger" href="logout.php">  
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout  
                                </a></li>  
                            </ul>  
                        </li>  
                    </ul>  
                </div>  
            </div>  
        </nav>   

        <!-- Content -->
        <div class="content container py-4">
            <h4 class="mb-4">Keranjang Belanja</h4>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($sellers)): ?>
            <div class="row">
                <!-- Cart Items -->
                 
                <div class="col-lg-8">
                    <?php foreach ($sellers as $seller_name => $seller): ?>
                    <div class="seller-card">
                        <div class="seller-header">
                            <img src="uploads/profile_pictures/<?php echo htmlspecialchars($seller['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($seller_name); ?>" 
                                 class="seller-avatar">
                            <span class="fw-medium"><?php echo htmlspecialchars($seller_name); ?></span>
                        </div>

                        <?php foreach ($seller['products'] as $item): ?>
                        <div class="cart-item">
                            <!-- Tambahkan checkbox -->
                            <div class="me-3">
                                <input type="checkbox" 
                                       class="form-check-input select-item" 
                                       name="selected_items[]" 
                                       value="<?php echo $item['product_id']; ?>"
                                       data-price="<?php echo $item['price'] * $item['quantity']; ?>">
                            </div>
                            <img src="uploads/product_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="product-image me-3">
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <div class="text-primary mb-2">
                                    Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                </div>
                                
                                <?php if ($item['quantity'] > $item['stock']): ?>
                                <div class="stock-warning">
                                    Stok tersedia: <?php echo $item['stock']; ?>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center">
                                    <form method="post" class="d-flex align-items-center">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" 
                                               class="form-control quantity-input me-2"
                                               onchange="this.form.submit()">
                                    </form>

                                    <form method="post" class="ms-2">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Hapus produk ini dari keranjang?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="text-end ms-3">
                                <div class="fw-bold">
                                    Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary -->
                <div class="col-lg-4">
                    <div class="card sticky-summary">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Ringkasan Belanja</h5>

                            <div class="d-flex justify-content-between mb-3">
                                <span>Total Harga (<?php echo '<span id="selected-count">0</span> item'; ?>)</span>
                                <span class="fw-bold" id="selected-total">Rp 0</span>
                            </div>

                            <form action="checkout.php" method="POST" id="checkout-form">
                                <input type="hidden" name="selected_items" id="selected-items-input">
                                <button type="submit" class="btn btn-checkout" id="checkout-button" disabled>
                                    Checkout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <h5 class="mt-3">Keranjang Belanja Kosong</h5>
                <p class="text-muted">Ayo mulai belanja dan tambahkan produk ke keranjang!</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    Mulai Belanja
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript untuk auto-submit form saat quantity berubah -->
    <script>
        // Fungsi untuk memformat angka ke format rupiah
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka);
        }

        // Auto submit form saat quantity berubah
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const quantity = parseInt(this.value);
                const stock = parseInt(this.getAttribute('max'));
                
                if (quantity > stock) {
                    alert(`Stok tersedia hanya ${stock} unit`);
                    this.value = stock;
                } else if (quantity < 1) {
                    this.value = 1;
                }
                
                this.closest('form').submit();
            });
        });

        // Konfirmasi sebelum menghapus item
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')) {
                    e.preventDefault();
                }
            });
        });

        // Tambahkan setelah script yang sudah ada
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.select-item');
            const selectedTotal = document.getElementById('selected-total');
            const selectedCount = document.getElementById('selected-count');
            const checkoutButton = document.getElementById('checkout-button');
            const checkoutForm = document.getElementById('checkout-form');
            const selectedItemsInput = document.getElementById('selected-items-input');
        
            function updateTotal() {
                let total = 0;
                let count = 0;
                const selectedItems = [];
            
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        total += parseFloat(checkbox.dataset.price);
                        count++;
                        selectedItems.push(checkbox.value);
                    }
                });
            
                // Update UI
                selectedTotal.textContent = formatRupiah(total);
                selectedCount.textContent = count;

                // Enable/disable checkout button
                checkoutButton.disabled = count === 0;

                // Update hidden input with selected items
                selectedItemsInput.value = JSON.stringify(selectedItems);
            }
        
            // Add event listeners to checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateTotal);
            });
        
            // Add "Select All" functionality
            const selectAllCheckbox = document.createElement('input');
            selectAllCheckbox.type = 'checkbox';
            selectAllCheckbox.className = 'form-check-input me-2';
            selectAllCheckbox.id = 'select-all';
        
            const selectAllLabel = document.createElement('label');
            selectAllLabel.className = 'form-check-label';
            selectAllLabel.htmlFor = 'select-all';
            selectAllLabel.textContent = 'Pilih Semua';
        
            const selectAllContainer = document.createElement('div');
            selectAllContainer.className = 'mb-3 d-flex align-items-center';
            selectAllContainer.appendChild(selectAllCheckbox);
            selectAllContainer.appendChild(selectAllLabel);
        
            // Insert "Select All" checkbox before the first seller-card
            const firstSellerCard = document.querySelector('.seller-card');
            if (firstSellerCard) {
                firstSellerCard.parentNode.insertBefore(selectAllContainer, firstSellerCard);
            }
        
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateTotal();
            });
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$stmt_user->close();
$conn->close();
?>