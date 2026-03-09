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

$conn->query("\n    CREATE TABLE IF NOT EXISTS abteilungen (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        ausbilder_id INT NOT NULL,\n        name VARCHAR(255) NOT NULL,\n        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY uniq_dep (ausbilder_id, name)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n");

$current_page  = 'klassen_verwalten.php';
$ausbilder_id  = (int)$_SESSION['user_id'];
$success_msgs  = [];
$error_msgs    = [];

$h = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
$formatDate = fn(?string $date) => $date ? date('d.m.Y', strtotime($date)) : '';

// ------------------------------------------------------------------
// POST: Klassen
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Klasse anlegen
    if (isset($_POST['create_class'])) {
        $name        = trim($_POST['name'] ?? '');
        $abteilung   = trim($_POST['abteilung'] ?? '');
        $jahrgang    = trim($_POST['jahrgang'] ?? '');
        $jahrgangInt = (int)$jahrgang;

        if ($name === '' || $abteilung === '' || $jahrgang === '') {
            $error_msgs[] = 'Bitte Name, Abteilung und Startjahr angeben.';
        } elseif (!preg_match('/^[0-9]{4}$/', $jahrgang)) {
            $error_msgs[] = 'Startjahr muss vierstellig sein (z. B. 2025).';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO klassen (name, abteilung, jahrgang, ausbilder_id, erstellt_am)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssii", $name, $abteilung, $jahrgangInt, $ausbilder_id);
            if ($stmt->execute()) {
                $success_msgs[] = 'Klasse wurde erstellt.';
            } else {
                $error_msgs[] = 'Klasse konnte nicht gespeichert werden.';
            }
            $stmt->close();
        }
    }

    // Bestehende Klasse übernehmen
    if (isset($_POST['import_class'])) {
        $sourceClassId = (int)($_POST['existing_class_id'] ?? 0);
        if ($sourceClassId <= 0) {
            $error_msgs[] = 'Bitte eine bestehende Klasse auswÃ¤hlen.';
        } else {
            $myDeptNames = [];
            $depStmt = $conn->prepare("SELECT name FROM abteilungen WHERE ausbilder_id = ?");
            $depStmt->bind_param("i", $ausbilder_id);
            $depStmt->execute();
            $depRes = $depStmt->get_result();
            while ($row = $depRes->fetch_assoc()) {
                $depName = trim($row['name'] ?? '');
                if ($depName !== '') {
                    $myDeptNames[] = $depName;
                }
            }
            $depStmt->close();

            if (empty($myDeptNames)) {
                $error_msgs[] = 'Bitte zuerst mindestens eine Abteilung anlegen.';
            } else {
                $stmt = $conn->prepare("SELECT id, name, abteilung, jahrgang, ausbilder_id FROM klassen WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $sourceClassId);
                $stmt->execute();
                $source = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$source) {
                    $error_msgs[] = 'Klasse wurde nicht gefunden.';
                } elseif ((int)($source['ausbilder_id'] ?? 0) === $ausbilder_id) {
                    $error_msgs[] = 'Diese Klasse ist bereits in Ihrer Liste.';
                } elseif (!in_array(trim($source['abteilung'] ?? ''), $myDeptNames, true)) {
                    $error_msgs[] = 'Diese Klasse passt nicht zu Ihren Abteilungen.';
                } else {
                    $dupCheck = $conn->prepare("
                        SELECT id FROM klassen
                        WHERE ausbilder_id = ? AND name = ? AND abteilung = ? AND jahrgang = ?
                        LIMIT 1
                    ");
                    $dupCheck->bind_param("issi", $ausbilder_id, $source['name'], $source['abteilung'], $source['jahrgang']);
                    $dupCheck->execute();
                    $already = (bool)$dupCheck->get_result()->fetch_assoc();
                    $dupCheck->close();

                    if ($already) {
                        $error_msgs[] = 'Diese Klasse existiert bereits in Ihrer Liste.';
                    } else {
                        $jahrgangInt = (int)($source['jahrgang'] ?? 0);
                        $ins = $conn->prepare("
                            INSERT INTO klassen (name, abteilung, jahrgang, ausbilder_id, erstellt_am)
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $ins->bind_param("ssii", $source['name'], $source['abteilung'], $jahrgangInt, $ausbilder_id);
                        if ($ins->execute()) {
                            $success_msgs[] = 'Klasse wurde zu Ihren Klassen hinzugefügt.';
                        } else {
                            $error_msgs[] = 'Klasse konnte nicht übernommen werden.';
                        }
                        $ins->close();
                    }
                }
            }
        }
    }

    // Klasse bearbeiten
    if (isset($_POST['update_class'])) {
        $klasse_id   = (int)($_POST['klasse_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $abteilung   = trim($_POST['abteilung'] ?? '');
        $jahrgang    = trim($_POST['jahrgang'] ?? '');
        $jahrgangInt = (int)$jahrgang;

        if ($klasse_id <= 0) {
            $error_msgs[] = 'Keine Klasse ausgewÃ¤hlt.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, abteilung, jahrgang FROM klassen WHERE id = ? AND ausbilder_id = ? LIMIT 1");
            $stmt->bind_param("ii", $klasse_id, $ausbilder_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {
                $error_msgs[] = 'Diese Klasse gehört nicht zu Ihrem Konto.';
            } else {
                // Falls Felder leer (z. B. durch deaktivierte Selects), alte Werte übernehmen
                if ($name === '')       { $name = trim((string)($existing['name'] ?? '')); }
                if ($abteilung === '')  { $abteilung = trim((string)($existing['abteilung'] ?? '')); }
                if ($jahrgang === '')   { $jahrgang = (string)($existing['jahrgang'] ?? ''); }

                $jahrgangInt = (int)$jahrgang;
                $abteilungSafe = $abteilung !== '' ? $abteilung : trim((string)($existing['abteilung'] ?? ''));

                if ($name === '' || $jahrgang === '') {
                    $error_msgs[] = 'Bitte Name und Startjahr angeben.';
                } elseif (!preg_match('/^[0-9]{4}$/', $jahrgang)) {
                    $error_msgs[] = 'Startjahr muss vierstellig sein (z. B. 2025).';
                } else {
                    if ($abteilungSafe !== '') {
                        $stmt = $conn->prepare("
                            UPDATE klassen
                            SET name = ?, abteilung = ?, jahrgang = ?
                            WHERE id = ? AND ausbilder_id = ?
                        ");
                        $stmt->bind_param("ssiii", $name, $abteilungSafe, $jahrgangInt, $klasse_id, $ausbilder_id);
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE klassen
                            SET name = ?, jahrgang = ?
                            WHERE id = ? AND ausbilder_id = ?
                        ");
                        $stmt->bind_param("siii", $name, $jahrgangInt, $klasse_id, $ausbilder_id);
                    }
                    if ($stmt->execute()) {
                        $upd = $conn->prepare("
                            UPDATE users
                            SET ausbildungsjahr = ?
                            WHERE klasse_id = ? AND rolle = 'azubi'
                        ");
                        $upd->bind_param("ii", $jahrgangInt, $klasse_id);

                        // Exceptions wÃ¤hrend des User-Updates unterdrücken, sauber prüfen über errno
                        $prevReport = mysqli_report(MYSQLI_REPORT_OFF);
                        $upd->execute();
                        mysqli_report($prevReport);

                        if ($upd->errno) {
                            $success_msgs[] = 'Klasse wurde aktualisiert (Azubi-Daten blieben unveraendert).';
                        } else {
                            $success_msgs[] = 'Klasse wurde aktualisiert.';
                        }
                        $upd->close();
                    } else {
                        $error_msgs[] = 'Klasse konnte nicht aktualisiert werden.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ------------------------------------------------------------------
// Daten laden
// ------------------------------------------------------------------
$departments = [];
$stmt = $conn->prepare("SELECT id, name FROM abteilungen WHERE ausbilder_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $ausbilder_id);
$stmt->execute();
$departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$klassen = [];
$stmt = $conn->prepare("
    SELECT k.id, k.name, k.abteilung, k.jahrgang, k.erstellt_am,
           (SELECT COUNT(*) FROM users u WHERE u.klasse_id = k.id AND u.rolle = 'azubi') AS azubi_count
    FROM klassen k
    WHERE k.ausbilder_id = ?
    ORDER BY k.abteilung ASC, k.name ASC
");
$stmt->bind_param("i", $ausbilder_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $klassen[] = $row;
}
$stmt->close();

$deptOptions = [];
$addDeptName = function ($name) use (&$deptOptions) {
    $n = trim((string)$name);
    if ($n !== '' && !in_array($n, $deptOptions, true)) {
        $deptOptions[] = $n;
    }
};
foreach ($departments as $dep) {
    $addDeptName($dep['name'] ?? '');
}
foreach ($klassen as $k) {
    $addDeptName($k['abteilung'] ?? '');
}
$resAllDeps = $conn->query("SELECT DISTINCT name FROM abteilungen");
if ($resAllDeps) {
    while ($row = $resAllDeps->fetch_assoc()) {
        $addDeptName($row['name'] ?? '');
    }
}
$resClassDeps = $conn->query("SELECT DISTINCT abteilung FROM klassen WHERE abteilung IS NOT NULL AND abteilung <> ''");
if ($resClassDeps) {
    while ($row = $resClassDeps->fetch_assoc()) {
        $addDeptName($row['abteilung'] ?? '');
    }
}
sort($deptOptions);

$importableClasses = [];
if (!empty($departments)) {
    $myDeptNames = array_values(array_filter(array_map(static fn($d) => trim($d['name'] ?? ''), $departments)));
    if (!empty($myDeptNames)) {
        $placeholders = implode(',', array_fill(0, count($myDeptNames), '?'));
        $types = str_repeat('s', count($myDeptNames)) . 'i';
        $sql = "
            SELECT id, name, abteilung, jahrgang
            FROM klassen
            WHERE CAST(abteilung AS CHAR) IN ({$placeholders}) AND ausbilder_id <> ?
            ORDER BY abteilung ASC, name ASC, jahrgang ASC
        ";
        $params = array_merge($myDeptNames, [$ausbilder_id]);
        $stmt = $conn->prepare($sql);
        $bind = [];
        foreach ($params as $k => $v) {
            $bind[$k] = &$params[$k];
        }
        array_unshift($bind, $types);
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $importRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $existingKeys = [];
        foreach ($klassen as $k) {
            $key = strtolower(trim(($k['abteilung'] ?? '') . '|' . ($k['name'] ?? '') . '|' . (int)($k['jahrgang'] ?? 0)));
            $existingKeys[$key] = true;
        }
        foreach ($importRows as $row) {
            $key = strtolower(trim(($row['abteilung'] ?? '') . '|' . ($row['name'] ?? '') . '|' . (int)($row['jahrgang'] ?? 0)));
            if (!isset($existingKeys[$key])) {
                $importableClasses[] = $row;
            }
        }
    }
}

$editClassId = (int)($_GET['edit'] ?? 0);
$editClass = null;
foreach ($klassen as $kl) {
    if ((int)$kl['id'] === $editClassId) {
        $editClass = $kl;
        break;
    }
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Klassen verwalten</h1>
        <div class="text-muted small">Klassen anlegen, bearbeiten oder importieren.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="#create" class="btn btn-primary">Neue Klasse erstellen</a>
        <a href="abteilungen.php" class="btn btn-outline-secondary">Zu den Abteilungen</a>
    </div>
</div>

<?php foreach ($success_msgs as $msg): ?>
    <div class="alert alert-success"><?= $h($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($error_msgs as $msg): ?>
    <div class="alert alert-danger"><?= $h($msg) ?></div>
<?php endforeach; ?>

<div class="card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Alle Klassen</h5>
        <span class="text-muted small"><?= count($klassen) ?> Eintrag(e)</span>
    </div>
    <?php if ($klassen): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Abteilung</th>
                        <th>Klassenname</th>
                        <th>Startjahr</th>
                        <th>Azubis</th>
                        <th>Erstellt</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($klassen as $kl): ?>
                        <tr>
                            <td><?= $h($kl['abteilung'] ?? '-') ?></td>
                            <td><?= $h($kl['name']) ?></td>
                            <td><?= $h($kl['jahrgang']) ?></td>
                            <td><?= (int)$kl['azubi_count'] ?></td>
                            <td><?= $h($formatDate($kl['erstellt_am'] ?? '')) ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a class="btn btn-outline-secondary btn-sm" href="klassen_verwalten.php?edit=<?= (int)$kl['id'] ?>#edit">Bearbeiten</a>
                                    <a class="btn btn-primary btn-sm" href="klassen.php?klasse=<?= (int)$kl['id'] ?>">Ã–ffnen</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">Noch keine Klassen angelegt.</p>
    <?php endif; ?>
</div>

<div class="card mb-4" id="edit">
    <h5 class="mb-3">Klasse bearbeiten</h5>
    <?php if ($editClass): ?>
        <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="update_class" value="1">
            <input type="hidden" name="klasse_id" value="<?= (int)$editClass['id'] ?>">
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= $h($editClass['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Abteilung</label>
                <select name="abteilung" class="form-select" required <?= empty($deptOptions) ? 'disabled' : '' ?>>
                    <option value=""><?= $deptOptions ? 'Bitte wÃ¤hlen...' : 'Bitte zuerst Abteilung anlegen' ?></option>
                    <?php foreach ($deptOptions as $dep): ?>
                        <option value="<?= $h($dep) ?>" <?= ($editClass['abteilung'] ?? '') === $dep ? 'selected' : '' ?>><?= $h($dep) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Startjahr</label>
                <input type="number" name="jahrgang" class="form-control" value="<?= $h($editClass['jahrgang'] ?? '') ?>" min="2000" max="2100" required>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary" <?= empty($deptOptions) ? 'disabled' : '' ?>>Speichern</button>
            </div>
        </form>
        <div class="text-muted small mt-2">Ã„nderungen werden auch bei zugeordneten Azubis aktualisiert.</div>
    <?php else: ?>
        <p class="text-muted mb-0">Bitte oben auf â€žBearbeitenâ€œ klicken, um eine Klasse zu laden.</p>
    <?php endif; ?>
</div>

<div class="row g-4" id="create">
    <div class="col-lg-7">
        <div class="card h-100">
            <h5 class="mb-3">Neue Klasse erstellen</h5>
            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="create_class" value="1">
                <div class="col-md-5">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Abteilung</label>
                    <select name="abteilung" class="form-select" required <?= empty($deptOptions) ? 'disabled' : '' ?>>
                        <option value=""><?= $deptOptions ? 'Bitte wÃ¤hlen...' : 'Bitte zuerst Abteilung anlegen' ?></option>
                        <?php foreach ($deptOptions as $dep): ?>
                            <option value="<?= $h($dep) ?>"><?= $h($dep) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Startjahr</label>
                    <input type="number" name="jahrgang" class="form-control" min="2000" max="2100" required>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-success" <?= empty($deptOptions) ? 'disabled' : '' ?>>Anlegen</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <h5 class="mb-3">Bestehende Klassen importieren</h5>
            <?php if (!empty($importableClasses)): ?>
                <form method="post" class="row g-3 align-items-end">
                    <input type="hidden" name="import_class" value="1">
                    <div class="col-12">
                        <label class="form-label">Klasse aus meinen Abteilungen</label>
                        <select name="existing_class_id" class="form-select" required>
                            <option value="">Bitte wÃ¤hlen...</option>
                            <?php foreach ($importableClasses as $ic): ?>
                                <option value="<?= (int)$ic['id'] ?>"><?= $h($ic['abteilung'] ?? '') ?> - <?= $h($ic['name'] ?? '') ?> (<?= $h($ic['jahrgang'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-outline-primary">Zu meinen Klassen hinzufügen</button>
                    </div>
                </form>
                <div class="small text-muted mt-2">Nur Klassen aus Ihren Abteilungen, die noch nicht in der Liste stehen.</div>
            <?php else: ?>
                <p class="text-muted mb-0">Keine passenden Klassen aus Ihren Abteilungen verfügbar.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
$page_scripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', () => {
    const createLink = document.querySelector('a[href="#create"]');
    const createBlock = document.getElementById('create');
    const nameInput = document.querySelector('#create input[name="name"]');
    if (createLink && createBlock) {
        createLink.addEventListener('click', (ev) => {
            ev.preventDefault();
            createBlock.scrollIntoView({behavior: 'smooth', block: 'start'});
            if (nameInput) { nameInput.focus(); }
        });
    }
});
</script>
HTML;
include __DIR__ . '/layout.php';

