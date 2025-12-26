<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$car_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 1. FETCH CAR & SECURITY CHECK
if ($is_admin) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND user_id = ?");
    $stmt->execute([$car_id, $user_id]);
}
$car = $stmt->fetch();

if (!$car) {
    die("Listing not found.");
}

// 2. BLOCK MANUAL URL ACCESS FOR SOLD ADS
// If the car is sold and the visitor is NOT an admin, deny access.
if ($car['status'] === 'sold' && !$is_admin) {
    die("<h1>Access Denied</h1><p>Sold listings cannot be edited. Please contact support if you need to relist this vehicle.</p><a href='dashboard.php'>Back to Dashboard</a>");
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll();
$colors = $pdo->query("SELECT * FROM colors ORDER BY color_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_brand_id = $_POST['brand_id'];
    $new_model = $_POST['model'];
    $new_year = $_POST['year_produced'];
    $new_mileage = $_POST['mileage'];
    $new_price = $_POST['price'];
    $new_city = $_POST['city'];
    $new_description = $_POST['description'];
    $new_color = $_POST['color'];
    $requested_status = $_POST['status'];

    // LOGIC: Check if core details changed
    $details_changed = (
        $new_brand_id != $car['brand_id'] ||
        $new_model    != $car['model'] ||
        $new_year     != $car['year_produced'] ||
        $new_mileage  != $car['mileage'] ||
        $new_price    != $car['price'] ||
        $new_city     != $car['city'] ||
        $new_description != $car['description'] ||
        $new_color    != $car['color']
    );

    // DETERMINING FINAL STATUS
    if ($is_admin) {
        $final_status = $requested_status;
    } else {
        // If they change details, it must go back to pending.
        // If they only switch Available -> Sold, it stays as requested.
        $final_status = ($details_changed) ? 'pending' : $requested_status;
    }

    $update_sql = "UPDATE cars SET brand_id = ?, model = ?, year_produced = ?, mileage = ?, price = ?, city = ?, description = ?, status = ?, color = ? WHERE id = ?";
    $update_params = [$new_brand_id, $new_model, $new_year, $new_mileage, $new_price, $new_city, $new_description, $final_status, $new_color, $car_id];
    
    if (!$is_admin) {
        $update_sql .= " AND user_id = ?";
        $update_params[] = $user_id;
    }

    $update = $pdo->prepare($update_sql);
    $update->execute($update_params);

    // Photo Management
    if (!empty($_POST['delete_photo_ids'])) {
        foreach ($_POST['delete_photo_ids'] as $photo_id) {
            $img_stmt = $pdo->prepare("SELECT image_url FROM car_images WHERE id = ? AND car_id = ?");
            $img_stmt->execute([$photo_id, $car_id]);
            $photo = $img_stmt->fetch();
            if ($photo) {
                if (file_exists($photo['image_url'])) unlink($photo['image_url']);
                $pdo->prepare("DELETE FROM car_images WHERE id = ?")->execute([$photo_id]);
            }
        }
        if (!$is_admin) $pdo->prepare("UPDATE cars SET status = 'pending' WHERE id = ?")->execute([$car_id]);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $target = "uploads/" . time() . "_" . $_FILES['images']['name'][$key];
                if (move_uploaded_file($tmp_name, $target)) {
                    $pdo->prepare("INSERT INTO car_images (car_id, image_url, is_main) VALUES (?, ?, 0)")->execute([$car_id, $target]);
                }
            }
        }
        if (!$is_admin) $pdo->prepare("UPDATE cars SET status = 'pending' WHERE id = ?")->execute([$car_id]);
    }

    $redirect = $is_admin ? "admin.php?view=all_ads" : "dashboard.php";
    header("Location: $redirect");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Listing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 40px; margin: 0; }
        .form-card { background: white; max-width: 800px; margin: 0 auto; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; margin-top: 20px; font-weight: 600; font-size: 0.85rem; color: #64748b; }
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-save { width: 100%; padding: 15px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 30px; }
        .current-images { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .image-wrapper { position: relative; width: 100px; height: 80px; }
        .image-wrapper img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; }
        .remove-btn { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; cursor: pointer; border: 2px solid white; font-size: 12px; }
        .marked-for-delete { opacity: 0.2; filter: grayscale(1); pointer-events: none; }
    </style>
</head>
<body>

<div class="form-card">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin:0;">Edit Listing #<?= htmlspecialchars($car_id) ?></h2>
        <a href="<?= $is_admin ? 'admin.php?view=all_ads' : 'dashboard.php' ?>" style="text-decoration:none; color:#64748b; font-weight:600;">Cancel</a>
    </div>

    <form method="POST" enctype="multipart/form-data" id="editForm">
        <label>Status</label>
        <select name="status">
            <option value="available" <?= $car['status'] == 'available' ? 'selected' : '' ?>>Available</option>
            <option value="sold" <?= $car['status'] == 'sold' ? 'selected' : '' ?>>Sold</option>
            <?php if ($is_admin): ?><option value="pending" <?= $car['status'] == 'pending' ? 'selected' : '' ?>>Pending</option><?php endif; ?>
        </select>

        <div class="grid-inputs">
            <div><label>Brand</label><select name="brand_id"><?php foreach($brands as $b): ?><option value="<?= $b['id'] ?>" <?= $car['brand_id']==$b['id']?'selected':'' ?>><?= $b['brand_name'] ?></option><?php endforeach; ?></select></div>
            <div><label>Model</label><input type="text" name="model" value="<?= htmlspecialchars($car['model']) ?>"></div>
            <div><label>Price ($)</label><input type="number" name="price" value="<?= $car['price'] ?>"></div>
            <div><label>Mileage</label><input type="number" name="mileage" value="<?= $car['mileage'] ?>"></div>
            <div><label>Color</label><select name="color"><?php foreach($colors as $c): ?><option value="<?= $c['color_name'] ?>" <?= $car['color']==$c['color_name']?'selected':'' ?>><?= $c['color_name'] ?></option><?php endforeach; ?></select></div>
        </div>

        <label>Description</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($car['description']) ?></textarea>

        <label>Photos (Click X to remove)</label>
        <div class="current-images">
            <?php
            $img_stmt = $pdo->prepare("SELECT * FROM car_images WHERE car_id = ?");
            $img_stmt->execute([$car_id]);
            foreach ($img_stmt->fetchAll() as $img): ?>
                <div class="image-wrapper" id="photo-<?= $img['id'] ?>">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>">
                    <div class="remove-btn" onclick="markForDeletion(<?= $img['id'] ?>)">×</div>
                </div>
            <?php endforeach; ?>
        </div>

        <label>Add More Photos</label>
        <input type="file" name="images[]" multiple>

        <button type="submit" class="btn-save">Update Listing</button>
    </form>
</div>

<script>
function markForDeletion(photoId) {
    const wrapper = document.getElementById('photo-' + photoId);
    wrapper.classList.add('marked-for-delete');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'delete_photo_ids[]';
    input.value = photoId;
    document.getElementById('editForm').appendChild(input);
}
</script>

</body>
</html>