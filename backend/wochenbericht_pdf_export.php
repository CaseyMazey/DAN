<?php
// backend/wochenbericht_pdf_export.php
session_start();

require_once __DIR__ . '/wochenbericht_task_loader.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$rolle   = $_SESSION['rolle'] ?? '';

$wb_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($wb_id <= 0) {
    die("Ungültige Wochenbericht-ID.");
}

// Bericht + User laden (Zugriffsschutz je nach Rolle)
$stmt = $conn->prepare("
    SELECT wb.*,
           u.vorname, u.nachname, u.ausbildungsjahr, u.abteilung,
           COALESCE(k_wb.name, k_user.name, '') AS klassenname
    FROM wochenberichte wb
    JOIN users u         ON wb.azubi_id   = u.id
    LEFT JOIN klassen k_wb   ON wb.klasse_id  = k_wb.id
    LEFT JOIN klassen k_user ON u.klasse_id   = k_user.id
    WHERE wb.id = ?
      AND (
            (? = 'ausbilder' AND (
                (k_wb.ausbilder_id IS NOT NULL AND k_wb.ausbilder_id = ?)
             OR (k_user.ausbilder_id IS NOT NULL AND k_user.ausbilder_id = ?)
            ))
         OR (? = 'azubi' AND wb.azubi_id = ?)
      )
    LIMIT 1
");
$stmt->bind_param("isiiii", $wb_id, $rolle, $user_id, $user_id, $rolle, $user_id);

$stmt->execute();
$result = $stmt->get_result();
$bericht = $result->fetch_assoc();
$stmt->close();

if (!$bericht) {
    die("Bericht nicht gefunden oder keine Berechtigung.");
}

$azubi_name      = trim(($bericht['vorname'] ?? '') . ' ' . ($bericht['nachname'] ?? ''));
$ausbildungsjahr = $bericht['ausbildungsjahr'] ?? '';
$abteilung       = $bericht['abteilung'] ?? '';
$klasse          = $bericht['klassenname'] ?? '';
$startdatum      = $bericht['startdatum'];
$enddatum        = $bericht['enddatum'];

$wrapLine = function(string $text, int $limit = 15): array {
    $lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
    $subFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
    $posFn = function_exists('mb_strrpos') ? 'mb_strrpos' : 'strrpos';

    if ($lenFn($text) <= $limit) {
        return [$text, ''];
    }
    $snippet = $subFn($text, 0, $limit + 1);
    $spacePos = $posFn($snippet, ' ');
    $cut = ($spacePos !== false) ? $spacePos : $limit;
    $line1 = trim($subFn($text, 0, $cut));
    $line2 = trim($subFn($text, $cut));
    if ($line2 !== '' && !preg_match('/[-]$/', $line1)) {
        $line1 .= '-';
    }
    return [$line1, $line2];
};

$start_fmt = date('d.m.Y', strtotime($startdatum));
$end_fmt   = date('d.m.Y', strtotime($enddatum));

$tasks = loadWochenberichtTasks($conn, $bericht);


$blocks = [
    'betrieb'       => [],
    'unterweisung'  => [],
    'berufsschule'  => []
];

$stunden_betrieb_real = 0.0;
$stunden_schule_real  = 0.0;
$stunden_unterweisung_real_betrieb = 0.0;
$stunden_unterweisung_real_schule  = 0.0;

foreach ($tasks as $row) {
    $parts = [];
    $hourVal = is_numeric($row['stunden'] ?? null) ? (float)$row['stunden'] : 0.0;

    if (!empty($row['titel'])) {
        $parts[] = trim($row['titel']);
    }
    if (!empty($row['beschreibung'])) {
        $parts[] = trim($row['beschreibung']);
    }
    if (!empty($row['kommentar'])) {
        $parts[] = 'Kommentar: ' . trim($row['kommentar']);
    }

    $line = '- ' . implode(' | ', $parts);

    $kat = strtolower(trim($row['kategorie'] ?? ''));
    $stundenTyp = strtolower(trim($row['stunden_typ'] ?? ''));

    if ($kat === 'unterweisung') {
        if ($hourVal > 0) {
            if ($stundenTyp === 'berufsschule') {
                $stunden_unterweisung_real_schule += $hourVal;
            } else {
                $stunden_unterweisung_real_betrieb += $hourVal;
            }
        }
        $blocks['unterweisung'][] = $line;
    } elseif ($kat === 'berufsschule') {
        if ($hourVal > 0) {
            $stunden_schule_real += $hourVal;
        }
        $blocks['berufsschule'][] = $line;
    } else {
        if ($hourVal > 0) {
            $stunden_betrieb_real += $hourVal;
        }
        $blocks['betrieb'][] = $line;
    }
}

$block_betrieb      = $blocks['betrieb'] ? implode("\n", $blocks['betrieb']) : '-';
$block_schulung     = $blocks['unterweisung'] ? implode("\n", $blocks['unterweisung']) : '-';
$block_berufsschule = $blocks['berufsschule'] ? implode("\n", $blocks['berufsschule']) : '-';

$stunden_gesamt = $stunden_betrieb_real + $stunden_schule_real;
$fmt = fn($val) => number_format((float)$val, 2, ',', '.');
$stunden_betrieb_real_fmt = $fmt($stunden_betrieb_real);
$stunden_schule_real_fmt  = $fmt($stunden_schule_real);
$stunden_unterweisung_sum_fmt = $fmt($stunden_unterweisung_real_betrieb + $stunden_unterweisung_real_schule);
$stunden_gesamt_fmt = $fmt($stunden_gesamt);

$config = getStundenConfigUser($conn, (int)($bericht['azubi_id'] ?? 0));
$maxBetrieb = (float)($config['max_betrieb'] ?? 0);
$maxSchule  = (float)($config['max_berufsschule'] ?? 0);
$verfuegbar_betrieb = max(0, $maxBetrieb - $stunden_unterweisung_real_betrieb);
$verfuegbar_schule  = max(0, $maxSchule - $stunden_unterweisung_real_schule);
$verfuegbar_betrieb_fmt = $fmt($verfuegbar_betrieb);
$verfuegbar_schule_fmt  = $fmt($verfuegbar_schule);

// PDF erzeugen, IHK-Vorlage einbinden
$templatePath = __DIR__ . '/../assets/Vorlage Wochenbericht.pdf';
if (!is_file($templatePath)) {
    // Fallback falls Name anders geschrieben ist
    $altPath = __DIR__ . '/../assets/Vorlage_Wochenbericht.pdf';
    $templatePath = is_file($altPath) ? $altPath : '';
}
if (!$templatePath) {
    die('PDF-Vorlage nicht gefunden.');
}

$pdf = new Fpdi();
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);
$pageCount = $pdf->setSourceFile($templatePath);
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx, 0, 0, 210);

