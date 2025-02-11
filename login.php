<?php
session_start();
include 'includes/db.php';

// Cek cookie terlebih dahulu
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    
    $stmt = $conn->prepare("SELECT users.id, users.username, users.role, user_tokens.token 
                           FROM users 
                           JOIN user_tokens ON users.id = user_tokens.user_id 
                           WHERE user_tokens.token = ? AND user_tokens.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Perbarui token untuk keamanan
        $new_token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $update_stmt = $conn->prepare("UPDATE user_tokens SET token = ?, expires_at = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssi", $new_token, $expires, $user['id']);
        $update_stmt->execute();
        
        setcookie('remember_me', $new_token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']) ? true : false;

    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Jika remember me dicentang
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Hapus token lama jika ada
                $delete_stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
                $delete_stmt->bind_param("i", $user['id']);
                $delete_stmt->execute();
                
                // Simpan token baru
                $insert_stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $user['id'], $token, $expires);
                $insert_stmt->execute();
                
                // Set cookie
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }

            header("Location: index.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username atau email tidak ditemukan!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-control {
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        .btn-login {
            background-color: #007bff;
            color: white;
            width: 100%;
            padding: 10px;
        }
        .btn-login:hover {
            background-color: #0056b3;
            color: white;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        .remember-me {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Login</h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="process_login.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
            </div>
            <div class="remember-me">
                <label class="custom-checkbox">
                    <input type="checkbox" name="remember_me" value="1">
                    Remember me
                </label>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>