<?php
session_start();
require(__DIR__ . '/../db/connect.php');
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$company_id = $_SESSION['User']['company_id'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
// 🔒 Firma admini kontrolü
if (!isset($_SESSION['User']) || $_SESSION['User']['role'] !== 'company') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

// Firma bilgisi
if ($company_id) {
    $stmt = $db->prepare("SELECT name FROM BusCompany WHERE id = ?");
    $stmt->execute([$company_id]);
    $company_name = $stmt->fetchColumn();
} else {
    $company_name = 'Firma Bulunamadı';
}

// Filtreleme değerleri
$filter_from = $_GET['filter_from'] ?? '';
$filter_to = $_GET['filter_to'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Seferler
$query = "SELECT * FROM Trips WHERE company_id = ?";
$params = [$company_id];

if ($filter_from !== '') {
    $query .= " AND departure_city LIKE ?";
    $params[] = "%$filter_from%";
}
if ($filter_to !== '') {
    $query .= " AND destination_city LIKE ?";
    $params[] = "%$filter_to%";
}
if ($filter_date !== '') {
    $query .= " AND DATE(departure_time) = ?";
    $params[] = $filter_date;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kuponlar
$stmt = $db->prepare("SELECT * FROM Coupons WHERE company_id = ?");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($company_name) ?> Paneli</title>
<link rel="stylesheet" href="../styles2.css">
<style>
body { padding-top: 190px; }
.dashboard-container { max-width: 1200px; margin: 60px auto; background: rgba(255,255,255,0.05); padding: 25px; border-radius: 10px; }
.section { margin-top: 40px; }
.section h2 { border-bottom: 2px solid var(--accent); padding-bottom: 8px; margin-bottom: 20px; }

/* Kart tasarımı */
.trip-card {
  display: flex;
  flex-direction: column;
  background: rgba(255,255,255,0.05);
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
  gap: 10px;
}
.trip-card .row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.trip-card .row > div {
  flex: 1 1 45%;
  min-width: 120px;
}
.trip-card button {
  flex: 1 1 48%;
  margin-top: 10px;
}
@media (max-width: 600px) {
  .trip-card .row > div,
  .trip-card button {
    flex: 1 1 100%;
  }
}

input, select { background: rgba(255,255,255,0.1); border: none; color: white; padding: 8px; border-radius: 6px; width: 100%; }
button { padding: 8px 12px; border: none; border-radius: 6px; background: var(--accent); color: #fff; cursor: pointer; font-weight: 600; }
button:hover { background: #574bff; }
.add-form { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; margin-top: 20px; }
</style>
</head>
<body>
    
<?php include("../includes/header.php")?>
<div class="dashboard-container">
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'success'): ?>
            <p style="color:lightgreen;">✅ İşlem başarıyla tamamlandı.</p>
        <?php elseif ($_GET['msg'] === 'wrong_password'): ?>
            <p style="color:red;">❌ Mevcut şifre yanlış!</p>
        <?php elseif ($_GET['msg'] === 'empty_password'): ?>
            <p style="color:red;">❌ Lütfen tüm alanları doldurun.</p>
        <?php elseif ($_GET['msg'] === 'user_not_found'): ?>
            <p style="color:red;">❌ Kullanıcı bulunamadı!</p>
        <?php endif; ?>
    <?php endif; ?>

<div class="section">
  <h1><?= htmlspecialchars($company_name) ?> - Yönetim Paneli</h1>
  <h2>🔒 Şifre Değiştir</h2>
  <form action="company_actions.php" method="POST" class="add-form">
      <input type="password" name="current_password" placeholder="Mevcut Şifre" required>
      <input type="password" name="new_password" placeholder="Yeni Şifre" required>
      <input type="password" name="confirm_password" placeholder="Yeni Şifre Tekrar" required>
      <button type="submit" name="action" value="change_password">Şifreyi Güncelle</button>
  </form>
</div>

<!-- 🚌 SEFERLER -->
<div class="section">
  <h2>🚌 Sefer Yönetimi</h2>

  <!-- Filtreleme Formu -->
  <div class="add-form trip-card">
      <h3>Seferleri Filtrele</h3>
      <form action="" method="GET">
          <div class="row">
              <div>
                  <input type="text" name="filter_from" placeholder="Kalkış Şehri" value="<?= htmlspecialchars($_GET['filter_from'] ?? '') ?>">
              </div>
              <div>
                  <input type="text" name="filter_to" placeholder="Varış Şehri" value="<?= htmlspecialchars($_GET['filter_to'] ?? '') ?>">
              </div>
              <div>
                  <input type="date" name="filter_date" placeholder="Tarih" value="<?= htmlspecialchars($_GET['filter_date'] ?? '') ?>">
              </div>
          </div>
          <button type="submit" style="margin-top:10px;">Filtrele</button>
          <a href="dashboard.php" style="margin-left:10px; color:#fff; background:#555; padding:8px 12px; border-radius:6px; text-decoration:none;">Temizle</a>
      </form>
  </div>
  
  <?php foreach ($trips as $t):
      $departure = new DateTime($t['departure_time']);
      $arrival = new DateTime($t['arrival_time']);
  ?>
  <form action="company_actions.php" method="POST" class="trip-card">
      <div class="row">
          <div>
              <label>Kalkış Şehir</label>
              <input type="text" name="from" value="<?= htmlspecialchars($t['departure_city']) ?>" required>
          </div>
          <div>
              <label>Varış Şehir</label>
              <input type="text" name="to" value="<?= htmlspecialchars($t['destination_city']) ?>" required>
          </div>
      </div>
      
      <div class="row">
          <div>
              <label>Kalkış Tarih & Saat</label>
              <input type="date" name="departure_date" value="<?= $departure->format('Y-m-d') ?>" required>
              <input type="time" name="departure_time" value="<?= $departure->format('H:i') ?>" required>
          </div>
          <div>
              <label>Varış Tarih & Saat</label>
              <input type="date" name="arrival_date" value="<?= $arrival->format('Y-m-d') ?>" required>
              <input type="time" name="arrival_time" value="<?= $arrival->format('H:i') ?>" required>
          </div>
      </div>
      
      <div class="row">
          <div>
              <label>Fiyat (₺)</label>
              <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($t['price']) ?>" required>
          </div>
          <div>
              <label>Koltuk</label>
              <input type="number" name="seat_count" value="<?= htmlspecialchars($t['capacity']) ?>" required>
          </div>
      </div>
      
      <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
      <div class="row">
          <button type="submit" name="action" value="update_trip">Kaydet</button>
          <button type="submit" name="action" value="delete_trip" onclick="return confirm('Bu seferi silmek istediğine emin misin?')">Sil</button>
      </div>
  </form>
  <?php endforeach; ?>

  <!-- Yeni Sefer Ekle -->
  <div class="add-form trip-card">
    <h3>Yeni Sefer Ekle</h3>
    <form action="company_actions.php" method="POST">
        <div class="row">
            <div>
                <input type="text" name="from" placeholder="Kalkış Yeri" required>
            </div>
            <div>
                <input type="text" name="to" placeholder="Varış Yeri" required>
            </div>
        </div>
        <div class="row">
            <div>
                <input type="date" name="departure_date" required>
                <input type="time" name="departure_time" required>
            </div>
            <div>
                <input type="date" name="arrival_date" placeholder="Varış Tarihi">
                <input type="time" name="arrival_time" placeholder="Varış Saati">
            </div>
        </div>
        <div class="row">
            <div>
                <input type="number" name="price" step="0.01" placeholder="Fiyat" required>
            </div>
            <div>
                <input type="number" name="seat_count" placeholder="Koltuk" required>
            </div>
        </div>
        <button type="submit" name="action" value="add_trip" style="margin-top:10px;">Sefer Ekle</button>
    </form>
  </div>
</div>

<!-- 🎟️ KUPONLAR -->
<div class="section">
  <h2>🎟️ Kupon Yönetimi</h2>
  <?php foreach ($coupons as $c): ?>
  <form action="company_actions.php" method="POST" class="trip-card">
      <div class="row">
          <div><input type="text" name="code" value="<?= htmlspecialchars($c['code']) ?>"></div>
          <div><input type="number" name="discount" value="<?= htmlspecialchars($c['discount']) ?>"></div>
          <div><input type="number" name="usage_limit" value="<?= htmlspecialchars($c['usage_limit']) ?>"></div>
          <div><input type="date" name="expire_date" value="<?= htmlspecialchars($c['expire_date']) ?>"></div>
      </div>
      <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
      <div class="row">
          <button type="submit" name="action" value="update_coupon">💾</button>
          <button type="submit" name="action" value="delete_coupon" onclick="return confirm('Bu kuponu silmek istediğine emin misin?')">🗑️</button>
      </div>
  </form>
  <?php endforeach; ?>

  <div class="add-form trip-card">
    <h3>Yeni Kupon Ekle</h3>
    <form action="company_actions.php" method="POST">
        <div class="row">
            <div><input type="text" name="code" placeholder="Kod" required></div>
            <div><input type="number" name="discount" placeholder="İndirim (%)" required></div>
            <div><input type="number" name="usage_limit" placeholder="Kullanım Limiti" required></div>
            <div><input type="date" name="expire_date" required></div>
        </div>
        <button type="submit" name="action" value="add_coupon" style="margin-top:15px;">Kupon Ekle</button>
    </form>
  </div>
</div>
  </div>
<?php include("../includes/footer.php")?>
</body>
</html>