$pdf->SetFont('Helvetica', '', 10);

// Kopfbereich
$pdf->SetXY(85, 46.5);
$pdf->Write(5, utf8_decode($azubi_name));

$pdf->SetXY(85, 53);
$pdf->Write(5, utf8_decode((string)$ausbildungsjahr));

$deptText = $abteilung !== '' ? $abteilung : $klasse;
[$deptLine1, $deptLine2] = $wrapLine($deptText, 15);
$pdf->SetXY(150.5, 53);
$pdf->Write(5, utf8_decode($deptLine1));
if ($deptLine2 !== '') {
    $pdf->SetXY(150.5, 58);
    $pdf->Write(5, utf8_decode($deptLine2));
}

$pdf->SetXY(84.5, 64);
$pdf->Write(5, utf8_decode($start_fmt));

$pdf->SetXY(116, 64);
$pdf->Write(5, utf8_decode($end_fmt));

// Textblöcke
$pdf->SetFont('Helvetica', '', 9);

$pdf->SetXY(27, 88);
$pdf->MultiCell(170, 4, utf8_decode($block_betrieb), 0, 'L');

$pdf->SetXY(27, 139);
$pdf->MultiCell(170, 4, utf8_decode($block_schulung), 0, 'L');

$pdf->SetXY(27, 189);
$pdf->MultiCell(170, 4, utf8_decode($block_berufsschule), 0, 'L');

// Rest- und Summenwerte rechts neben den Blöcken ausgeben (nur Zahl)
$rightX = 179;
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetXY($rightX, 88);
$pdf->Write(5, utf8_decode($verfuegbar_betrieb_fmt));
$pdf->SetXY($rightX, 139);
$pdf->Write(5, utf8_decode($stunden_unterweisung_sum_fmt));
$pdf->SetXY($rightX, 189);
$pdf->Write(5, utf8_decode($verfuegbar_schule_fmt));


// Unterschriften platzieren: Azubi links, Ausbilder rechts
$signatureY = max(235, $pdf->GetY() + 8);
if (!empty($bericht['signatur_img'])) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
    file_put_contents($tmpFile, $bericht['signatur_img']);
    $pdf->Image($tmpFile, 40, $signatureY, 40, 15); // Azubi links
    @unlink($tmpFile);
}

if (!empty($bericht['signatur_ausbilder_img'])) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'sig_trainer_') . '.png';
    file_put_contents($tmpFile, $bericht['signatur_ausbilder_img']);
    $pdf->Image($tmpFile, 140, $signatureY, 40, 15); // Ausbilder rechts
    @unlink($tmpFile);
}

$filename = 'Wochenbericht_' . preg_replace('/\s+/', '_', $azubi_name) . '_' . $startdatum . '_' . $enddatum . '.pdf';
if (ob_get_length()) {
    ob_end_clean(); // clear any stray output before sending the PDF headers
}
$pdf->Output('D', $filename);
exit;
