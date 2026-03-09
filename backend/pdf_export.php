<?php
require('fpdf/fpdf.php');
include_once __DIR__.'/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rolle']!=='ausbilder') exit('Zugriff verweigert.');

$wb_id = intval($_GET['wb'] ?? 0);
if($wb_id<=0) exit('Ungültige ID.');

// Wochenbericht + Aufgaben abrufen
$stmt = $conn->prepare("
    SELECT wb.*, u.vorname, u.nachname, k.name AS klassenname
    FROM wochenberichte wb
    JOIN users u ON wb.azubi_id=u.id
    JOIN klassen k ON wb.klasse_id=k.id
    WHERE wb.id=? LIMIT 1
");
$stmt->bind_param("i",$wb_id);
$stmt->execute();
$wb = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$wb) exit("Bericht nicht gefunden.");

$stmt = $conn->prepare("SELECT * FROM aufgaben WHERE wochenbericht_id=? ORDER BY erstellt_am ASC");
$stmt->bind_param("i",$wb_id);
$stmt->execute();
$aufgaben = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// PDF erstellen
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Wochenbericht",0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"Azubi: ".$wb['vorname']." ".$wb['nachname'],0,1);
$pdf->Cell(0,8,"Klasse: ".$wb['klassenname'],0,1);
$pdf->Cell(0,8,"Zeitraum: ".date('d.m.Y',strtotime($wb['startdatum']))." - ".date('d.m.Y',strtotime($wb['enddatum'])),0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,"Aufgaben:",0,1);
$pdf->SetFont('Arial','',12);
foreach($aufgaben as $a){
    $pdf->MultiCell(0,6,"- ".$a['titel']." (".$a['kategorie'].") - Status: ".$a['status'],0,1);
    if(!empty($a['kommentar'])) $pdf->MultiCell(0,6,"  Kommentar: ".$a['kommentar'],0,1);
}

$pdf->Ln(5);
$pdf->Cell(0,8,"Abgegeben: ".($wb['abgegeben']=='Y'?'Ja':'Nein'),0,1);
$pdf->Cell(0,8,"Signiert: ".($wb['signiert']=='Y'?'Ja':'Nein'),0,1);

$pdf->Output("Wochenbericht_".$wb['id'].".pdf","I");
exit;
?>


