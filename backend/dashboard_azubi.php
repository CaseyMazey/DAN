<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (($_SESSION['rolle'] ?? '') !== 'azubi') {
    header('Location: dashboard.php');
    exit;
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['name'] ?? '';

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmtDate = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '-';
$fmtDateTime = fn($d) => $d ? date('d.m.Y H:i', strtotime($d)) : '-';

$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$currentWeekEnd   = date('Y-m-d', strtotime($currentWeekStart . ' +4 days'));
$currentKW        = date('W', strtotime($currentWeekStart));

$currentWb = null;
$wbStatusBadge = '';
$stmt = $conn->prepare('
    SELECT id, startdatum, enddatum, abgegeben, signiert, abgegeben_am, signiert_am
    FROM wochenberichte
    WHERE azubi_id = ? AND startdatum = ? AND enddatum = ?
    LIMIT 1
');
$stmt->bind_param('iss', $user_id, $currentWeekStart, $currentWeekEnd);
$stmt->execute();
$currentWb = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($currentWb) {
    if ($currentWb['signiert'] === 'Y') {
        $wbStatusBadge = '<span class="badge bg-success">Signiert</span>';
    } elseif ($currentWb['abgegeben'] === 'Y') {
        $wbStatusBadge = '<span class="badge bg-warning text-dark">Abgegeben</span>';
    } else {
        $wbStatusBadge = '<span class="badge bg-secondary">In Bearbeitung</span>';
    }
}

$tasksCounts = ['offen' => 0, 'in_bearbeitung' => 0, 'fertig' => 0];
$stmt = $conn->prepare('
    SELECT status, COUNT(*) AS cnt
    FROM aufgaben
    WHERE azubi_id = ? AND (archiviert IS NULL OR archiviert = 0)
    GROUP BY status
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tasksCounts[$row['status']] = (int)$row['cnt'];
}
$stmt->close();

$hoursInfo = ['betrieb' => 0, 'schule' => 0, 'gesamt' => 0, 'max_betrieb' => 0, 'max_schule' => 0, 'max_gesamt' => 0, 'label_range' => ''];
$cfg = getStundenConfigUser($conn, $user_id);
$usage = getWeeklyHoursUsage($conn, $user_id);
$hoursInfo['betrieb'] = $usage['betrieb'] ?? 0;
$hoursInfo['schule']  = $usage['berufsschule'] ?? 0;
$hoursInfo['gesamt']  = $usage['gesamt'] ?? 0;
$hoursInfo['max_betrieb'] = $cfg['max_betrieb'] ?? 0;
$hoursInfo['max_schule']  = $cfg['max_berufsschule'] ?? 0;
$hoursInfo['max_gesamt']  = ($cfg['max_betrieb'] ?? 0) + ($cfg['max_berufsschule'] ?? 0);
$hoursInfo['label_range'] = (!empty($usage['week_start']) && !empty($usage['week_end']))
    ? $fmtDate($usage['week_start']) . ' - ' . $fmtDate($usage['week_end'])
    : '';

$activities = [];
$stmt = $conn->prepare('
    SELECT id, startdatum, enddatum, abgegeben, signiert, abgegeben_am, signiert_am, erstellt_am
    FROM wochenberichte
    WHERE azubi_id = ?
    ORDER BY GREATEST(COALESCE(signiert_am,"0000-00-00 00:00:00"), COALESCE(abgegeben_am,"0000-00-00 00:00:00"), erstellt_am) DESC
    LIMIT 5
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$wbAct = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($wbAct as $wb) {
    if ($wb['signiert'] === 'Y' && !empty($wb['signiert_am'])) {
        $activities[] = [
            'ts' => strtotime($wb['signiert_am']),
            'text' => 'Wochenbericht KW ' . date('W', strtotime($wb['startdatum'])) . ' wurde signiert am ' . $fmtDateTime($wb['signiert_am'])
        ];
    } elseif ($wb['abgegeben'] === 'Y' && !empty($wb['abgegeben_am'])) {
        $activities[] = [
            'ts' => strtotime($wb['abgegeben_am']),
            'text' => 'Wochenbericht KW ' . date('W', strtotime($wb['startdatum'])) . ' wurde abgegeben am ' . $fmtDateTime($wb['abgegeben_am'])
        ];
    } else {
        $activities[] = [
            'ts' => strtotime($wb['erstellt_am'] ?? $wb['startdatum']),
            'text' => 'Wochenbericht KW ' . date('W', strtotime($wb['startdatum'])) . ' wurde erstellt'
        ];
    }
}

$stmt = $conn->prepare('
    SELECT titel, status, geaendert_am
    FROM aufgaben
    WHERE azubi_id = ? AND geaendert_am IS NOT NULL
    ORDER BY geaendert_am DESC
    LIMIT 5
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$taskAct = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($taskAct as $t) {
    $activities[] = [
        'ts' => strtotime($t['geaendert_am']),
        'text' => 'Aufgabe "' . $h($t['titel']) . '" wurde als ' . $h(str_replace('_',' ', $t['status'])) . ' markiert'
    ];
}

usort($activities, fn($a,$b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
$activities = array_slice($activities, 0, 5);

$recentReports = [];
$stmt = $conn->prepare('
    SELECT id, startdatum, enddatum, abgegeben, signiert, abgegeben_am, signiert_am
    FROM wochenberichte
    WHERE azubi_id = ?
    ORDER BY startdatum DESC
    LIMIT 4
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recentReports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<div class="d-flex flex-column gap-2 mb-4">
    <h1 class="mb-0">Willkommen, <?= $h($user_name) ?>!</h1>
    <div class="text-muted">
        <?php if ($currentWb): ?>
            KW <?= $h($currentKW) ?> &middot; Dein aktueller Wochenbericht ist <?= $wbStatusBadge ?>
        <?php else: ?>
            KW <?= $h($currentKW) ?> &middot; Noch kein Wochenbericht fuer diese Woche erstellt.
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Aktueller Wochenbericht</h5>
                <?php if ($currentWb): ?>
                    <div class="small text-muted mb-2">
                        KW <?= $h($currentKW) ?> (<?= $fmtDate($currentWeekStart) ?> - <?= $fmtDate($currentWeekEnd) ?>)
                    </div>
                    <div class="mb-2"><?= $wbStatusBadge ?></div>
                    <p class="mb-3">Bearbeite deinen aktuellen Ausbildungsnachweis.</p>
                <?php else: ?>
                    <p class="mb-3 text-muted">Noch kein Wochenbericht fuer diese Woche erstellt.</p>
                <?php endif; ?>
                <a href="wochenbericht.php" class="btn btn-primary">Öffnen</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Meine Aufgaben</h5>
                <div class="small text-muted mb-2">Aktueller Stand</div>
                <p class="mb-1">Offen: <?= $tasksCounts['offen'] ?? 0 ?></p>
                <p class="mb-1">In Bearbeitung: <?= $tasksCounts['in_bearbeitung'] ?? 0 ?></p>
                <p class="mb-3">Fertig: <?= $tasksCounts['fertig'] ?? 0 ?></p>
                <a href="aufgaben.php" class="btn btn-primary">Zum Aufgabenboard</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Stunden dieser Woche</h5>
                <div class="small text-muted mb-2"><?= $h($hoursInfo['label_range'] ?? '') ?></div>
                <p class="mb-1">Betrieb: <?= number_format($hoursInfo['betrieb'],2,',','.') ?> / <?= number_format($hoursInfo['max_betrieb'],2,',','.') ?> Std</p>
                <p class="mb-1">Berufsschule: <?= number_format($hoursInfo['schule'],2,',','.') ?> / <?= number_format($hoursInfo['max_schule'],2,',','.') ?> Std</p>
                <p class="mb-3">Gesamt: <?= number_format($hoursInfo['gesamt'],2,',','.') ?> / <?= number_format($hoursInfo['max_gesamt'],2,',','.') ?> Std</p>
                <a href="wochenbericht.php" class="btn btn-primary">Details ansehen</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="h5 mb-3">Letzte Aktivitäten</h3>
        <?php if (!empty($activities)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($activities as $act): ?>
                    <li class="list-group-item px-0"><?= $act['text'] ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted mb-0">Noch keine Aktivitäten vorhanden.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0">Letzte Wochenberichte</h3>
            <a href="wochenbericht.php" class="btn btn-outline-primary btn-sm">Alle anzeigen</a>
        </div>
        <?php if (!empty($recentReports)): ?>
            <div class="row g-3">
                <?php foreach ($recentReports as $rep):
                    $kw = date('W', strtotime($rep['startdatum']));
                    $badge = $rep['signiert'] === 'Y'
                        ? '<span class="badge bg-success">Signiert</span>'
                        : ($rep['abgegeben'] === 'Y'
                            ? '<span class="badge bg-warning text-dark">Abgegeben</span>'
                            : '<span class="badge bg-secondary">In Bearbeitung</span>');
                ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-semibold">KW <?= $h($kw) ?></div>
                                        <div class="text-muted small"><?= $fmtDate($rep['startdatum']) ?> - <?= $fmtDate($rep['enddatum']) ?></div>
                                    </div>
                                    <?= $badge ?>
                                </div>
                                <p class="mb-1 text-muted small">Abgegeben am: <?= $fmtDateTime($rep['abgegeben_am'] ?? null) ?></p>
                                <p class="mb-3 text-muted small">Signiert am: <?= $fmtDateTime($rep['signiert_am'] ?? null) ?></p>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-outline-primary btn-sm" href="wochenbericht.php?id=<?= (int)$rep['id'] ?>#top">Vorschau</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="wochenbericht_pdf_export.php?id=<?= (int)$rep['id'] ?>" target="_blank">PDF</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Keine Wochenberichte vorhanden.</p>
        <?php endif; ?>
    </div>
</div>

<?php
$page_content = ob_get_clean();
$current_page = 'dashboard.php';
include __DIR__ . '/layout.php';
