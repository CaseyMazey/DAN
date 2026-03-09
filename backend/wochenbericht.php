<?php
// -------------------------------------------------------------
// SESSION & BASISKONTROLLE
// -------------------------------------------------------------
session_start();


// Wenn kein User eingeloggt ist ? zurück zur Loginseite
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// -------------------------------------------------------------
// DATENBANK + UTILS LADEN
// -------------------------------------------------------------
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';


$current_page = 'wochenbericht.php';
$user_id      = (int)$_SESSION['user_id'];

// -------------------------------------------------------------
// SNAPSHOT-TABELLE SICHERSTELLEN (für archivierte Aufgabenstände)
// -------------------------------------------------------------
// Diese Tabelle speichert Aufgaben exakt so, wie sie zum Zeitpunkt
// der Wochenbericht-?Erstellung waren. Dadurch bleiben Berichte stabil,
// selbst wenn Aufgaben später geändert werden.
$ensureSnapshotTable = function(mysqli $conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS wochenbericht_aufgaben (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bericht_id INT NOT NULL,
            titel VARCHAR(255) DEFAULT '',
            beschreibung TEXT,
            kommentar TEXT,
            kategorie VARCHAR(100) DEFAULT '',
            status VARCHAR(100) DEFAULT '',
            stunden DECIMAL(6,2) DEFAULT NULL,
            stunden_typ VARCHAR(50) DEFAULT '',
            erstellt_am DATETIME DEFAULT NULL,
            angelegt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bericht (bericht_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($sql);

    // Prüft nachträglich, ob die Spalte "kommentar" existiert,
    // falls die Tabelle aus früheren Versionen stammt
    $conn->query("ALTER TABLE wochenbericht_aufgaben ADD COLUMN IF NOT EXISTS kommentar TEXT");
};

$ensureSnapshotTable($conn);

// -------------------------------------------------------------
// DATEN FÜR DIE AKTUELLE KALENDERWOCHE BERECHNEN
// -------------------------------------------------------------
$heute       = date('Y-m-d');
$heute_w     = date('N'); // Wochen-Tag 1–7
$uhrzeit     = date('H:i');

$week_start  = date('Y-m-d', strtotime('monday this week', strtotime($heute)));
$week_end    = date('Y-m-d', strtotime($week_start . ' +4 days')); // Freitag
$week_key    = date('o-W', strtotime($week_start)); // ISO-Jahr + KW

// -------------------------------------------------------------
// PROFIL-SIGNATUR STATUS (Profil-Signatur wird beim Signieren genutzt)
// -------------------------------------------------------------
$profileSigType = "none";
$hasProfileSignature = false;
$stmt = $conn->prepare(" 
    SELECT signature_type, CASE WHEN signature_img IS NULL THEN 0 ELSE 1 END AS has_sig
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $profileSigType = $row['signature_type'] ?? 'none';
    $hasProfileSignature = !empty($row['has_sig']);
}
$stmt->close();
// -------------------------------------------------------------
// AKTUELLEN WOCHENBERICHT LADEN (falls vorhanden)
// -------------------------------------------------------------
$stmt = $conn->prepare("
    SELECT wb.*, u.vorname, u.nachname, k.name AS klassenname, u.abteilung, u.ausbildungsjahr
    FROM wochenberichte wb
    JOIN users u ON wb.azubi_id = u.id
    LEFT JOIN klassen k ON wb.klasse_id = k.id
    WHERE wb.azubi_id = ? AND wb.startdatum = ? AND wb.enddatum = ?
    LIMIT 1
");
$stmt->bind_param("iss", $user_id, $week_start, $week_end);
$stmt->execute();
$current_wb = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -------------------------------------------------------------
// AUTOMATISCHEN WOCHENBERICHT ANLEGEN (Freitag ab 18:00 Uhr)
// -------------------------------------------------------------
// Nur wenn keiner existiert, werden die Aufgaben der Woche kopiert.
if (!$current_wb && $heute_w == 5 && $uhrzeit >= '18:00') {

    // ---------------------------------------------------------
    // KLASSEN-ID DES AZUBIS LADEN
    // ---------------------------------------------------------
    $klasse_id = 0;
    $stmt = $conn->prepare("SELECT klasse_id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && isset($res['klasse_id'])) {
        $klasse_id = (int)$res['klasse_id'];
    }
    $stmt->close();

    // ---------------------------------------------------------
    // AUFGABEN DER WOCHE SUCHEN (fertige bzw. laufende)
//     die nicht archiviert sind
// ---------------------------------------------------------
    $stmt = $conn->prepare("
        SELECT id, titel, beschreibung, kommentar, kategorie,
            status, stunden, stunden_typ, erstellt_am
        FROM aufgaben
        WHERE azubi_id = ?
        AND (archiviert IS NULL OR archiviert = 0)
        AND status IN ('in_bearbeitung','fertig')
    ");
    $stmt->bind_param("i", $user_id);


    $stmt->execute();
    $tasks_to_take = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ---------------------------------------------------------
    // WOCHENBERICHT ANLEGEN
    // ---------------------------------------------------------
    $stmt = $conn->prepare("
        INSERT INTO wochenberichte (azubi_id, klasse_id, startdatum, enddatum, erstellt_am, abgegeben, signiert)
        VALUES (?, NULLIF(?,0), ?, ?, NOW(), 'N', 'N')
    ");
    $stmt->bind_param("iiss", $user_id, $klasse_id, $week_start, $week_end);
    $stmt->execute();
    $wb_id = $conn->insert_id;
    $stmt->close();



    // Neu erstellten Bericht erneut laden
    $stmt = $conn->prepare("
        SELECT wb.*, u.vorname, u.nachname, k.name AS klassenname,
               u.abteilung, u.ausbildungsjahr
        FROM wochenberichte wb
        JOIN users u ON wb.azubi_id = u.id
        LEFT JOIN klassen k ON wb.klasse_id = k.id
        WHERE wb.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $wb_id);
    $stmt->execute();
    $current_wb = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}


// -------------------------------------------------------------
// HELPER: WOCHENBERICHT LADEN (nach ID oder neuesten Bericht)
// -------------------------------------------------------------
// Wird verwendet, wenn der Benutzer per GET?id einen Bericht auswählt
// oder wenn standardmäßig der neueste Bericht angezeigt werden soll.
$loadReport = function (?int $id = null) use ($conn, $user_id): ?array {
    if ($id) {
        // Spezifischen Bericht laden (nur wenn er dem angemeldeten User gehört)
        $stmt = $conn->prepare("
            SELECT wb.*, u.vorname, u.nachname, u.abteilung, u.ausbildungsjahr, k.name AS klassenname
            FROM wochenberichte wb
            JOIN users u ON wb.azubi_id = u.id
            LEFT JOIN klassen k ON wb.klasse_id = k.id
            WHERE wb.id = ? AND wb.azubi_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $id, $user_id);
    } else {
        // Neuester Bericht des Users
        $stmt = $conn->prepare("
            SELECT wb.*, u.vorname, u.nachname, u.abteilung, u.ausbildungsjahr, k.name AS klassenname
            FROM wochenberichte wb
            JOIN users u ON wb.azubi_id = u.id
            LEFT JOIN klassen k ON wb.klasse_id = k.id
            WHERE wb.azubi_id = ?
            ORDER BY wb.startdatum DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
};

// -------------------------------------------------------------
// HELPER: AUFGABEN LADEN 
// -------------------------------------------------------------

$loadTasks = function (array $report) use ($conn): array {

    $isCurrent = ($report['abgegeben'] ?? 'N') === 'N';


    // -------------------------------
    // AKTUELLER BERICHT → LIVE
    // -------------------------------
    if ($isCurrent) {

        $stmt = $conn->prepare("
            SELECT
                titel,
                beschreibung,
                kommentar,
                kategorie,
                status,
                stunden,
                stunden_typ,
                erstellt_am
            FROM aufgaben
            WHERE azubi_id = ?
                AND (archiviert IS NULL OR archiviert = 0)
                AND status IN ('in_bearbeitung', 'fertig')
            ORDER BY erstellt_am ASC
        ");

        $stmt->bind_param("i", $report['azubi_id']);
        $stmt->execute();

        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $tasks;
    }

    // -------------------------------
    // ABGESCHLOSSENER BERICHT → SNAPSHOT
    // -------------------------------
    $stmtSnap = $conn->prepare("
        SELECT
            titel,
            beschreibung,
            kommentar,
            kategorie,
            status,
            stunden,
            stunden_typ,
            erstellt_am
        FROM wochenbericht_aufgaben
        WHERE bericht_id = ?
        ORDER BY erstellt_am ASC, id ASC
    ");

    $stmtSnap->bind_param("i", $report['id']);
    $stmtSnap->execute();

    $tasks = $stmtSnap->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtSnap->close();

    return $tasks;
};



// -------------------------------------------------------------
// HELPER: STUNDEN ZUSAMMENFASSEN + SEKTIONS-AUFTEILUNG
// -------------------------------------------------------------
// Baut die drei Bereiche:
//  - Betrieb
//  - Unterweisung
//  - Berufsschule
// und berechnet verfügbare Stunden sowie Unterweisungsanteile.
$summarizeTasks = function (array $tasks) use ($conn, $user_id): array {

    // Aufgaben in Sektionsblöcke sortieren
    $sections = [
        'betrieb'       => [],
        'unterweisung'  => [],
        'berufsschule'  => []
    ];

    // Stundenkonto vorbereiten
    $hours = [
        'betrieb'              => 0.0,
        'schule'               => 0.0,
        'unterweisung_betrieb' => 0.0,
        'unterweisung_schule'  => 0.0
    ];

    // Alle Aufgaben durchgehen
    foreach ($tasks as $task) {

        // Einheitliche Datensatzstruktur
        $entry = [
            'titel'        => $task['titel'] ?? '',
            'beschreibung' => $task['beschreibung'] ?? '',
            'kommentar'    => $task['kommentar'] ?? '',
            'stunden'      => is_numeric($task['stunden'] ?? null) ? (float)$task['stunden'] : null,
            'status'       => $task['status'] ?? '',
            'kategorie'    => $task['kategorie'] ?? '',
            'stunden_typ'  => $task['stunden_typ'] ?? '',
        ];

        // Kategoriewerte normalisieren
        $kat        = strtolower((string)$task['kategorie']);
        $stundenTyp = strtolower((string)($task['stunden_typ'] ?? ''));
        $val        = is_numeric($task['stunden'] ?? null) ? (float)$task['stunden'] : 0.0;

        // ---------------------------------------------------------
        // UNTERWEISUNG
        // ---------------------------------------------------------
        if ($kat === 'unterweisung') {
            if ($val > 0) {
                if ($stundenTyp === 'berufsschule') {
                    $hours['unterweisung_schule'] += $val;
                } else {
                    $hours['unterweisung_betrieb'] += $val;
                }
            }
            $sections['unterweisung'][] = $entry;
        }

        // ---------------------------------------------------------
        // BERUFSSCHULE
        // ---------------------------------------------------------
        elseif ($kat === 'berufsschule') {
            if ($val > 0) {
                $hours['schule'] += $val;
            }
            $sections['berufsschule'][] = $entry;
        }

        // ---------------------------------------------------------
        // STANDARD: BETRIEB
        // ---------------------------------------------------------
        else {
            if ($val > 0) {
                $hours['betrieb'] += $val;
            }
            $sections['betrieb'][] = $entry;
        }
    }

    // -------------------------------------------------------------
    // GRENZEN / KONFIG AUS stunden_utils LADEN
    // -------------------------------------------------------------
    $config = getStundenConfigUser($conn, $user_id);

    // Summen berechnen
    $hours['unterweisung_sum'] = $hours['unterweisung_betrieb'] + $hours['unterweisung_schule'];
    $hours['gesamt']           = $hours['betrieb'] + $hours['schule'];

    // Verfügbare Reststunden nach Abzug der Unterweisung
    $hours['verfuegbar_betrieb'] = max(0, (float)($config['max_betrieb'] ?? 0) - $hours['unterweisung_betrieb']);
    $hours['verfuegbar_schule']  = max(0, (float)($config['max_berufsschule'] ?? 0) - $hours['unterweisung_schule']);

    return [
        'sections' => $sections,
        'hours'    => $hours
    ];
};

// -------------------------------------------------------------
// AKTUELLEN ODER ANGEFORDERTEN BERICHT LADEN
// -------------------------------------------------------------
$selectedId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentReport  = $loadReport($selectedId ?: null);

// Falls requested ID nicht existiert ? auf neuesten Bericht zurückfallen
if (!$currentReport && $selectedId > 0) {
    $currentReport = $loadReport(null);
}

$currentTasks = $currentReport ? $loadTasks($currentReport) : [];
$summary      = $currentReport ? $summarizeTasks($currentTasks) : [
    'sections' => [],
    'hours'    => []
];


// -------------------------------------------------------------
// FILTER FÜR DIE HISTORIE (Jahr / Monat / KW / Nur Abgegebene)
// -------------------------------------------------------------
$filterYear    = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$filterMonth   = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterKw      = isset($_GET['kw']) ? (int)$_GET['kw'] : 0;
$onlySigned = !empty($_GET['signed']) && $_GET['signed'] === '1';

// -------------------------------------------------------------
// PRÜFEN, OB FILTER AKTIV SIND
// -------------------------------------------------------------
$hasActiveFilter =
    $filterYear > 0 ||
    $filterMonth > 0 ||
    $filterKw > 0 ||
    $onlySigned;


// -------------------------------------------------------------
// VERFÜGBARE JAHRE AUS DER DB LADEN
// -------------------------------------------------------------
$availableYears = [];
$yearStmt = $conn->prepare("
    SELECT DISTINCT YEAR(startdatum) AS jahr
    FROM wochenberichte
    WHERE azubi_id = ?
    ORDER BY jahr DESC
");
$yearStmt->bind_param("i", $user_id);
$yearStmt->execute();
$yearRes = $yearStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$yearStmt->close();

foreach ($yearRes as $yRow) {
    $availableYears[] = (int)$yRow['jahr'];
}

// -------------------------------------------------------------
// DYNAMISCHES WHERE-BUILDING FÜR DIE HISTORIENABFRAGE
// -------------------------------------------------------------
$historyWhere  = ["wb.azubi_id = ?"];
$historyTypes  = 'i';
$historyParams = [$user_id];

// Das aktuell geöffnete Dokument nicht in der Liste erneut anzeigen
if ($currentReport && isset($currentReport['id'])) {
    $historyWhere[] = 'wb.id <> ?';
    $historyTypes .= 'i';
    $historyParams[] = (int)$currentReport['id'];
}

// Filter: Jahr
if ($filterYear > 0) {
    $yearStart = $filterYear . '-01-01';
    $yearEnd   = $filterYear . '-12-31';

    $historyWhere[] = "wb.startdatum BETWEEN ? AND ?";
    $historyTypes  .= 'ss';
    $historyParams[] = $yearStart;
    $historyParams[] = $yearEnd;

}

// Filter: Monat
if ($filterMonth > 0 && $filterMonth <= 12) {
    $historyWhere[] = "MONTH(wb.startdatum) = ?";
    $historyTypes .= 'i';
    $historyParams[] = $filterMonth;
}

// -------------------------------------------------------------
// Filter: KW (ISO-Kalenderwoche)
// -------------------------------------------------------------
if ($filterKw > 0) {

    // Jahr bestimmen: ausgewähltes Jahr oder aktuelles ISO-Jahr
    $isoYear = $filterYear > 0 ? $filterYear : (int)date('o');

    try {
        $dt = new DateTime();
        $dt->setISODate($isoYear, $filterKw);

        $monday = $dt->format('Y-m-d');
        $friday = $dt->modify('+4 days')->format('Y-m-d');

        $historyWhere[] = "wb.startdatum BETWEEN ? AND ?";
        $historyTypes   .= 'ss';
        $historyParams[] = $monday;
        $historyParams[] = $friday;
    } catch (Exception $e) {
        // ungültige KW → Filter ignorieren
    }
}


// Filter: Nur signierte Berichte
if ($onlySigned) {
    $historyWhere[] = "wb.signiert = 'Y'";
}

// -------------------------------------------------------------
// HISTORIE-SQL ZUSAMMENSETZEN
// -------------------------------------------------------------
$limitSql = $hasActiveFilter ? '' : ' LIMIT 10';

$historySql = "
    SELECT wb.*, k.name AS klassenname
    FROM wochenberichte wb
    LEFT JOIN klassen k ON wb.klasse_id = k.id
    WHERE " . implode(' AND ', $historyWhere) . "
    ORDER BY wb.startdatum DESC
    $limitSql
";


// Query vorbereiten & Parameter binden
$stmt = $conn->prepare($historySql);

// bind_param benötigt Referenzen – daher Konstruktion über array_unshift
$bind = [];
foreach ($historyParams as $k => $v) {
    $bind[$k] = &$historyParams[$k];
}
array_unshift($bind, $historyTypes);

// Parameter anwenden
call_user_func_array([$stmt, 'bind_param'], $bind);

// Ausführen & Ergebnisse holen
$stmt->execute();
$historyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// -------------------------------------------------------------
// FORMATIERUNGSHILFEN (Datum, Stunden, HTML Escape)
// -------------------------------------------------------------
$fmtDate  = fn($d) => date('d.m.Y', strtotime($d));
$fmtHours = fn($v) => number_format((float)$v, 2, ',', '.');
$h        = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');


// -------------------------------------------------------------
// ------------------------- SEITENINHALT -----------------------
// -------------------------------------------------------------
ob_start();
?>

<div id="top"></div>

<!-- -----------------------------------------------------------
     SEITENÜBERSCHRIFT + Hinweis auf Sortierung
------------------------------------------------------------ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <h1 class="mb-0">Wochenberichte</h1>
    
</div>

<!-- -----------------------------------------------------------
     FALLS EIN AKTUELLER BERICHT EXISTIERT ? KOPFZEILE & DETAILS
------------------------------------------------------------ -->
<?php if ($currentReport): ?>
    <?php
        $kw   = date('W', strtotime($currentReport['startdatum']));
        $jahr = date('Y', strtotime($currentReport['startdatum']));
        $hours = $summary['hours'];
    ?>
    <div class="card mb-4">
        <div class="card-body">

            <!-- -------------------------------------------------
                 BERICHT-KOPF: Titel, Zeitraum, Azubi-Daten
            -------------------------------------------------- -->
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-muted small">Ausbildungsnachweis (IHK-Struktur)</div>
                    <h3 class="mb-1">
                        KW <?= $h($kw) ?> / <?= $h($jahr) ?>
                        &middot;
                        <?= $h($fmtDate($currentReport['startdatum'])) ?>
                        -
                        <?= $h($fmtDate($currentReport['enddatum'])) ?>
                    </h3>

                    <!-- Anzeige von Name, Klasse, Abteilung, Ausbildungsjahr -->
                    <div class="text-muted small">
                        <?= $h(trim(($currentReport['vorname'] ?? '') . ' ' . ($currentReport['nachname'] ?? ''))) ?>
                        <?php if (!empty($currentReport['klassenname'])): ?>
                            | Klasse <?= $h($currentReport['klassenname']) ?>
                        <?php endif; ?>
                        <?php if (!empty($currentReport['abteilung'])): ?>
                            | Abteilung <?= $h($currentReport['abteilung']) ?>
                        <?php endif; ?>
                        <?php if (!empty($currentReport['ausbildungsjahr'])): ?>
                            | Ausbildungsjahr <?= $h($currentReport['ausbildungsjahr']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ---------------------------------------------
                     BADGES: abgegeben? signiert?
                ---------------------------------------------- -->
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge <?= $currentReport['abgegeben'] === 'Y' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $currentReport['abgegeben'] === 'Y' ? 'Abgegeben' : 'Nicht abgegeben' ?>
                    </span>

                    <span class="badge <?= $currentReport['signiert'] === 'Y' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $currentReport['signiert'] === 'Y' ? 'Signiert' : 'Nicht signiert' ?>
                    </span>
                </div>
            </div>

            <hr>

            <!-- -------------------------------------------------
                 HAUPTBEREICH: Aufgabenlisten + Stundenübersicht
            -------------------------------------------------- -->
            <div class="row g-4">

                <!-- ---------------------------------------------
                     SPALTE 1: Aufgaben nach Kategorien (links)
                ---------------------------------------------- -->
                <div class="col-lg-8">

                    <?php
                        // Mapping für lesbare Überschriften
                        $labels = [
                            'betrieb'       => 'Betriebliche Tätigkeiten',
                            'unterweisung'  => 'Unterweisungen / Schulungen',
                            'berufsschule'  => 'Berufsschule'
                        ];
                    ?>

                    <?php foreach ($labels as $key => $label): ?>
                        <!-- Einzelner Kategorienblock -->
                        <div class="mb-3 p-3 border rounded">

                            <!-- Kategorie-Header + Reststunden / Summe -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><?= $h($label) ?></h5>

                                <?php if ($key === 'betrieb'): ?>
                                    <span class="badge bg-light text-dark">
                                        Betrieb: <?= $h($fmtHours($hours['verfuegbar_betrieb'] ?? 0)) ?> Std.
                                    </span>

                                <?php elseif ($key === 'unterweisung'): ?>
                                    <span class="badge bg-light text-dark">
                                        Summe: <?= $h($fmtHours($hours['unterweisung_sum'] ?? 0)) ?> Std.
                                    </span>

                                <?php else: ?>
                                    <span class="badge bg-light text-dark">
                                        Schule: <?= $h($fmtHours($hours['verfuegbar_schule'] ?? 0)) ?> Std.
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Liste der Aufgaben dieser Kategorie -->
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
                                <!-- Keine Aufgaben vorhanden -->
                                <div class="text-muted">Keine Einträge.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- ---------------------------------------------
                     SPALTE 2: Stundenübersicht + Signatur (rechts)
                ---------------------------------------------- -->
                <div class="col-lg-4">
                    <div class="border rounded h-100 p-3 bg-light">

                        <!-- Stundenübersichtstabelle -->
                        <h5>StundenÜbersicht</h5>
                        <table class="table table-sm mb-3">
                            <tbody>
                                <tr><th>Betrieb</th><td class="text-end"><?= $h($fmtHours($hours['betrieb'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Unterweisung (Betrieb)</th><td class="text-end"><?= $h($fmtHours($hours['unterweisung_betrieb'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Unterweisung (Schule)</th><td class="text-end"><?= $h($fmtHours($hours['unterweisung_schule'] ?? 0)) ?> Std.</td></tr>
                                <tr><th>Berufsschule</th><td class="text-end"><?= $h($fmtHours($hours['schule'] ?? 0)) ?> Std.</td></tr>

                                <tr class="table-light">
                                    <th>Gesamt</th>
                                    <td class="text-end fw-semibold"><?= $h($fmtHours($hours['gesamt'] ?? 0)) ?> Std.</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- -----------------------------------------
                             SIGNATURBEREICH DES AZUBIS
                        ------------------------------------------ -->
                        <div class="mb-3">
                            <h6 class="mb-1">Signatur</h6>

                            <?php if ($currentReport['signiert'] === 'Y'): ?>

                                <!-- Bericht ist signiert -->
                                <div class="text-success small mb-1">
                                    Signiert
                                    <?= $currentReport['signiert_am'] ? ' am ' . $h(date('d.m.Y H:i', strtotime($currentReport['signiert_am']))) : '' ?>
                                </div>

                                <!-- Falls Bild gespeichert wurde -->
                                <?php if (!empty($currentReport['signatur_img'])): ?>
                                    <?php $imgData = base64_encode($currentReport['signatur_img']); ?>
                                    <img src="data:image/png;base64,<?= $imgData ?>"
                                         alt="Signatur"
                                         class="img-fluid border"
                                         style="max-width:260px;">
                                <?php endif; ?>

                                <!-- Option zum Entfernen -->
                                <form method="post" action="wochenbericht_update.php" class="mt-2">
                                    <input type="hidden" name="wb_id" value="<?= (int)$currentReport['id'] ?>">
                                    <button type="submit" name="unsignieren" value="1"
                                            class="btn btn-outline-warning btn-sm">
                                        Signatur entfernen
                                    </button>
                                </form>

                            <?php else: ?>

                                <!-- Bericht noch nicht signiert -->
                                <div class="text-muted small mb-2">Noch nicht signiert.</div>
                                <?php if ($hasProfileSignature): ?>
                                    <form method="post" action="wochenbericht_update.php">
                                        <input type="hidden" name="wb_id" value="<?= (int)$currentReport['id'] ?>">
                                        <button type="submit" name="signieren" value="1" class="btn btn-primary btn-sm">
                                            Bericht signieren
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning small p-2">
                                        Keine Profil-Signatur vorhanden. Bitte im <a href="account.php">Profil</a> hinterlegen.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- PDF-Link -->
                        <a href="wochenbericht_pdf_export.php?id=<?= (int)$currentReport['id'] ?>"
                           class="btn btn-outline-secondary w-100 mt-2"
                           target="_blank">
                           PDF exportieren
                        </a>

                    </div> <!-- /col-right card -->
                </div> <!-- /col-lg-4 -->
            </div> <!-- /row -->
        </div>
    </div>

<?php else: ?>
    <!-- Kein aktueller Bericht -->
    <div class="alert alert-info">Keine Wochenberichte vorhanden.</div>
<?php endif; ?>


<div class="card">
    <div class="card-body">

        <!-- -----------------------------------------------------
             ANKER für Scroll-Ziel (#history)
        ------------------------------------------------------ -->
        <div id="history"></div>

        <!-- -----------------------------------------------------
             ÜBERSCHRIFT + ANZAHL DER GEFUNDENEN EINTRÄGE
        ------------------------------------------------------ -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Vergangene Wochenberichte</h2>
            <div class="text-muted small"><?= count($historyRows) ?> Eintrag(e)</div>
        </div>

        <!-- -----------------------------------------------------
             FILTERFORMULAR (Jahr, Monat, KW, Nur abgeschlossene)
        ------------------------------------------------------ -->
        <form method="get" class="row g-3 align-items-end mb-3">

            <!-- Jahr -->
            <div class="col-sm-3 col-md-2">
                <label class="form-label">Jahr</label>
                <select name="year" class="form-select">
                    <option value="">Alle</option>
                    <?php foreach ($availableYears as $yr): ?>
                        <option value="<?= (int)$yr ?>"
                                <?= $filterYear === (int)$yr ? 'selected' : '' ?>>
                            <?= (int)$yr ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Monat -->
            <div class="col-sm-3 col-md-2">
                <label class="form-label">Monat</label>
                <?php
                    $months = [
                        1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',
                        7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'
                    ];
                ?>
                <select name="month" class="form-select">
                    <option value="">Alle</option>
                    <?php foreach ($months as $nr => $name): ?>
                        <option value="<?= (int)$nr ?>"
                                <?= $filterMonth === (int)$nr ? 'selected' : '' ?>>
                            <?= $h($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Kalenderwoche -->
            <div class="col-sm-3 col-md-2">
                <label class="form-label">KW</label>
                <input type="number" min="1" max="53"
                       name="kw"
                       class="form-control"
                       value="<?= $h($filterKw ?: '') ?>">
            </div>

            <!-- Nur abgeschlossene -->
            <div class="col-sm-4 col-md-2 form-check mt-4">
                <input class="form-check-input"
                    type="checkbox"
                    value="1"
                    name="signed"
                    id="signed"
                    <?= $onlySigned ? 'checked' : '' ?>>
                <label class="form-check-label" for="signed">
                    Nur signierte
                </label>

            </div>

            <!-- Filtern-Button -->
            <div class="col-sm-4 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Filtern</button>
            </div>

            <!-- Zurücksetzen -->
            <div class="col-sm-4 col-md-2 d-grid">
                <a href="wochenbericht.php#history"
                   class="btn btn-outline-secondary">
                   Zurücksetzen
                </a>
            </div>

        </form>

        <!-- -----------------------------------------------------
             SAMMEL-PDF OPTIONEN
        ------------------------------------------------------ -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="text-muted small">
                Checkboxen markieren und Sammel-PDF exportieren.
            </div>

            <div class="d-flex gap-2">
                <!-- nur ausgewählte -->
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        data-export="selected">
                    Ausgewählte als Sammel-PDF
                </button>

                <!-- alle gefilterten -->
                <button type="button"
                        class="btn btn-outline-success btn-sm"
                        data-export="filtered">
                    Alle gefilterten als Sammel-PDF
                </button>

                <!-- LÖSCHEN (nur ausgewählte) -->
                <button type="button"
                        id="bulkDeleteBtn"
                        class="icon-btn delete">
                    <svg xmlns="http://www.w3.org/2000/svg
                        fill="none" 
                        viewBox="0 0 50 59" 
                        class="bin">
                        <path fill="#B5BAC1" d="M0 7.5C0 5.01472 2.01472 3 4.5 3H45.5C47.9853 3 50 5.01472 50 7.5V7.5C50 8.32843 49.3284 9 48.5 9H1.5C0.671571 9 0 8.32843 0 7.5V7.5Z"></path>
                        <path fill="#B5BAC1" d="M17 3C17 1.34315 18.3431 0 20 0H29.3125C30.9694 0 32.3125 1.34315 32.3125 3V3H17V3Z"></path>
                        <path fill="#B5BAC1" d="M2.18565 18.0974C2.08466 15.821 3.903 13.9202 6.18172 13.9202H43.8189C46.0976 13.9202 47.916 15.821 47.815 18.0975L46.1699 55.1775C46.0751 57.3155 44.314 59.0002 42.1739 59.0002H7.8268C5.68661 59.0002 3.92559 57.3155 3.83073 55.1775L2.18565 18.0974ZM18.0003 49.5402C16.6196 49.5402 15.5003 48.4209 15.5003 47.0402V24.9602C15.5003 23.5795 16.6196 22.4602 18.0003 22.4602C19.381 22.4602 20.5003 23.5795 20.5003 24.9602V47.0402C20.5003 48.4209 19.381 49.5402 18.0003 49.5402ZM29.5003 47.0402C29.5003 48.4209 30.6196 49.5402 32.0003 49.5402C33.381 49.5402 34.5003 48.4209 34.5003 47.0402V24.9602C34.5003 23.5795 33.381 22.4602 32.0003 22.4602C30.6196 22.4602 29.5003 23.5795 29.5003 24.9602V47.0402Z" clip-rule="evenodd" fill-rule="evenodd"></path>
                        <path fill="#B5BAC1" d="M2 13H48L47.6742 21.28H2.32031L2 13Z"></path>
                    </svg>
                    <span class="tooltip">Ausgewählte Berichte löschen</span>
                </button>


            </div>
        </div>

        <!-- -----------------------------------------------------
             TABELLE: ALLE VERGANGENEN WOCHENBERICHTE
        ------------------------------------------------------ -->
        <?php if (!empty($historyRows)): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:36px;">
                                <input type="checkbox" id="checkAllHistory">
                            </th>
                            <th>Zeitraum</th>
                            <th>Azubi Signatur</th>
                            <th>Ausbilder Signatur</th>
                            <th>PDF</th>
                            <th>Vorschau</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($historyRows as $row): ?>
                            <?php
                                // Markieren, falls dies der gerade gezeigte Bericht ist
                                $isActive = $currentReport
                                            && (int)$currentReport['id'] === (int)$row['id'];

                                $kwRow   = date('W', strtotime($row['startdatum']));
                                $yearRow = date('Y', strtotime($row['startdatum']));
                            ?>

                            <tr class="<?= $isActive ? 'table-primary' : '' ?>">
                                <!-- Checkbox für Sammel-PDF -->
                                <td>
                                    <input type="checkbox"
                                           class="form-check-input history-check"
                                           value="<?= (int)$row['id'] ?>">
                                </td>

                                <!-- Zeitraum -->
                                <td>
                                    <div class="fw-semibold">
                                        KW <?= $h($kwRow) ?> / <?= $h($yearRow) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= $h($fmtDate($row['startdatum'])) ?>
                                        -
                                        <?= $h($fmtDate($row['enddatum'])) ?>
                                    </div>
                                </td>

                                <!-- Azubi-Signatur -->
                                <td>
                                    <?= $row['signiert'] === 'Y'
                                        ? '<span class="badge bg-success">Ja</span>'
                                        : '<span class="badge bg-warning text-dark">Nein</span>' ?>
                                </td>

                                <!-- Ausbilder-Signatur -->
                                <td>
                                    <?= ($row['signiert_ausbilder'] ?? 'N') === 'Y'
                                        ? '<span class="badge bg-success">Ja</span>'
                                        : '<span class="badge bg-warning text-dark">Nein</span>' ?>
                                </td>

                                <!-- PDF-Link -->
                                <td>
                                    <a class="btn btn-outline-secondary btn-sm"
                                       href="wochenbericht_pdf_export.php?id=<?= (int)$row['id'] ?>"
                                       target="_blank">
                                       PDF
                                    </a>
                                </td>

                                <!-- Vorschau-Link -->
                                <td>
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="wochenbericht.php?id=<?= (int)$row['id'] ?>#top">
                                       Vorschau
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Wenn Filter keine Berichte liefert -->
            <p class="text-muted mb-0">
                Keine Berichte für die aktuellen Filter gefunden.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- -------------------------------------------------------------
     VERSTECKTES FORMULAR FÜR SAMMEL-PDF EXPORT
-------------------------------------------------------------- -->
<form id="bulkExportForm"
      method="post"
      action="wochenbericht_range_export.php"
      target="_blank"
      class="d-none">

    <input type="hidden" name="azubi" value="<?= $user_id ?>">
    <input type="hidden" name="ids" id="bulkIds">
    <input type="hidden" name="exclude_id" value="<?= $currentReport['id'] ?? '' ?>">

    <!-- Filterzustände weitergeben -->
    <input type="hidden" name="year" value="<?= $h($filterYear) ?>">
    <input type="hidden" name="month" value="<?= $h($filterMonth) ?>">
    <input type="hidden" name="kw" value="<?= $h($filterKw) ?>">
    <input type="hidden" name="signed" value="<?= $onlySigned ? '1' : '0' ?>">
</form>

<form id="bulkDeleteForm"
      method="post"
      action="wochenbericht_bulk_delete.php"
      class="d-none">
    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>

<?php
$page_content = ob_get_clean();

$page_scripts = '';

// -------------------------------------------------------------
// JAVASCRIPT: SAMMEL-PDF EXPORT (Checkboxen + Buttons)
// -------------------------------------------------------------
$page_scripts .= <<<'JS'

<script>
(function(){
    console.log('SCRIPT START');


    // Checkbox zum „alle auswählen“
    const checkAll      = document.getElementById('checkAllHistory');

    // Alle Einzel-Checkboxen
    const checkboxes    = document.querySelectorAll('.history-check');

    // Verstecktes Formular, das an wochenbericht_range_export.php geschickt wird
    const bulkForm      = document.getElementById('bulkExportForm');

    // Hidden-Feld für IDs
    const bulkIdsField  = document.getElementById('bulkIds');

    // ---------------------------------------------------------
    // „Alle auswählen“ toggeln
    // ---------------------------------------------------------
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        });
    }

    // ---------------------------------------------------------
    // Buttons: ausgewählte oder alle gefilterten exportieren
    // data-export="selected"  ? nur markierte
    // data-export="filtered"  ? alle gefilterten
    // ---------------------------------------------------------
    document.querySelectorAll('[data-export]').forEach(btn => {

        btn.addEventListener('click', () => {

            if (!bulkForm || !bulkIdsField) return;

            const mode = btn.getAttribute('data-export');

            // --- NUR MARKIERTE EXPORTIEREN ---
            if (mode === 'selected') {

                // IDs aus Checkboxen sammeln
                const ids = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (!ids.length) {
                    alert('Bitte mindestens einen Bericht auswählen.');
                    return;
                }

                // IDs als CSV in hidden-Feld
                bulkIdsField.value = ids.join(',');

            } else {
                // --- ALLE GEFILTERTEN EXPORTIEREN ---
                // einfach nichts eintragen ? Server nutzt die Filter
                bulkIdsField.value = '';
            }

            // Formular abschicken (öffnet neues Tab)
            bulkForm.submit();
        });
    });

    // ---------------------------------------------------------
    // BULK DELETE: ausgewählte Wochenberichte löschen
    // ---------------------------------------------------------
    const bulkDeleteBtn  = document.getElementById('bulkDeleteBtn');
    console.log('bulkDeleteBtn:', bulkDeleteBtn);

    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkDeleteIds  = document.getElementById('bulkDeleteIds');

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', () => {

            const ids = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            if (!ids.length) {
                alert('Bitte mindestens einen Wochenbericht auswählen.');
                return;
            }

            if (!confirm("Ausgewählte Wochenberichte wirklich löschen?\n(Signierte bleiben erhalten)")) {
                return;
            }

            bulkDeleteIds.value = ids.join(',');
            bulkDeleteForm.submit();
        });
    }


})();
</script>
JS;



include __DIR__ . '/layout.php';
?>



