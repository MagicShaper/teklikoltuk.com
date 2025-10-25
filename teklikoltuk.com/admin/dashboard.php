<?php
session_start();
require(__DIR__ . '/../db/connect.php');
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$companyId = $isLoggedIn ? $_SESSION['User']['company_id'] : null;

// 🔹 Sadece site admini erişimi
if ($role !== 'admin') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

// 🔹 Firmaları çek
$companies = $db->query("SELECT * FROM BusCompany ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Firma adminlerini çek
$admins = $db->query("
    SELECT u.id, u.name, u.email, COALESCE(c.name, '') AS company_name
    FROM User u
    LEFT JOIN BusCompany c ON u.company_id = c.id
    WHERE u.role = 'company'
")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Kuponları çek
$coupons = $db->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Tüm seferleri çek
$companyFilter = $_GET['company_id'] ?? null;
$query = "
    SELECT t.*, c.name AS company_name
    FROM Trips t
    INNER JOIN BusCompany c ON t.company_id = c.id
";
$params = [];
if ($companyFilter) {
    $query .= " WHERE t.company_id = ?";
    $params[] = $companyFilter;
}
$query .= " ORDER BY t.departure_time ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$all_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Kullanıcıları çek (rol user ve company)
$userFilter = $_GET['user_filter'] ?? null;
$userQuery = "SELECT * FROM User WHERE role != 'admin'";
$userParams = [];
if($userFilter){
    $userQuery .= " AND (name LIKE ? OR email LIKE ?)";
    $userParams[] = "%$userFilter%";
    $userParams[] = "%$userFilter%";
}
$userQuery .= " ORDER BY name ASC";
$users = $db->prepare($userQuery);
$users->execute($userParams);
$users = $users->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Genel Admin Paneli</title>
<link rel="stylesheet" href="../styles2.css">
<style>
    body{padding-top: 150px;}
    .admin-dashboard { max-width: 1200px; margin: 50px auto; color: #fff; }
    .admin-section { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; margin-bottom: 30px; }
    .admin-section h2 { margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #555; }
    th { background: rgba(255,255,255,0.1); }
    input, select { padding: 6px 10px; border-radius: 6px; border: none; background: rgba(255,255,255,0.1); color: #fff; margin-right: 10px; }
    button { background: var(--accent); border: none; padding: 6px 12px; border-radius: 6px; color: #fff; cursor: pointer; }
    button:hover { background: #574bff; }
    form { display: flex; flex-wrap: wrap; align-items: center; margin-bottom: 10px; gap:10px; }
</style>
</head>
<?php include("../includes/header.php")?>
<body>
<?php if (isset($_SESSION['message'])): ?>
    <div style="background:rgba(0,128,0,0.2);color:#9f9;padding:10px;border-radius:6px;margin-bottom:20px;text-align:center;">
        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>
<div class="admin-dashboard">

    <!-- 🔹 Firmalar -->
    <div class="admin-section">
        <h2>Otobüs Firmaları</h2>
        <form action="admin_actions.php" method="POST">
            <input type="hidden" name="action" value="add_company">
            <input type="text" name="name" placeholder="Firma adı" required>
            <button type="submit">Firma Ekle</button>
        </form>
        <table>
            <tr>
                <th>ID</th>
                <th>Firma Adı</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach($companies as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td>
                    <form action="admin_actions.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_company">
                        <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                        <button type="submit" onclick="return confirm('Firma silinecek!')">Sil</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- 🔹 Firma Adminleri -->
    <div class="admin-section">
        <h2>Firma Adminleri</h2>
        <form action="admin_actions.php" method="POST">
            <input type="hidden" name="action" value="add_company_admin">
            <input type="text" name="name" placeholder="Ad Soyad" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <select name="company_id" required>
                <option value="">Firma Seçin</option>
                <?php foreach($companies as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Admin Ekle</button>
        </form>
        <table>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Email</th>
                <th>Firma</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach($admins as $a): ?>
<tr>
    <td><?= htmlspecialchars($a['id'] ?? '') ?></td>
    <td><?= htmlspecialchars($a['name'] ?? '') ?></td>
    <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
    <td><?= htmlspecialchars($a['company_name'] ?? '—') ?></td>
    <td>
        <form action="admin_actions.php" method="POST" style="display:inline;">
            <input type="hidden" name="action" value="delete_admin">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($a['id'] ?? '') ?>">
            <button type="submit" onclick="return confirm('Admin silinecek!')">Sil</button>
        </form>
    </td>
</tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- 🔹 Kullanıcı Yönetimi -->
    <div class="admin-section">
        <h2>Kullanıcılar</h2>

        <!-- Filtreleme -->
        <form method="GET" style="margin-bottom:15px; gap:10px; display:flex; align-items:center; flex-wrap:wrap;">
            <input type="text" name="user_filter" placeholder="İsim veya Email ile ara" value="<?= htmlspecialchars($_GET['user_filter'] ?? '') ?>">
            <button type="submit">Ara</button>
            <?php if(isset($_GET['user_filter'])): ?>
                <a href="dashboard.php" style="color:#fff; text-decoration:underline;">Filtreyi Temizle</a>
            <?php endif; ?>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Email</th>
                <th>Bakiye</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </tr>
            <?php if(count($users) > 0): ?>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['id']) ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['balance'] ?? 0) ?> TL</td>
                    <td>
                        <form action="admin_actions.php" method="POST">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Kullanıcı</option>
                                <option value="company" <?= $u['role'] === 'company' ? 'selected' : '' ?>>Firma Admini</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="admin_actions.php" method="POST" style="display:inline; margin-right:5px;">
                            <input type="hidden" name="action" value="add_balance">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="number" name="amount" placeholder="Miktar" style="width:80px;" required>
                            <button type="submit">Ekle</button>
                        </form>
                        <form action="admin_actions.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" onclick="return confirm('Kullanıcı silinecek!')">Sil</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#bbb;">Kullanıcı bulunamadı.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- 🔹 Tüm Seferler -->
    <div class="admin-section">
        <h2>Tüm Firmaların Seferleri</h2>
        <form method="GET" style="margin-bottom:15px; gap:10px; display:flex; align-items:center; flex-wrap:wrap;">
            <select name="company_id">
                <option value="">Tüm Firmalar</option>
                <?php foreach($companies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($companyFilter == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrele</button>
            <?php if($companyFilter): ?>
                <a href="dashboard.php" style="color:#fff; text-decoration:underline;">Filtreyi Temizle</a>
            <?php endif; ?>
        </form>
        <table>
            <tr>
                <th>ID</th>
                <th>Firma</th>
                <th>Kalkış</th>
                <th>Varış</th>
                <th>Kalkış Saati</th>
                <th>Varış Saati</th>
                <th>Fiyat</th>
                <th>Kapasite</th>
            </tr>
            <?php if(count($all_trips) > 0): ?>
                <?php foreach($all_trips as $trip): ?>
                <tr>
                    <td><?= $trip['id'] ?></td>
                    <td><?= htmlspecialchars($trip['company_name']) ?></td>
                    <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                    <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                    <td><?= htmlspecialchars($trip['departure_time']) ?></td>
                    <td><?= htmlspecialchars($trip['arrival_time']) ?></td>
                    <td><?= htmlspecialchars($trip['price']) ?> TL</td>
                    <td><?= htmlspecialchars($trip['capacity']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center; color:#bbb;">Sefer bulunamadı.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- 🔹 Kuponlar -->
    <div class="admin-section">
        <h2>Kuponlar</h2>
        <form action="admin_actions.php" method="POST">
            <input type="hidden" name="action" value="add_coupon">
            <input type="text" name="code" placeholder="Kupon Kodu" required>
            <input type="number" name="discount" placeholder="İndirim (%)" min="0" max="100" required>
            <input type="number" name="usage_limit" placeholder="Kullanım Limiti" min="1" required>
            <input type="date" name="expire_date" required>
            <button type="submit">Kupon Ekle</button>
        </form>
        <table>
            <tr>
                <th>Kod</th>
                <th>İndirim (%)</th>
                <th>Kullanım Limiti</th>
                <th>Son Kullanma Tarihi</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach($coupons as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['code']) ?></td>
                <td><?= $c['discount'] ?></td>
                <td><?= $c['usage_limit'] ?></td>
                <td><?= htmlspecialchars($c['expire_date']) ?></td>
                <td>
                    <form action="admin_actions.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_coupon">
                        <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                        <button type="submit" onclick="return confirm('Kupon silinecek!')">Sil</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>
<?php include("../includes/footer.php")?>
</body>
</html>