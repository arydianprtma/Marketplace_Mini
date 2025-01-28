<?php
session_start();
include '../includes/db.php';

// Cek jika admin sudah login
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Proses login admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cari user berdasarkan username/email dan role = 'admin'
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek apakah user ditemukan
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id']; // Simpan ID admin di session
            header("Location: admin_dashboard.php"); // Redirect ke dashboard admin
            exit();
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username atau email tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 50px;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <h2 class="text-center mb-4">Admin Login</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class='alert alert-danger'><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username atau Email</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login">Login</button>
                    </form>
                    
                    <div class="register-link">
                        <p class="text-muted">
                            Don't have an account? <a href="admin_register.php" class="text-primary">Register</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
