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
    $stock = isset($_POST['stock']) ? $_POST['stock'] : 0; // Menghindari undefined index
    $seller_id = $_SESSION['user_id'];  
    $image = null; // Inisialisasi variabel untuk gambar

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
                $image_new_name = 'product_' . time() . '.' . $image_extension; // Menggunakan timestamp untuk nama unik
                $upload_path = '../uploads/product_images/' . $image_new_name;

                if (move_uploaded_file($image_tmp_name, $upload_path)) {
                    $image = $image_new_name; // Simpan nama gambar
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
</head>  
<body>  
    <div class="container mt-5">  
        <h2>Tambah Produk</h2>  
        <?php if (isset($error_message)): ?>  
            <div class="alert alert-danger"><?php echo $error_message; ?></div>  
        <?php endif; ?>  
        <?php if (isset($success_message)): ?>  
            <div class="alert alert-success"><?php echo $success_message; ?></div>  
        <?php endif; ?>  
        <form method="POST" enctype="multipart/form-data">  
            <div class="mb-3">  
                <label for="name" class="form-label">Nama Produk</label>  
                <input type="text" class="form-control" id="name" name="name" required>  
            </div>  
            <div class="mb-3">  
                <label for="description" class="form-label">Deskripsi Produk</label>  
                <textarea class="form-control" id="description" name="description"></textarea>  
            </div>  
            <div class="mb-3">  
                <label for="price" class="form-label">Harga Produk</label>  
                <input type="number" class="form-control" id="price" name="price" step="0.01" required>  
            </div>  
            <div class="mb-3">  
                <label for="stock" class="form-label">Jumlah Stok</label>  
                <input type="number" class="form-control" id="stock" name="stock" required>  
            </div>  
            <div class="mb-3">  
                <label for="image" class="form-label">Gambar Produk</label>  
                <input type="file" class="form-control" id="image" name="image" accept="image/*">  
            </div>  
            <button type="submit" name="add_product" class="btn btn-primary">Tambah Produk</button>  
        </form>  
    </div>  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>  
</html>  
