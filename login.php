<?php
include 'db.php';
session_start();

// Remember Me Logic: Auto-login if cookie exists
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?"); // In a real app, use a unique token table
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    }
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Remember Me: Set cookie for 30 days
        if (isset($_POST['remember'])) {
            setcookie("remember_user", $user['username'], time() + (86400 * 30), "/");
        }

        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .remember-flex { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; font-size: 0.85rem; color: #64748b; }
        .error { color: #ef4444; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
        .links { margin-top: 20px; text-align: center; font-size: 0.9rem; }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2 style="text-align:center">Login</h2>
        <?php if($error): ?> <div class="error"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <div class="remember-flex">
                <label><input type="checkbox" name="remember"> Remember Me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            
            <button type="submit">Sign In</button>
        </form>
        <div class="links">No account? <a href="register.php">Register</a></div>
    </div>
</body>
</html>