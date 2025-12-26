<?php
session_start();
if (!isset($_SESSION['user_id'])) exit;
include 'db.php';

$photo_id = $_GET['id'] ?? 0;
$car_id = $_GET['car_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT car_images.* FROM car_images 
                       JOIN cars ON car_images.car_id = cars.id 
                       WHERE car_images.id = ? AND cars.user_id = ?");
$stmt->execute([$photo_id, $user_id]);
$photo = $stmt->fetch();

if ($photo) {
    if (file_exists($photo['image_url'])) {
        unlink($photo['image_url']);
    }

    $del = $pdo->prepare("DELETE FROM car_images WHERE id = ?");
    $del->execute([$photo_id]);
}

header("Location: edit-ad.php?id=" . $car_id);
exit();