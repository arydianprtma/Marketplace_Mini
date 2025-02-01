<?php
session_start();
include '../includes/db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Proses penambahan produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = isset($_POST['stock']) ? $_POST['stock'] : 0;
    $seller_id = $_SESSION['user_id'];
    $image = null;

    // Validasi input
    if (empty($name) || empty($price) || empty($stock)) {
        $error_message = "Nama, harga, dan stok produk tidak boleh kosong!";
    } else {
        // Proses upload gambar jika ada
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['image']['type'], $allowed_types)) {
                $image_name = $_FILES['image']['name'];
                $image_tmp_name = $_FILES['image']['tmp_name'];
                $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                $image_new_name = 'product_' . time() . '.' . $image_extension;
                $upload_path = '../uploads/product_images/' . $image_new_name;

                if (move_uploaded_file($image_tmp_name, $upload_path)) {
                    $image = $image_new_name;
                } else {
                    $error_message = "Gagal mengupload gambar produk.";
                }
            } else {
                $error_message = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
            }
        }

        // Jika tidak ada error, simpan produk ke database
        if (!isset($error_message)) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock, seller_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsii", $name, $description, $price, $image, $stock, $seller_id);

            if ($stmt->execute()) {
                $success_message = "Produk berhasil ditambahkan!";
            } else {
                $error_message = "Gagal menambahkan produk. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
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
            color: #2c3e50;
        }
        .form-header i {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: 8px;
            display: none;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            padding: 0.8rem 2rem;
            font-weight: 600;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-box-open"></i>
                <h2>Tambah Produk Baru</h2>
                <p class="text-muted">Lengkapi informasi produk Anda di bawah ini</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="name" class="form-label">Nama Produk</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" class="form-control with-icon" id="name" name="name" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Deskripsi Produk</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                        <textarea class="form-control with-icon" id="description" name="description" rows="4"></textarea>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="price" class="form-label">Harga Produk</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                            <input type="number" class="form-control with-icon" id="price" name="price" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Jumlah Stok</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                            <input type="number" class="form-control with-icon" id="stock" name="stock" required>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="image" class="form-label">Gambar Produk</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-image"></i></span>
                        <input type="file" class="form-control with-icon" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <img id="imagePreview" class="preview-image" src="#" alt="Preview">
                </div>

                <div class="d-grid">
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar yang diupload
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        (function () {
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
    </script>
</body>
</html>