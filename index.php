<?php 
include 'db.php'; 

$stmt = $pdo->query("SELECT cars.*, brands.brand_name, 
                    (SELECT image_url FROM car_images WHERE car_id = cars.id LIMIT 1) as main_image 
                    FROM cars 
                    JOIN brands ON cars.brand_id = brands.id 
                    WHERE status = 'available' 
                    ORDER BY created_at DESC LIMIT 8");
$recent_cars = $stmt->fetchAll();
$brands = $pdo->query("SELECT * FROM brands")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Car Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --light: #f8fafc; --accent: #ef4444; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: var(--dark); line-height: 1.6; }
        
        header { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); sticky; top: 0; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        nav a { text-decoration: none; color: var(--dark); margin-left: 20px; font-weight: 500; transition: 0.3s; }
        nav a:hover { color: var(--primary); }
        .btn-post { background: var(--primary); color: white !important; padding: 10px 20px; border-radius: 8px; }

        .hero { background: linear-gradient(rgba(30, 41, 59, 0.7), rgba(30, 41, 59, 0.7)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1500&q=80'); height: 400px; background-size: cover; background-position: center; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; text-align: center; padding: 0 20px; }
        .hero h1 { font-size: 2.5rem; margin-bottom: 20px; }

        .search-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 90%; max-width: 1000px; margin: -50px auto 50px auto; display: flex; flex-wrap: wrap; gap: 15px; }
        .search-container select, .search-container input { flex: 1; min-width: 180px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .search-container button { background: var(--accent); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .search-container button:hover { opacity: 0.9; transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .section-title { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding-bottom: 50px; }
        .card { background: white; border-radius: 15px; overflow: hidden; transition: 0.3s; border: 1px solid #e2e8f0; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-img { width: 100%; height: 200px; object-fit: cover; }
        .card-content { padding: 20px; }
        .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; color: var(--dark); }
        .card-meta { font-size: 0.85rem; color: #64748b; margin-bottom: 15px; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px; }
        .price { font-size: 1.25rem; font-weight: 800; color: var(--primary); }
        .btn-view { text-decoration: none; font-size: 0.9rem; font-weight: 600; color: var(--dark); border: 1px solid #ddd; padding: 5px 12px; border-radius: 4px; transition: 0.3s; }
        .btn-view:hover { background: var(--dark); color: white; }
    </style>
</head>
<body>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <a href="index.php" class="logo">CARSPAGE</a>
    <nav>
        <a href="search.php">Browse Cars</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="nav-btn admin-btn">Admin Panel</a>
            <?php else: ?>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
            <?php endif; ?>

            <a href="settings.php">Settings</a>
            <a href="logout.php" class="logout-link">Logout</a>
            <a href="post-ad.php" class="btn-post">+ Post Ad</a>
            
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-reg">Register</a>
        <?php endif; ?>
    </nav>
</header>

<style>
    /* Styling for the new button look */
    .nav-btn {
        background: #e2e8f0;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        color: #1e293b;
    }
    .admin-btn {
        background: #1e293b;
        color: #f8fafc;
    }
    .logout-link {
        color: #ef4444;
        margin-left: 10px;
        text-decoration: none;
        font-size: 0.9rem;
    }
    .btn-post {
        background: #2563eb;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        margin-left: 15px;
    }
</style>

<div class="hero">
    <h1>Find Your Dream Car</h1>
    <p>Thousands of new and used cars at your fingertips</p>
</div>

<form action="search.php" method="GET" class="search-container">
    <select name="brand">
        <option value="">Select Brand</option>
        <?php foreach($brands as $brand): ?>
            <option value="<?= $brand['id'] ?>"><?= $brand['brand_name'] ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="min_price" placeholder="Min Price ($)">
    <input type="number" name="max_price" placeholder="Max Price ($)">
    <button type="submit">Search Cars</button>
</form>

<div class="container">
    <div class="section-title">
        <h2>Latest Listings</h2>
        <a href="search.php" style="color: var(--primary); text-decoration: none;">View All →</a>
    </div>

    <div class="grid">
        <?php foreach($recent_cars as $car): ?>
        <div class="card">
            <img src="<?= $car['main_image'] ?? 'https://via.placeholder.com/400x300?text=No+Image' ?>" class="card-img" alt="Car">
            <div class="card-content">
                <div class="card-title"><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></div>
                <div class="card-meta">
                    <?= $car['year_produced'] ?> • <?= number_format($car['mileage']) ?> km • <?= htmlspecialchars($car['city']) ?>
                </div>
                <div class="card-footer">
                    <div class="price">$<?= number_format($car['price']) ?></div>
                    <a href="car-details.php?id=<?= $car['id'] ?>" class="btn-view">Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>