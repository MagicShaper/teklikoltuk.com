<?php
session_start();
require(__DIR__ . '/../db/connect.php');
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['User'])) {
    die("Bu sayfaya erişim için giriş yapmalısınız.");
}

$user_id = $_SESSION['User']['id'];
$now = date('Y-m-d H:i:s');

// Kullanıcının biletleri ve sefer bilgilerini çek, BookedSeats ile join
$stmt = $db->prepare("
    SELECT 
        t.id as ticket_id, 
        tr.price, 
        tr.departure_city, 
        tr.destination_city, 
        tr.departure_time, 
        tr.arrival_time, 
        GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number ASC) as seats
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    LEFT JOIN BookedSeats bs ON bs.ticket_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY tr.departure_time ASC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seferleri Gelecek ve Geçmiş olarak ayır
$upcoming = [];
$past = [];

foreach ($tickets as $t) {
    $departure = new DateTime($t['departure_time']);
    $t['departure_dt'] = $departure;
    if ($departure >= new DateTime()) {
        $upcoming[] = $t;
    } else {
        $past[] = $t;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profilim</title>
<link rel="stylesheet" href="../styles2.css">
<style>
body { padding-top: 120px; background: #111; color: #fff; font-family: sans-serif; }
.profile-container { max-width: 900px; margin: 0 auto; padding: 20px; }
h1 { text-align: center; margin-bottom: 30px; }

.tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.tabs button { flex: 1; padding: 10px; background: #222; border: none; border-radius: 6px; color: #fff; cursor: pointer; font-weight: 600; }
.tabs button.active { background: #574bff; }

.ticket-card {
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: transform 0.2s;
}
.ticket-card:hover { transform: scale(1.01); }

.ticket-card .row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.ticket-card .row div { flex: 1 1 45%; }
@media (max-width:600px) { .ticket-card .row div { flex: 1 1 100%; } }

.ticket-card button {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    background: #ff4b5c;
    color: #fff;
    cursor: pointer;
    font-weight: 600;
    margin-top: 10px;
}
.ticket-card button:hover { background: #ff6b7a; }

.message { text-align: center; margin-bottom: 20px; padding: 10px; border-radius: 6px; }
.message.success { background: rgba(0,255,0,0.2); color: lightgreen; }
.message.fail { background: rgba(255,0,0,0.2); color: #ff6b6b; }
</style>
<script>
function openTab(tabName) {
    const tabs = document.querySelectorAll('.ticket-section');
    tabs.forEach(t => t.style.display = 'none');

    document.getElementById(tabName).style.display = 'block';

    document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
    document.querySelector(`button[data-tab="${tabName}"]`).classList.add('active');
}

window.onload = () => {
    openTab('upcoming'); // Varsayılan olarak gelecek seferler açık
};
</script>
</head>
<body>
<?php include("../includes/header.php")?>
<div class="profile-container">
    <h1>Merhaba, <?= htmlspecialchars($_SESSION['User']['name'] ?? 'Kullanıcı') ?></h1>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'cancel_success'): ?>
            <div class="message success">✅ Biletiniz başarıyla iptal edildi ve ödenen tutar iade edildi.</div>
        <?php elseif ($_GET['msg'] === 'cancel_fail'): ?>
            <div class="message fail">⚠️ Bilet iptali yapılamadı. Sefer kalkışına 1 saatten az kaldı.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="tabs">
        <button data-tab="upcoming" onclick="openTab('upcoming')">Gelecek Seferler (<?= count($upcoming) ?>)</button>
        <button data-tab="past" onclick="openTab('past')">Geçmiş Seferler (<?= count($past) ?>)</button>
    </div>

    <!-- Gelecek Seferler -->
    <div id="upcoming" class="ticket-section">
        <?php if (empty($upcoming)): ?>
            <p>Henüz gelecek seferiniz yok.</p>
        <?php else: ?>
            <?php foreach ($upcoming as $t):
                $departure = $t['departure_dt'];
                $arrival = new DateTime($t['arrival_time']);
            ?>
            <div class="ticket-card">
                <div class="row">
                    <div><strong>Kalkış:</strong> <?= htmlspecialchars($t['departure_city']) ?></div>
                    <div><strong>Varış:</strong> <?= htmlspecialchars($t['destination_city']) ?></div>
                </div>
                <div class="row">
                    <div><strong>Kalkış Tarih & Saat:</strong> <?= $departure->format('d.m.Y H:i') ?></div>
                    <div><strong>Varış Tarih & Saat:</strong> <?= $arrival->format('d.m.Y H:i') ?></div>
                </div>
                <div class="row">
                    <div><strong>Fiyat:</strong> <?= htmlspecialchars($t['price']) ?>₺</div>
                    <div><strong>Koltuk:</strong> <?= htmlspecialchars($t['seats'] ?? '-') ?></div>
                </div>
                <form action="../functions/user_actions.php" method="POST" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?')">
                    <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                    <button type="submit" name="action" value="cancel_ticket">Bileti İptal Et</button>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Geçmiş Seferler -->
    <div id="past" class="ticket-section" style="display:none;">
        <?php if (empty($past)): ?>
            <p>Henüz geçmiş seferiniz yok.</p>
        <?php else: ?>
            <?php foreach ($past as $t):
                $departure = $t['departure_dt'];
                $arrival = new DateTime($t['arrival_time']);
            ?>
            <div class="ticket-card">
                <div class="row">
                    <div><strong>Kalkış:</strong> <?= htmlspecialchars($t['departure_city']) ?></div>
                    <div><strong>Varış:</strong> <?= htmlspecialchars($t['destination_city']) ?></div>
                </div>
                <div class="row">
                    <div><strong>Kalkış Tarih & Saat:</strong> <?= $departure->format('d.m.Y H:i') ?></div>
                    <div><strong>Varış Tarih & Saat:</strong> <?= $arrival->format('d.m.Y H:i') ?></div>
                </div>
                <div class="row">
                    <div><strong>Fiyat:</strong> <?= htmlspecialchars($t['price']) ?>₺</div>
                    <div><strong>Koltuk:</strong> <?= htmlspecialchars($t['seats'] ?? '-') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include("../includes/footer.php") ?>
</body>
</html>