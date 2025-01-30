<?php    
session_start();    
include '../includes/db.php';    
    
// Cek apakah user sudah login    
if (!isset($_SESSION['user_id'])) {    
    header("Location: login.php");    
    exit();    
}    
    
// Ambil data user    
$user_id = $_SESSION['user_id'];    
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");    
$stmt->bind_param("i", $user_id);    
$stmt->execute();    
$user = $stmt->get_result()->fetch_assoc();    

// Jika user tidak ditemukan di database
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}
    
// Proses update profil    
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {    
    $username = $_POST['username'];    
    $email = $_POST['email'];    
    $password = $_POST['password'];    

    // Validasi input    
    if (empty($username) || empty($email)) {    
        $_SESSION['error_message'] = "Username dan Email tidak boleh kosong!";    
        $_SESSION['profile_form_data'] = $_POST;    
        $_SESSION['active_tab'] = 'profile-update';    
    } else {    
        $profile_picture = $user['profile_picture']; // Default: gunakan data sebelumnya    

        // Proses upload foto profil    
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {    
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                $picture_name = $_FILES['profile_picture']['name'];    
                $picture_tmp_name = $_FILES['profile_picture']['tmp_name'];    
                $picture_extension = pathinfo($picture_name, PATHINFO_EXTENSION);    
                $picture_new_name = 'profile_' . $user_id . '.' . $picture_extension;    
                $upload_path = '../uploads/profile_pictures/' . $picture_new_name;    
    
                if (move_uploaded_file($picture_tmp_name, $upload_path)) {    
                    // Hapus foto profil lama jika ada    
                    if ($user['profile_picture'] && file_exists('../uploads/profile_pictures/' . $user['profile_picture'])) {    
                        unlink('../uploads/profile_pictures/' . $user['profile_picture']);    
                    }    
                    $profile_picture = $picture_new_name;    
                } else {    
                    $_SESSION['error_message'] = "Gagal mengupload foto profil.";    
                    $_SESSION['profile_form_data'] = $_POST;    
                    $_SESSION['active_tab'] = 'profile-update';    
                    header("Location: " . $_SERVER['PHP_SELF']);    
                    exit();    
                }    
            } else {
                $_SESSION['error_message'] = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
                $_SESSION['profile_form_data'] = $_POST;
                $_SESSION['active_tab'] = 'profile-update';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }    
    
        // Cek apakah password diisi    
        if (!empty($password)) {    
            // Hash password    
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);    
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_picture = ? WHERE id = ?");    
            $stmt->bind_param("ssssi", $username, $email, $hashed_password, $profile_picture, $user_id);    
        } else {    
            // Update tanpa password    
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");    
            $stmt->bind_param("sssi", $username, $email, $profile_picture, $user_id);    
        }    
    
        if ($stmt->execute()) {    
            $_SESSION['success_message'] = "Profil berhasil diupdate!";    
            
            // Update session data    
            $_SESSION['username'] = $username;    
        } else {    
            $_SESSION['error_message'] = "Gagal update profil. Silakan coba lagi.";    
            $_SESSION['profile_form_data'] = $_POST;    
            $_SESSION['active_tab'] = 'profile-update';    
        }    
    }    
        
    // Redirect to prevent form resubmission    
    header("Location: " . $_SERVER['PHP_SELF']);    
    exit();    
}    

