<?php
session_start();
include('C:\xampp\htdocs\DAN\backend\db.php');
require('C:\xampp\htdocs\DAN\vendor\fpdf\fpdf.php');

if (!isset($_SESSION['user_id'])) {
    die("Nicht eingeloggt.");
}

$user_id = $_SESSION['user_id'];
$wb_id = intval($_GET['id'] ?? 0);
if ($wb_id <= 0) die("Wochenbericht-ID fehlt.");

// --- Wochenbericht abrufen ---
$stmt = $conn->prepare("
    SELECT wb.*, u.vorname, u.nachname, u.ausbildungsjahr, k.name AS klassenname
    FROM wochenberichte wb
    JOIN users u ON wb.azubi_id = u.id
    JOIN klassen k ON wb.klasse_id = k.id
    WHERE wb.id=? AND wb.azubi_id=?
");
$stmt->bind_param("ii", $wb_id, $user_id);
$stmt->execute();
$wb = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$wb) die("Bericht nicht gefunden.");

// --- Aufgaben abrufen ---
$aufgaben = [];
$result = $conn->query("SELECT * FROM aufgaben WHERE wochenbericht_id=$wb_id");
while ($row = $result->fetch_assoc()) {
    $aufgaben[] = $row;
}

// Stunden aufteilen
$betrieb_stunden = 0;
$unterweisung_stunden = 0;
$berufsschule_stunden = 0;

foreach ($aufgaben as $aufg) {
    $stunden = intval($aufg['stunden'] ?? 8); // Default 8 Std pro Aufgabe, falls nicht gesetzt
    switch ($aufg['kategorie']) {
        case 'Betrieb': $betrieb_stunden += $stunden; break;
        case 'Unterweisung': $unterweisung_stunden += $stunden; break;
        case 'Berufsschule': $berufsschule_stunden += $stunden; break;
    }
}

// --- PDF erstellen ---
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Ausbildungsnachweis (wöchentlich)',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',12);
$pdf->Cell(70,8,'Name des/der Auszubildenden:',0,0);
$pdf->Cell(0,8,$wb['vorname'].' '.$wb['nachname'],0,1);

$pdf->Cell(70,8,'Ausbildungsjahr:',0,0);
$pdf->Cell(0,8,$wb['ausbildungsjahr'] ?? '',0,1);

$pdf->Cell(70,8,'Klasse / Abteilung:',0,0);
$pdf->Cell(0,8,$wb['klassenname'],0,1);

$pdf->Cell(70,8,'Ausbildungswoche vom:',0,0);
$pdf->Cell(0,8,$wb['startdatum'].' bis '.$wb['enddatum'],0,1);

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Betriebliche Tätigkeiten',0,1);
$pdf->SetFont('Arial','',12);
if ($betrieb_stunden > 0) {
    foreach ($aufgaben as $aufg) {
        if ($aufg['kategorie']=='Betrieb') {
            $pdf->MultiCell(0,6,'- '.$aufg['titel'].' ('.$aufg['status'].') '.$aufg['kommentar'].' | Stunden: '.($aufg['stunden'] ?? 8));
        }
    }
}
$pdf->Cell(0,6,'Summe Stunden: '.$betrieb_stunden,0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Unterweisungen / Schulungen / berbetriebliche Unterweisungen',0,1);
$pdf->SetFont('Arial','',12);
if ($unterweisung_stunden > 0) {
    foreach ($aufgaben as $aufg) {
        if ($aufg['kategorie']=='Unterweisung') {
            $pdf->MultiCell(0,6,'- '.$aufg['titel'].' ('.$aufg['status'].') '.$aufg['kommentar'].' | Stunden: '.($aufg['stunden'] ?? 8));
        }
    }
}
$pdf->Cell(0,6,'Summe Stunden: '.$unterweisung_stunden,0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Themen des Berufsschulunterrichts',0,1);
$pdf->SetFont('Arial','',12);
if ($berufsschule_stunden > 0) {
    foreach ($aufgaben as $aufg) {
        if ($aufg['kategorie']=='Berufsschule') {
            $pdf->MultiCell(0,6,'- '.$aufg['titel'].' ('.$aufg['status'].') '.$aufg['kommentar'].' | Stunden: '.($aufg['stunden'] ?? 8));
        }
    }
}
$pdf->Cell(0,6,'Summe Stunden: '.$berufsschule_stunden,0,1);
$pdf->Ln(20);

// Unterschriften
$pdf->Cell(90,8,'Datum, Unterschrift Auszubildende/r',0,0);
$pdf->Cell(0,8,'Datum, Unterschrift Ausbildende/r oder Ausbilder/in',0,1);

// Signatur einfuegen: Azubi links, Ausbilder rechts
if (!empty($wb['signatur_img'])) {
    $tmp = tempnam(sys_get_temp_dir(), 'sig').'.png';
    file_put_contents($tmp, $wb['signatur_img']);
    $pdf->Image($tmp, 10, $pdf->GetY(), 80, 40);
    unlink($tmp);
}

if (!empty($wb['signatur_ausbilder_img'])) {
    $tmp = tempnam(sys_get_temp_dir(), 'sigtr').'.png';
    file_put_contents($tmp, $wb['signatur_ausbilder_img']);
    $pdf->Image($tmp, 110, $pdf->GetY(), 80, 40);
    unlink($tmp);
}
$pdf->Output('I','Wochenbericht_'.$wb['vorname'].'_'.$wb['nachname'].'.pdf');
exit;
?>




