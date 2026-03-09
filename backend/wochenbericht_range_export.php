<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/wochenbericht_task_loader.php';


use setasign\Fpdi\Fpdi;

if (!isset($_SESSION['user_id'])) {
    exit('Nicht eingeloggt.');
}

$rolle = $_SESSION['rolle'] ?? '';
$request  = $_POST + $_GET;
$azubiId  = 0;

if ($rolle === 'ausbilder') {
    $azubiId = isset($request['azubi']) ? (int)$request['azubi'] : 0;
    if ($azubiId <= 0) {
        exit('Azubi-ID fehlt.');
    }
    $stmt = $conn->prepare("
        SELECT 1
        FROM users u
        JOIN klassen k ON u.klasse_id = k.id
        WHERE u.id = ? AND k.ausbilder_id = ?
        LIMIT 1
    ");
    $trainerId = (int)$_SESSION['user_id'];
    $stmt->bind_param("ii", $azubiId, $trainerId);
    $stmt->execute();
    $allowed = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$allowed) {
        exit('Keine Berechtigung fuer diesen Azubi.');
    }
} else {
    $azubiId = (int)$_SESSION['user_id'];
}

$h = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');

$parseIds = function ($raw): array {
    if (is_array($raw)) {
        $list = $raw;
    } elseif (is_string($raw)) {
        $list = preg_split('/[,;]+/', $raw);
    } else {
        $list = [];
    }
    $ids = [];
    foreach ($list as $val) {
        $id = (int)$val;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return array_values(array_unique($ids));
};

$ids            = $parseIds($request['ids'] ?? []);
$filterYear     = isset($request['year']) ? (int)$request['year'] : 0;
$filterMonth    = isset($request['month']) ? (int)$request['month'] : 0;
$filterStatus   = isset($request['status']) ? trim((string)$request['status']) : '';
$filterKw       = isset($request['kw']) ? (int)$request['kw'] : 0;
$filterKwYear   = isset($request['kw_year']) ? (int)$request['kw_year'] : 0;
$onlyCompleted  = !empty($request['completed']) && (string)$request['completed'] === '1';
$fromDate       = isset($request['from']) ? trim((string)$request['from']) : '';
$toDate         = isset($request['to']) ? trim((string)$request['to']) : '';
$excludeId      = isset($request['exclude_id']) ? (int)$request['exclude_id'] : 0;

$where   = ["wb.azubi_id = ?"];
$types   = 'i';
$params  = [$azubiId];

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $where[] = "wb.id IN ({$placeholders})";
    $types  .= str_repeat('i', count($ids));
    $params = array_merge($params, $ids);
}

if ($fromDate !== '' && $toDate !== '') {
    $where[] = "wb.startdatum >= ? AND wb.enddatum <= ?";
    $types  .= 'ss';
    $params[] = $fromDate;
    $params[] = $toDate;
} else {
if ($filterYear > 0) {
    $where[] = "YEAR(wb.startdatum) = ?";
    $types  .= 'i';
    $params[] = $filterYear;
}
    if ($filterMonth > 0 && $filterMonth <= 12) {
        $where[] = "MONTH(wb.startdatum) = ?";
        $types  .= 'i';
        $params[] = $filterMonth;
    }
    if ($filterKw > 0) {
        $isoYear = $filterKwYear > 0 ? $filterKwYear : (int)date('o');
        try {
            $dt = new DateTime();
            $dt->setISODate($isoYear, $filterKw);
            $monday = $dt->format('Y-m-d');
            $friday = $dt->modify('+4 days')->format('Y-m-d');
            $where[] = "wb.startdatum BETWEEN ? AND ?";
            $types  .= 'ss';
            $params[] = $monday;
            $params[] = $friday;
        } catch (Exception $e) {
            // ignorieren, wenn KW unGültig
        }
    }
    if ($filterStatus === 'signiert') {
        $where[] = "wb.signiert = 'Y'";
    } elseif ($filterStatus === 'nicht_signiert') {
        $where[] = "wb.signiert = 'N'";
    }
if ($onlyCompleted) {
    $where[] = "wb.abgegeben = 'Y'";
}
if ($excludeId > 0) {
    $where[] = "wb.id <> ?";
    $types  .= 'i';
    $params[] = $excludeId;
}
}

