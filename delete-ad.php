<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$car_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$user_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();
$is_admin = ($current_user && $current_user['role'] === 'admin');

$car_stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
$car_stmt->execute([$car_id]);
$car = $car_stmt->fetch();

if ($car) {
    if ($car['user_id'] == $user_id || $is_admin) {
        
        $img_stmt = $pdo->prepare("SELECT image_url FROM car_images WHERE car_id = ?");
        $img_stmt->execute([$car_id]);
        $images = $img_stmt->fetchAll();

        foreach ($images as $img) {
            if (file_exists($img['image_url'])) {
                unlink($img['image_url']);
            }
        }

        $del = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $del->execute([$car_id]);

        if ($is_admin && $car['user_id'] != $user_id) {
            header("Location: admin.php?msg=deleted");
        } else {
            header("Location: dashboard.php?msg=deleted");
        }
    } else {
        die("Unauthorized: You do not have permission to delete this listing.");
    }
} else {
    die("Error: Listing not found.");
}
exit();