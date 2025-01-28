<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Role otomatis menjadi 'buyer'
    $role = 'buyer';

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registrasi berhasil. Silakan login!";
        header("Location: login.php");
        exit();
    } else {
        $error = "Registrasi gagal. Silakan coba lagi.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .register-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-control {
            margin-bottom: 15px;
        }
        .btn-register {
            width: 100%;
            background-color: #007bff;
            color: white;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Create an Account</h2>
            <p class="text-muted">Register to start your journey</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-register">Register</button>
        </form>
        
        <div class="login-link">
            <p class="text-muted">
                Already have an account? <a href="login.php" class="text-primary">Login</a>
            </p>
        </div>
    </div>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>