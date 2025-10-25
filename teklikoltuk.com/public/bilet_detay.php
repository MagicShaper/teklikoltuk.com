<?php
session_start();
require(__DIR__ . '/../db/connect.php');

// 🔹 Giriş kontrolü
if (!isset($_SESSION['User'])) {
  header("Location: login.php");
  exit;
}

$user = $_SESSION['User'];
$userId = $user['id'];

// 🔹 GET parametresi kontrolü
$ticketId = $_GET['id'] ?? null;
if (!$ticketId) {
  die("❌ Geçersiz bilet ID'si!");
}

// 🔹 Bilet bilgilerini çek
$stmt = $db->prepare("
  SELECT 
    t.id AS ticket_id,
    t.status,
    t.total_price,
    t.created_date,
    bs.seat_number,
    tr.departure_city,
    tr.destination_city,
    tr.departure_time,
    tr.arrival_time
  FROM Tickets t
  JOIN BookedSeats bs ON bs.ticket_id = t.id
  JOIN Trips tr ON tr.id = t.trip_id
  WHERE t.id = :ticket_id AND t.user_id = :user_id
");
$stmt->execute([
  ':ticket_id' => $ticketId,
  ':user_id' => $userId
]);

$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
  die("❌ Bu bilete erişim yetkiniz yok veya bilet bulunamadı.");
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bilet Detayı | Teklikoltuk</title>
  <link rel="stylesheet" href="../styles2.css">
  <style>
    .ticket-container {
      max-width: 700px;
      margin: 50px auto;
      padding: 25px;
      background: rgba(255,255,255,0.05);
      border-radius: 15px;
      color: #fff;
      text-align: center;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .ticket-header {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: var(--accent, #6c63ff);
    }
    .ticket-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin: 20px 0;
      text-align: left;
    }
    .ticket-info span {
      font-weight: bold;
      color: #ccc;
    }
    .ticket-status {
      margin-top: 20px;
      padding: 10px;
      border-radius: 8px;
      font-weight: bold;
      display: inline-block;
    }
    .status-ACTIVE {
      background: #28a745;
      color: white;
    }
    .status-CANCELED {
      background: #dc3545;
      color: white;
    }
    .status-EXPIRED {
      background: #6c757d;
      color: white;
    }
    .back-btn {
      margin-top: 30px;
      display: inline-block;
      padding: 10px 20px;
      border-radius: 8px;
      background: var(--accent, #6c63ff);
      color: white;
      text-decoration: none;
      transition: 0.3s;
    }
    .back-btn:hover {
      background: #574bff;
    }
  </style>
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="ticket-container">
  <h2 class="ticket-header">🎟️ Bilet Detayları</h2>

  <div class="ticket-info">
    <p><span>Bilet No:</span> <?= htmlspecialchars($ticket['ticket_id']) ?></p>
    <p><span>Koltuk:</span> <?= htmlspecialchars($ticket['seat_number']) ?></p>
    <p><span>Kalkış:</span> <?= htmlspecialchars($ticket['departure_city']) ?></p>
    <p><span>Varış:</span> <?= htmlspecialchars($ticket['destination_city']) ?></p>
    <p><span>Kalkış Saati:</span> <?= htmlspecialchars($ticket['departure_time']) ?></p>
    <p><span>Varış Saati:</span> <?= htmlspecialchars($ticket['arrival_time']) ?></p>
    <p><span>Fiyat:</span> <?= htmlspecialchars($ticket['total_price']) ?> TL</p>
    <p><span>Tarih:</span> <?= htmlspecialchars($ticket['created_date']) ?></p>
  </div>

  <div class="ticket-status status-<?= htmlspecialchars($ticket['status']) ?>">
    <?= htmlspecialchars($ticket['status']) ?>
  </div>

  <a href="travlers.php" class="back-btn">← Seferlere Dön</a>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>