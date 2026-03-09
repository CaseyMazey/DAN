<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
if (($_SESSION['rolle'] ?? '') !== 'ausbilder') {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';

$current_page  = 'wochenbericht_ausbilder.php';
$ausbilderId   = (int)$_SESSION['user_id'];
$highlightId   = isset($_GET['highlight']) ? (int)$_GET['highlight'] : 0;
$selectedAzubi = null;
$success_msgs  = [];
$error_msgs    = [];

// Profil-Signatur des Ausbilders prüfen
$trainerSignatureExists = false;
$stmt = $conn->prepare("SELECT signature_img, signature_type FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $ausbilderId);
$stmt->execute();
$trainerSigRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!empty($trainerSigRow['signature_img'])) {
    $trainerSignatureExists = true;
}

$h           = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmtDate     = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '';
$fmtHours    = fn($v) => number_format((float)$v, 2, ',', '.');
$fmtDateTime = fn($d) => $d ? date('d.m.Y H:i', strtotime($d)) : '';

// Azubis des Ausbilders laden
$azubis = [];
$stmt = $conn->prepare("
    SELECT u.id, u.vorname, u.nachname, u.email, u.abteilung, u.ausbildungsjahr, u.klasse_id, k.name AS klasse_name
    FROM users u
    JOIN klassen k ON u.klasse_id = k.id
    WHERE u.rolle = 'azubi' AND k.ausbilder_id = ?
    ORDER BY u.nachname, u.vorname
");
$stmt->bind_param("i", $ausbilderId);
$stmt->execute();
$azubis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$selectedAzubiId = isset($_GET['azubi']) ? (int)$_GET['azubi'] : 0;

// -------------------------------------------------------------
// POST-Handling: Signaturen / Auswahl
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAzubiId = (int)($_POST['azubi'] ?? 0);
    if ($postAzubiId > 0) {
        $selectedAzubiId = $postAzubiId;
    }

    // Einzelnen Bericht signieren (immer Profil-Signatur)
    if (isset($_POST['sign_report'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);

        $sigStmt = $conn->prepare("SELECT signature_img FROM users WHERE id = ? LIMIT 1");
        $sigStmt->bind_param("i", $ausbilderId);
        $sigStmt->execute();
        $sigRow = $sigStmt->get_result()->fetch_assoc();
        $sigStmt->close();
        $sigBin = $sigRow['signature_img'] ?? null;

        if ($reportId <= 0 || $postAzubiId <= 0) {
            $error_msgs[] = 'Signatur fehlgeschlagen (Parameter).';
        } elseif (empty($sigBin)) {
            $error_msgs[] = 'Keine Profil-Signatur vorhanden. Bitte im Profil hinterlegen.';
        } else {
            $chk = $conn->prepare("
                SELECT wb.id
                FROM wochenberichte wb
                JOIN users u ON wb.azubi_id = u.id
                JOIN klassen k ON u.klasse_id = k.id
                WHERE wb.id = ? AND u.id = ? AND k.ausbilder_id = ?
                LIMIT 1
            ");
            $chk->bind_param("iii", $reportId, $postAzubiId, $ausbilderId);
            $chk->execute();
            $ok = (bool)$chk->get_result()->fetch_assoc();
            $chk->close();

            if ($ok) {
                $upd = $conn->prepare("
                    UPDATE wochenberichte
                    SET signiert_ausbilder = 'Y', signiert_ausbilder_am = NOW(), signatur_ausbilder_img = ?
                    WHERE id = ?
                ");
                $null = null;
                $upd->bind_param("bi", $null, $reportId);
                $upd->send_long_data(0, $sigBin);

                if ($upd->execute()) {
                    $success_msgs[] = 'Bericht wurde signiert.';
                    $highlightId = $reportId;
                } else {
                    $error_msgs[] = 'Bericht konnte nicht signiert werden.';
                }
                $upd->close();
            } else {
                $error_msgs[] = 'Kein Zugriff auf diesen Bericht.';
            }
        }
    }

    // Ausbilder-Signatur entfernen
    if (isset($_POST['unsign_trainer'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId <= 0 || $postAzubiId <= 0) {
            $error_msgs[] = 'Signatur konnte nicht entfernt werden (Parameter).';
        } else {
            $chk = $conn->prepare("
                SELECT wb.id
                FROM wochenberichte wb
                JOIN users u ON wb.azubi_id = u.id
                JOIN klassen k ON u.klasse_id = k.id
                WHERE wb.id = ? AND u.id = ? AND k.ausbilder_id = ?
                LIMIT 1
            ");
            $chk->bind_param("iii", $reportId, $postAzubiId, $ausbilderId);
            $chk->execute();
            $ok = (bool)$chk->get_result()->fetch_assoc();
            $chk->close();

            if ($ok) {
                $upd = $conn->prepare("
                    UPDATE wochenberichte
                    SET signiert_ausbilder = 'N', signiert_ausbilder_am = NULL, signatur_ausbilder_img = NULL
                    WHERE id = ?
                ");
                $upd->bind_param("i", $reportId);
                if ($upd->execute()) {
                    $success_msgs[] = 'Ausbilder-Signatur wurde entfernt.';
                } else {
                    $error_msgs[] = 'Ausbilder-Signatur konnte nicht entfernt werden.';
                }
                $upd->close();
            } else {
                $error_msgs[] = 'Kein Zugriff auf diesen Bericht.';
            }
        }
    }

    // Alle Berichte eines Azubis signieren
    if (isset($_POST['sign_all_reports'])) {
        if ($postAzubiId <= 0) {
            $error_msgs[] = 'Kein Azubi ausgewählt.';
        } else {
            $sigStmt = $conn->prepare("SELECT signature_img FROM users WHERE id = ? LIMIT 1");
            $sigStmt->bind_param("i", $ausbilderId);
            $sigStmt->execute();
            $sigRow = $sigStmt->get_result()->fetch_assoc();
            $sigStmt->close();
            $sigBinAll = $sigRow['signature_img'] ?? null;

            $chk = $conn->prepare("
                SELECT 1
                FROM users u
                JOIN klassen k ON u.klasse_id = k.id
                WHERE u.id = ? AND k.ausbilder_id = ?
                LIMIT 1
            ");
            $chk->bind_param("ii", $postAzubiId, $ausbilderId);
            $chk->execute();
            $ok = (bool)$chk->get_result()->fetch_assoc();
            $chk->close();

            if ($ok && $sigBinAll) {
                $updAll = $conn->prepare("
                    UPDATE wochenberichte wb
                    JOIN users u ON wb.azubi_id = u.id
                    JOIN klassen k ON u.klasse_id = k.id
                    SET wb.signiert_ausbilder = 'Y', wb.signiert_ausbilder_am = NOW(), wb.signatur_ausbilder_img = ?
                    WHERE wb.azubi_id = ? AND k.ausbilder_id = ?
                ");
                $null = null;
                $updAll->bind_param("bii", $null, $postAzubiId, $ausbilderId);
                $updAll->send_long_data(0, $sigBinAll);
                if ($updAll->execute()) {
                    $affected = $updAll->affected_rows;
                    $success_msgs[] = $affected > 0
                        ? "Alle Berichte des Azubis wurden signiert ({$affected})."
                        : 'Keine Berichte zum Signieren gefunden.';
                } else {
                    $error_msgs[] = 'Berichte konnten nicht signiert werden.';
                }
                $updAll->close();
            } elseif (!$sigBinAll) {
                $error_msgs[] = 'Keine Profil-Signatur des Ausbilders vorhanden.';
            } else {
                $error_msgs[] = 'Kein Zugriff auf die Berichte dieses Azubis.';
            }
        }
    }
}

// Azubi-Objekt herausfinden
foreach ($azubis as $az) {
    if ((int)$az['id'] === $selectedAzubiId) {
        $selectedAzubi = $az;
        break;
    }
}

// Berichte des gewählten Azubis laden
$reports = [];
if ($selectedAzubiId) {
    $stmt = $conn->prepare("
        SELECT wb.*, k.name AS klassenname
        FROM wochenberichte wb
        LEFT JOIN klassen k ON wb.klasse_id = k.id
        WHERE wb.azubi_id = ?
        ORDER BY wb.startdatum DESC
    ");
    $stmt->bind_param("i", $selectedAzubiId);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$selectedReportId = $highlightId ?: (int)($_GET['bericht'] ?? 0);
$selectedReportId = $selectedReportId ?: (!empty($reports) ? (int)$reports[0]['id'] : 0);

// Hilfsfunktionen
$loadReport = function (int $azubiId, ?int $reportId) use ($conn): ?array {
    if ($reportId) {
        $stmt = $conn->prepare("
            SELECT wb.*, u.vorname, u.nachname, u.email, u.abteilung, u.ausbildungsjahr, k.name AS klassenname
            FROM wochenberichte wb
            JOIN users u ON wb.azubi_id = u.id
            LEFT JOIN klassen k ON wb.klasse_id = k.id
            WHERE wb.id = ? AND wb.azubi_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $reportId, $azubiId);
    } else {
        $stmt = $conn->prepare("
            SELECT wb.*, u.vorname, u.nachname, u.email, u.abteilung, u.ausbildungsjahr, k.name AS klassenname
            FROM wochenberichte wb
            JOIN users u ON wb.azubi_id = u.id
            LEFT JOIN klassen k ON wb.klasse_id = k.id
            WHERE wb.azubi_id = ?
            ORDER BY wb.startdatum DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $azubiId);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
};

$loadTasks = function (array $report) use ($conn): array {
    if (empty($report['id'])) {
        return [];
    }

    // Snapshot-Tabelle bevorzugen
    $stmtSnap = $conn->prepare("
        SELECT titel, beschreibung, kommentar, kategorie, status, stunden, stunden_typ, erstellt_am
        FROM wochenbericht_aufgaben
        WHERE bericht_id = ?
        ORDER BY erstellt_am ASC, id ASC
    ");
    $stmtSnap->bind_param("i", $report['id']);
    $stmtSnap->execute();
    $tasks = $stmtSnap->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtSnap->close();

    if (!empty($tasks)) {
        return $tasks;
    }

    // Fallback: Alle nicht offenen, nicht archivierten Aufgaben bis zum Enddatum, falls kein Snapshot existiert
    $rangeEnd = $report['enddatum'] . ' 23:59:59';
    $weekKey = date('o-W', strtotime($report['startdatum'] ?? 'now'));
    $stmt = $conn->prepare("
        SELECT titel, beschreibung, kategorie, kommentar, status, stunden, stunden_typ, erstellt_am
        FROM aufgaben
        WHERE azubi_id = ?
          AND (archiviert IS NULL OR archiviert = 0)
          AND status IN ('in_bearbeitung','fertig')
          AND DATE(erstellt_am) <= ?
          AND (wochenbericht_id IS NULL OR wochenbericht_id = ?)
          AND (week_id IS NULL OR week_id = ?)
        ORDER BY erstellt_am ASC
    ");
    $stmt->bind_param("isis", $report['azubi_id'], $rangeEnd, $report['id'], $weekKey);
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $tasks;
};

$summarizeTasks = function (array $tasks, array $config): array {
    $sections = ['betrieb' => [], 'unterweisung' => [], 'berufsschule' => []];
    $hours = [
        'betrieb' => 0.0,
        'schule' => 0.0,
        'unterweisung_betrieb' => 0.0,
        'unterweisung_schule' => 0.0,
    ];

    foreach ($tasks as $task) {
        $entry = [
            'titel' => $task['titel'] ?? '',
            'beschreibung' => $task['beschreibung'] ?? '',
            'kommentar' => $task['kommentar'] ?? '',
            'stunden' => is_numeric($task['stunden'] ?? null) ? (float)$task['stunden'] : null,
            'status' => $task['status'] ?? '',
            'kategorie' => $task['kategorie'] ?? '',
            'stunden_typ' => $task['stunden_typ'] ?? '',
        ];

        $kat = strtolower((string)$task['kategorie']);
        $stundenTyp = strtolower((string)($task['stunden_typ'] ?? ''));
        $val = is_numeric($task['stunden'] ?? null) ? (float)$task['stunden'] : 0.0;

        if ($kat === 'unterweisung') {
            if ($val > 0) {
                if ($stundenTyp === 'berufsschule') {
                    $hours['unterweisung_schule'] += $val;
                } else {
                    $hours['unterweisung_betrieb'] += $val;
                }
            }
            $sections['unterweisung'][] = $entry;
        } elseif ($kat === 'berufsschule') {
            if ($val > 0) {
                $hours['schule'] += $val;
            }
            $sections['berufsschule'][] = $entry;
        } else {
            if ($val > 0) {
                $hours['betrieb'] += $val;
            }
            $sections['betrieb'][] = $entry;
        }
    }

    $hours['unterweisung_sum'] = $hours['unterweisung_betrieb'] + $hours['unterweisung_schule'];
    $hours['gesamt'] = $hours['betrieb'] + $hours['schule'];
    $hours['verfuegbar_betrieb'] = max(0, (float)($config['max_betrieb'] ?? 0) - $hours['unterweisung_betrieb']);
    $hours['verfuegbar_schule']  = max(0, (float)($config['max_berufsschule'] ?? 0) - $hours['unterweisung_schule']);

    return ['sections' => $sections, 'hours' => $hours];
};

$selectedReport = null;
$selectedTasks  = [];
$summary        = ['sections' => [], 'hours' => []];
$config         = [];

if ($selectedAzubiId) {
    $selectedReport = $loadReport($selectedAzubiId, $selectedReportId ?: null);
    if (!$selectedReport && !empty($reports)) {
        $selectedReport = $loadReport($selectedAzubiId, (int)$reports[0]['id']);
        $selectedReportId = (int)($selectedReport['id'] ?? 0);
    }

    if ($selectedReport) {
        $selectedTasks = $loadTasks($selectedReport);
        $config = getStundenConfigUser($conn, $selectedAzubiId);
        $summary = $summarizeTasks($selectedTasks, $config);
    }
}

// Breadcrumb
$page_breadcrumbs = '';
if ($selectedAzubi) {
    ob_start();
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="klassen.php">Klassen</a></li>
            <li class="breadcrumb-item active" aria-current="page">Azubi <?= $h(trim(($selectedAzubi['vorname'] ?? '') . ' ' . ($selectedAzubi['nachname'] ?? ''))) ?></li>
        </ol>
    </nav>
    <?php
    $page_breadcrumbs = ob_get_clean();
}

// Seiteninhalt
ob_start();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-0">Wochenberichte</h1>
        <div class="text-muted small">Ausbilder-Ansicht (ohne Azubi-Signaturbild).</div>
    </div>
    <?php if ($selectedAzubi): ?>
        <div class="text-muted small">
            Aktueller Azubi: <?= $h(trim(($selectedAzubi['vorname'] ?? '') . ' ' . ($selectedAzubi['nachname'] ?? ''))) ?>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($success_msgs as $msg): ?>
    <div class="alert alert-success"><?= $h($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($error_msgs as $msg): ?>
    <div class="alert alert-danger"><?= $h($msg) ?></div>
<?php endforeach; ?>

<div class="card mb-4">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-6 col-lg-5">
            <label class="form-label">Azubi auswählen</label>
            <select name="azubi" class="form-select" onchange="this.form.submit()">
                <option value="">Bitte wählen...</option>
                <?php foreach ($azubis as $az): ?>
                    <option value="<?= (int)$az['id'] ?>" <?= $selectedAzubi && (int)$az['id'] === $selectedAzubiId ? 'selected' : '' ?>>
                        <?= $h(trim(($az['nachname'] ?? '') . ', ' . ($az['vorname'] ?? ''))) ?><?= $az['klasse_name'] ? ' – ' . $h($az['klasse_name']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedAzubi && !$reports): ?>
    <div class="alert alert-info">Für diesen Azubi liegen noch keine Wochenberichte vor.</div>
<?php endif; ?>

<?php if ($selectedReport): ?>
    <?php
        $kw = date('W', strtotime($selectedReport['startdatum']));
        $jahr = date('Y', strtotime($selectedReport['startdatum']));
        $hours = $summary['hours'];
    ?>
    <div class="card mb-4" id="wb-<?= (int)$selectedReport['id'] ?>">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-muted small">Ausbildungsnachweis (IHK-Struktur)</div>
                    <h3 class="mb-1">KW <?= $h($kw) ?> / <?= $h($jahr) ?> · <?= $h($fmtDate($selectedReport['startdatum'])) ?> - <?= $h($fmtDate($selectedReport['enddatum'])) ?></h3>
                    <div class="text-muted small">
                        <?= $h(trim(($selectedReport['vorname'] ?? '') . ' ' . ($selectedReport['nachname'] ?? ''))) ?>
                        <?php if (!empty($selectedReport['klassenname'])): ?> | Klasse <?= $h($selectedReport['klassenname']) ?><?php endif; ?>
                        <?php if (!empty($selectedReport['abteilung'])): ?> | Abteilung <?= $h($selectedReport['abteilung']) ?><?php endif; ?>
                        <?php if (!empty($selectedReport['ausbildungsjahr'])): ?> | Ausbildungsjahr <?= $h($selectedReport['ausbildungsjahr']) ?><?php endif; ?>
                        <?php if (!empty($selectedReport['email'])): ?> | <?= $h($selectedReport['email']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge <?= $selectedReport['abgegeben'] === 'Y' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $selectedReport['abgegeben'] === 'Y' ? 'Abgegeben' : 'Nicht abgegeben' ?>
                    </span>
                    <span class="badge <?= $selectedReport['signiert'] === 'Y' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        Azubi Signatur: <?= $selectedReport['signiert'] === 'Y' ? 'Ja' : 'Nein' ?>
                    </span>
                    <span class="badge <?= ($selectedReport['signiert_ausbilder'] ?? 'N') === 'Y' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        Ausbilder Signatur: <?= ($selectedReport['signiert_ausbilder'] ?? 'N') === 'Y' ? 'Ja' : 'Nein' ?>
                    </span>
                </div>
            </div>

            <hr>

            <div class="row g-4">
                <div class="col-lg-8">
                    <?php
                        $labels = [
                            'betrieb' => 'Betriebliche Tätigkeiten',
                            'unterweisung' => 'Unterweisungen / Schulungen',
                            'berufsschule' => 'Berufsschule',
                        ];
                    ?>
                    <?php foreach ($labels as $key => $label): ?>
                        <div class="mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><?= $h($label) ?></h5>
                                <?php if ($key === 'betrieb'): ?>
                                    <span class="badge bg-light text-dark">Rest Betrieb: <?= $h($fmtHours($hours['verfuegbar_betrieb'] ?? 0)) ?> Std.</span>
                                <?php elseif ($key === 'unterweisung'): ?>
                                    <span class="badge bg-light text-dark">Summe: <?= $h($fmtHours($hours['unterweisung_sum'] ?? 0)) ?> Std.</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">Rest Schule: <?= $h($fmtHours($hours['verfuegbar_schule'] ?? 0)) ?> Std.</span>
                                <?php endif; ?>
                            </div>
                            <?php $list = $summary['sections'][$key] ?? []; ?>
                            <?php if (!empty($list)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($list as $task): ?>
                                        <li class="list-group-item px-0">
                                            <div class="fw-semibold"><?= $h($task['titel']) ?></div>
                                            <?php if (!empty($task['beschreibung'])): ?>
                                                <div class="text-muted small"><?= $h($task['beschreibung']) ?></div>
                                            <?php endif; ?>
                                            <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                                                <?php if (!empty($task['stunden'])): ?>
                                                    <span>Stunden: <?= $h($fmtHours($task['stunden'])) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($task['kommentar'])): ?>
                                                    <span>Kommentar: <?= $h($task['kommentar']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($task['stunden_typ']) && strtolower($task['stunden_typ']) === 'berufsschule'): ?>
                                                    <span>Stundenkonto: Berufsschule</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-muted">Keine Einträge.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded h-100 p-3 bg-light">
                        <h5>Stundenübersicht</h5>
                        <table class="table table-sm mb-3">
                            <tbody>
                                <tr><th>Betrieb</th><td class="text-end"><?= $h($fmtHours($hours['betrieb'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Unterweisung (Betrieb)</th><td class="text-end"><?= $h($fmtHours($hours['unterweisung_betrieb'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Unterweisung (Schule)</th><td class="text-end"><?= $h($fmtHours($hours['unterweisung_schule'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Berufsschule</th><td class="text-end"><?= $h($fmtHours($hours['schule'] ?? 0)) ?> Std.</td></tr>
                                <tr class="table-light"><th>Gesamt</th><td class="text-end fw-semibold"><?= $h($fmtHours($hours['gesamt'] ?? 0)) ?> Std.</td></tr>
                            </tbody>
                        </table>

                        <div class="mb-3">
                            <h6 class="mb-1">Azubi Signatur</h6>
                            <?php if ($selectedReport['signiert'] === 'Y'): ?>
                                <div class="text-success small mb-1">
                                    Signiert<?= $selectedReport['signiert_am'] ? ' am ' . $h($fmtDateTime($selectedReport['signiert_am'])) : '' ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small mb-1">Noch nicht signiert.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-1">Ausbilder Signatur</h6>
                            <?php if (($selectedReport['signiert_ausbilder'] ?? 'N') === 'Y'): ?>
                                <div class="text-success small mb-1">
                                    Signiert<?= $selectedReport['signiert_ausbilder_am'] ? ' am ' . $h($fmtDateTime($selectedReport['signiert_ausbilder_am'])) : '' ?>
                                </div>
                                <form method="post" class="d-grid gap-2 mb-2">
                                    <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
                                    <input type="hidden" name="report_id" value="<?= (int)$selectedReport['id'] ?>">
                                    <button type="submit" name="unsign_trainer" class="btn btn-outline-warning btn-sm">
                                        Ausbilder-Signatur entfernen
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-muted small mb-1">Noch nicht signiert.</div>
                                <?php if ($trainerSignatureExists): ?>
                                    <form method="post" class="d-grid gap-2 mb-3">
                                        <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
                                        <input type="hidden" name="report_id" value="<?= (int)$selectedReport['id'] ?>">
                                        <button type="submit" name="sign_report" class="btn btn-outline-primary">
                                            Diesen Bericht signieren
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning small p-2">
                                        Keine Profil-Signatur vorhanden. Bitte im <a href="account.php">Profil</a> hinterlegen.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <form method="post" class="d-grid gap-2 mb-3">
                            <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
                            <button type="submit" name="sign_all_reports" class="btn btn-outline-primary">
                                Alle Berichte dieses Azubis signieren
                            </button>
                        </form>

                        <a href="wochenbericht_pdf_export.php?id=<?= (int)$selectedReport['id'] ?>" class="btn btn-outline-secondary w-100 mt-2" target="_blank">PDF exportieren</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($selectedAzubi && $reports): ?>
    <div class="card">
        <h5 class="mb-3">Weitere Wochenberichte von <?= $h(trim(($selectedAzubi['vorname'] ?? '') . ' ' . ($selectedAzubi['nachname'] ?? ''))) ?></h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>KW</th>
                        <th>Zeitraum</th>
                        <th>Azubi Signatur</th>
                        <th>Ausbilder Signatur</th>
                        <th>PDF</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $rep): ?>
                        <?php
                            $kwRow = date('W', strtotime($rep['startdatum']));
                            $yearRow = date('Y', strtotime($rep['startdatum']));
                            $isActive = $selectedReport && (int)$rep['id'] === (int)$selectedReport['id'];
                        ?>
                        <tr class="<?= $isActive ? 'table-primary' : '' ?>">
                            <td>KW <?= $h($kwRow) ?> / <?= $h($yearRow) ?></td>
                            <td><?= $h($fmtDate($rep['startdatum'])) ?> - <?= $h($fmtDate($rep['enddatum'])) ?></td>
                            <td><?= $rep['signiert'] === 'Y' ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-warning text-dark">Nein</span>' ?></td>
                            <td><?= ($rep['signiert_ausbilder'] ?? 'N') === 'Y' ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-warning text-dark">Nein</span>' ?></td>
                            <td><a class="btn btn-outline-secondary btn-sm" href="wochenbericht_pdf_export.php?id=<?= (int)$rep['id'] ?>" target="_blank">PDF</a></td>
                            <td class="text-end">
                                <a class="btn btn-outline-primary btn-sm" href="wochenbericht_ausbilder.php?azubi=<?= (int)$selectedAzubiId ?>&highlight=<?= (int)$rep['id'] ?>">Vorschau</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$page_content = ob_get_clean();
$page_scripts = '';
include __DIR__ . '/layout.php';
