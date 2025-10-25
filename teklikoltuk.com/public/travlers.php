<?php
session_start();
require(__DIR__ . '/../db/connect.php');

$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$currentPage = basename($_SERVER['PHP_SELF']);

// POST verilerini al
$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';
$date = $_POST['date'] ?? '';

$query = "
  SELECT Trips.*, BusCompany.name AS company_name
  FROM Trips
  LEFT JOIN BusCompany ON Trips.company_id = BusCompany.id
  WHERE 1=1
";

$params = [];

if (!empty($from)) {
  $query .= " AND Trips.departure_city LIKE :from COLLATE NOCASE";
  $params[':from'] = "%$from%";
}
if (!empty($to)) {
  $query .= " AND Trips.destination_city LIKE :to COLLATE NOCASE";
  $params[':to'] = "%$to%";
}
if (!empty($date)) {
  $query .= " AND DATE(Trips.departure_time) = :date";
  $params[':date'] = $date;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şehir listeleri
$citiesFrom = $db->query("SELECT DISTINCT departure_city FROM Trips ORDER BY departure_city ASC")->fetchAll(PDO::FETCH_COLUMN);
$citiesTo = $db->query("SELECT DISTINCT destination_city FROM Trips ORDER BY destination_city ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sefer Ara | Teklikoltuk</title>
  <link rel="stylesheet" href="../styles2.css">
  <style>
    .trip-info { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .trip-departure, .trip-arrival { display: flex; flex-direction: column; align-items: flex-start; }
    .trip-departure .city, .trip-arrival .city { font-size: 1.2rem; font-weight: 600; color: #fff; }
    .trip-departure .time, .trip-arrival .time { font-size: 1rem; color: #ccc; }
    .trip-arrival .time.small { font-size: 0.85rem; color: #aaa; }
    .trip-company { font-size: 1rem; color: #bbb; margin-top: 2px; }
    .trip-price { display: flex; align-items: center; justify-content: center; min-width: 100px; }
    .trip-price .price { font-size: 1.1rem; font-weight: 600; color: #00d26a; }
    .trip-action { margin-left: auto; }
    .buy-btn { padding: 8px 16px; border-radius: 6px; background: #574bff; color: #fff; font-weight: 600; cursor: pointer; border: none; }
    .buy-btn:hover { background: #6b5fff; }
    .seat { display: inline-block; width: 30px; height: 30px; margin: 2px; text-align: center; line-height: 30px; border-radius: 4px; background: #444; color: #fff; cursor: pointer; }
    .seat.booked { background: #888; cursor: not-allowed; }
    .seat.selected { background: #00d26a; }
    .seat-row { margin: 6px 0; }
  </style>
</head>
<body>
<?php include("../includes/header.php") ?>

<section class="trip-search">
  <div class="trip-search__container">
    <form class="trip-search__form" method="POST">
      <div class="trip-search__fields">
        <div class="form-group">
          <label for="from">Kalkış Noktası</label>
          <select id="from" name="from">
            <option value="">-- Şehir Seçin --</option>
            <?php foreach ($citiesFrom as $city): ?>
              <option value="<?= htmlspecialchars($city) ?>" <?= ($from === $city) ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="to">Varış Noktası</label>
          <select id="to" name="to">
            <option value="">-- Şehir Seçin --</option>
            <?php foreach ($citiesTo as $city): ?>
              <option value="<?= htmlspecialchars($city) ?>" <?= ($to === $city) ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="date">Tarih</label>
          <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>">
        </div>

        <button type="submit" class="search-btn">Seferleri Göster</button>
      </div>
    </form>

    <div class="trip-results">
      <?php if ($_POST): ?>
        <h3>Uygun Seferler</h3>

        <?php if (count($trips) > 0): ?>
          <div class="trip-list">
            <?php foreach ($trips as $trip):

              // Dolu koltukları al
              $stmt = $db->prepare("
                SELECT seat_number 
                FROM BookedSeats 
                WHERE CAST(ticket_id AS TEXT) IN (
                  SELECT id FROM Tickets 
                  WHERE trip_id = ? AND status='ACTIVE'
                )
              ");
              $stmt->execute([$trip['id']]);
              $bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

              $seatCount = (int)$trip['capacity'] ?: 8;
              $half = ceil($seatCount / 2);
            ?>
<div class="trip-card" data-trip-id="<?= $trip['id'] ?>">
  <div class="trip-info">
    <div class="trip-departure">
      <span class="city"><?= htmlspecialchars($trip['departure_city']) ?></span>
      <span class="time"><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></span>
      <span class="trip-company"><?= htmlspecialchars($trip['company_name'] ?? 'Şirket Bilinmiyor') ?></span>
    </div>

    <div class="trip-arrival">
      <span class="city"><?= htmlspecialchars($trip['destination_city']) ?></span>
      <span class="time small"><?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?></span>
    </div>

    <div class="trip-price">
      <span class="price"><?= number_format($trip['price'], 0, ',', '.') ?> ₺</span>
    </div>

    <div class="trip-action">
      <button type="button" class="buy-btn" onclick="toggleSeats(<?= $trip['id'] ?>)">Koltuk Seç</button>
    </div>
  </div>

  <div class="seat-selection" id="seats-<?= $trip['id'] ?>" style="display:none;">
    <form method="POST" action="../functions/buy.php">
        <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
        <input type="hidden" name="seat_number" id="seat-input-<?= $trip['id'] ?>">
        <input type="hidden" name="total_price" value="<?= $trip['price'] ?>">

        <div class="seat-row">
          <?php for ($i = 1; $i <= $half; $i++):
            $isBooked = in_array($i, $bookedSeats);
            $class = $isBooked ? 'seat booked' : 'seat';
          ?>
            <div class="<?= $class ?>" data-seat="<?= $i ?>" data-trip="<?= $trip['id'] ?>"><?= $i ?></div>
          <?php endfor; ?>
        </div>

        <div class="aisle-row"></div>

        <div class="seat-row">
          <?php for ($i = $half + 1; $i <= $seatCount; $i++):
            $isBooked = in_array($i, $bookedSeats);
            $class = $isBooked ? 'seat booked' : 'seat';
          ?>
            <div class="<?= $class ?>" data-seat="<?= $i ?>" data-trip="<?= $trip['id'] ?>"><?= $i ?></div>
          <?php endfor; ?>
        </div>

        <p class="selected-seat" id="selected-seat-<?= $trip['id'] ?>"></p>
        <button type="submit" class="confirm-seat" disabled id="confirm-btn-<?= $trip['id'] ?>">Onayla</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:#bbb;">Seçilen kriterlere uygun sefer bulunamadı.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
function toggleSeats(tripId) {
  const section = document.getElementById(`seats-${tripId}`);
  section.style.display = section.style.display === "none" ? "block" : "none";
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('seat') && !e.target.classList.contains('booked')) {
    const tripId = e.target.dataset.trip;
    document.querySelectorAll(`#seats-${tripId} .seat`).forEach(s => s.classList.remove('selected'));
    e.target.classList.add('selected');
    document.getElementById(`seat-input-${tripId}`).value = e.target.dataset.seat;

    document.getElementById(`selected-seat-${tripId}`).textContent = `Seçilen koltuk: ${e.target.dataset.seat}`;
    document.getElementById(`confirm-btn-${tripId}`).disabled = false;
  }
});
</script>

<?php include("../includes/footer.php"); ?>
</body>
</html>