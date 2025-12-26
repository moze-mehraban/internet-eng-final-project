<?php
include 'db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $phone = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, phone_number, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $phone, $password]);
        header("Location: login.php?registered=success");
        exit();
    } catch (PDOException $e) {
        $message = "Username or Phone already exists.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-bottom: 20px; color: #1e293b; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .error { color: #ef4444; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Create Account</h2>
        <?php if($message): ?> <div class="error"><?= $message ?></div> <?php endif; ?>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="phone_number" placeholder="Phone Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p style="margin-top:15px; font-size:0.9rem;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>