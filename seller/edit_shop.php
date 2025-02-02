<?php
session_start();
include '../includes/db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil data toko dari database
$stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$shop_data = $result->fetch_assoc();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = trim($_POST['shop_name']);
    $shop_description = trim($_POST['shop_description']);
    $shop_address = trim($_POST['shop_address']);
    $phone_number = trim($_POST['phone_number']);
    $shop_email = trim($_POST['shop_email']);
    $error_message = [];
    $success_message = "";

    // Validasi input
    if (empty($shop_name)) {
        $error_message[] = "Nama toko tidak boleh kosong!";
    }
    if (empty($shop_description)) {
        $error_message[] = "Deskripsi toko tidak boleh kosong!";
    }
    if (empty($shop_address)) {
        $error_message[] = "Alamat toko tidak boleh kosong!";
    }
    if (empty($phone_number)) {
        $error_message[] = "Nomor telepon tidak boleh kosong!";
    } else {
        // Ubah awalan 0 menjadi +62
        if (substr($phone_number, 0, 1) === '0') {
            $phone_number = '+62' . substr($phone_number, 1);
        }
        // Validasi nomor telepon
        if (!preg_match('/^\+62\d{9,12}$/', $phone_number)) {
            $error_message[] = "Nomor telepon tidak valid!";
        }
    }
    if (empty($shop_email) || !filter_var($shop_email, FILTER_VALIDATE_EMAIL)) {
        $error_message[] = "Email toko tidak valid!";
    }

    // Proses upload logo jika ada
    $shop_logo = $shop_data['shop_logo']; // Gunakan logo yang ada sebagai default
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['shop_logo']['type'], $allowed_types)) {
            $file_name = $_FILES['shop_logo']['name'];
            $file_tmp = $_FILES['shop_logo']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = 'shop_' . time() . '.' . $file_ext;
            $upload_path = '../uploads/shop_logos/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Hapus logo lama jika ada dan bukan default
                if (!empty($shop_data['shop_logo']) && $shop_data['shop_logo'] != 'default_shop.png') {
                    $old_file = '../uploads/shop_logos/' . $shop_data['shop_logo'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $shop_logo = $new_file_name;
            } else {
                $error_message[] = "Gagal mengupload logo toko.";
            }
        } else {
            $error_message[] = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
        }
    }

    // Update data toko jika tidak ada error
    if (empty($error_message)) {
        $update_stmt = $conn->prepare("UPDATE sellers SET shop_name = ?, shop_description = ?, shop_logo = ?, shop_address = ?, phone_number = ?, shop_email = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssssssi", $shop_name, $shop_description, $shop_logo, $shop_address, $phone_number, $shop_email, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $success_message = "Data toko berhasil diperbarui!";
            $_SESSION['shop_logo'] = $shop_logo;
            // Reload data toko
            $stmt->execute();
            $shop_data = $stmt->get_result()->fetch_assoc();
        } else {
            $error_message[] = "Gagal memperbarui data toko. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Toko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-color);
        }

        .form-header i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .logo-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 1rem;
            border: 2px solid #dee2e6;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }

        .form-control.with-icon {
            border-left: none;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 2rem;
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-shop"></i> Marketplace
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                        <a class="nav-link" href="manage_shop.php">
                            <i class="bi bi-shop-window"></i> Kelola Toko
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="../uploads/shop_logos/<?php echo htmlspecialchars($shop_data['shop_logo'] ?? 'default_shop.png'); ?>" alt="Logo Toko" class="profile-picture">
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Tamu'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../user/profile.php"><i class="bi bi-person"></i> Profil</a></li>
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
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <i class="bi bi-shop"></i>
                <h2>Edit Informasi Toko</h2>
                <p class="text-muted">Perbarui informasi toko Anda di bawah ini</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php 
                    foreach ($error_message as $error) {
                        echo htmlspecialchars($error) . "<br>";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="shop_name" class="form-label">Nama Toko</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shop"></i></span>
                        <input type="text" class="form-control with-icon" id="shop_name" name="shop_name" 
                               value="<?php echo htmlspecialchars($shop_data['shop_name'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="shop_description" class="form-label">Deskripsi Toko</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                        <textarea class="form-control with-icon" id="shop_description" name="shop_description" 
                                  rows="4" required><?php echo htmlspecialchars($shop_data['shop_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="shop_logo" class="form-label">Logo Toko</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-image"></i></span>
                        <input type="file" class="form-control with-icon" id="shop_logo" name="shop_logo" 
                               accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="text-center">
                        <img id="logoPreview" src="../uploads/shop_logos/<?php echo htmlspecialchars($shop_data['shop_logo'] ?? 'default_shop.png'); ?>" 
                             alt="Logo Preview" class="logo-preview">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="shop_address" class="form-label">Alamat Toko</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <textarea class="form-control with-icon" id="shop_address" name="shop_address" 
                                  rows="3" required><?php echo htmlspecialchars($shop_data['shop_address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Nomor Telepon</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control with-icon" id="phone_number" name="phone_number"
                                   value="<?php echo htmlspecialchars($shop_data['phone_number'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="shop_email" class="form-label">Email Toko</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control with-icon" id="shop_email" name="shop_email"
                                   value="<?php echo htmlspecialchars($shop_data['shop_email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                    <a href="manage_shop.php" class="btn btn-secondary py-2">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar yang diupload
        function previewImage(input) {
            const preview = document.getElementById('logoPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
               reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Form validation
        (function() {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Konfirmasi sebelum meninggalkan halaman jika ada perubahan
        let formChanged = false;
        const form = document.querySelector('form');
        const formInputs = form.querySelectorAll('input, textarea');

        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Reset warning ketika form disubmit
        form.addEventListener('submit', () => {
            formChanged = false;
        });

        // Phone number validation
        const phoneInput = document.getElementById('phone_number');
        phoneInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                // Format nomor telepon Indonesia
                if (value.startsWith('0')) {
                    value = '62' + value.slice(1);
                }
                if (value.startsWith('62')) {
                    // Batasi panjang nomor
                    value = value.slice(0, 13);
                    // Format dengan spasi
                    value = value.replace(/(\d{2})(\d{3})(\d{4})(\d{4})/, '$1 $2 $3 $4');
                }
            }
            e.target.value = value;
        });

        // Email validation
        const emailInput = document.getElementById('shop_email');
        emailInput.addEventListener('blur', (e) => {
            const email = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                emailInput.setCustomValidity('Masukkan alamat email yang valid');
            } else {
                emailInput.setCustomValidity('');
            }
        });

        // Character counter untuk deskripsi
        const descriptionInput = document.getElementById('shop_description');
        const maxLength = 500; // Maksimum karakter untuk deskripsi

        function createCharCounter() {
            const counter = document.createElement('small');
            counter.classList.add('text-muted', 'float-end');
            descriptionInput.parentNode.insertAdjacentElement('afterend', counter);
            
            function updateCounter() {
                const remaining = maxLength - descriptionInput.value.length;
                counter.textContent = `${remaining} karakter tersisa`;
                if (remaining < 50) {
                    counter.classList.add('text-warning');
                } else {
                    counter.classList.remove('text-warning');
                }
            }
            
            descriptionInput.addEventListener('input', updateCounter);
            updateCounter(); // Initial count
        }

        createCharCounter();
    </script>
</body>
</html>
