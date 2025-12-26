<?php
include 'db.php';
$message = ""; $error = ""; $verified = false; $user_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify'])) {
        $username = $_POST['username'];
        $phone = $_POST['phone'];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND phone_number = ?");
        $stmt->execute([$username, $phone]);
        $user = $stmt->fetch();
        if ($user) {
            $verified = true;
            $user_id = $user['id'];
        } else {
            $error = "User details not found.";
        }
    }

    if (isset($_POST['reset'])) {
        $user_id = $_POST['user_id'];
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_pass, $user_id]);
        $message = "Password updated! <a href='login.php'>Login here</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 350px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1e293b; color: white; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Reset Password</h3>
        <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
        <?php if($message) echo "<p style='color:green'>$message</p>"; ?>

        <?php if (!$verified): ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="phone" placeholder="Registered Phone Number" required>
                <button type="submit" name="verify">Verify Identity</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <input type="password" name="new_password" placeholder="New Password" required>
                <button type="submit" name="reset">Set New Password</button>
            </form>
        <?php endif; ?>
        <p style="text-align:center; font-size:0.8rem; margin-top:15px;"><a href="login.php" style="color:#64748b">Back to Login</a></p>
    </div>
</body>
</html>