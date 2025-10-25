<?php
session_start();
require_once '../connect.php';
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : 'user';
if (!isset($_SESSION['User'])) {
    die("Giriş yapmadan bilet görüntüleyemezsiniz.");
}

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    die("Bilet ID bulunamadı.");
}

// 🔹 Bilet bilgilerini çek
$stmt = $db->prepare("
    SELECT t.*, b.seat_number, tr.from_location, tr.to_location, tr.date, tr.price
    FROM Tickets t
    JOIN BookedSeats b ON t.id = b.ticket_id
    JOIN Trips tr ON t.trip_id = tr.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı.");
}

// 🔹 Yetki kontrolü
if ($ticket['user_id'] != $_SESSION['user_id']) {
    die("Bu bilete erişim yetkiniz yok.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Bilet Detayı</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Bilet Detayı</h2>
  <p><strong>Sefer:</strong> <?= htmlspecialchars($ticket['from_location'] . " → " . $ticket['to_location']) ?></p>
  <p><strong>Tarih:</strong> <?= htmlspecialchars($ticket['date']) ?></p>
  <p><strong>Koltuk No:</strong> <?= htmlspecialchars($ticket['seat_number']) ?></p>
  <p><strong>Fiyat:</strong> <?= htmlspecialchars($ticket['total_price']) ?> ₺</p>
  <p><strong>Durum:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
</body>
</html>