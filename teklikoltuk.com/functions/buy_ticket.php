<?php
session_start();
require(__DIR__ . '/../db/connect.php');

if (!isset($_SESSION['User'])) {
    die("Bilet almak için giriş yapmalısınız.");
}

$user_id = $_SESSION['User']['id'];

// Form verileri
$trip_id = $_POST['trip_id'] ?? null;
$seat_number = $_POST['seat_number'] ?? null;
$total_price = $_POST['total_price'] ?? null;
$full_name = trim($_POST['full_name'] ?? '');
$tc_number = trim($_POST['tc_number'] ?? '');

if (!$trip_id || !$seat_number || !$total_price || !$full_name || !$tc_number) {
    die("Eksik veya geçersiz bilgiler var.");
}

try {
    // PDO hatalarını exception olarak fırlat
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Foreign key'leri aktif et
    $db->exec('PRAGMA foreign_keys = ON;');

    // Transaction başlat
    $db->beginTransaction();

    // Sefer kontrolü
    $stmt = $db->prepare("SELECT * FROM Trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception("Sefer bulunamadı.");
    }

    // Koltuk kontrolü
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM BookedSeats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? AND bs.seat_number = ?
    ");
    $stmt->execute([$trip_id, $seat_number]);
    $booked = $stmt->fetchColumn();

    if ($booked > 0) {
        throw new Exception("Seçilen koltuk dolu, lütfen başka bir koltuk seçin.");
    }

    // Bilet ID oluştur
    $ticket_id = uniqid('TKT_');

    // Tickets tablosuna ekle
    $stmt = $db->prepare("
        INSERT INTO Tickets (id, trip_id, user_id, total_price) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$ticket_id, $trip_id, $user_id, $total_price]);

    // BookedSeats tablosuna ekle (seat_number string olarak)
    $stmt = $db->prepare("
        INSERT INTO BookedSeats (ticket_id, seat_number) 
        VALUES (?, ?)
    ");
    $stmt->execute([$ticket_id, (string)$seat_number]);

    // Commit
    $db->commit();

    echo "<h2>Bilet Başarıyla Alındı!</h2>";
    echo "<p>Sefer: {$trip['departure_city']} → {$trip['destination_city']}</p>";
    echo "<p>Koltuk: {$seat_number}</p>";
    echo "<p>Yolcu: {$full_name} (TC: {$tc_number})</p>";
    echo "<p>Toplam: {$total_price} TL</p>";
    echo "<a href='../public/travlers.php'>Geri Dön</a>";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Hata: " . $e->getMessage());
}
?>