<?php
session_start();
require(__DIR__ . '/../db/connect.php');
require(__DIR__ . '/../public/fpdf/tfpdf.php');

$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$currentPage = basename($_SERVER['PHP_SELF']);
if (!$isLoggedIn) die("Bilet almak için giriş yapmalısınız.");

$user_id = $_SESSION['User']['id'];

// 🔹 POST parametreleri
$trip_id     = $_POST['trip_id'] ?? null;
$seat_number = $_POST['seat_number'] ?? null;
if (!$trip_id || !$seat_number) die("Sefer veya koltuk bilgisi eksik.");

// 🔹 Sefer bilgisi
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı.");

// 🔹 Firma bilgisi
$stmt = $db->prepare("SELECT name FROM BusCompany WHERE id = ?");
$stmt->execute([$trip['company_id']]);
$company_name = $stmt->fetchColumn() ?: 'Bilinmeyen Firma';

// 🔹 Fiyat
$total_price = $trip['price'];
$final_price = $total_price;

// 🔹 AJAX kupon kontrolü
if (isset($_POST['ajax_check_coupon'])) {
    $coupon_code = trim($_POST['coupon'] ?? '');
    $response = ['success' => false, 'message' => '', 'new_price' => $total_price];

    if ($coupon_code) {
        $stmt = $db->prepare("SELECT * FROM Coupons WHERE code = ?");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {
            $today = date('Y-m-d');
            if ($coupon['expire_date'] < $today) {
                $response['message'] = "⚠️ Kuponun süresi dolmuş.";
            } else {
                $usedCount = $db->prepare("SELECT COUNT(*) FROM UserCoupons WHERE coupon_id = ?");
                $usedCount->execute([$coupon['id']]);
                $usedCount = $usedCount->fetchColumn();

                if ($usedCount >= $coupon['usage_limit']) {
                    $response['message'] = "⚠️ Kuponun kullanım limiti dolmuş.";
                } else {
                    $check = $db->prepare("SELECT 1 FROM UserCoupons WHERE coupon_id = ? AND user_id = ?");
                    $check->execute([$coupon['id'], $user_id]);
                    $alreadyUsed = $check->fetch();

                    if ($alreadyUsed) {
                        $response['message'] = "⚠️ Bu kuponu zaten kullandınız!";
                    } elseif ($coupon['company_id'] && $coupon['company_id'] != $trip['company_id']) {
                        $response['message'] = "⚠️ Bu kupon bu firmada geçerli değil.";
                    } else {
                        $discount_percent = $coupon['discount'];
                        $new_price = $total_price * (1 - ($discount_percent / 100));
                        $response = [
                            'success' => true,
                            'message' => "✅ Kupon geçerli! {$discount_percent}% indirim uygulandı.",
                            'new_price' => number_format($new_price, 2)
                        ];
                    }
                }
            }
        } else {
            $response['message'] = "❌ Geçersiz kupon kodu.";
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 🔹 Ödeme işlemi
$message = '';
$ticket_id = '';
if (isset($_POST['action']) && $_POST['action'] === 'buy_ticket') {
    $full_name   = trim($_POST['full_name'] ?? '');
    $tc_number   = trim($_POST['tc_number'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $coupon_code = trim($_POST['coupon'] ?? '');
    $final_price = floatval($_POST['final_price'] ?? $total_price);

    // Kullanıcı bakiyesi
    $stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance = $user['balance'] ?? 0;

    if ($balance < $final_price) {
        $message = "<p style='color:red;text-align:center;'>❌ Yetersiz bakiye!</p>";
    } else {
        $db->beginTransaction();
        try {
            // 🔹 Bakiye düş
            $db->prepare("UPDATE User SET balance = ? WHERE id = ?")
               ->execute([$balance - $final_price, $user_id]);

            // 🔹 Bilet kaydı
            $ticket_id = uniqid('TKT');
            $db->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price, status, created_date)
                          VALUES (?, ?, ?, ?, 'ACTIVE', datetime('now'))")
               ->execute([$ticket_id, $trip_id, $user_id, $final_price]);

            $db->prepare("INSERT INTO BookedSeats (ticket_id, seat_number)
                          VALUES (?, ?)")
               ->execute([$ticket_id , $seat_number]);


            // 🔹 Kupon kaydı
            if ($coupon_code) {
                $stmt = $db->prepare("SELECT id FROM Coupons WHERE code = ?");
                $stmt->execute([$coupon_code]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($coupon) {
                    $db->prepare("INSERT INTO UserCoupons (coupon_id, user_id, created_at)
                                  VALUES (?, ?, datetime('now'))")
                       ->execute([$coupon['id'], $user_id]);
                }
            }

            $db->commit();
            $message = "<div style='text-align:center;color:#0f0;font-weight:bold;margin-top:15px;'>✅ Ödeme başarılı! Biletiniz oluşturuldu.</div>";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "<p style='color:red;text-align:center;'>❌ Bir hata oluştu: " . $e->getMessage() . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Bilet Satın Al</title>
<link rel="stylesheet" href="../styles2.css">
<style>
body { color: #fff; font-family: Arial, sans-serif; padding-top: 160px; }
.buy-form { max-width: 520px; margin: 0 auto; background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; }
.buy-form h2 { text-align: center; margin-bottom: 20px; }
.buy-form label { display: block; margin-top: 10px; font-weight: 600; }
.buy-form input { width: 100%; padding: 8px; margin-top: 5px; border-radius: 6px; border: none; background: rgba(255,255,255,0.1); color: #fff; }
.buy-form button { margin-top: 20px; padding: 10px; width: 100%; border: none; border-radius: 6px; background: var(--accent, #6c63ff); color: #fff; font-weight: 600; cursor: pointer; }
.buy-form button:hover { background: #574bff; }
.trip-info { margin-bottom: 20px; text-align: center; }
#coupon-message { margin-top: 10px; text-align: center; font-weight: bold; }
.ticket-btn { text-align:center; margin-top:20px; }
.ticket-btn button { background:#28a745; }
</style>
</head>
<body>
<?php include("../includes/header.php")?>
<div class="buy-form">
<h2>Bilet Satın Al</h2>
<div class="trip-info">
<p><strong>Sefer:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
<p><strong>Koltuk:</strong> <?= htmlspecialchars($seat_number) ?></p>
<p><strong>Fiyat:</strong> <span id="trip-price"><?= htmlspecialchars($total_price) ?></span> TL</p>
<p><strong>Firma:</strong> <?= htmlspecialchars($company_name) ?></p>
</div>

<?= $message ?>

<form id="buy-form" method="POST">
<input type="hidden" name="action" value="buy_ticket">
<input type="hidden" id="final_price" name="final_price" value="<?= htmlspecialchars($total_price) ?>">
<input type="hidden" name="trip_id" value="<?= $trip_id ?>">
<input type="hidden" name="seat_number" value="<?= $seat_number ?>">

<label for="coupon">Kupon Kodu</label>
<div style="display:flex;gap:10px;">
<input type="text" id="coupon" name="coupon" placeholder="Örn: YAZ2025">
<button type="button" id="apply-coupon">Kuponu Kullan</button>
</div>
<div id="coupon-message"></div>

<label for="full_name">Yolcu Adı Soyadı</label>
<input type="text" id="full_name" name="full_name" required>

<label for="tc_number">TC Kimlik No</label>
<input type="text" id="tc_number" name="tc_number" pattern="\d{11}" required>

<label for="phone">Telefon Numarası</label>
<input type="text" id="phone" name="phone" pattern="\d{10,11}" required>

<button type="submit">💳 Ödeme Yap</button>
</form>

<?php if (!empty($ticket_id)): ?>
<div class="ticket-btn">
    <form action="ticket_pdf.php" method="GET" target="_blank">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_id) ?>">
        <button type="submit">🎫 Bileti Görüntüle</button>
    </form>
</div>
<?php endif; ?>

</div>

<script>
document.getElementById('apply-coupon').addEventListener('click', function() {
    const coupon = document.getElementById('coupon').value.trim();
    const messageDiv = document.getElementById('coupon-message');
    const priceElement = document.getElementById('trip-price');
    const finalInput = document.getElementById('final_price');

    if (!coupon) {
        messageDiv.innerHTML = "⚠️ Kupon kodu giriniz.";
        return;
    }

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ ajax_check_coupon: 1, coupon: coupon })
    })
    .then(res => res.json())
    .then(data => {
        messageDiv.innerHTML = data.message;
        if (data.success) {
            priceElement.textContent = data.new_price;
            finalInput.value = data.new_price;
        }
    })
    .catch(() => messageDiv.innerHTML = "❌ Kupon kontrolü başarısız.");
});
</script>
<?php include("../includes/footer.php")?>
</body>
</html>