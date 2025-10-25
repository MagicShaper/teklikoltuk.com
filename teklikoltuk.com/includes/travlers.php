<?php
session_start();
require(__DIR__ . '/../db/connect.php');
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;


$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

$query = "SELECT * FROM Trips WHERE 1=1";
$params = [];


if (!empty($from)) {
  $query .= " AND departure_city LIKE :from COLLATE NOCASE";
  $params[':from'] = "%$from%";
}
if (!empty($to)) {
  $query .= " AND destination_city LIKE :to COLLATE NOCASE";
  $params[':to'] = "%$to%";
}
if (!empty($date)) {
  $query .= " AND DATE(arrival_time) = :date"; 
  $params[':date'] = $date;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
