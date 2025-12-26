<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all cars for this user
$stmt = $pdo->prepare("SELECT cars.*, brands.brand_name, 
                      (SELECT image_url FROM car_images WHERE car_id = cars.id LIMIT 1) as main_image 
                      FROM cars 
                      JOIN brands ON cars.brand_id = brands.id 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$all_cars = $stmt->fetchAll();

// Categorize cars into arrays
$pending_cars = [];
$available_cars = [];
$sold_cars = [];

foreach ($all_cars as $car) {
    if ($car['status'] == 'pending') $pending_cars[] = $car;
    elseif ($car['status'] == 'available') $available_cars[] = $car;
    elseif ($car['status'] == 'sold') $sold_cars[] = $car;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - CARSPAGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --pending: #f59e0b; --success: #22c55e; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        h2 { font-size: 1.2rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .section { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 40px; }
        
        .car-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f5f9; gap: 20px; }
        .car-row:last-child { border-bottom: none; }
        .car-img { width: 100px; height: 70px; border-radius: 8px; object-fit: cover; background: #eee; }
        .car-info { flex: 1; }
        .car-info h4 { margin: 0; font-size: 1rem; color: var(--dark); }
        .car-info p { margin: 5px 0 0; font-size: 0.85rem; color: #64748b; }
        
        .actions { display: flex; gap: 15px; }
        .btn { text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .btn-edit { color: var(--primary); }
        .btn-delete { color: var(--danger); }
        .btn-post { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; }
        
        .status-tag { font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 700; }
        .tag-pending { background: #fef3c7; color: #92400e; }
        .tag-available { background: #dcfce7; color: #166534; }
        .tag-sold { background: #f1f5f9; color: #475569; }
        
        .empty-msg { font-size: 0.9rem; color: #94a3b8; padding: 20px 0; }
        .lock-icon { font-size: 0.8rem; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>My Dashboard</h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="index.php" style="text-decoration:none; color:#64748b;">Home</a>
            <a href="settings.php" style="text-decoration:none; color:#64748b;">Settings</a>
            <a href="post-ad.php" class="btn btn-post">+ New Ad</a>
        </div>
    </div>

    <div class="section">
        <h2 style="color: var(--success);">🟢 Available Listings</h2>
        <?php if(empty($available_cars)): ?>
            <p class="empty-msg">No active ads currently live.</p>
        <?php else: foreach($available_cars as $car): ?>
            <div class="car-row">
                <img src="<?= $car['main_image'] ?: 'https://via.placeholder.com/100x70' ?>" class="car-img">
                <div class="car-info">
                    <h4><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></h4>
                    <p>$<?= number_format($car['price']) ?> • <?= $car['city'] ?></p>
                </div>
                <div class="actions">
                    <a href="edit-ad.php?id=<?= $car['id'] ?>" class="btn btn-edit">Edit</a>
                    <a href="delete-ad.php?id=<?= $car['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete permanently?')">Delete</a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="section">
        <h2 style="color: var(--pending);">⏳ Pending Approval</h2>
        <?php if(empty($pending_cars)): ?>
            <p class="empty-msg">No ads waiting for review.</p>
        <?php else: foreach($pending_cars as $car): ?>
            <div class="car-row">
                <img src="<?= $car['main_image'] ?: 'https://via.placeholder.com/100x70' ?>" class="car-img">
                <div class="car-info">
                    <h4><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></h4>
                    <p>Submited on: <?= date('M d, Y', strtotime($car['created_at'])) ?></p>
                </div>
                <div class="actions">
                    <a href="edit-ad.php?id=<?= $car['id'] ?>" class="btn btn-edit">Edit</a>
                    <a href="delete-ad.php?id=<?= $car['id'] ?>" class="btn btn-delete" onclick="return confirm('Cancel this submission?')">Delete</a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="section">
        <h2 style="color: #64748b;">📁 Sold / Archived</h2>
        <?php if(empty($sold_cars)): ?>
            <p class="empty-msg">Your sold cars will appear here.</p>
        <?php else: foreach($sold_cars as $car): ?>
            <div class="car-row" style="opacity: 0.7;">
                <img src="<?= $car['main_image'] ?: 'https://via.placeholder.com/100x70' ?>" class="car-img" style="filter: grayscale(1);">
                <div class="car-info">
                    <h4><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></h4>
                    <p>Status: <span class="status-tag tag-sold">SOLD</span></p>
                </div>
                <div class="actions">
                    <span class="lock-icon">🔒 Editing Disabled</span>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

</div>

</body>
</html>