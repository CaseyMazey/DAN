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

$current_page $ausbilderId  $highlightId  $selectedAzubi$success_msgs $error_msgs   
$h         $fmtDate   $fmtHours  $fmtDateTime
// Nur Azubis des eigenen Kontos laden
$azubis$stmt = $conn->prepare("
    SELECT u.id, u.vorname, u.nachname, u.email, u.abteilung, u.ausbildungsjahr, u.klasse_id, k.name AS klasse_name
    FROM users u
    JOIN klassen k ON u.klasse_id = k.id
    WHERE u.rolle = 'azubi' AND k.ausbilder_id = ?
    ORDER BY u.nachname, u.vorname
");
$stmt->bind_param("i", $ausbilderId);
$stmt->execute();
$azubis$stmt->close();

$selectedAzubiId
    // Signatur-Handling (Ausbilder)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postAzubiId        if ($postAzubiId > 0) {
            $selectedAzubiId        }

        // Einzelnen Bericht signieren
    if (isset($_POST['sign_report'])) {
        $reportId        $sigDataUrl        $sigBin        if (preg_match('#^data:image/(png|jpe?g);base64,(.+)$#i', $sigDataUrl, $m)) {
                $sigBin            }
            if ($reportId <= 0 || $postAzubiId <= 0) {
                $error_msgs[]            } else {
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
            $ok            $chk->close();

            if ($ok) {
                if ($sigBin !== null) {
                    $upd                    $upd->bind_param("si", $sigBin, $reportId);
                } else {
                    $upd                    $upd->bind_param("i", $reportId);
                }
                if ($upd->execute()) {
                    $success_msgs[]                    $highlightId                } else {
                    $error_msgs[]                }
                $upd->close();
            } else {
                $error_msgs[]            }
        }
    }

    // Ausbilder-Signatur entfernen
    if (isset($_POST['unsign_trainer'])) {
        $reportId        if ($reportId <= 0 || $postAzubiId <= 0) {
            $error_msgs[]        } else {
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
            $ok            $chk->close();

            if ($ok) {
                $upd                $upd->bind_param("i", $reportId);
                if ($upd->execute()) {
                    $success_msgs[]                } else {
                    $error_msgs[]                }
                $upd->close();
            } else {
                $error_msgs[]            }
        }
    }

    // Alle Berichte eines Azubis signieren
