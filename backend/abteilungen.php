<?php
session_start();
require_once __DIR__ . '/db.php';

/* -------------------------------------------------------------
   ZUGRIFFSCHUTZ
------------------------------------------------------------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
if (($_SESSION['rolle'] ?? '') !== 'ausbilder') {
    header("Location: dashboard.php");
    exit;
}

$ausbilder_id = (int)$_SESSION['user_id'];
$current_page = 'abteilungen.php';
$page_css     = [];
$success_msgs = [];
$error_msgs   = [];

/* -------------------------------------------------------------
   HILFSMETHODEN
------------------------------------------------------------- */
$h          = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$formatDate = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '';

/* Abfrage einer Abteilung für diesen Ausbilder */
function get_department(mysqli $db, int $id, int $ausbilder_id) {
    $stmt = $db->prepare("SELECT id, name FROM abteilungen WHERE id=? AND ausbilder_id=? LIMIT 1");
    $stmt->bind_param("ii", $id, $ausbilder_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/* Prüfen, ob Name bereits existiert */
function department_exists(mysqli $db, int $ausbilder_id, string $name, int $excludeId = 0): bool {
    $sql = "SELECT 1 FROM abteilungen WHERE ausbilder_id=? AND name=?"
         . ($excludeId ? " AND id<>?" : "") . " LIMIT 1";
    $stmt = $db->prepare($sql);

    if ($excludeId) {
        $stmt->bind_param("isi", $ausbilder_id, $name, $excludeId);
    } else {
        $stmt->bind_param("is", $ausbilder_id, $name);
    }

    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_column();
}


/* -------------------------------------------------------------
   TABELLE ABTEILUNGEN SICHERSTELLEN
------------------------------------------------------------- */
$conn->query("
    CREATE TABLE IF NOT EXISTS abteilungen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ausbilder_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_dep (ausbilder_id, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* -------------------------------------------------------------
   POST-VERARBEITUNG
------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =========================================================
       1) ANLEGEN
    ========================================================= */
    if (isset($_POST['create_department'])) {

        $name = trim($_POST['dep_name'] ?? '');

        if ($name === '') {
            $error_msgs[] = "Abteilung darf nicht leer sein.";

        } elseif (department_exists($conn, $ausbilder_id, $name)) {
            $error_msgs[] = "Diese Abteilung existiert bereits.";

        } else {
            $stmt = $conn->prepare("INSERT INTO abteilungen (ausbilder_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $ausbilder_id, $name);
            $stmt->execute();
            $success_msgs[] = "Abteilung wurde angelegt.";
        }
    }

    /* =========================================================
       2) UMBENENNEN
    ========================================================= */
    if (isset($_POST['update_department'])) {

        $id   = (int)($_POST['department_id'] ?? 0);
        $name = trim($_POST['dep_name'] ?? '');

        if ($id <= 0)                      $error_msgs[] = "Keine Abteilung ausgewählt.";
        elseif ($name === '')              $error_msgs[] = "Name darf nicht leer sein.";
        elseif (!$old = get_department($conn, $id, $ausbilder_id))
                                           $error_msgs[] = "Abteilung gehört nicht zu Ihrem Konto.";
        elseif (department_exists($conn, $ausbilder_id, $name, $id))
                                           $error_msgs[] = "Eine Abteilung mit diesem Namen existiert bereits.";
        else {

            // Name aktualisieren
            $stmt = $conn->prepare("UPDATE abteilungen SET name=? WHERE id=? AND ausbilder_id=?");
            $stmt->bind_param("sii", $name, $id, $ausbilder_id);
            $stmt->execute();

            // Auch Klassen & User aktualisieren (gleiche Logik wie vorher, nur kompakter)
            $oldName = $old['name'];

            $upd = $conn->prepare("
                UPDATE klassen SET abteilung=? 
                WHERE ausbilder_id=? AND abteilung=?");
            $upd->bind_param("sis", $name, $ausbilder_id, $oldName);
            $upd->execute();

            $upd = $conn->prepare("
                UPDATE users u 
                JOIN klassen k ON u.klasse_id = k.id
                SET u.abteilung=? 
                WHERE k.ausbilder_id=? AND u.abteilung=?");
            $upd->bind_param("sis", $name, $ausbilder_id, $oldName);
            $upd->execute();

            $success_msgs[] = "Abteilung wurde umbenannt.";
        }
    }

    /* =========================================================
       3) LÖSCHEN
    ========================================================= */
    if (isset($_POST['delete_department'])) {

        $id = (int)($_POST['department_id'] ?? 0);

        if (!$dep = get_department($conn, $id, $ausbilder_id)) {
            $error_msgs[] = "Diese Abteilung gehört nicht zu Ihrem Konto.";

        } else {
            // prüfen, ob sie noch genutzt wird
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM klassen WHERE ausbilder_id=? AND abteilung=?");
            $stmt->bind_param("is", $ausbilder_id, $dep['name']);
            $stmt->execute();
            $count = (int)$stmt->get_result()->fetch_assoc()['c'];

            if ($count > 0) {
                $error_msgs[] = "Abteilung wird noch in Klassen genutzt und kann nicht gelöscht werden.";

            } else {
                $stmt = $conn->prepare("DELETE FROM abteilungen WHERE id=? AND ausbilder_id=?");
                $stmt->bind_param("ii", $id, $ausbilder_id);
                $stmt->execute();
                $success_msgs[] = "Abteilung wurde gelöscht.";
            }
        }
    }
}

/* -------------------------------------------------------------
   DATEN LADEN
------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, name, erstellt_am 
    FROM abteilungen 
    WHERE ausbilder_id=? 
    ORDER BY name ASC
");
$stmt->bind_param("i", $ausbilder_id);
$stmt->execute();
$departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Dropdown: vorhandene Namen sammeln */
$deptOptions = [];

$sql = "
    SELECT DISTINCT name COLLATE utf8mb4_uca1400_german2_as_ci AS n FROM abteilungen
    UNION
    SELECT DISTINCT abteilung COLLATE utf8mb4_uca1400_german2_as_ci AS n
    FROM klassen
    WHERE abteilung<>'' AND abteilung IS NOT NULL
";

$res = $conn->query($sql);
foreach ($res->fetch_all(MYSQLI_ASSOC) as $row) {
    $name = trim($row['n']);
    if ($name !== '') {
        $deptOptions[] = $name;
    }
}
sort($deptOptions);



/* Nutzung prüfen (welche Klassen verwenden welche Abteilung) */
$deptUsage = [];

$stmt = $conn->prepare("
    SELECT abteilung, COUNT(*) AS cnt, GROUP_CONCAT(name SEPARATOR ', ') AS klassen
    FROM klassen
    WHERE ausbilder_id=? AND abteilung<>'' AND abteilung IS NOT NULL
    GROUP BY abteilung
");
$stmt->bind_param("i", $ausbilder_id);
$stmt->execute();

foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $deptUsage[$row['abteilung']] = [
        'count'   => (int)$row['cnt'],
        'classes' => $row['klassen']
    ];
}


/* herausfinden ob edit=ID aktiv ist */
$editDepId = (int)($_GET['edit'] ?? 0);
$editDep   = null;
foreach ($departments as $d)
    if ($d['id'] == $editDepId) $editDep = $d;

/* -------------------------------------------------------------
   HTML-Ausgabe starten
------------------------------------------------------------- */
ob_start();

?>


<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Abteilungen verwalten</h1>
        <div class="text-muted small">
            Abteilungen anlegen, umbenennen und löschen.
        </div>
    </div>

    <!-- Navigation zu Klassenverwaltung -->
    <div class="d-flex gap-2">
        <a href="klassen_verwalten.php" class="btn btn-outline-primary">
            Zu den Klassen
        </a>
    </div>
</div>

<!-- ----------------------------------------------------------
     MELDUNGEN (Erfolg / Fehler)
----------------------------------------------------------- -->
<?php foreach ($success_msgs as $msg): ?>
    <div class="alert alert-success"><?= $h($msg) ?></div>
<?php endforeach; ?>

<?php foreach ($error_msgs as $msg): ?>
    <div class="alert alert-danger"><?= $h($msg) ?></div>
<?php endforeach; ?>

<!-- ----------------------------------------------------------
     FORMULAR: NEUE ABTEILUNG ANLEGEN
     Mit Dropdown zur schnellen Auswahl vorhandener Namen
----------------------------------------------------------- -->
<div class="card mb-4">
    <h5 class="mb-3">Neue Abteilung anlegen</h5>

    <form method="post" class="row g-2 align-items-end">
        <input type="hidden" name="create_department" value="1">

        <!-- Dropdown bestehender Abteilungsnamen -->
        <div class="col-md-5">
            <label class="form-label">Vorhandene Abteilungen</label>
            <select class="form-select" id="depPresetSelect">
                <option value="">Bitte wählen oder neu eingeben</option>
                <?php foreach ($deptOptions as $dep): ?>
                    <option value="<?= $h($dep) ?>"><?= $h($dep) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Textfeld für neuen Namen -->
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text"
                   name="dep_name"
                   id="depNameInput"
                   class="form-control"
                   placeholder="z. B. IT, Marketing"
                   required>
        </div>

        <!-- Anlegen-Button -->
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary">
                Hinzufügen
            </button>
        </div>
    </form>
</div>

<!-- ----------------------------------------------------------
     TABELLE: MEINE ABTEILUNGEN
----------------------------------------------------------- -->
<div class="card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Meine Abteilungen</h5>
        <span class="text-muted small">
            <?= count($departments) ?> Eintrag(e)
        </span>
    </div>

    <?php if ($departments): ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width:35%;">Name</th>
                        <th>Klassen</th>
                        <th>Erstellt</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($departments as $dep):

                        // Name und Nutzung der Abteilung
                        $depName   = $dep['name'] ?? '';
                        $usage     = $deptUsage[$depName] ?? ['count' => 0, 'classes' => ''];
                        $usageCount = (int)($usage['count'] ?? 0);

                        // Löschen nur möglich, wenn die Abteilung nirgends genutzt wird
                        $canDelete = $usageCount === 0;
                    ?>
                        <tr>

                            <!-- Name -->
                            <td><?= $h($depName) ?></td>

                            <!-- Anzahl/verwendete Klassen -->
                            <td>
                                <?= $usageCount ?>
                                <?php if (!empty($usage['classes'])): ?>
                                    <div class="text-muted small">
                                        <?= $h($usage['classes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Erstellungsdatum -->
                            <td><?= $h($formatDate($dep['erstellt_am'] ?? '')) ?></td>

                            <!-- Aktionen: Bearbeiten / Löschen -->
                            <td class="text-end">

                                <!-- Bearbeiten-Button -->
                                <a href="abteilungen.php?edit=<?= (int)$dep['id'] ?>#edit"
                                   class="btn btn-outline-secondary btn-sm">
                                    Bearbeiten
                                </a>

                                <!-- Löschen-Button -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="delete_department" value="1">
                                    <input type="hidden" name="department_id" value="<?= (int)$dep['id'] ?>">

                                    <button type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Abteilung wirklich löschen?');"
                                            <?= $canDelete ? '' : 'disabled' ?>
                                            title="<?= $h($canDelete
                                                ? 'Abteilung löschen'
                                                : 'Löschen nicht möglich: Abteilung wird genutzt'
                                            ) ?>">
                                        Löschen
                                    </button>
                                </form>

                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php else: ?>

        <p class="text-muted mb-0">Noch keine Abteilungen angelegt.</p>

    <?php endif; ?>
</div>

<!-- ----------------------------------------------------------
     FORMULAR: ABTEILUNG BEARBEITEN (wenn ausgewählt)
----------------------------------------------------------- -->
<div class="card" id="edit">
    <h5 class="mb-3">Abteilung bearbeiten</h5>

    <?php if ($editDep): ?>

        <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="update_department" value="1">
            <input type="hidden" name="department_id" value="<?= (int)$editDep['id'] ?>">

            <!-- Name eingeben -->
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text"
                       class="form-control"
                       name="dep_name"
                       value="<?= $h($editDep['name'] ?? '') ?>"
                       required>
            </div>

            <!-- Erstellungsdatum (nur lesen) -->
            <div class="col-md-3">
                <label class="form-label">Erstellt</label>
                <input type="text"
                       class="form-control"
                       value="<?= $h($formatDate($editDep['erstellt_am'] ?? '')) ?>"
                       disabled>
            </div>

            <!-- Speichern-Button -->
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">
                    Speichern
                </button>
            </div>
        </form>

        <div class="text-muted small mt-2">
            Hinweis: Eine Umbenennung aktualisiert auch Klassen und Azubi-Einträge,
            die dieser Abteilung zugeordnet sind.
        </div>

    <?php else: ?>

        <p class="text-muted mb-0">
            Bitte oben eine Abteilung wählen, um sie hier zu bearbeiten.
        </p>

    <?php endif; ?>
</div>


<?php
// ------------------------------------------------------------------
// ABSCHLUSS: HTML sammeln
// ------------------------------------------------------------------
$page_content = ob_get_clean();

$page_scripts = '<script src="js/abteilungen.js"></script>';

// Layout einbinden (Seite wird mit Styles, Menü, Header etc. umschlossen)
include __DIR__ . '/layout.php';
