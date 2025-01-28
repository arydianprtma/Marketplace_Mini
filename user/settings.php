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
    
// Proses pengajuan menjadi seller    
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_seller'])) {    
    $shop_name = $_POST['shop_name'];    
    $shop_description = $_POST['shop_description'];    
    $shop_address = $_POST['shop_address'];    
    $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';    
    
    // Pastikan data tidak kosong    
    if (empty($shop_name) || empty($shop_description) || empty($shop_address) || empty($phone_number)) {    
        $_SESSION['error_message'] = "Semua field harus diisi!";    
        $_SESSION['form_data'] = $_POST;    
        $_SESSION['active_tab'] = 'seller-application';    
    } else {    
        // Proses upload logo toko    
        if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {    
            $logo_name = $_FILES['shop_logo']['name'];    
            $logo_tmp_name = $_FILES['shop_logo']['tmp_name'];    
            $logo_extension = pathinfo($logo_name, PATHINFO_EXTENSION);    
            $logo_new_name = time() . '.' . $logo_extension;    
            $upload_path = '../uploads/' . $logo_new_name;    
    
            if (move_uploaded_file($logo_tmp_name, $upload_path)) {    
                // Masukkan data pengajuan seller ke database    
                $stmt = $conn->prepare("INSERT INTO sellers (user_id, shop_name, shop_description, shop_logo, shop_address, phone_number)     
                                        VALUES (?, ?, ?, ?, ?, ?)");    
                $stmt->bind_param("isssss", $user_id, $shop_name, $shop_description, $logo_new_name, $shop_address, $phone_number);    
    
                if ($stmt->execute()) {    
                    $_SESSION['success_message'] = "Pengajuan menjadi seller berhasil!";    
                } else {    
                    $_SESSION['error_message'] = "Pengajuan gagal. Silakan coba lagi.";    
                }    
    
                // Redirect to prevent form resubmission    
                header("Location: " . $_SERVER['PHP_SELF']);    
                exit();    
            } else {    
                $_SESSION['error_message'] = "Gagal mengupload logo toko.";    
                $_SESSION['form_data'] = $_POST;    
                $_SESSION['active_tab'] = 'seller-application';    
            }    
        }    
    }    
        
    // Redirect to prevent form resubmission    
    header("Location: " . $_SERVER['PHP_SELF']);    
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
            $picture_name = $_FILES['profile_picture']['name'];    
            $picture_tmp_name = $_FILES['profile_picture']['tmp_name'];    
            $picture_extension = pathinfo($picture_name, PATHINFO_EXTENSION);    
            $picture_new_name = 'profile_' . $user_id . '.' . $picture_extension;    
            $upload_path = '../uploads/profile_pictures/' . $picture_new_name;    
    
            if (move_uploaded_file($picture_tmp_name, $upload_path)) {    
                $profile_picture = $picture_new_name;    
            } else {    
                $_SESSION['error_message'] = "Gagal mengupload foto profil.";    
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
    
// Ambil pesan dan data form dari session    
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;    
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;    
$seller_form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];    
$profile_form_data = isset($_SESSION['profile_form_data']) ? $_SESSION['profile_form_data'] : [];    
$active_tab = isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'seller-application';    
    
// Hapus session setelah digunakan    
unset($_SESSION['success_message']);    
unset($_SESSION['error_message']);    
unset($_SESSION['form_data']);    
unset($_SESSION['profile_form_data']);    
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
                        <a class="nav-link <?php echo $active_tab == 'seller-application' ? 'active' : ''; ?>" href="#seller-application" data-bs-toggle="tab">    
                            <i class="bi bi-shop me-2"></i> Pengajuan Seller    
                        </a>    
                    </li>    
                    <li class="nav-item">    
                        <a class="nav-link <?php echo $active_tab == 'profile-update' ? 'active' : ''; ?>" href="#profile-update" data-bs-toggle="tab">    
                            <i class="bi bi-person me-2"></i> Update Profil    
                        </a>    
                    </li>  
                    <li class="nav-item">  
                        <a class="nav-link" href="../index.php">  
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
                                <h2 class="mb-4">Update Profil</h2>    
                                <form method="POST" enctype="multipart/form-data">    
                                    <div class="row">    
                                        <div class="col-md-6 mb-3">    
                                            <label for="username" class="form-label">Username</label>    
                                            <input type="text" class="form-control" id="username" name="username"     
                                                   value="<?php echo htmlspecialchars($profile_form_data['username'] ?? $user['username']); ?>" required>    
                                        </div>    
                                        <div class="col-md-6 mb-3">    
                                            <label for="email" class="form-label">Email</label>    
                                            <input type="email" class="form-control" id="email" name="email"     
                                                   value="<?php echo htmlspecialchars($profile_form_data['email'] ?? $user['email']); ?>" required>    
                                        </div>    
                                    </div>    
                                    <div class="mb-3">    
                                        <label for="password" class="form-label">Password Baru (Kosongkan jika tidak ingin mengubah)</label>    
                                        <input type="password" class="form-control" id="password" name="password">    
                                    </div>    
                                    <div class="mb-3">  
                                        <label for="profile_picture" class="form-label">Foto Profil</label>  
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">  
                                    </div>  
                                    <button type="submit" name="update_profile" class="btn btn-warning">    
                                        <i class="bi bi-pencil me-2"></i> Update Profil    
                                    </button>    
                                </form>    
                            </div>    
                        </div>    
                    </div>    
                </div>    
            </div>    
        </div>    
    </div>    
    
    <!-- Bootstrap JS Bundle with Popper -->    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>    
</body>    
</html>  
