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
require_once __DIR__ . '/mail_helper.php';

$conn->query("\n    CREATE TABLE IF NOT EXISTS abteilungen (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        ausbilder_id INT NOT NULL,\n        name VARCHAR(255) NOT NULL,\n        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY uniq_dep (ausbilder_id, name)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n");

$current_page  = 'verwaltung.php';
$ausbilder_id  = (int)$_SESSION['user_id'];
$success_msgs  = [];
$error_msgs    = [];

$cleanEmail = function (string $email): string {
    return strtolower(trim($email));
};
$h = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
$formatDate = fn(?string $date) => $date ? date('d.m.Y', strtotime($date)) : '';
$formatDateTime = fn(?string $date) => $date ? date('d.m.Y H:i', strtotime($date)) : '';

// ------------------------------------------------------------------
// POST: Verwaltung
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Abteilung anlegen
    if (isset($_POST['create_department'])) {
        $depName = trim($_POST['dep_name'] ?? '');
        if ($depName === '') {
            $error_msgs[] = 'Abteilung darf nicht leer sein.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM abteilungen WHERE ausbilder_id = ? AND name = ? LIMIT 1");
            $stmt->bind_param("is", $ausbilder_id, $depName);
            $stmt->execute();
            $exists = (bool)$stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($exists) {
                $error_msgs[] = 'Diese Abteilung existiert bereits.';
            } else {
                $stmt = $conn->prepare("INSERT INTO abteilungen (ausbilder_id, name, erstellt_am) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $ausbilder_id, $depName);
                if ($stmt->execute()) {
                    $success_msgs[] = 'Abteilung wurde angelegt.';
                } else {
                    $error_msgs[] = 'Abteilung konnte nicht gespeichert werden.';
                }
                $stmt->close();
            }
        }
    }

    // Abteilung bearbeiten
    if (isset($_POST['update_department'])) {
        $depId   = (int)($_POST['department_id'] ?? 0);
        $depName = trim($_POST['dep_name'] ?? '');
        if ($depId <= 0) {
            $error_msgs[] = 'Keine Abteilung ausgewählt.';
        } elseif ($depName === '') {
            $error_msgs[] = 'Abteilungsname darf nicht leer sein.';
        } else {
            $stmt = $conn->prepare("SELECT name FROM abteilungen WHERE id = ? AND ausbilder_id = ? LIMIT 1");
            $stmt->bind_param("ii", $depId, $ausbilder_id);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$old) {
                $error_msgs[] = 'Diese Abteilung gehört nicht zu Ihrem Konto.';
            } else {
                $stmt = $conn->prepare("SELECT id FROM abteilungen WHERE ausbilder_id = ? AND name = ? AND id <> ? LIMIT 1");
                $stmt->bind_param("isi", $ausbilder_id, $depName, $depId);
                $stmt->execute();
                $dup = (bool)$stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($dup) {
                    $error_msgs[] = 'Eine Abteilung mit diesem Namen existiert bereits.';
                } else {
                    $stmt = $conn->prepare("UPDATE abteilungen SET name = ? WHERE id = ? AND ausbilder_id = ?");
                    $stmt->bind_param("sii", $depName, $depId, $ausbilder_id);
                    if ($stmt->execute()) {
                        $oldName = $old['name'] ?? '';
                        $updK = $conn->prepare("UPDATE klassen SET abteilung = ? WHERE ausbilder_id = ? AND abteilung = ?");
                        $updK->bind_param("sis", $depName, $ausbilder_id, $oldName);
                        $updK->execute();
                        $updK->close();
                        $updU = $conn->prepare("
                            UPDATE users u
                            JOIN klassen k ON u.klasse_id = k.id
                            SET u.abteilung = ?
                            WHERE k.ausbilder_id = ? AND u.abteilung = ?
                        ");
                        $updU->bind_param("sis", $depName, $ausbilder_id, $oldName);
                        $updU->execute();
                        $updU->close();
                        $success_msgs[] = 'Abteilung wurde umbenannt.';
                    } else {
                        $error_msgs[] = 'Abteilung konnte nicht aktualisiert werden.';
                    }
                }
            }
        }
    }

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
            $error_msgs[] = 'Bitte eine bestehende Klasse auswählen.';
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
            $error_msgs[] = 'Keine Klasse ausgewählt.';
        } elseif ($name === '' || $abteilung === '' || $jahrgang === '') {
            $error_msgs[] = 'Bitte Name, Abteilung und Startjahr angeben.';
        } elseif (!preg_match('/^[0-9]{4}$/', $jahrgang)) {
            $error_msgs[] = 'Startjahr muss vierstellig sein (z. B. 2025).';
        } else {
            $stmt = $conn->prepare("SELECT id FROM klassen WHERE id = ? AND ausbilder_id = ? LIMIT 1");
            $stmt->bind_param("ii", $klasse_id, $ausbilder_id);
            $stmt->execute();
            $exists = (bool)$stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$exists) {
                $error_msgs[] = 'Diese Klasse gehört nicht zu Ihrem Konto.';
            } else {
                $stmt = $conn->prepare("
                    UPDATE klassen
                    SET name = ?, abteilung = ?, jahrgang = ?
                    WHERE id = ? AND ausbilder_id = ?
                ");
                $stmt->bind_param("ssiii", $name, $abteilung, $jahrgangInt, $klasse_id, $ausbilder_id);
                if ($stmt->execute()) {
                    $upd = $conn->prepare("
                        UPDATE users
                        SET abteilung = ?, ausbildungsjahr = ?
                        WHERE klasse_id = ? AND rolle = 'azubi'
                    ");
                    $upd->bind_param("sii", $abteilung, $jahrgangInt, $klasse_id);
                    $upd->execute();
                    $upd->close();
                    $success_msgs[] = 'Klasse wurde aktualisiert.';
                } else {
                    $error_msgs[] = 'Klasse konnte nicht aktualisiert werden.';
                }
                $stmt->close();
            }
        }
    }

    // Azubi zuordnen / einladen
    if (isset($_POST['add_azubi'])) {
        $email     = $cleanEmail($_POST['email'] ?? '');
        $klasse_id = (int)($_POST['klasse_id'] ?? 0);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msgs[] = 'Bitte eine gültige E-Mail eingeben.';
        } elseif ($klasse_id <= 0) {
            $error_msgs[] = 'Bitte eine Klasse auswählen.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, abteilung, jahrgang FROM klassen WHERE id = ? AND ausbilder_id = ? LIMIT 1");
            $stmt->bind_param("ii", $klasse_id, $ausbilder_id);
            $stmt->execute();
            $klasse = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$klasse) {
                $error_msgs[] = 'Diese Klasse gehört nicht zu Ihrem Konto.';
            } else {
                $stmt = $conn->prepare("SELECT id, rolle FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($user) {
                    if (($user['rolle'] ?? '') !== 'azubi') {
                        $error_msgs[] = 'Diese E-Mail gehört nicht zu einem Azubi-Konto.';
                    } else {
                        $uid = (int)$user['id'];
                        $stmt = $conn->prepare("UPDATE users SET klasse_id = ?, abteilung = ?, ausbildungsjahr = ? WHERE id = ?");
                        $jahrgangInt = (int)($klasse['jahrgang'] ?? 0);
                        $stmt->bind_param("isii", $klasse_id, $klasse['abteilung'], $jahrgangInt, $uid);
                        if ($stmt->execute()) {
                            $success_msgs[] = 'Azubi wurde der Klasse zugeordnet.';
                        } else {
                            $error_msgs[] = 'Zuordnung konnte nicht gespeichert werden.';
                        }
                        $stmt->close();
                    }
                } else {
                    $token = bin2hex(random_bytes(16));
                    $delStmt = $conn->prepare("DELETE FROM einladungen WHERE email = ? AND klasse_id = ? AND angenommen = 'N'");
                    $delStmt->bind_param("si", $email, $klasse_id);
                    $delStmt->execute();
                    $delStmt->close();

                    $stmt = $conn->prepare("
                        INSERT INTO einladungen (email, klasse_id, token, angenommen, erstellt_am)
                        VALUES (?, ?, ?, 'N', NOW())
                    ");
                    $stmt->bind_param("sis", $email, $klasse_id, $token);
                    if ($stmt->execute()) {
                        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
                        $host = $_SERVER['HTTP_HOST'];
                        $link = $base . $host . '/index.php?register=1&token=' . urlencode($token);
                        $validUntil = date('d.m.Y H:i', strtotime('+7 days'));

                        $html = file_get_contents(__DIR__ . '/email_templates/invite.html');
                        $text = file_get_contents(__DIR__ . '/email_templates/invite.txt');

                        $html = str_replace(
                            ['{{KLASSE}}', '{{JAHRGANG}}', '{{GUELTIG_BIS}}', '{{LINK}}'],
                            [$klasse['name'], $klasse['jahrgang'], $validUntil, $link],
                            $html
                        );
                        $text = str_replace(
                            ['{{KLASSE}}', '{{JAHRGANG}}', '{{GUELTIG_BIS}}', '{{LINK}}'],
                            [$klasse['name'], $klasse['jahrgang'], $validUntil, $link],
                            $text
                        );
                        sendInviteMail($email, 'Einladung zur DAN Plattform', $html, $text);
                        $success_msgs[] = 'Einladung erstellt und E-Mail versendet (oder bereitgestellt).';
                    } else {
                        $error_msgs[] = 'Einladung konnte nicht gespeichert werden.';
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
$stmt = $conn->prepare("SELECT id, name, erstellt_am FROM abteilungen WHERE ausbilder_id = ? ORDER BY name ASC");
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
            WHERE abteilung IN ({$placeholders}) AND ausbilder_id <> ?
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

$classOptionsForJs = [];
foreach ($klassen as $k) {
    $classOptionsForJs[] = [
        'id' => (int)($k['id'] ?? 0),
        'name' => (string)($k['name'] ?? ''),
        'abteilung' => (string)($k['abteilung'] ?? ''),
        'jahrgang' => (int)($k['jahrgang'] ?? 0),
    ];
}

$azubiOptionsByClass = [];
if (!empty($klassen)) {
    $classIds = array_column($klassen, 'id');
    $placeholders = implode(',', array_fill(0, count($classIds), '?'));
    $types = str_repeat('i', count($classIds));
    $sqlAz = "
        SELECT id, vorname, nachname, klasse_id
        FROM users
        WHERE rolle = 'azubi' AND klasse_id IN ({$placeholders})
        ORDER BY nachname, vorname
    ";
    $stmt = $conn->prepare($sqlAz);
    $bind = [];
    foreach ($classIds as $k => $v) {
        $bind[$k] = &$classIds[$k];
    }
    array_unshift($bind, $types);
    call_user_func_array([$stmt, 'bind_param'], $bind);
    $stmt->execute();
    $resAz = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($resAz as $row) {
        $cid = (int)($row['klasse_id'] ?? 0);
        $azName = trim(($row['nachname'] ?? '') . ', ' . ($row['vorname'] ?? ''));
        $azubiOptionsByClass[$cid][] = [
            'id' => (int)($row['id'] ?? 0),
            'name' => $azName
        ];
    }
}

$globalInvites = [];
if (!empty($klassen)) {
    $inviteClassIds = array_column($klassen, 'id');
    $placeholders = implode(',', array_fill(0, count($inviteClassIds), '?'));
    $types = str_repeat('i', count($inviteClassIds));
    $sqlInv = "
        SELECT e.id, e.email, e.token, e.erstellt_am, e.angenommen, k.name AS klassenname, k.id AS klasse_id
        FROM einladungen e
        JOIN klassen k ON e.klasse_id = k.id
        WHERE k.ausbilder_id = ? AND e.klasse_id IN ({$placeholders})
        ORDER BY e.erstellt_am DESC
    ";
    $types = 'i' . $types;
    $params = array_merge([$ausbilder_id], $inviteClassIds);
    $stmt = $conn->prepare($sqlInv);
    $bind = [];
    foreach ($params as $k => $v) {
        $bind[$k] = &$params[$k];
    }
    array_unshift($bind, $types);
    call_user_func_array([$stmt, 'bind_param'], $bind);
    $stmt->execute();
    $globalInvites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <h1 class="mb-0">Verwaltung</h1>
</div>

<?php foreach ($success_msgs as $msg): ?>
    <div class="alert alert-success"><?= $h($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($error_msgs as $msg): ?>
    <div class="alert alert-danger"><?= $h($msg) ?></div>
<?php endforeach; ?>

<?php if ($klassen): ?>
    <div class="card mb-4">
        <h5 class="mb-3">Schnellauswahl: Abteilung > Klasse > Azubi</h5>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Abteilung</label>
                <select id="selectDept" class="form-select">
                    <option value="">Bitte wählen...</option>
                    <?php foreach ($deptOptions as $dep): ?>
                        <option value="<?= $h($dep) ?>"><?= $h($dep) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Klasse</label>
                <select id="selectClass" class="form-select" disabled>
                    <option value="">Bitte zuerst Abteilung wählen</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Azubi (optional)</label>
                <select id="selectAzubi" class="form-select" disabled>
                    <option value="">Bitte erst Klasse wählen</option>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex justify-content-end">
            <button type="button" class="btn btn-primary" id="openSelection" disabled>Öffnen</button>
        </div>
    </div>
<?php endif; ?>

<div class="row row-cols-1 row-cols-md-3 g-3 g-lg-4 mb-4 action-tiles">
    <div class="col d-flex">
        <div class="card h-100 text-center w-100">
            <h5 class="mb-3">Abteilungen verwalten</h5>
            <p class="text-muted small mb-3">Abteilungen hinzufuegen oder umbenennen.</p>
            <a class="btn btn-outline-primary" href="abteilungen.php">Oeffnen</a>
        </div>
    </div>
    <div class="col d-flex">
        <div class="card h-100 text-center w-100">
            <h5 class="mb-3">Klassen verwalten</h5>
            <p class="text-muted small mb-3">Klassen anlegen oder bearbeiten.</p>
            <a class="btn btn-outline-primary" href="klassen_verwalten.php">Oeffnen</a>
        </div>
    </div>
    <div class="col d-flex">
        <div class="card h-100 text-center w-100">
            <h5 class="mb-3">Azubi einladen/zuordnen</h5>
            <p class="text-muted small mb-3">E-Mail eintragen und Klasse wählen.</p>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">Öffnen</button>
        </div>
    </div>
</div>

<div class="card">
    <h5>Offene Einladungen</h5>
    <?php if ($globalInvites): ?>
        <div class="table-responsive mt-3">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Klasse</th>
                        <th>E-Mail</th>
                        <th>Erstellt</th>
                        <th>Status</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($globalInvites as $inv):
                        $isExpired = (strtotime($inv['erstellt_am']) < strtotime('-7 days'));
                        $statusLabel = $inv['angenommen'] === 'Y' ? 'Angenommen' : ($isExpired ? 'Abgelaufen' : 'Offen');
                        $statusClass = $inv['angenommen'] === 'Y' ? 'bg-success' : ($isExpired ? 'bg-secondary' : 'bg-info text-dark');
                        $link = '../index.php?register=1&token=' . urlencode($inv['token']);
                    ?>
                        <tr>
                            <td><?= $h($inv['klassenname']) ?></td>
                            <td><?= $h($inv['email']) ?></td>
                            <td><?= $h($formatDateTime($inv['erstellt_am'])) ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $h($statusLabel) ?></span></td>
                            <td><a href="<?= $h($link) ?>" target="_blank" rel="noopener">Öffnen</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mt-2 mb-0">Keine offenen Einladungen vorhanden.</p>
    <?php endif; ?>
</div>

<div class="modal fade" id="inviteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Azubi einladen / zuordnen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form method="post" class="mt-2">
                <input type="hidden" name="add_azubi" value="1">
                <div class="mb-3">
                    <label class="form-label">E-Mail des Azubis</label>
                    <input type="email" name="email" class="form-control" placeholder="azubi@example.de" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Klasse</label>
                    <select name="klasse_id" class="form-select" required>
                        <option value="">Bitte wählen...</option>
                        <?php foreach ($klassen as $kl): ?>
                            <option value="<?= (int)$kl['id'] ?>"><?= $h($kl['abteilung'] ?? '') ?> - <?= $h($kl['name']) ?> (<?= $h($kl['jahrgang']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100">Zuordnen / Einladung senden</button>
                <div class="form-text mt-2">Existiert noch kein Account, wird automatisch eine Einladung erzeugt (7 Tage gültig).</div>
            </form>
        </div>
    </div>
  </div>
</div>

<script>
(() => {
    const classes = <?= json_encode($classOptionsForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const azubiMap = <?= json_encode($azubiOptionsByClass, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const selDept = document.getElementById('selectDept');
    const selClass = document.getElementById('selectClass');
    const selAzubi = document.getElementById('selectAzubi');
    const openBtn = document.getElementById('openSelection');

    const resetSelect = (el, placeholder, disabled = true) => {
        if (!el) return;
        el.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        el.appendChild(opt);
        el.disabled = disabled;
    };

    const fillClasses = (department) => {
        resetSelect(selClass, department ? 'Bitte Klasse wählen' : 'Bitte zuerst Abteilung wählen', !department);
        resetSelect(selAzubi, 'Bitte erst Klasse wählen', true);
        if (openBtn) openBtn.disabled = true;
        if (!department) return;
        const filtered = classes.filter(c => (c.abteilung || '') === department);
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = String(c.id);
            opt.textContent = `${c.name} (${c.jahrgang})`;
            selClass.appendChild(opt);
        });
        selClass.disabled = filtered.length === 0;
    };

    const fillAzubis = (classId) => {
        resetSelect(selAzubi, 'Bitte erst Klasse wählen', true);
        if (!classId) {
            if (openBtn) openBtn.disabled = true;
            return;
        }
        const list = azubiMap[classId] || [];
        if (list.length > 0) {
            list.forEach(a => {
                const opt = document.createElement('option');
                opt.value = String(a.id);
                opt.textContent = a.name;
                selAzubi.appendChild(opt);
            });
            selAzubi.disabled = false;
        }
        if (openBtn) openBtn.disabled = false;
    };

    const goToSelection = () => {
        if (!selClass || !selClass.value) return;
        const params = new URLSearchParams();
        params.set('klasse', selClass.value);
        if (selAzubi && selAzubi.value) {
            params.set('azubi', selAzubi.value);
        }
        window.location.href = `klassen.php?${params.toString()}`;
    };

    selDept && selDept.addEventListener('change', () => fillClasses(selDept.value));
    selClass && selClass.addEventListener('change', () => fillAzubis(selClass.value));
    openBtn && openBtn.addEventListener('click', goToSelection);
})();
</script>

<?php
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';



