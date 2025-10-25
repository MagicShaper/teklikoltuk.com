<?php
session_start();
require(__DIR__ . '/../db/connect.php');

if (!isset($_SESSION['User']) || $_SESSION['User']['role'] !== 'company') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

$company_id = $_SESSION['User']['company_id'] ?? null;
$user_id = $_SESSION['User']['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$user_id) {
    die("Kullanıcı bulunamadı.");
}

// ===================== ŞİFRE DEĞİŞTİRME =====================
if ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        header("Location: dashboard.php?msg=empty_password");
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: dashboard.php?msg=password_mismatch");
        exit;
    }

    $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current_password, $row['password'])) {
        header("Location: dashboard.php?msg=wrong_password");
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE User SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);

    header("Location: dashboard.php?msg=success");
    exit;
}

// ===================== SEFER İŞLEMLERİ =====================
$trip_id = $_POST['trip_id'] ?? null;
$departure_city = trim($_POST['from'] ?? '');
$destination_city = trim($_POST['to'] ?? '');
$departure_date = $_POST['departure_date'] ?? '';
$departure_time = $_POST['departure_time'] ?? '';
$arrival_date = $_POST['arrival_date'] ?? '';
$arrival_time = $_POST['arrival_time'] ?? '';
$price = $_POST['price'] ?? '';
$seat_count = $_POST['seat_count'] ?? '';

// 🚍 Yeni sefer ekleme
if ($action === 'add_trip') {
    if ($departure_city && $destination_city && $departure_date && $departure_time && $price && $seat_count) {
        $departure_datetime = $departure_date . ' ' . $departure_time;
        $arrival_datetime = ($arrival_date && $arrival_time)
            ? ($arrival_date . ' ' . $arrival_time)
            : $departure_datetime;

        $stmt = $db->prepare("
            INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$company_id, $departure_city, $destination_city, $departure_datetime, $arrival_datetime, floatval($price), intval($seat_count)]);
    }
}

// ✏️ Sefer güncelleme
if ($action === 'update_trip' && $trip_id) {
    if ($departure_city && $destination_city && $departure_date && $departure_time && $price && $seat_count) {
        $departure_datetime = $departure_date . ' ' . $departure_time;
        $arrival_datetime = ($arrival_date && $arrival_time)
            ? ($arrival_date . ' ' . $arrival_time)
            : $departure_datetime;

        $stmt = $db->prepare("
            UPDATE Trips
            SET departure_city = ?, destination_city = ?, 
                departure_time = ?, arrival_time = ?, 
                price = ?, capacity = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $departure_city, $destination_city,
            $departure_datetime, $arrival_datetime,
            floatval($price), intval($seat_count),
            $trip_id, $company_id
        ]);
    }
}

// ❌ Sefer silme
if ($action === 'delete_trip' && $trip_id) {
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
}

// ===================== KUPON İŞLEMLERİ =====================
$coupon_id = $_POST['coupon_id'] ?? null;
$code = trim($_POST['code'] ?? '');
$discount = $_POST['discount'] ?? '';
$usage_limit = $_POST['usage_limit'] ?? '';
$expire_date = $_POST['expire_date'] ?? '';

// Kupon ekleme
if ($action === 'add_coupon') {
    if ($code && $discount && $usage_limit && $expire_date) {
        $stmt = $db->prepare("
            INSERT INTO Coupons (id, company_id, code, discount, usage_limit, expire_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([uniqid('COUPON_'), $company_id, strtoupper($code), floatval($discount), intval($usage_limit), $expire_date]);
    }
}

// Kupon güncelleme
if ($action === 'update_coupon' && $coupon_id) {
    if ($code && $discount && $usage_limit && $expire_date) {
        $stmt = $db->prepare("
            UPDATE Coupons
            SET code = ?, discount = ?, usage_limit = ?, expire_date = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([strtoupper($code), floatval($discount), intval($usage_limit), $expire_date, $coupon_id, $company_id]);
    }
}

// Kupon silme
if ($action === 'delete_coupon' && $coupon_id) {
    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $company_id]);
}

// ✅ İşlem sonrası dashboard'a yönlendir
header("Location: dashboard.php?msg=success");
exit;