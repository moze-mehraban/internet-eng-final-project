<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// 1. FETCH ALL FILTER & SORT INPUTS
$brand_id = $_GET['brand'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$min_year = $_GET['min_year'] ?? '';
$max_year = $_GET['max_year'] ?? '';
$max_mileage = $_GET['max_mileage'] ?? '';
$color = $_GET['color'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// 2. DYNAMIC SQL QUERY
$query = "SELECT cars.*, brands.brand_name, 
          (SELECT image_url FROM car_images WHERE car_id = cars.id LIMIT 1) as main_image 
          FROM cars 
          JOIN brands ON cars.brand_id = brands.id 
          WHERE status = 'available'";

$params = [];

if ($brand_id != '') { $query .= " AND brand_id = ?"; $params[] = $brand_id; }
if ($min_price != '') { $query .= " AND price >= ?"; $params[] = $min_price; }
if ($max_price != '') { $query .= " AND price <= ?"; $params[] = $max_price; }
if ($min_year != '') { $query .= " AND year_produced >= ?"; $params[] = $min_year; }
if ($max_year != '') { $query .= " AND year_produced <= ?"; $params[] = $max_year; }
if ($max_mileage != '') { $query .= " AND mileage <= ?"; $params[] = $max_mileage; }
if ($color != '') { $query .= " AND color = ?"; $params[] = $color; }

// 3. APPLY SORTING
switch ($sort) {
    case 'price_low': $query .= " ORDER BY price ASC"; break;
    case 'price_high': $query .= " ORDER BY price DESC"; break;
    case 'mileage': $query .= " ORDER BY mileage ASC"; break;
    default: $query .= " ORDER BY created_at DESC"; break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll();
$colors = $pdo->query("SELECT * FROM colors ORDER BY color_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Cars - CARSPAGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --light: #f1f5f9; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--light); margin: 0; padding: 0; }

        /* --- POLISHED HEADER CSS --- */
        header { 
            background: white; 
            height: 70px; /* Fixed height for consistency */
            padding: 0 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo { 
            font-size: 1.6rem; 
            font-weight: 800; 
            color: var(--primary); 
            text-decoration: none; 
            letter-spacing: -1px;
        }
        nav { 
            display: flex; 
            align-items: center; 
            gap: 30px; 
        }
        nav a { 
            text-decoration: none; 
            color: var(--dark); 
            font-weight: 600; 
            font-size: 0.95rem; 
            transition: color 0.2s;
        }
        nav a:hover { color: var(--primary); }
        
        .btn-post { 
            background: var(--primary); 
            color: white !important; 
            padding: 10px 22px; 
            border-radius: 10px; 
            font-weight: 700; 
            transition: transform 0.2s, background 0.2s;
        }
        .btn-post:hover { 
            background: #1d4ed8; 
            transform: translateY(-1px); 
        }

        /* --- LAYOUT & CONTENT --- */
        .main-wrapper { 
            max-width: 1350px; 
            margin: 40px auto; 
            padding: 0 25px; 
            display: flex; 
            gap: 30px; 
        }

        .filter-sidebar { 
            width: 280px; 
            flex-shrink: 0; 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
        }
        
        .filter-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; }
        
        input, select { 
            width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 10px; 
            font-family: inherit; font-size: 0.9rem; box-sizing: border-box; 
        }

        .results-header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 20px; background: white; padding: 15px 25px; border-radius: 15px;
        }

        .results-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 25px; 
        }

        .car-card { 
            background: white; border-radius: 20px; overflow: hidden; 
            text-decoration: none; color: inherit; transition: 0.3s; 
            border: 1px solid rgba(0,0,0,0.02);
        }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .car-card img { width: 100%; height: 200px; object-fit: cover; }
        .car-body { padding: 20px; }
        .car-price { font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-top: 10px; }

        @media (max-width: 1100px) { .results-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">CARSPAGE</a>
    <nav>
        <a href="index.php">Home</a>
        <a href="search.php" style="color:var(--primary)">Browse</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="post-ad.php" class="btn-post">+ Post Ad</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-post">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="main-wrapper">
    <aside class="filter-sidebar">
        <h2 style="margin:0 0 25px 0;">Filters</h2>
        <form action="search.php" method="GET">
            <div class="filter-group">
                <label>Brand</label>
                <select name="brand">
                    <option value="">All Brands</option>
                    <?php foreach($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['brand_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Price Range ($)</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>">
                    <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>">
                </div>
            </div>

            <div class="filter-group">
                <label>Year Range</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <input type="number" name="min_year" placeholder="From" value="<?= htmlspecialchars($min_year) ?>">
                    <input type="number" name="max_year" placeholder="To" value="<?= htmlspecialchars($max_year) ?>">
                </div>
            </div>

            <div class="filter-group">
                <label>Color</label>
                <select name="color">
                    <option value="">All Colors</option>
                    <?php foreach($colors as $c): ?>
                        <option value="<?= htmlspecialchars($c['color_name']) ?>" <?= $color == $c['color_name'] ? 'selected' : '' ?>><?= htmlspecialchars($c['color_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" style="width:100%; padding:15px; background:var(--primary); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">Search Results</button>
            <a href="search.php" style="display:block; text-align:center; margin-top:15px; color:#94a3b8; text-decoration:none; font-size:0.85rem;">Clear Filters</a>
        </form>
    </aside>

    <main style="flex-grow:1;">
        <div class="results-header">
            <div style="font-weight:700; color:#64748b;"><?= count($results) ?> Cars Available</div>
            <form action="search.php" method="GET" onchange="this.submit()">
                <input type="hidden" name="brand" value="<?= htmlspecialchars($brand_id) ?>">
                <input type="hidden" name="min_price" value="<?= htmlspecialchars($min_price) ?>">
                <input type="hidden" name="max_price" value="<?= htmlspecialchars($max_price) ?>">
                
                <select name="sort" style="width:auto; padding:8px 15px; font-weight:600; border-radius:8px;">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low</option>
                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High</option>
                </select>
            </form>
        </div>

        <div class="results-grid">
            <?php foreach($results as $car): ?>
            <a href="car-details.php?id=<?= $car['id'] ?>" class="car-card">
                <img src="<?= $car['main_image'] ?? 'placeholder.jpg' ?>">
                <div class="car-body">
                    <div style="font-weight:800; font-size:1.1rem;"><?= htmlspecialchars($car['brand_name'] . ' ' . $car['model']) ?></div>
                    <div style="font-size:0.85rem; color:#64748b; margin-top:5px;"><?= $car['year_produced'] ?> • <?= number_format($car['mileage']) ?> km</div>
                    <div class="car-price">$<?= number_format($car['price']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>

</body>
</html>