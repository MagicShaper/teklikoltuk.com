<?php
require(__DIR__ . '/../db/connect.php');
require(__DIR__ . '/../public/fpdf/tfpdf.php');

// 🔹 GET parametresi kontrolü
$ticket_id = $_GET['ticket_id'] ?? null;
if (!$ticket_id) {
    die("Bilet ID bilgisi eksik.");
}

// 🔹 Bilet bilgilerini çek
$stmt = $db->prepare("SELECT * FROM Tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı.");
}

// 🔹 Sefer bilgilerini çek
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$ticket['trip_id']]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

// 🔹 Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT name FROM User WHERE id = ?");
$stmt->execute([$ticket['user_id']]);
$user_name = $stmt->fetchColumn() ?: "Bilinmeyen Kullanıcı";

// 🔹 Firma bilgilerini çek
$stmt = $db->prepare("SELECT name FROM BusCompany WHERE id = ?");
$stmt->execute([$trip['company_id']]);
$company_name = $stmt->fetchColumn() ?: "Bilinmeyen Firma";

// 🔹 PDF oluştur
$pdf = new tFPDF();
$pdf->AddPage();
$pdf->AddFont('DejaVu','','DejaVuSans.ttf',true);
$pdf->SetFont('DejaVu','',14);

$pdf->Cell(0,10, 'Teklikoltuk.com - Yolcu Bileti',0,1,'C');
$pdf->Ln(5);
$pdf->Cell(0,10, "Firma: $company_name",0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('DejaVu','',12);
$pdf->Cell(0,10,"Bilet No: $ticket_id",0,1);
$pdf->Cell(0,10,"Yolcu: $user_name",0,1);
$pdf->Cell(0,10,"Sefer: {$trip['departure_city']} → {$trip['destination_city']}",0,1);
$pdf->Cell(0,10,"Kalkış Tarihi: {$trip['departure_time']}",0,1);
$pdf->Ln(5);
$pdf->Cell(0,10,"Ödenen Tutar: {$ticket['total_price']} TL",0,1);
$pdf->Cell(0,10,"Durum: {$ticket['status']}",0,1);
$pdf->Cell(0,10,"Oluşturulma Tarihi: {$ticket['created_date']}",0,1);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="bilet_'.$ticket_id.'.pdf"');
$pdf->Output('I');
?>