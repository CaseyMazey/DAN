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

$current_page  = 'klassen.php';
$ausbilder_id  = (int)$_SESSION['user_id'];

$success_msgs  = [];
$error_msgs    = [];
$invite_info   = '';

$h = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
$formatDate = fn(?string $date) => $date ? date('d.m.Y', strtotime($date)) : '';
$formatDateTime = fn(?string $date) => $date ? date('d.m.Y H:i', strtotime($date)) : '';

$cleanEmail = fn(string $email) => strtolower(trim($email));

$selectedClassId = (int)($_GET['klasse'] ?? 0);
$selectedAzubiId = (int)($_GET['azubi'] ?? 0);
$filterYear      = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 0;
$filterMonth     = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : 0;

/* ------------------------------
 POST-Handling (nur relevante Aktionen)
------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_class'])) {
        $klasse_id   = (int)$_POST['klasse_id'];
        $name        = trim($_POST['name']);
        $abteilung   = trim($_POST['abteilung']);
        $jahrgang    = trim($_POST['jahrgang']);
        $jahrgangInt = (int)$jahrgang;

        if ($klasse_id <= 0 || $name === '' || $abteilung === '' || !preg_match('/^[0-9]{4}$/', $jahrgang)) {
            $error_msgs[] = 'Fehlerhafte Eingabe.';
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
                    UPDATE klassen SET name = ?, abteilung = ?, jahrgang = ?
                    WHERE id = ? AND ausbilder_id = ?
                ");
                $stmt->bind_param("ssiii", $name, $abteilung, $jahrgangInt, $klasse_id, $ausbilder_id);
                if ($stmt->execute()) {
                    $upd = $conn->prepare("
                        UPDATE users SET abteilung = ?, ausbildungsjahr = ? 
                        WHERE klasse_id = ? AND rolle = 'azubi'
                    ");
                    $upd->bind_param("sii", $abteilung, $jahrgangInt, $klasse_id);
                    $upd->execute();
                    $upd->close();
                    $success_msgs[] = 'Klasse wurde aktualisiert.';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['add_azubi'])) {
        $email     = $cleanEmail($_POST['email'] ?? '');
        $klasse_id = (int)$_POST['klasse_id'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $klasse_id <= 0) {
            $error_msgs[] = 'Ungültige Eingabe.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, abteilung, jahrgang FROM klassen WHERE id = ? AND ausbilder_id = ?");
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

                if ($user && $user['rolle'] === 'azubi') {
                    $uid = (int)$user['id'];
                    $stmt = $conn->prepare("UPDATE users SET klasse_id = ?, abteilung = ?, ausbildungsjahr = ? WHERE id = ?");
                    $stmt->bind_param("isii", $klasse_id, $klasse['abteilung'], $klasse['jahrgang'], $uid);
                    $stmt->execute();
                    $stmt->close();
                    $success_msgs[] = 'Azubi zugeordnet.';
                }

                if (!$user) {
                    $token = bin2hex(random_bytes(16));
                    $stmt = $conn->prepare("
                        INSERT INTO einladungen (email, klasse_id, token, angenommen, erstellt_am)
                        VALUES (?, ?, ?, 'N', NOW())
                    ");
                    $stmt->bind_param("sis", $email, $klasse_id, $token);
                    $stmt->execute();
                    $stmt->close();
                    $success_msgs[] = 'Einladung erstellt.';
                }
            }
        }
    }
}

/* ------------------------------
 Stammdaten laden
------------------------------- */

