<?php
session_start();
require(__DIR__ . '/../db/connect.php');

// 🔹 Firma admini kontrolü
if (!isset($_SESSION['User']) || $_SESSION['User']['role'] !== 'company') {
    die("Bu işlem için yetkiniz yok.");
}

$company_id = $_SESSION['User']['company_id'];
$trip_id = $_POST['trip_id'] ?? null;

if (!$trip_id) {
    die("Sefer ID eksik.");
}

// 🔹 Seferin gerçekten bu firmaya ait olduğunu doğrula
$check = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$check->execute([$trip_id, $company_id]);
if (!$check->fetch()) {
    die("Bu sefer size ait değil.");
}

// 🔹 Formdan gelen veriler
$departure_city = $_POST['departure_city'] ?? '';
$destination_city = $_POST['destination_city'] ?? '';
$departure_time = $_POST['departure_time'] ?? '';
$arrival_time = $_POST['arrival_time'] ?? '';
$price = $_POST['price'] ?? 0;
$capacity = $_POST['capacity'] ?? 0;

if (!$departure_city || !$destination_city || !$departure_time || !$arrival_time) {
    die("Tüm alanları doldurmalısınız!");
}

// 🔹 Güncelleme sorgusu
$stmt = $db->prepare("
    UPDATE Trips 
    SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?
    WHERE id = ? AND company_id = ?
");
$stmt->execute([
    $departure_city,
    $destination_city,
    $departure_time,
    $arrival_time,
    $price,
    $capacity,
    $trip_id,
    $company_id
]);

header("Location: dashboard.php?msg=trip_updated");
exit;
?>