if (isset($_POST['sign_all_reports'])) {
        if ($postAzubiId <= 0) {
            $error_msgs[]        } else {
            $chk = $conn->prepare("
                SELECT 1
                FROM users u
                JOIN klassen k ON u.klasse_id = k.id
                WHERE u.id = ? AND k.ausbilder_id = ?
                LIMIT 1
            ");
            $chk->bind_param("ii", $postAzubiId, $ausbilderId);
            $chk->execute();
            $ok            $chk->close();

            if ($ok) {
                $updAll = $conn->prepare("
                    UPDATE wochenberichte wb
                    JOIN users u ON wb.azubi_id = u.id
                    JOIN klassen k ON u.klasse_id = k.id
                    SET wb.signiert_ausbilder = 'Y', wb.signiert_ausbilder_am = NOW()
                    WHERE wb.azubi_id = ? AND k.ausbilder_id = ?
                ");
                $updAll->bind_param("ii", $postAzubiId, $ausbilderId);
                if ($updAll->execute()) {
                    $affected                    $success_msgs[] = $affected > 0
                        ? "Alle Berichte des Azubis wurden signiert ({$affected})."
                        : 'Keine Berichte zum Signieren gefunden.';
                } else {
                    $error_msgs[]                }
                $updAll->close();
            } else {
                $error_msgs[]            }
        }
    }
}
foreach ($azubis as $az) {
    if ((int)$az['id'] === $selectedAzubiId) {
        $selectedAzubi        break;
    }
}
$trainer_signature_data_url$trainer_signature_url$upload_dir$trainer_signature_pathforeach (['png', 'jpg', 'jpeg'] as $ext) {
    $candidate    if (is_file($candidate)) {
        $trainer_signature_path        break;
    }
}
if ($trainer_signature_path) {
    $mime    $fileData    if ($fileData !== false) {
        $trainer_signature_data_url        $trainer_signature_url    }
}

// Berichte des gewaehlten Azubis laden
$reportsif ($selectedAzubiId) {
    $stmt = $conn->prepare("
        SELECT wb.*, k.name AS klassenname
        FROM wochenberichte wb
        LEFT JOIN klassen k ON wb.klasse_id = k.id
        WHERE wb.azubi_id = ?
        ORDER BY wb.startdatum DESC
    ");
    $stmt->bind_param("i", $selectedAzubiId);
    $stmt->execute();
    $reports    $stmt->close();
}

$selectedReportIdif (!$selectedReportId && !empty($reports)) {
    $selectedReportId}

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
    $row    $stmt->close();
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
    $tasks    $stmtSnap->close();

    if (!empty($tasks)) {
        return $tasks;
    }

    // Fallback: Alle nicht offenen, nicht archivierten Aufgaben bis zum Enddatum, falls kein Snapshot existiert
    $rangeEnd      $weekKey    $stmt = $conn->prepare("
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
    $tasks    $stmt->close();

    return $tasks;
};

$summarizeTasks = function (array $tasks) use ($conn): array {
    $sections    $hours = [
        'betrieb' => 0.0,
        'schule' => 0.0,
        'unterweisung_betrieb' => 0.0,
        'unterweisung_schule' => 0.0
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

        $kat        $stundenTyp        $val
        if ($kat === 'unterweisung') {
            if ($val > 0) {
                if ($stundenTyp === 'berufsschule') {
                    $hours['unterweisung_schule'] += $val;
                } else {
                    $hours['unterweisung_betrieb'] += $val;
                }
            }
            $sections['unterweisung'][]        } elseif ($kat === 'berufsschule') {
            if ($val > 0) {
                $hours['schule'] += $val;
            }
            $sections['berufsschule'][]        } else {
            if ($val > 0) {
                $hours['betrieb'] += $val;
            }
            $sections['betrieb'][]        }
    }

    $config    $hours['unterweisung_sum']    $hours['gesamt']    $hours['verfuegbar_betrieb']    $hours['verfuegbar_schule'] 
    return ['sections' => $sections, 'hours' => $hours];
};

$selectedReport$selectedTasks $summary       
if ($selectedAzubiId) {
    $selectedReport    if (!$selectedReport && !empty($reports)) {
        $selectedReport        $selectedReportId    }

    if ($selectedReport) {
        $selectedTasks        $summary    }
}

// Breadcrumb
$page_breadcrumbsif ($selectedAzubi) {
    ob_start();
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="klassen.php">Klassen</a></li>
            <li class="breadcrumb-item active" aria-current="page">Azubi <?= $h(trim(($selectedAzubi['vorname'] ?? '') . ' ' . ($selectedAzubi['nachname'] ?? ''))) ?></li>
        </ol>
    </nav>
    <?php
    $page_breadcrumbs}

// Seiteninhalt
ob_start();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-0">Wochenberichte</h1>
        <div class="text-muted small">Ansicht wie in der Azubi-Vorschau.</div>
    </div>
    <?php if ($selectedAzubi): ?>
        <div class="text-muted small">
            Aktueller Azubi: <?= $h(trim(($selectedAzubi['vorname'] ?? '') . ' ' . ($selectedAzubi['nachname'] ?? ''))) ?>
        </div>
    <?php endif; ?>
</div>

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
        $kw        $jahr        $hours        $labels = [
            'betrieb' => 'Betriebliche Tätigkeiten',
            'unterweisung' => 'Unterweisungen / Schulungen',
            'berufsschule' => 'Berufsschule'
        ];
        $signatureImg    ?>
    <div class="card mb-4" id="wb-<?= (int)$selectedReport['id'] ?>">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-muted small">Ausbildungsnachweis (IHK-Struktur)</div>
                    <h3 class="mb-1">KW <?= $h($kw) ?> / <?= $h($jahr) ?> &middot; <?= $h($fmtDate($selectedReport['startdatum'])) ?> - <?= $h($fmtDate($selectedReport['enddatum'])) ?></h3>
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
                            <?php endif; ?>
                        </div>

                        <?php if (($selectedReport['signiert_ausbilder'] ?? 'N') !== 'Y'): ?>
                            <div class="mb-3 p-2 border rounded bg-white">
                                <h6 class="mb-2">Ausbilder-Signatur setzen</h6>
                                <div class="d-flex flex-column gap-2">
                                    <button type="button" id="showTrainerCanvas" class="btn btn-outline-primary btn-sm">Mit Zeichnung signieren</button>
                                    <div id="trainerCanvasWrap" class="mt-2 d-none">
                                        <?php if (!empty($trainer_signature_url)): ?>
                                            <div class="mb-2">
                                                <p class="small mb-1 text-success">Profil-Signatur gefunden.</p>
                                                <img src="<?= $h($trainer_signature_url) ?>"
                                                     alt="Profil-Signatur"
                                                     style="max-width:240px; border:1px solid #ccc; background:#fff; padding:4px;">

                                                <button type="button" id="useTrainerUploadedSignature"
                                                        class="btn btn-outline-success btn-sm mt-1">
                                                    Mit Profil-Signatur signieren
                                                </button>

                                                <div class="text-muted small">Oder direkt zeichnen:</div>
                                            </div>
                                        <?php endif; ?>

                                        <canvas id="trainerCanvas"
                                                width="360" height="150"
                                                style="border:1px solid #ccc; background:#fff;"></canvas>

                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" id="trainerClear" class="btn btn-outline-secondary btn-sm">Zuruecksetzen</button>
                                            <button type="button" id="trainerSubmit" class="btn btn-success btn-sm">Signieren</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        

                        <form method="post" class="d-grid gap-2 mb-3">
                            <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
                            <input type="hidden" name="report_id" value="<?= (int)$selectedReport['id'] ?>">
                            <button type="submit" name="sign_report" class="btn btn-outline-primary">
                                Diesen Bericht signieren (ohne Zeichnung)
                            </button>
                        </form>

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
                            $kwRow                            $yearRow                            $isActive                        ?>
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
$page_content
$page_scripts
if ($selectedReport && (($selectedReport['signiert_ausbilder'] ?? 'N') !== 'Y')) {
    $trainerSigData    $trainerReportId    $trainerAzubiId    $page_scripts .= <<<JS
<script>
(function(){
    const toggleBtn    const wrap    const canvas    const clearBtn    const submitBtn    const useUploadedBtn    const uploadedSignature
    let ctx    let drawing
    function initCanvas(){
        if (!canvas) return;
        ctx
        canvas.addEventListener('mousedown', e => {
            drawing            ctx.moveTo(e.offsetX, e.offsetY);
        });

        canvas.addEventListener('mouseup', () => {
            drawing            ctx.beginPath();
        });

        canvas.addEventListener('mousemove', e => {
            if (!drawing) return;
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();
        });
    }

    function showCanvas(){
        if (!wrap) return;
        wrap.classList.remove('d-none');
        if (toggleBtn) toggleBtn.classList.add('d-none');
        if (!ctx) { initCanvas(); }
    }

    function sendSignature(dataURL){
        if (!dataURL) return;

        const params        params.append('sign_report', '1');
        params.append('azubi', '{$trainerAzubiId}');
        params.append('report_id', '{$trainerReportId}');
        params.append('signature_data', dataURL);

        fetch('wochenbericht_ausbilder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(() => window.location.reload())
        .catch(err => alert('Fehler: ' + err));
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', showCanvas);
    }

    if (clearBtn && canvas) {
        clearBtn.addEventListener('click', () => {
            if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }

    if (submitBtn && canvas) {
        submitBtn.addEventListener('click', () => {
            const dataURL            sendSignature(dataURL);
        });
    }

    if (useUploadedBtn && uploadedSignature) {
        useUploadedBtn.addEventListener('click', () => sendSignature(uploadedSignature));
    }
})();
</script>
JS;
}

include __DIR__ . '/layout.php';



