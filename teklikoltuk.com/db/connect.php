<?php
try {
    // Bağlantı: her zaman doğru dizine bağlanır
    $db = new PDO('sqlite:' . __DIR__ . '/teklikoltuk.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
