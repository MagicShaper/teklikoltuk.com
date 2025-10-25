<?php
session_start();
require(__DIR__ . '/../db/connect.php');

// 🔹 Firma admini kontrolü
if (!isset($_SESSION['User']) || $_SESSION['User']['role'] !== 'company') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

$company_id = $_SESSION['User']['company_id'];
$trip_id = $_GET['id'] ?? null;

if (!$trip_id) {
    die("Sefer ID belirtilmemiş.");
}

// 🔹 Seferi çek
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Bu sefer size ait değil veya bulunamadı.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sefer Düzenle</title>
<link rel="stylesheet" href="../styles2.css">
<style>
    .edit-container {
        max-width: 700px;
        margin: 100px auto;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 25px;
        color: #fff;
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: var(--accent);
    }
    form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    input {
        padding: 10px;
        border-radius: 6px;
        border: none;
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    button {
        padding: 10px;
        border: none;
        border-radius: 8px;
        background: var(--accent);
        color: #fff;
        cursor: pointer;
        font-weight: 600;
    }
    button:hover {
        background: #574bff;
    }
</style>
</head>
<body>
    <?php include("includes/header.php"); ?>
    <div class="edit-container">
        <h2>Sefer Düzenle</h2>
        <form action="update_trip.php" method="POST">
            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">

            <label>Kalkış Noktası</label>
            <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required>

            <label>Varış Noktası</label>
            <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required>

            <label>Kalkış Saati</label>
            <input type="datetime-local" name="departure_time" value="<?= htmlspecialchars($trip['departure_time']) ?>" required>

            <label>Varış Saati</label>
            <input type="datetime-local" name="arrival_time" value="<?= htmlspecialchars($trip['arrival_time']) ?>" required>

            <label>Bilet Fiyatı (₺)</label>
            <input type="number" name="price" value="<?= htmlspecialchars($trip['price']) ?>" min="0" step="0.01" required>

            <label>Koltuk Sayısı</label>
            <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" min="1" required>

            <button type="submit">Değişiklikleri Kaydet</button>
        </form>
    </div>
</body>