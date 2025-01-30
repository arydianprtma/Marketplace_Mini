<?php  
session_start();  
include 'includes/db.php';  
  
// Cek apakah user sudah login  
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
$profile_picture = $user['profile_picture'] ?? 'default.jpg'; // Gunakan default.jpg jika foto profil tidak ada
  
// Fungsi untuk menambahkan produk ke keranjang  
function addToCart($conn, $user_id, $product_id, $quantity) {  
    // Cek apakah produk sudah ada di keranjang  
    $stmt_check = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");  
    $stmt_check->bind_param("ii", $user_id, $product_id);  
    $stmt_check->execute();  
    $result_check = $stmt_check->get_result();  
  
    if ($result_check->num_rows > 0) {  
        // Produk sudah ada, tambahkan jumlahnya  
        $row = $result_check->fetch_assoc();  
        $new_quantity = $row['quantity'] + $quantity;  
  
        $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");  
        $stmt_update->bind_param("iii", $new_quantity, $user_id, $product_id);  
        if (!$stmt_update->execute()) {  
            return "Gagal memperbarui jumlah produk di keranjang: " . $stmt_update->error;  
        }  
    } else {  
        // Produk belum ada, tambahkan baru  
        $stmt_insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");  
        $stmt_insert->bind_param("iii", $user_id, $product_id, $quantity);  
        if (!$stmt_insert->execute()) {  
            return "Gagal menambahkan produk ke keranjang: " . $stmt_insert->error;  
        }  
    }  
    return "Produk berhasil ditambahkan ke keranjang.";  
}  
  
// Fungsi untuk menghapus produk dari keranjang  
function removeFromCart($conn, $user_id, $product_id) {  
    $stmt_delete = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");  
    $stmt_delete->bind_param("ii", $user_id, $product_id);  
    if (!$stmt_delete->execute()) {  
        return "Gagal menghapus produk dari keranjang: " . $stmt_delete->error;  
    }  
    return "Produk berhasil dihapus dari keranjang.";  
}  
  
// Fungsi untuk memperbarui jumlah produk di keranjang  
function updateCartItem($conn, $user_id, $product_id, $quantity) {  
    if ($quantity <= 0) {  
        return "Jumlah produk harus lebih dari 0.";  
    }  
    $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");  
    $stmt_update->bind_param("iii", $quantity, $user_id, $product_id);  
    if (!$stmt_update->execute()) {  
        return "Gagal memperbarui jumlah produk di keranjang: " . $stmt_update->error;  
    }  
    return "Jumlah produk berhasil diperbarui.";  
}  
  
// Proses penambahan produk ke keranjang  
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {  
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;  
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;  
  
    if ($product_id > 0) {  
        $message = addToCart($conn, $user_id, $product_id, $quantity);  
        $_SESSION['cart_message'] = $message;  
    } else {  
        $_SESSION['cart_message'] = "Produk tidak valid.";  
    }  
    header("Location: cart.php");  
    exit();  
}  
  
// Proses penghapusan produk dari keranjang  
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {  
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;  
  
    if ($product_id > 0) {  
        $message = removeFromCart($conn, $user_id, $product_id);  
        $_SESSION['cart_message'] = $message;  
    } else {  
        $_SESSION['cart_message'] = "Produk tidak valid.";  
    }  
    header("Location: cart.php");  
    exit();  
}  
  
// Proses pembaruan jumlah produk di keranjang  
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {  
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;  
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;  
  
    if ($product_id > 0) {  
        $message = updateCartItem($conn, $user_id, $product_id, $quantity);  
        $_SESSION['cart_message'] = $message;  
    } else {  
        $_SESSION['cart_message'] = "Produk tidak valid.";  
    }  
    header("Location: cart.php");  
    exit();  
}  
  
// Ambil semua produk di keranjang  
$stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image   
                        FROM cart c   
                        JOIN products p ON c.product_id = p.id   
                        WHERE c.user_id = ?");  
$stmt->bind_param("i", $user_id);  
$stmt->execute();  
$result = $stmt->get_result();  
  
$total_price = 0;  
while ($row = $result->fetch_assoc()) {  
    $total_price += $row['price'] * $row['quantity'];  
}  
  
