<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// 1. SECURITY: Only admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$view = $_GET['view'] ?? 'stats';

// 2. ACTION LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Brand
    if (isset($_POST['save_brand'])) {
        $name = trim($_POST['brand_name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO brands (brand_name) VALUES (?)");
            $stmt->execute([$name]);
        }
    }
    // Add Color
    if (isset($_POST['save_color'])) {
        $name = trim($_POST['color_name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO colors (color_name) VALUES (?)");
            $stmt->execute([$name]);
        }
    }
}

// Approval Handler
if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?");
    $stmt->execute([$_GET['approve']]);
    header("Location: admin.php?view=pending&msg=approved");
    exit();
}

// Brand Deletion
if (isset($_GET['delete_brand'])) {
    $id = $_GET['delete_brand'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE brand_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM brands WHERE id = ?")->execute([$id]);
        header("Location: admin.php?view=brands");
    } else {
        header("Location: admin.php?view=brands&error=in_use");
    }
    exit();
}

// Color Deletion
if (isset($_GET['delete_color'])) {
    $pdo->prepare("DELETE FROM colors WHERE id = ?")->execute([$_GET['delete_color']]);
    header("Location: admin.php?view=colors");
    exit();
}

// 3. FETCH GLOBAL DATA
$total_value = $pdo->query("SELECT SUM(price) FROM cars WHERE status = 'available'")->fetchColumn() ?: 0;
$new_users = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 1 DAY")->fetchColumn();
$counts = $pdo->query("SELECT status, COUNT(*) as count FROM cars GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$top_brand = $pdo->query("SELECT b.brand_name FROM cars c JOIN brands b ON c.brand_id = b.id GROUP BY c.brand_id ORDER BY COUNT(*) DESC LIMIT 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #1e293b; --accent: #2563eb; --danger: #ef4444; --success: #22c55e; --warning: #f59e0b; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--primary); color: white; min-height: 100vh; padding: 20px; position: fixed; }
        .sidebar h2 { color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 25px; }
        .sidebar a { display: block; color: #cbd5e1; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 5px; font-size: 0.9rem; }
        .sidebar a:hover, .active { background: #334155; color: white; }
        
        /* Layout */
        .content { flex: 1; margin-left: 260px; padding: 40px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        /* Stats */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; border-left: 4px solid var(--accent); }
        .stat-card h3 { margin: 0; font-size: 0.8rem; color: #64748b; }
        .stat-card .value { font-size: 1.4rem; font-weight: 800; margin-top: 5px; color: var(--primary); }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 12px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .status-available { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-sold { background: #f1f5f9; color: #475569; }

        /* Action Buttons */
        .btn-view { color: var(--accent); font-weight: 600; text-decoration: none; margin-right: 10px; }
        .btn-edit { color: #6366f1; font-weight: 600; text-decoration: none; margin-right: 10px; }
        .btn-delete { color: var(--danger); font-weight: 600; text-decoration: none; }
        .btn-approve { color: var(--success); font-weight: 700; text-decoration: none; margin-right: 10px; }
        
        .item-tag { display: inline-flex; align-items: center; background: #f1f5f9; padding: 5px 15px; border-radius: 20px; margin: 5px; font-weight: 600; font-size: 0.85rem; }
        .input-group { display: flex; gap: 10px; margin-top: 15px; }
        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-add { background: var(--accent); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Dashboard</h2>
    <a href="admin.php?view=stats" class="<?= $view=='stats'?'active':'' ?>">📊 Overview</a>
    <a href="admin.php?view=all_ads" class="<?= $view=='all_ads'?'active':'' ?>">🚗 Manage All Ads</a>
    <a href="admin.php?view=pending" class="<?= $view=='pending'?'active':'' ?>">⏳ Approval Queue (<?= $counts['pending'] ?? 0 ?>)</a>
    <a href="admin.php?view=brands" class="<?= $view=='brands'?'active':'' ?>">🏷️ Car Brands</a>
    <a href="admin.php?view=colors" class="<?= $view=='colors'?'active':'' ?>">🎨 Color Options</a>
    <hr style="border: 0; border-top: 1px solid #334155; margin: 20px 0;">
    <a href="index.php" target="_blank">🌐 View Site</a>
    <a href="logout.php" style="color:var(--danger)">Logout</a>
</div>

<div class="content">

    <?php if ($view == 'stats'): ?>
        <h1>System Statistics</h1>
        <div class="summary-grid">
            <div class="stat-card"><h3>Market Value</h3><div class="value">$<?= number_format($total_value) ?></div></div>
            <div class="stat-card" style="border-color:var(--success)"><h3>Today's Signups</h3><div class="value"><?= $new_users ?></div></div>
            <div class="stat-card" style="border-color:var(--warning)"><h3>Pending Approval</h3><div class="value"><?= $counts['pending'] ?? 0 ?></div></div>
            <div class="stat-card"><h3>Top Brand</h3><div class="value"><?= $top_brand ?: 'N/A' ?></div></div>
        </div>

    <?php elseif ($view == 'all_ads'): ?>
        <h1>Master Listing Manager</h1>
        <div class="card">
            <table>
                <thead>
                    <tr><th>Car Model</th><th>Seller</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT cars.*, brands.brand_name, users.username FROM cars 
                                        JOIN brands ON cars.brand_id = brands.id 
                                        JOIN users ON cars.user_id = users.id 
                                        ORDER BY created_at DESC");
                    foreach($stmt->fetchAll() as $ad): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ad['brand_name'] . ' ' . $ad['model']) ?></strong></td>
                        <td>@<?= htmlspecialchars($ad['username']) ?></td>
                        <td>$<?= number_format($ad['price']) ?></td>
                        <td><span class="status-pill status-<?= $ad['status'] ?>"><?= $ad['status'] ?></span></td>
                        <td>
                            <a href="car-details.php?id=<?= $ad['id'] ?>" target="_blank" class="btn-view">View</a>
                            <a href="edit-ad.php?id=<?= $ad['id'] ?>" class="btn-edit">Edit</a>
                            <a href="delete-ad.php?id=<?= $ad['id'] ?>" class="btn-delete" onclick="return confirm('Delete permanently?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($view == 'pending'): ?>
        <h1>Ads Awaiting Review</h1>
        <div class="card">
            <?php
            $stmt = $pdo->query("SELECT cars.*, brands.brand_name, users.username FROM cars 
                                JOIN brands ON cars.brand_id = brands.id 
                                JOIN users ON cars.user_id = users.id 
                                WHERE status = 'pending'");
            $pending = $stmt->fetchAll();
            if (!$pending): echo "<p>The approval queue is empty.</p>"; else: ?>
            <table>
                <thead><tr><th>Car Details</th><th>Seller</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($pending as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['brand_name'] . ' ' . $p['model']) ?> ($<?= number_format($p['price']) ?>)</td>
                        <td>@<?= htmlspecialchars($p['username']) ?></td>
                        <td>
                            <a href="car-details.php?id=<?= $p['id'] ?>" target="_blank" class="btn-view">Preview Ad</a>
                            <a href="admin.php?approve=<?= $p['id'] ?>" class="btn-approve">Approve</a>
                            <a href="delete-ad.php?id=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Reject and Delete?')">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    <?php elseif ($view == 'brands'): ?>
        <h1>Manage Car Brands</h1>
        <div class="card">
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="brand_name" placeholder="Enter brand name..." required>
                    <button type="submit" name="save_brand" class="btn-add">Add Brand</button>
                </div>
            </form>
            <div style="margin-top:20px;">
                <?php foreach($pdo->query("SELECT * FROM brands ORDER BY brand_name ASC") as $b): ?>
                    <div class="item-tag">
                        <?= htmlspecialchars($b['brand_name']) ?>
                        <a href="admin.php?view=brands&delete_brand=<?= $b['id'] ?>" style="color:var(--danger); text-decoration:none; margin-left:10px;" onclick="return confirm('Delete Brand?')">×</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($view == 'colors'): ?>
        <h1>Manage Car Colors</h1>
        <div class="card">
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="color_name" placeholder="Enter color name..." required>
                    <button type="submit" name="save_color" class="btn-add">Add Color</button>
                </div>
            </form>
            <div style="margin-top:20px;">
                <?php foreach($pdo->query("SELECT * FROM colors ORDER BY color_name ASC") as $c): ?>
                    <div class="item-tag">
                        <?= htmlspecialchars($c['color_name']) ?>
                        <a href="admin.php?view=colors&delete_color=<?= $c['id'] ?>" style="color:var(--danger); text-decoration:none; margin-left:10px;" onclick="return confirm('Delete Color?')">×</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>