$stmt = $conn->prepare("SELECT id, name, abteilung, jahrgang, erstellt_am FROM klassen WHERE id = ? AND ausbilder_id = ?");
$stmt->bind_param("ii", $selectedClassId, $ausbilder_id);
$stmt->execute();
$selectedClass = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$selectedClass) {
    header("Location: verwaltung.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT id, vorname, nachname, email, erstellt_am, abteilung, ausbildungsjahr 
    FROM users WHERE rolle = 'azubi' AND klasse_id = ? ORDER BY nachname, vorname
");
$stmt->bind_param("i", $selectedClassId);
$stmt->execute();
$classAzubis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT id, email, token, erstellt_am, angenommen FROM einladungen WHERE klasse_id = ? ORDER BY erstellt_am DESC");
$stmt->bind_param("i", $selectedClassId);
$stmt->execute();
$classInvites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$selectedAzubi = null;
foreach ($classAzubis as $az) {
    if ((int)$az['id'] === $selectedAzubiId) { $selectedAzubi = $az; break; }
}

/* ------------------------------
 Berichte laden
------------------------------- */
$reports = [];
$availableYears = [];
$exportMinDate = null;
$exportMaxDate = null;
$hasReports = false;

if ($selectedAzubiId) {
    $stmt = $conn->prepare("SELECT DISTINCT YEAR(startdatum) AS jahr FROM wochenberichte WHERE azubi_id = ? ORDER BY jahr DESC");
    $stmt->bind_param("i", $selectedAzubiId);
    $stmt->execute();
    $years = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($years as $y) $availableYears[] = (int)$y['jahr'];

    $where = ["wb.azubi_id = ?"];
    $types = "i";
    $params = [$selectedAzubiId];

    if ($filterYear) { $where[] = "YEAR(startdatum)=?"; $types.="i"; $params[]=$filterYear; }
    if ($filterMonth) { $where[]="MONTH(startdatum)=?"; $types.="i"; $params[]=$filterMonth; }

    $sql = "
        SELECT wb.*, k.name AS klassenname
        FROM wochenberichte wb
        LEFT JOIN klassen k ON k.id = wb.klasse_id
        WHERE ".implode(" AND ", $where)."
        ORDER BY startdatum DESC
    ";
    $stmt = $conn->prepare($sql);

    $bind = [];
    foreach ($params as $k => $v) { $bind[$k] = &$params[$k]; }
    array_unshift($bind, $types);
    call_user_func_array([$stmt, "bind_param"], $bind);

    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Min/Max fuer Export
    $stmt = $conn->prepare("SELECT MIN(startdatum) AS first_start, MAX(enddatum) AS last_end FROM wochenberichte WHERE azubi_id = ?");
    $stmt->bind_param("i", $selectedAzubiId);
    $stmt->execute();
    $range = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $exportMinDate = $range['first_start'] ?? null;
    $exportMaxDate = $range['last_end'] ?? null;

    $hasReports = count($reports) > 0 || ($exportMinDate && $exportMaxDate);
}

/* ------------------------------
 HTML Start
------------------------------- */

ob_start();
?>

<!-- HEADER: Klasseninfos -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h1 class="mb-1">Klasse <?= $h($selectedClass['name']) ?> (<?= $h($selectedClass['jahrgang']) ?>)</h1>
        <div class="text-muted small">
            Abteilung: <?= $h($selectedClass['abteilung']) ?> - Erstellt am <?= $h($formatDate($selectedClass['erstellt_am'])) ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#editClassModal"
                data-id="<?= $selectedClass['id'] ?>"
                data-name="<?= $h($selectedClass['name']) ?>"
                data-abteilung="<?= $h($selectedClass['abteilung']) ?>"
                data-jahrgang="<?= $h($selectedClass['jahrgang']) ?>">
            Klasse bearbeiten
        </button>
        <a href="klassen_verwalten.php" class="btn btn-outline-secondary">Zurueck</a>
    </div>
</div>
<?php foreach ($success_msgs as $msg): ?>
    <div class="alert alert-success"><?= $h($msg) ?></div>
<?php endforeach; ?>

<?php foreach ($error_msgs as $msg): ?>
    <div class="alert alert-danger"><?= $h($msg) ?></div>
<?php endforeach; ?>


<!-- 2-SPALTEN LAYOUT -->
<div class="row g-4">

    <!-- ================= LEFT COLUMN ================= -->
    <div class="col-lg-4">

        <!-- AZUBI KARTE -->
        <?php if ($selectedAzubi): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="mb-1"><?= $h($selectedAzubi['vorname'].' '.$selectedAzubi['nachname']) ?></h4>
                <div class="text-muted mb-2"><?= $h($selectedAzubi['email']) ?></div>

                <div class="small text-muted mb-2">
                    Ausbildungsjahr: <strong><?= $h($selectedAzubi['ausbildungsjahr']) ?></strong><br>
                    Abteilung: <strong><?= $h($selectedAzubi['abteilung']) ?></strong>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-2">Kein Azubi ausgewählt</h5>
                <p class="text-muted mb-0">Bitte wähle rechts in der Liste einen Azubi, um Details anzuzeigen.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- FILTER (jetzt über Einladungen) -->
        <?php if ($selectedAzubi): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Filter</h5>

                <form method="get" class="row g-2">
                    <input type="hidden" name="klasse" value="<?= $selectedClassId ?>">
                    <input type="hidden" name="azubi" value="<?= $selectedAzubiId ?>">

                    <div class="col-6">
                        <label class="form-label">Jahr</label>
                        <select name="year" class="form-select">
                            <option value="">Alle</option>
                            <?php foreach ($availableYears as $yr): ?>
                                <option value="<?= $yr ?>" <?= $filterYear==$yr?'selected':'' ?>><?= $yr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label">Monat</label>
                        <?php $months = [
                            1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',
                            7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'
                        ]; ?>
                        <select name="month" class="form-select">
                            <option value="">Alle</option>
                            <?php foreach ($months as $nr=>$name): ?>
                                <option value="<?= $nr ?>" <?= $filterMonth==$nr?'selected':'' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 d-grid mt-2">
                        <button class="btn btn-primary">Filtern</button>
                    </div>

                    <div class="col-12 d-grid">
                        <a href="klassen.php?klasse=<?= $selectedClassId ?>&azubi=<?= $selectedAzubiId ?>"
                        class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <!-- EINLADUNGEN (jetzt unter dem Filter) -->
        <?php if (!$selectedAzubi): ?>
        <div class="card">
            <div class="card-body">
                <h5>Einladungen</h5>

                <?php if ($classInvites): ?>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr><th>Email</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($classInvites as $inv): ?>
                                <tr>
                                    <td><?= $h($inv['email']) ?></td>
                                    <td><?= $inv['angenommen']==='Y'?'Angenommen':'Offen' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mt-2">Keine Einladungen.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>


    <!-- ================= RIGHT COLUMN ================= -->
    <div class="col-lg-8">

        <!-- AZUBI LISTE DER KLASSE -->
        <?php if (!$selectedAzubi): ?>
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Azubis der Klasse</h5>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
                        Azubi einladen
                    </button>
                </div>

                <?php if ($classAzubis): ?>
                    <div class="table-responsive mt-2">
                        <table class="table align-middle">
                            <thead><tr>
                                <th>Name</th><th>Email</th><th>Jahr</th><th></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($classAzubis as $az): ?>
                                <tr>
                                    <td><?= $h($az['vorname'].' '.$az['nachname']) ?></td>
                                    <td><?= $h($az['email']) ?></td>
                                    <td><?= $h($az['ausbildungsjahr']) ?></td>
                                    <td class="text-end">
                                        <a href="klassen.php?klasse=<?= $selectedClassId ?>&azubi=<?= $az['id'] ?>"
                                           class="btn btn-primary btn-sm">Öffnen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Noch keine Azubis zugeordnet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- WOCHENBERICHTE -->
        <?php if ($selectedAzubi): ?>
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Wochenberichte</h5>
                    <span class="text-muted small"><?= count($reports) ?> Bericht(e)</span>
                </div>

                <?php if ($reports): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr>
                                <th>KW</th><th>Zeitraum</th><th></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($reports as $rep): ?>
                                <?php $kw = date('W', strtotime($rep['startdatum'])); ?>
                                <tr>
                                    <td>KW <?= $kw ?></td>
                                    <td><?= $formatDate($rep['startdatum']) ?> - <?= $formatDate($rep['enddatum']) ?></td>
                                    <td class="text-end">
                                        <a href="wochenbericht_ausbilder.php?azubi=<?= $selectedAzubiId ?>&highlight=<?= $rep['id'] ?>"
                                           class="btn btn-outline-primary btn-sm">Öffnen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Keine Berichte gefunden.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- EXPORT BLOCK -->
<?php if ($selectedAzubi && $hasReports): ?>
<div class="card mb-4">
    <div class="card-body">

        <h5 class="mb-3">PDF-Export</h5>
        <p class="text-muted small">
            Exportiere alle Wochenberichte oder einzelne Zeiträume als PDF-Sammlung.
        </p>

        <!-- Alles exportieren -->
        <form method="get"
              action="wochenbericht_range_export.php"
              target="_blank"
              class="mb-3 d-flex flex-column flex-sm-row gap-2">

            <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
            <input type="hidden" name="from" value="<?= $h($exportMinDate ?? '') ?>">
            <input type="hidden" name="to" value="<?= $h($exportMaxDate ?? '') ?>">

            <button class="btn btn-primary w-100"
                    <?= (!$exportMinDate || !$exportMaxDate) ? 'disabled' : '' ?>>
                Alle Berichte exportieren
            </button>
        </form>

        <hr>

        <!-- Jahres-/Halbjahresexport -->
        <form id="exportYearForm"
              method="get"
              action="wochenbericht_range_export.php"
              target="_blank"
              class="row g-3">

            <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">
            <input type="hidden" name="from" id="exportYearFrom">
            <input type="hidden" name="to" id="exportYearTo">

            <div class="col-sm-4">
                <label class="form-label">Jahr</label>
                <select id="exportYearSelect" class="form-select">
                    <?php
                        $yearList = !empty($availableYears)
                            ? $availableYears
                            : [(int)date('Y')];
                        $defaultYear = $filterYear ?: $yearList[0];
                    ?>
                    <?php foreach ($yearList as $yr): ?>
                        <option value="<?= $yr ?>" <?= $yr == $defaultYear ? 'selected' : '' ?>>
                            <?= $yr ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-8">
                <label class="form-label">Bereich</label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit"
                            class="btn btn-outline-primary"
                            data-range="year">Ganzes Jahr</button>

                    <button type="submit"
                            class="btn btn-outline-primary"
                            data-range="h1">1. Halbjahr</button>

                    <button type="submit"
                            class="btn btn-outline-primary"
                            data-range="h2">2. Halbjahr</button>
                </div>
            </div>
        </form>

        <hr>

        <!-- Benutzerdefinierter Bereich -->
        <form method="get"
              action="wochenbericht_range_export.php"
              target="_blank"
              class="row g-3">

            <input type="hidden" name="azubi" value="<?= (int)$selectedAzubiId ?>">

            <div class="col-sm-4">
                <label class="form-label">Von</label>
                <input type="date"
                       name="from"
                       class="form-control"
                       value="<?= $h($exportMinDate ?? '') ?>">
            </div>

            <div class="col-sm-4">
                <label class="form-label">Bis</label>
                <input type="date"
                       name="to"
                       class="form-control"
                       value="<?= $h($exportMaxDate ?? '') ?>">
            </div>

            <div class="col-sm-4 d-grid">
                <button class="btn btn-success">Benutzerdefiniert exportieren</button>
            </div>
        </form>

    </div>
</div>

<!-- JAVASCRIPT für Jahres-/Halbjahresexport -->
<script>
(() => {
    const form = document.getElementById('exportYearForm');
    if (!form) return;

    const select = document.getElementById('exportYearSelect');
    const fromField = document.getElementById('exportYearFrom');
    const toField = document.getElementById('exportYearTo');

    const setRange = (mode) => {
        const year = select.value;
        let from = `${year}-01-01`;
        let to = `${year}-12-31`;

        if (mode === 'h1') to = `${year}-06-30`;
        if (mode === 'h2') from = `${year}-07-01`;

        fromField.value = from;
        toField.value = to;
    };

    form.querySelectorAll('button[data-range]').forEach(btn => {
        btn.addEventListener('click', () => setRange(btn.dataset.range));
    });

    setRange('year');
})();
</script>
<?php endif; ?>
<?php endif; ?>

<!-- Modal: Klasse bearbeiten -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Klasse bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="update_class" value="1">
                <input type="hidden" name="klasse_id" id="editKlasseId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="editKlasseName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Abteilung</label>
                        <input type="text" class="form-control" name="abteilung" id="editKlasseAbteilung" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jahrgang</label>
                        <input type="number" class="form-control" name="jahrgang" id="editKlasseJahrgang" min="2000" max="2100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Prefill: Klasse bearbeiten -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const classModal = document.getElementById('editClassModal');
    if (classModal) {
        classModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            classModal.querySelector('#editKlasseId').value = button.getAttribute('data-id') || '';
            classModal.querySelector('#editKlasseName').value = button.getAttribute('data-name') || '';
            classModal.querySelector('#editKlasseAbteilung').value = button.getAttribute('data-abteilung') || '';
            classModal.querySelector('#editKlasseJahrgang').value = button.getAttribute('data-jahrgang') || '';
        });
    }
});
</script>    </div>

</div>

<!-- Modals bleiben unverändert -->
<?php
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';