// Proses pengajuan seller
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_seller'])) {
    $shop_name = $_POST['shop_name'];
    $phone_number = $_POST['phone_number'];
    $shop_description = $_POST['shop_description'];
    $shop_address = $_POST['shop_address'];

    // Validasi input
    if (empty($shop_name) || empty($phone_number) || empty($shop_description) || empty($shop_address)) {
        $_SESSION['error_message'] = "Semua field harus diisi!";
        $_SESSION['seller_form_data'] = $_POST;
        $_SESSION['active_tab'] = 'seller-application';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Proses upload logo toko
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['shop_logo']['type'], $allowed_types)) {
            $logo_name = $_FILES['shop_logo']['name'];
            $logo_tmp_name = $_FILES['shop_logo']['tmp_name'];
            $logo_extension = pathinfo($logo_name, PATHINFO_EXTENSION);
            $logo_new_name = 'shop_' . $user_id . '.' . $logo_extension;
            $upload_path = '../uploads/shop_logos/' . $logo_new_name;

            if (move_uploaded_file($logo_tmp_name, $upload_path)) {
                $shop_logo = $logo_new_name;
            } else {
                $_SESSION['error_message'] = "Gagal mengupload logo toko.";
                $_SESSION['seller_form_data'] = $_POST;
                $_SESSION['active_tab'] = 'seller-application';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
            $_SESSION['seller_form_data'] = $_POST;
            $_SESSION['active_tab'] = 'seller-application';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Logo toko harus diunggah.";
        $_SESSION['seller_form_data'] = $_POST;
        $_SESSION['active_tab'] = 'seller-application';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Insert data seller
    $stmt = $conn->prepare("INSERT INTO sellers (user_id, shop_name, phone_number, shop_description, shop_address, shop_logo, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssss", $user_id, $shop_name, $phone_number, $shop_description, $shop_address, $shop_logo);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pengajuan menjadi seller berhasil dikirim!";
    } else {
        $_SESSION['error_message'] = "Gagal mengajukan menjadi seller. Silakan coba lagi.";
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Query untuk mendapatkan status toko jika user adalah seller
$seller_status = null;
$shop_info = null;
if ($user['role'] == 'seller') {
    $stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $seller_data = $result->fetch_assoc();
        $seller_status = $seller_data['status'];
        $shop_info = $seller_data;
    }
}

// Query untuk mendapatkan riwayat pesanan
$orders = [];
$stmt = $conn->prepare("
    SELECT o.*, oi.quantity, oi.price, p.name as product_name 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Ambil pesan dan data form dari session    
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;    
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;    
$profile_form_data = isset($_SESSION['profile_form_data']) ? $_SESSION['profile_form_data'] : [];    
$seller_form_data = isset($_SESSION['seller_form_data']) ? $_SESSION['seller_form_data'] : [];
$active_tab = isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'profile-update';    
    
// Hapus session setelah digunakan    
unset($_SESSION['success_message']);    
unset($_SESSION['error_message']);    
unset($_SESSION['profile_form_data']);    
unset($_SESSION['seller_form_data']);    
unset($_SESSION['active_tab']);    
?>    
  
<!DOCTYPE html>    
<html lang="id">    
<head>    
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>Pengaturan Akun</title>    
    <!-- Bootstrap CSS -->    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <!-- Bootstrap Icons -->    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">    
    <style>    
        body {    
            background-color: #f4f6f9;    
        }    
        .sidebar {    
            height: 100vh;    
            background-color: #ffffff;    
            box-shadow: 0 0 15px rgba(0,0,0,0.1);    
            padding-top: 30px;    
        }    
        .sidebar .nav-link {    
            color: #6c757d;    
            transition: all 0.3s ease;    
        }    
        .sidebar .nav-link.active {    
            background-color: #007bff;    
            color: white !important;    
            border-radius: 5px;    
        }    
        .sidebar .nav-link:hover {    
            color: #007bff;    
        }    
        .content-area {    
            background-color: #ffffff;    
            border-radius: 10px;    
            box-shadow: 0 0 15px rgba(0,0,0,0.1);    
            padding: 30px;    
            margin-top: 20px;    
        }    
        .form-control, .btn {    
            border-radius: 5px;    
        }    
    </style>    
</head>    
<body>    
    <div class="container-fluid">    
        <div class="row">    
            <!-- Sidebar -->    
            <div class="col-md-3 col-lg-2 sidebar">    
                <h4 class="text-center mb-4">Pengaturan Akun</h4>    
                <ul class="nav flex-column">
                    <li class="nav-item">    
                        <a class="nav-link <?php echo $active_tab == 'profile-update' ? 'active' : ''; ?>" href="#profile-update" data-bs-toggle="tab">    
                            <i class="bi bi-person me-2"></i> Update Profil    
                        </a>    
                    </li>    
                    <li class="nav-item">    
                        <a class="nav-link <?php echo $active_tab == 'seller-application' ? 'active' : ''; ?>" href="#seller-application" data-bs-toggle="tab">    
                            <i class="bi bi-shop me-2"></i> Pengajuan Seller    
                        </a>    
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'order-history' ? 'active' : ''; ?>" href="#order-history" data-bs-toggle="tab">
                            <i class="bi bi-receipt me-2"></i> Pesanan
                        </a>
                    </li>
                    <li class="nav-item">  
                        <a class="nav-link" href="../index.php" style="color:rgb(255, 0, 0);">  
                            <i class="bi bi-house me-2"></i> Kembali ke Beranda  
                        </a>  
                    </li>  
                </ul>    
            </div>    
    
            <!-- Main Content Area -->    
            <div class="col-md-9 col-lg-10">    
                <div class="container">    
                    <?php     
                    if ($success_message) {    
                        echo "<div class='alert alert-success mt-3'>" . htmlspecialchars($success_message) . "</div>";    
                    }    
                    if ($error_message) {    
                        echo "<div class='alert alert-danger mt-3'>" . htmlspecialchars($error_message) . "</div>";    
                    }    
                    ?>    
    
                    <div class="tab-content">    
                        <!-- Seller Application Tab -->    
                        <div class="tab-pane fade <?php echo $active_tab == 'seller-application' ? 'show active' : ''; ?>" id="seller-application">    
                            <div class="content-area">    
                                <h2 class="mb-4">Pengajuan Menjadi Seller</h2>    
                                <form method="POST" enctype="multipart/form-data">    
                                    <div class="row">    
                                        <div class="col-md-6 mb-3">    
                                            <label for="shop_name" class="form-label">Nama Toko</label>    
                                            <input type="text" class="form-control" id="shop_name" name="shop_name"     
                                                   value="<?php echo htmlspecialchars($seller_form_data['shop_name'] ?? ''); ?>" required>    
                                        </div>    
                                        <div class="col-md-6 mb-3">    
                                            <label for="phone_number" class="form-label">Nomor Telepon</label>    
                                            <input type="tel" class="form-control" id="phone_number" name="phone_number"     
                                                   value="<?php echo htmlspecialchars($seller_form_data['phone_number'] ?? ''); ?>" required>    
                                        </div>    
                                    </div>    
                                    <div class="mb-3">    
                                        <label for="shop_description" class="form-label">Deskripsi Toko</label>    
                                        <textarea class="form-control" id="shop_description" name="shop_description" rows="3" required><?php     
                                            echo htmlspecialchars($seller_form_data['shop_description'] ?? '');     
                                        ?></textarea>    
                                    </div>    
                                    <div class="mb-3">    
                                        <label for="shop_address" class="form-label">Alamat Toko</label>    
                                        <input type="text" class="form-control" id="shop_address" name="shop_address"     
                                               value="<?php echo htmlspecialchars($seller_form_data['shop_address'] ?? ''); ?>" required>    
                                    </div>    
                                    <div class="mb-3">    
                                        <label for="shop_logo" class="form-label">Logo Toko</label>    
                                        <input type="file" class="form-control" id="shop_logo" name="shop_logo" accept="image/*" required>    
                                    </div>    
                                    <button type="submit" name="apply_seller" class="btn btn-primary">    
                                        <i class="bi bi-send me-2"></i> Ajukan Menjadi Seller    
                                    </button>    
                                </form>    
                            </div>    
                        </div>    
    
                        <!-- Profile Update Tab -->    
                        <div class="tab-pane fade <?php echo $active_tab == 'profile-update' ? 'show active' : ''; ?>" id="profile-update">    
                            <div class="content-area">
                                <div class="row">
                                    <!-- Informasi Profil -->
                                    <div class="col-md-4">
                                        <div class="text-center mb-4">
                                            <div class="position-relative d-inline-block">
                                                <?php if ($user['profile_picture']): ?>
                                                    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                         alt="Profile Picture" class="img-fluid rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 200px; height: 200px;">
                                                        <i class="bi bi-person-fill text-white" style="font-size: 5rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <h4 class="mt-3"><?php echo htmlspecialchars($user['username']); ?></h4>
                                            <p class="text-muted">
                                                Role: <?php echo ucfirst($user['role']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Form Update Profil -->
                                    <div class="col-md-8">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" name="username"
                                                       value="<?php echo htmlspecialchars($profile_form_data['username'] ?? $user['username']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                       value="<?php echo htmlspecialchars($profile_form_data['email'] ?? $user['email']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password (Biarkan kosong jika tidak ingin mengubah)</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                            </div>
                                            <div class="mb-3">
                                            <label for="profile_picture" class="form-label">Foto Profil</label>
                                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                            </div>
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Simpan Perubahan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Pesanan Tab -->
                        <div class="tab-pane fade" id="order-history">
                            <div class="content-area">
                                <h2 class="mb-4">Riwayat Pesanan</h2>
                                <?php if (empty($orders)): ?>
                                    <p class="text-muted">Anda belum memiliki pesanan.</p>
                                <?php else: ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID Pesanan</th>
                                                <th>Nama Produk</th>
                                                <th>Jumlah</th>
                                                <th>Harga</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['price']); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($order['created_at']))); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

