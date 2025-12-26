<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$profile_msg = $profile_err = $pass_msg = $pass_err = "";

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SECTION 1: UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $phone = $_POST['phone_number'];
        
        try {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, phone_number = ? WHERE id = ?");
            $update->execute([$full_name, $username, $phone, $user_id]);
            
            $_SESSION['username'] = $username; // Update session in case username changed
            $profile_msg = "Profile updated successfully!";
            
            // Refresh local variable to show new data in form
            $user['full_name'] = $full_name;
            $user['username'] = $username;
            $user['phone_number'] = $phone;
        } catch (PDOException $e) {
            $profile_err = "Username or Phone is already taken.";
        }
    }

    // SECTION 2: CHANGE PASSWORD
    if (isset($_POST['update_password'])) {
        $current_pw = $_POST['current_password'];
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];
        
        if (password_verify($current_pw, $user['password'])) {
            if ($new_pw === $confirm_pw) {
                $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hashed_pw, $user_id]);
                $pass_msg = "Password changed successfully!";
            } else {
                $pass_err = "New passwords do not match.";
            }
        } else {
            $pass_err = "Current password is incorrect.";
        }
    }
}

// Determine where the "Back" button should go
$back_url = ($_SESSION['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
$back_text = ($_SESSION['role'] === 'admin') ? 'Admin Panel' : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --success: #22c55e; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 40px; color: var(--dark); margin: 0; }
        .container { max-width: 600px; margin: 0 auto; }
        
        .back-btn { 
            display: inline-block; 
            margin-bottom: 25px; 
            text-decoration: none; 
            color: #64748b; 
            font-weight: 600; 
            transition: 0.2s;
        }
        .back-btn:hover { color: var(--primary); }

        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h2 { margin-top: 0; margin-bottom: 20px; font-size: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        
        label { display: block; margin-top: 15px; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        
        button { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px; font-size: 1rem; }
        button:hover { opacity: 0.9; }
        
        .error { color: var(--danger); font-size: 0.85rem; margin-top: 10px; font-weight: 500; }
        .success { color: var(--success); font-size: 0.85rem; margin-top: 10px; font-weight: 500; }
        
        .password-btn { background: var(--dark); }
    </style>
</head>
<body>

<div class="container">
    <a href="<?= $back_url ?>" class="back-btn">← Back to <?= $back_text ?></a>
    
    <div class="card">
        <h2>Profile Information</h2>
        <?php if($profile_err): ?> <p class="error"><?= $profile_err ?></p> <?php endif; ?>
        <?php if($profile_msg): ?> <p class="success"><?= $profile_msg ?></p> <?php endif; ?>
        
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            
            <label>Phone Number</label>
            <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>" required>
            
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>

    <div class="card">
        <h2>Change Password</h2>
        <?php if($pass_err): ?> <p class="error"><?= $pass_err ?></p> <?php endif; ?>
        <?php if($pass_msg): ?> <p class="success"><?= $pass_msg ?></p> <?php endif; ?>
        
        <form method="POST">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
            
            <label>New Password</label>
            <input type="password" name="new_password" required>
            
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
            
            <button type="submit" name="update_password" class="password-btn">Change Password</button>
        </form>
    </div>
</div>

</body>
</html>