// Ambil pesan dari session  
$cart_message = isset($_SESSION['cart_message']) ? $_SESSION['cart_message'] : null;  
unset($_SESSION['cart_message']);  
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Keranjang Belanja</title>  
    <!-- Bootstrap CSS -->  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
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
  
        body {  
            background-color: var(--light-color);  
            color: var(--dark-color);  
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
            background-color: #38a169;  
            border-color: #38a169;  
        }  
  
        .text-primary {  
            color: var(--primary-color) !important;  
        }  
  
        footer {  
            background-color: white !important;  
            box-shadow: 0 -2px 4px rgba(0,0,0,0.05);  
        }  
  
        .cart-item {  
            border-bottom: 1px solid #ddd;  
            padding: 15px 0;  
        }  
  
        .cart-item:last-child {  
            border-bottom: none;  
        }  
  
        .cart-total {  
            font-size: 1.25rem;  
            font-weight: bold;  
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
                                <li><a class="dropdown-item" href="profile.php">  
                                    <i class="bi bi-person me-2"></i> Profil  
                                </a></li>  
                                <li><a class="dropdown-item" href="cart.php">  
                                    <i class="bi bi-cart me-2"></i> Keranjang  
                                </a></li>  
                                <li><a class="dropdown-item" href="orders.php">  
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
  
        <!-- Main Content -->  
        <div class="content container mt-4">  
            <div class="row">  
                <div class="col-12">  
                    <h2 class="mb-4 text-center" style="color: var(--dark-color);">Keranjang Belanja</h2>  
                </div>  
            </div>  
  
            <?php  
            if ($cart_message) {  
                echo '<div class="alert alert-info mb-4">' . htmlspecialchars($cart_message) . '</div>';  
            }  
            ?>  
  
            <?php if ($result->num_rows > 0): ?>  
                <form method="POST" action="">  
                    <div class="table-responsive">  
                        <table class="table table-hover table-striped">  
                            <thead class="table-light">  
                                <tr>  
                                    <th>Produk</th>  
                                    <th>Harga</th>  
                                    <th>Jumlah</th>  
                                    <th>Total</th>  
                                    <th>Aksi</th>  
                                </tr>  
                            </thead>  
                            <tbody>  
                                <?php while ($row = $result->fetch_assoc()): ?>  
                                    <tr class="cart-item">  
                                        <td>  
                                            <div class="d-flex align-items-center">  
                                                <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="img-fluid me-3" style="width: 50px;">  
                                                <span><?php echo htmlspecialchars($row['name']); ?></span>  
                                            </div>  
                                        </td>  
                                        <td>Rp <?php echo number_format($row['price'], 2, ',', '.'); ?></td>  
                                        <td>  
                                            <input type="number" name="quantity[<?php echo htmlspecialchars($row['product_id']); ?>]" value="<?php echo htmlspecialchars($row['quantity']); ?>" class="form-control" min="1" required>  
                                        </td>  
                                        <td>Rp <?php echo number_format($row['price'] * $row['quantity'], 2, ',', '.'); ?></td>  
                                        <td>  
                                            <button type="submit" name="update_cart" class="btn btn-sm btn-primary me-2">  
                                                <i class="bi bi-pencil"></i> Update  
                                            </button>  
                                            <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')">  
                                                <i class="bi bi-trash"></i> Hapus  
                                            </button>  
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">  
                                        </td>  
                                    </tr>  
                                <?php endwhile; ?>  
                            </tbody>  
                        </table>  
                    </div>  
                    <div class="d-flex justify-content-between align-items-center mt-4">  
                        <div>  
                            <a href="index.php" class="btn btn-secondary">  
                                <i class="bi bi-arrow-left me-2"></i> Kembali ke Beranda  
                            </a>  
                        </div>  
                        <div>  
                            <div class="cart-total">Total Harga: Rp <?php echo number_format($total_price, 2, ',', '.'); ?></div>  
                            <a href="checkout.php" class="btn btn-primary mt-2">  
                                <i class="bi bi-check-outbox me-2"></i> Checkout  
                            </a>  
                        </div>  
                    </div>  
                </form>  
            <?php else: ?>  
                <div class="alert alert-warning mb-4">Keranjang Anda kosong.</div>  
                <div class="text-center">  
                    <a href="index.php" class="btn btn-secondary">  
                        <i class="bi bi-arrow-left me-2"></i> Kembali ke Beranda  
                    </a>  
                </div>  
            <?php endif; ?>  
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
$conn->close();  
?>  
