<?php
session_start();
require(__DIR__ . '/../db/connect.php');

$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : 'admin';
$user = isset($_SESSION['User']);
$userId = $user ? $_SESSION['User']['id'] : null;

// 🔒 Yalnızca admin erişebilsin
if (!$isLoggedIn || $_SESSION['User']['role'] !== 'admin') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

$action = $_POST['action'] ?? '';
$message = '';

try {
    switch ($action) {

        // 🏢 Yeni firma ekleme
        case 'add_company':
            $company_name = trim($_POST['name'] ?? '');
            $logo_path = trim($_POST['logo'] ?? '');

            if ($company_name !== '') {
                $stmt = $db->prepare("INSERT INTO BusCompany (name, logo) VALUES (?, ?)");
                $stmt->execute([$company_name, $logo_path ?: null]);
                $message = "Firma başarıyla eklendi.";
            } else {
                $message = "Firma adı boş olamaz.";
            }
            break;

        // 🧹 Firma silme
        case 'delete_company':
            $company_id = $_POST['company_id'] ?? '';
            if ($company_id) {
                $stmt = $db->prepare("DELETE FROM BusCompany WHERE id = ?");
                $stmt->execute([$company_id]);
                $message = "Firma başarıyla silindi.";
            }
            break;

        // 👤 Firma admini ekleme
        case 'add_company_admin':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $company_id = $_POST['company_id'] ?? '';

            if ($name && $email && $password && $company_id) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO User (name, email, password, role, company_id) VALUES (?, ?, ?, 'company', ?)");
                $stmt->execute([$name, $email, $hashed, $company_id]);
                $message = "Firma yöneticisi başarıyla eklendi.";
            } else {
                $message = "Tüm alanları doldurmalısınız.";
            }
            break;

        // 👤 Firma admini silme
        case 'delete_admin':
            $user_id = $_POST['user_id'] ?? '';
            if ($user_id) {
                $stmt = $db->prepare("DELETE FROM User WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "Firma yöneticisi silindi.";
            }
            break;
            // 🔄 Kullanıcı rolünü değiştirme
            case 'change_role':
                $user_id = $_POST['user_id'] ?? '';
                $new_role = $_POST['role'] ?? '';

                // Geçerli veriler kontrolü
                if ($user_id && in_array($new_role, ['user', 'company'])) {

                    // Site admininin rolünü değiştirmeye çalışmayı engelle
                    $stmt = $db->prepare("SELECT role FROM User WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $currentRole = $stmt->fetchColumn();

                    if ($currentRole === 'admin') {
                        $message = "Site admininin rolü değiştirilemez!";
                    } else {
                        $stmt = $db->prepare("UPDATE User SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        $message = "Kullanıcı rolü başarıyla güncellendi.";
                    }

                } else {
                    $message = "Geçerli kullanıcı ve rol seçin.";
                }
                break;
        // 🎟️ Kupon ekleme
        case 'add_coupon':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discount = floatval($_POST['discount'] ?? 0);
            $usage_limit = intval($_POST['usage_limit'] ?? 0);
            $expire_date = trim($_POST['expire_date'] ?? '');

            if ($code && $discount > 0 && $usage_limit > 0 && $expire_date) {
                $stmt = $db->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([uniqid('COUPON_'), $code, $discount, $usage_limit, $expire_date]);
                $message = "Kupon başarıyla oluşturuldu.";
            } else {
                $message = "Tüm kupon bilgilerini doldurmalısınız.";
            }
            break;

        // 🎟️ Kupon silme
        case 'delete_coupon':
            $coupon_id = $_POST['coupon_id'] ?? '';
            if ($coupon_id) {
                $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");
                $stmt->execute([$coupon_id]);
                $message = "Kupon başarıyla silindi.";
            }
            break;

        // 💰 Kullanıcıya bakiye ekleme
        case 'add_balance':
            $user_id = $_POST['user_id'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);

            if ($user_id && $amount > 0) {
                $stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $new_balance = ($user['balance'] ?? 0) + $amount;
                    $stmt = $db->prepare("UPDATE User SET balance = ? WHERE id = ?");
                    $stmt->execute([$new_balance, $user_id]);
                    $message = "Kullanıcının bakiyesi başarıyla güncellendi.";
                } else {
                    $message = "Kullanıcı bulunamadı.";
                }
            } else {
                $message = "Geçerli bir kullanıcı ve bakiye miktarı girin.";
            }
            break;

        // 👤 Kullanıcı silme
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? '';
            if ($user_id) {
                $stmt = $db->prepare("DELETE FROM User WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "Kullanıcı başarıyla silindi.";
            } else {
                $message = "Silinecek kullanıcı seçilmedi.";
            }
            break;

        default:
            $message = "Geçersiz işlem.";
    }
} catch (Exception $e) {
    $message = "Bir hata oluştu: " . $e->getMessage();
}

// 🔁 Geri yönlendir
$_SESSION['message'] = $message;
header("Location: dashboard.php");
exit;
?>