$sql = "
    SELECT wb.*, u.vorname, u.nachname, u.ausbildungsjahr, u.abteilung, k.name AS klassenname
    FROM wochenberichte wb
    JOIN users u ON wb.azubi_id = u.id
    LEFT JOIN klassen k ON wb.klasse_id = k.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY wb.startdatum ASC
";
$stmt = $conn->prepare($sql);
$bind = [];
foreach ($params as $k => $v) {
    $bind[$k] = &$params[$k];
}
array_unshift($bind, $types);
call_user_func_array([$stmt, 'bind_param'], $bind);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($reports)) {
    exit('Keine passenden Wochenberichte gefunden.');
}



$prepareBlocks = function (array $bericht) use ($conn, $azubiId): array {

    // ✅ ZENTRALER LOADER (LIVE oder SNAPSHOT)
    $tasks = loadWochenberichtTasks($conn, $bericht);

    $blocks = [
        'betrieb' => [],
        'unterweisung' => [],
        'berufsschule' => []
    ];

    $stunden_betrieb_real = 0.0;
    $stunden_schule_real  = 0.0;
    $stunden_unterweisung_real_betrieb = 0.0;
    $stunden_unterweisung_real_schule  = 0.0;

    foreach ($tasks as $row) {
        $parts = [];

        $title   = trim((string)($row['titel'] ?? ''));
        $desc    = trim((string)($row['beschreibung'] ?? ''));
        $comment = trim((string)($row['kommentar'] ?? ''));
        $hourVal = is_numeric($row['stunden'] ?? null) ? (float)$row['stunden'] : 0.0;

        if ($title !== '')   $parts[] = $title;
        if ($desc !== '')    $parts[] = $desc;
        if ($comment !== '') $parts[] = 'Kommentar: ' . $comment;

        $line = $parts ? implode(' | ', $parts) : '-';

        $kat = strtolower((string)($row['kategorie'] ?? ''));
        $stundenTyp = strtolower((string)($row['stunden_typ'] ?? ''));

        if ($kat === 'unterweisung') {
            if ($hourVal > 0) {
                $stundenTyp === 'berufsschule'
                    ? $stunden_unterweisung_real_schule += $hourVal
                    : $stunden_unterweisung_real_betrieb += $hourVal;
            }
            $blocks['unterweisung'][] = $line;

        } elseif ($kat === 'berufsschule') {
            if ($hourVal > 0) $stunden_schule_real += $hourVal;
            $blocks['berufsschule'][] = $line;

        } else {
            if ($hourVal > 0) $stunden_betrieb_real += $hourVal;
            $blocks['betrieb'][] = $line;
        }
    }

    $fmt = fn($v) => number_format((float)$v, 2, ',', '.');

    $config = getStundenConfigUser($conn, $azubiId);

    return [
        'block_betrieb' => $blocks['betrieb'] ? implode("\n", $blocks['betrieb']) : '-',
        'block_schulung' => $blocks['unterweisung'] ? implode("\n", $blocks['unterweisung']) : '-',
        'block_berufsschule' => $blocks['berufsschule'] ? implode("\n", $blocks['berufsschule']) : '-',

        'stunden_unterweisung_sum_fmt' =>
            $fmt($stunden_unterweisung_real_betrieb + $stunden_unterweisung_real_schule),

        'verfuegbar_betrieb_fmt' =>
            $fmt(max(0, ($config['max_betrieb'] ?? 0) - $stunden_unterweisung_real_betrieb)),

        'verfuegbar_schule_fmt' =>
            $fmt(max(0, ($config['max_berufsschule'] ?? 0) - $stunden_unterweisung_real_schule)),
    ];
};


