<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll();
$colors = $pdo->query("SELECT * FROM colors ORDER BY color_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $brand_id = $_POST['brand_id'];
    $model = $_POST['model'];
    $year = $_POST['year_produced'];
    $mileage = $_POST['mileage'];
    $price = $_POST['price'];
    $color = $_POST['color'];
    $city = $_POST['city'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO cars (user_id, brand_id, model, year_produced, mileage, price, color, city, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $brand_id, $model, $year, $mileage, $price, $color, $city, $description]);
    
    $car_id = $pdo->lastInsertId();

    if (!empty($_FILES['images']['name'][0])) {
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = time() . "_" . $key . "." . $file_extension;
                $target = "uploads/" . $file_name;
                
                if (move_uploaded_file($tmp_name, $target)) {
                    $is_main = ($key == 0) ? 1 : 0;
                    $img_stmt = $pdo->prepare("INSERT INTO car_images (car_id, image_url, is_main) VALUES (?, ?, ?)");
                    $img_stmt->execute([$car_id, $target, $is_main]);
                }
            }
        }
    }
    header("Location: dashboard.php?msg=submitted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Ad</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --light: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 40px; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; margin-top: 20px; font-weight: 600; font-size: 0.9rem; color: var(--dark); }
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; }
        .btn-submit { width: 100%; padding: 16px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 30px; font-size: 1rem; transition: background 0.3s; }
        .btn-submit:hover { background: #1d4ed8; }
        .info-box { background: #eff6ff; color: #1e40af; padding: 15px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <div class="header-area">
            <h1 style="font-size: 1.5rem; margin: 0;">Sell Your Car</h1>
            <a href="dashboard.php" style="text-decoration: none; color: #64748b; font-weight: 600;">Cancel</a>
        </div>

        <div class="info-box">
            <strong>Note:</strong> Your ad will be reviewed by an administrator before becoming visible to the public.
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid-inputs">
                <div>
                    <label>Car Brand</label>
                    <select name="brand_id" required>
                        <option value="">Select a Brand</option>
                        <?php foreach($brands as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Model Name</label>
                    <input type="text" name="model" placeholder="e.g., Civic, Pars, 206" required>
                </div>
                <div>
                    <label>Year</label>
                    <input type="number" name="year_produced" min="1900" max="<?= date('Y')+1 ?>" required>
                </div>
                <div>
                    <label>Mileage (km)</label>
                    <input type="number" name="mileage" placeholder="0 for new cars" required>
                </div>
                <div>
                    <label>Price ($)</label>
                    <input type="number" name="price" required>
                </div>
                <div>
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
            </div>

            <label>Color</label>
            <select name="color" required>
                <option value="">Select Color</option>
                <?php foreach($colors as $c): ?>
                    <option value="<?= htmlspecialchars($c['color_name']) ?>"><?= htmlspecialchars($c['color_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Detailed Description</label>
            <textarea name="description" rows="5" placeholder="Mention features, options, and car condition..."></textarea>

            <label>Car Photos (Select one or more)</label>
            <input type="file" name="images[]" multiple accept="image/*" required>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 8px;">
                💡 Tip: Use high-quality photos to sell faster. The first photo will be your main ad image.
            </p>

            <button type="submit" class="btn-submit">Submit for Review</button>
        </form>
    </div>
</div>

</body>
</html>