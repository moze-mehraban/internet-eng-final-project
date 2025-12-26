<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$car_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 1. FETCH CAR DETAILS
$stmt = $pdo->prepare("SELECT cars.*, brands.brand_name, users.username, users.phone_number as seller_phone 
                      FROM cars 
                      JOIN brands ON cars.brand_id = brands.id 
                      JOIN users ON cars.user_id = users.id 
                      WHERE cars.id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    die("<div style='text-align:center; padding:50px;'><h1>Listing not found.</h1><a href='index.php'>Back to Home</a></div>");
}

// 2. SECURITY CHECK
if ($car['status'] === 'pending') {
    if (!$is_admin && $user_id != $car['user_id']) {
        die("<div style='text-align:center; padding:50px;'><h1>Access Denied</h1><p>Moderation in progress.</p></div>");
    }
}

$img_stmt = $pdo->prepare("SELECT * FROM car_images WHERE car_id = ?");
$img_stmt->execute([$car_id]);
$images = $img_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?> - Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --success: #22c55e; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; color: var(--dark); }
        
        /* HEADER STYLES */
        header { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        nav { display: flex; align-items: center; gap: 20px; }
        nav a { text-decoration: none; color: var(--dark); font-weight: 500; font-size: 0.9rem; }
        .nav-btn { background: #e2e8f0; padding: 8px 16px; border-radius: 6px; font-weight: 600; }
        .admin-btn { background: var(--dark); color: white; }
        .btn-post { background: var(--primary); color: white; padding: 8px 16px; border-radius: 6px; }

        /* MAIN CONTAINER */
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .main-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        /* BACK BUTTON */
        .back-nav { margin-bottom: 20px; }
        .back-link { text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .back-link:hover { color: var(--primary); }

        /* MODERATION BAR */
        .mod-bar { background: #fff7ed; border: 1px solid #ffedd5; padding: 20px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }

        /* GALLERY */
        .gallery-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .main-img { width: 100%; height: 450px; object-fit: cover; border-radius: 15px; }
        .thumb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; height: 450px; overflow-y: auto; }
        .thumb-grid img { width: 100%; height: 105px; object-fit: cover; border-radius: 10px; cursor: pointer; opacity: 0.7; transition: 0.3s; }
        .thumb-grid img:hover { opacity: 1; }

        /* INFO SECTION */
        .details-layout { display: grid; grid-template-columns: 1fr 350px; gap: 40px; margin-top: 40px; }
        .price { font-size: 2.5rem; font-weight: 800; color: var(--primary); margin: 10px 0; }
        
        .specs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 25px 0; background: #f8fafc; padding: 20px; border-radius: 12px; }
        .spec-item span { display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; }
        .spec-item strong { font-size: 1.1rem; color: var(--dark); }

        .sidebar-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 15px; position: sticky; top: 20px; }
        .btn-call { display: block; text-align: center; background: var(--dark); color: white; padding: 15px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 1.1rem; margin-top: 15px; }
        .btn-share { width: 100%; padding: 12px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; font-weight: 600; color: #475569; cursor: pointer; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">CARSPAGE</a>
    <nav>
        <a href="index.php">Home</a>
        <a href="search.php">Browse</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="nav-btn admin-btn">Admin Panel</a>
            <?php else: ?>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" style="color:var(--danger)">Logout</a>
            <a href="post-ad.php" class="btn-post">+ Post Ad</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-post">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    
    <div class="back-nav">
        <a href="javascript:history.back()" class="back-link">← Back to Listings</a>
    </div>

    <div class="main-card">
        <?php if ($is_admin && $car['status'] === 'pending'): ?>
            <div class="mod-bar">
                <div><strong style="color:#c2410c">MODERATION REQUIRED</strong><p style="margin:0; font-size:0.8rem;">Review this ad before approval.</p></div>
                <div style="display:flex; gap:10px;">
                    <a href="admin.php?approve=<?= $car['id'] ?>" style="background:var(--success); color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700;">Approve</a>
                    <a href="delete-ad.php?id=<?= $car['id'] ?>" style="background:var(--danger); color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700;">Reject</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="gallery-container">
            <img src="<?= htmlspecialchars($images[0]['image_url'] ?? 'placeholder.jpg') ?>" class="main-img" id="main-view">
            <div class="thumb-grid">
                <?php foreach($images as $img): ?>
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" onclick="document.getElementById('main-view').src=this.src">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="details-layout">
            <div class="info-side">
                <h1 style="margin:0; font-size:2.2rem;"><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></h1>
                <div class="price">$<?= number_format($car['price']) ?></div>
                
                <div class="specs-grid">
                    <div class="spec-item"><span>Year</span><strong><?= $car['year_produced'] ?></strong></div>
                    <div class="spec-item"><span>Mileage</span><strong><?= number_format($car['mileage']) ?> km</strong></div>
                    <div class="spec-item"><span>Color</span><strong><?= htmlspecialchars($car['color']) ?></strong></div>
                    <div class="spec-item"><span>Location</span><strong><?= htmlspecialchars($car['city']) ?></strong></div>
                </div>

                <h3 style="border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Description</h3>
                <p style="line-height:1.8; color:#475569; white-space: pre-wrap;"><?= htmlspecialchars($car['description']) ?></p>
            </div>

            <div class="sidebar">
                <div class="sidebar-card">
                    <h3 style="margin-top:0;">Seller Details</h3>
                    <p style="color:#64748b; font-size:0.9rem;">Listed by: <strong style="color:var(--dark)">@<?= htmlspecialchars($car['username']) ?></strong></p>
                    
                    <a href="tel:<?= $car['seller_phone'] ?>" class="btn-call">Call Seller</a>
                    
                    <button class="btn-share" onclick="shareMe()">🔗 Share Listing</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function shareMe() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes($car['brand_name'] . " " . $car['model']) ?>',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
    }
}
</script>

</body>
</html>