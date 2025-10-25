<?php
session_start();
require(__DIR__ . '/../db/connect.php');

if (!isset($_SESSION['User'])) {
    die("Bu işlemi yapmak için giriş yapmalısınız.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_ticket') {

    $ticket_id = $_POST['ticket_id'];
    $user_id = $_SESSION['User']['id'];

    // Biletin kullanıcıya ait olup olmadığını kontrol et ve fiyat bilgisini Trips tablosundan al
    $stmt = $db->prepare("
        SELECT t.id, tr.price, tr.departure_time 
        FROM Tickets t 
        JOIN Trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Bilet bulunamadı veya size ait değil.");
    }

    // Zaman kontrolü: sefer kalkışından 1 saat öncesi
    $departure = new DateTime($ticket['departure_time']);
    $now = new DateTime();

    $interval = $departure->getTimestamp() - $now->getTimestamp();
    if ($interval <= 3600) { // 3600 saniye = 1 saat
        header("Location: ../public/profil.php?msg=cancel_fail");
        exit;
    }

    try {
        $db->beginTransaction();

        // Bileti iptal et
        $stmt = $db->prepare("DELETE FROM Tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);

        // Ödenen tutarı kullanıcı bakiyesine ekle
        $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$ticket['price'], $user_id]);

        $db->commit();

        header("Location: ../public/profil.php?msg=cancel_success");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die("Bilet iptali sırasında bir hata oluştu: " . $e->getMessage());
    }
}
?>