$templatePath = __DIR__ . '/../assets/Vorlage Wochenbericht.pdf';
if (!is_file($templatePath)) {
    $altPath = __DIR__ . '/../assets/Vorlage_Wochenbericht.pdf';
    $templatePath = is_file($altPath) ? $altPath : '';
}
if (!$templatePath) {
    exit('PDF-Vorlage nicht gefunden.');
}

$pdf = new Fpdi();
$pdf->SetAutoPageBreak(false);
$sourceFile = $pdf->setSourceFile($templatePath);
$tplIdx = $pdf->importPage(1);

foreach ($reports as $bericht) {
    $pdf->AddPage();
    $pdf->useTemplate($tplIdx, 0, 0, 210);
    $pdf->SetFont('Helvetica', '', 10);

    $blocks = $prepareBlocks($bericht);

    $azubiName = trim(($bericht['vorname'] ?? '') . ' ' . ($bericht['nachname'] ?? ''));
    $pdf->SetXY(85, 46.5);
    $pdf->Write(5, utf8_decode($azubiName));

    $pdf->SetXY(85, 53);
    $pdf->Write(5, utf8_decode((string)($bericht['ausbildungsjahr'] ?? '')));

    $deptText = ($bericht['abteilung'] ?? '') !== '' ? $bericht['abteilung'] : ($bericht['klassenname'] ?? '');
    $wrapLine = function (string $text, int $limit = 15): array {
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
    [$deptLine1, $deptLine2] = $wrapLine((string)$deptText, 15);
    $pdf->SetXY(150.5, 53);
    $pdf->Write(5, utf8_decode($deptLine1));
    if ($deptLine2 !== '') {
        $pdf->SetXY(150.5, 58);
        $pdf->Write(5, utf8_decode($deptLine2));
    }

    $start_fmt = date('d.m.Y', strtotime($bericht['startdatum']));
    $end_fmt   = date('d.m.Y', strtotime($bericht['enddatum']));
    $pdf->SetXY(84.5, 64);
    $pdf->Write(5, utf8_decode($start_fmt));
    $pdf->SetXY(116, 64);
    $pdf->Write(5, utf8_decode($end_fmt));

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(27, 88);
    $pdf->MultiCell(170, 4, utf8_decode($blocks['block_betrieb']), 0, 'L');

    $pdf->SetXY(27, 139);
    $pdf->MultiCell(170, 4, utf8_decode($blocks['block_schulung']), 0, 'L');

    $pdf->SetXY(27, 189);
    $pdf->MultiCell(170, 4, utf8_decode($blocks['block_berufsschule']), 0, 'L');

    $rightX = 179;
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetXY($rightX, 88);
    $pdf->Write(5, utf8_decode($blocks['verfuegbar_betrieb_fmt']));
    $pdf->SetXY($rightX, 139);
    $pdf->Write(5, utf8_decode($blocks['stunden_unterweisung_sum_fmt']));
    $pdf->SetXY($rightX, 189);
    $pdf->Write(5, utf8_decode($blocks['verfuegbar_schule_fmt']));

    $signatureY = max(235, $pdf->GetY() + 8);
    if (!empty($bericht['signatur_img'])) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
        file_put_contents($tmpFile, $bericht['signatur_img']);
        $pdf->Image($tmpFile, 40, $signatureY, 40, 15);
        @unlink($tmpFile);
    }
}

$namePart = trim(($reports[0]['vorname'] ?? 'Azubi') . '_' . ($reports[0]['nachname'] ?? ''));
$namePart = preg_replace('/\s+/', '_', $namePart);
$filename = 'Wochenberichte_' . $namePart . '.pdf';

// Falls irgendwo Output/Warnungen anstanden, Ausgabepuffer leeren, damit FPDF sauber senden kann
while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('D', $filename);
exit;


