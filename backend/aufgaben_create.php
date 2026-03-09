<?php
session_start();
require_once __DIR__ . '/db.php';

// -------------------------------------------------------------
// JSON-Antworten und Fehlervermeidung konfigurieren
// -------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0'); // verhindert kaputte JSON-Ausgabe
error_reporting(E_ALL);

// -------------------------------------------------------------
// UTF-8 Bereinigung — schützt JSON vor ungültigen Encodings
// -------------------------------------------------------------
function sanitize_utf8($value) {
    if (is_string($value)) {
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        return $clean === false ? '' : $clean;
    }
    if (is_array($value)) {
        return array_map('sanitize_utf8', $value);
    }
    return $value;
}

/**
 * Einheitliche JSON-Ausgabe
 * - setzt Statuscode
 * - entfernt ungültige Zeichen
 * - sorgt dafür, dass FE IMMER gültiges JSON bekommt
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    $payload = sanitize_utf8($payload);

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        echo '{"success":false,"msg":"Interner Kodierungsfehler"}';
    } else {
        echo $json;
    }
    exit;
}

/**
 * Liefert die maximale Zeichenzahl einer Spalte (z. B. VARCHAR-Länge).
 * Gibt null zurück, wenn die Information nicht ermittelt werden kann.
 */
function getColumnMaxLength(mysqli $conn, string $table, string $column): ?int {
    $stmt = $conn->prepare("
        SELECT CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->bind_result($length);
    $stmt->fetch();
    $stmt->close();

    return $length !== null ? (int)$length : null;
}

/* ============================================================
   LOGIN-PRÜFUNG
   ============================================================ */
if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'msg' => 'Nicht eingeloggt'], 401);
}

$user_id = (int)$_SESSION['user_id'];

/* ============================================================
   KLASSEN-ID LADEN (für automatische Wochenberichte)
   ============================================================ */
$klasse_stmt = $conn->prepare("
    SELECT klasse_id 
    FROM users 
    WHERE id = ? 
    LIMIT 1
");
$klasse_stmt->bind_param("i", $user_id);
$klasse_stmt->execute();
$klasse_res = $klasse_stmt->get_result()->fetch_assoc();
$klasse_stmt->close();

$klasse_id = isset($klasse_res['klasse_id']) ? (int)$klasse_res['klasse_id'] : 0;

/* ============================================================
   RESTORE FUNKTION
   ============================================================ */
$restore_id = isset($_POST['restore_id']) ? (int)$_POST['restore_id'] : 0;

if ($restore_id > 0) {

    // Aufgabe aus dem Archiv zurückholen
    $restore = $conn->prepare("
        UPDATE aufgaben 
        SET status = 'offen',
            archiviert = 0,
            archiviert_am = NULL,
            geaendert_am = NOW()
        WHERE id = ?
          AND azubi_id = ?
          AND archiviert = 1
    ");

    if (!$restore) {
        json_response(['success' => false, 'msg' => 'Datenbankfehler (Prepare Restore)'], 500);
    }

    $restore->bind_param("ii", $restore_id, $user_id);
    $restore->execute();

    // Aufgabe existiert nicht oder gehört nicht dem User
    if ($restore->affected_rows === 0) {
        $restore->close();
        json_response(['success'=>false, 'msg'=>'Keine passende archivierte Aufgabe gefunden.'], 404);
    }
    $restore->close();

    // Wiederhergestellte Aufgabe laden
    $select = $conn->prepare("
        SELECT id, titel, beschreibung, kategorie, status, stunden, stunden_typ, week_id 
        FROM aufgaben 
        WHERE id = ? AND azubi_id = ?
        LIMIT 1
    ");

    if (!$select) {
        json_response(['success'=>false, 'msg'=>'Datenbankfehler (Select Restore)'], 500);
    }

    $select->bind_param("ii", $restore_id, $user_id);
    $select->execute();
    $data = $select->get_result()->fetch_assoc();
    $select->close();

    if (!$data) {
        json_response(['success'=>false, 'msg'=>'Aufgabe nach Restore nicht gefunden.'], 500);
    }

    // Erfolgreich wiederhergestellt
    json_response([
        'success'       => true,
        'id'            => $data['id'],
        'titel'         => $data['titel'],
        'beschreibung'  => $data['beschreibung'],
        'kategorie'     => $data['kategorie'],
        'status'        => $data['status'],
        'status_label'  => ucfirst(str_replace('_',' ',$data['status'])),
        'stunden'       => $data['stunden'],
        'stunden_typ'   => $data['stunden_typ'],
        'archiviert'    => 0,
        'archiviert_am' => null,
        'week_id'       => $data['week_id']
    ]);
}

/* ============================================================
   NEUE AUFGABE ANLEGEN
   ============================================================ */

// Basiswerte einlesen & bereinigen
$titel         = trim($_POST['titel'] ?? '');
$beschreibung  = trim($_POST['beschreibung'] ?? '');
$kategorie     = $_POST['kategorie'] ?? '';
$status        = $_POST['status'] ?? 'offen';

// deutsche Kommas erlauben → Punkt konvertieren
$stundenInput  = str_replace(',', '.', $_POST['stunden'] ?? '');
$stunden       = $stundenInput === '' ? 0 : round((float)$stundenInput, 2);
$stunden_typ   = $_POST['stunden_typ'] ?? null;

// Pflichtfelder prüfen
if ($titel === '' || $kategorie === '') {
    json_response(['success'=>false, 'msg'=>'Titel und Kategorie müssen ausgefüllt werden'], 400);
}

// Zusätzliche Limits: Titel 30 Zeichen, Beschreibung 150 Zeichen
if (mb_strlen($titel) > 30) {
    json_response(['success' => false, 'msg' => 'Titel maximal 30 Zeichen.'], 400);
}

if ($beschreibung !== '' && mb_strlen($beschreibung) > 150) {
    json_response(['success' => false, 'msg' => 'Beschreibung maximal 150 Zeichen.'], 400);
}
// Max. Länge des Titels (schlägt sonst als DB-Fehler fehl)
$maxTitleLength = getColumnMaxLength($conn, 'aufgaben', 'titel');
if ($maxTitleLength !== null && mb_strlen($titel) > $maxTitleLength) {
    json_response([
        'success' => false,
        'msg'     => "Titel ist zu lang (max. {$maxTitleLength} Zeichen)."
    ], 400);
}


/* ------------------------------------------------------------
   Regel: Unterweisung → Pflichtstunden + Pflicht-Stundentyp
   ------------------------------------------------------------ */
if ($kategorie === 'unterweisung') {

    if ($stunden <= 0) {
        json_response(['success'=>false, 'msg'=>'Bitte Stunden für die Schulung angeben.'], 400);
    }

    if (!in_array($stunden_typ, ['betrieb','berufsschule'], true)) {
        json_response(['success'=>false, 'msg'=>'Bitte wähle, ob die Schulung Betrieb oder Berufsschule belastet.'], 400);
    }

} else {
    // Normale Aufgaben → Stunden optional, aber nie negativ
    $stunden = max(0, $stunden);

    // Automatische Zuordnung für normale Aufgaben
    $stunden_typ = ($kategorie === 'berufsschule') ? 'berufsschule' : 'betrieb';
}

/* ============================================================
   Wochenbericht bei Aufgaben, die bereits in Bearbeitung/Fertig sind
   ============================================================ */
$wochenbericht_id = 0;
$week_id = null;

if (in_array($status, ['in_bearbeitung','fertig'], true)) {

    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end   = date('Y-m-d', strtotime('friday this week'));
    $week_id    = date('o-W', strtotime($week_start));

    // Prüfen, ob Wochenbericht existiert
    $stmt = $conn->prepare("
        SELECT id 
        FROM wochenberichte 
        WHERE azubi_id = ?
          AND startdatum = ?
          AND enddatum = ?
        LIMIT 1
    ");
    $stmt->bind_param("iss", $user_id, $week_start, $week_end);
    $stmt->execute();
    $wb = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $wochenbericht_id = $wb['id'] ?? 0;

    // Falls kein Wochenbericht → neu anlegen
    if ($wochenbericht_id == 0) {
        $insert = $conn->prepare("
            INSERT INTO wochenberichte 
            (azubi_id, klasse_id, startdatum, enddatum, erstellt_am, abgegeben, signiert)
            VALUES (?, ?, ?, ?, NOW(), 'N', 'N')
        ");
        $insert->bind_param("iiss", $user_id, $klasse_id, $week_start, $week_end);
        $insert->execute();
        $wochenbericht_id = $insert->insert_id;
        $insert->close();
    }
}

// Archivstatus bei neuer Aufgabe
$archiviert    = 0;
$archiviert_am = null;

// Wenn fertig, aber Woche noch nicht gesetzt
if ($status === 'fertig' && !$week_id) {
    $week_id = date('o-W');
}

/* ============================================================
   INSERT: Aufgabe speichern
   ============================================================ */

$stmt = $conn->prepare("
    INSERT INTO aufgaben 
    (azubi_id, titel, beschreibung, status, kategorie, erstellt_am, geaendert_am, 
     wochenbericht_id, stunden, stunden_typ, week_id, archiviert, archiviert_am)
    VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    json_response(['success'=>false, 'msg'=>'Datenbankfehler (Prepare Insert)'], 500);
}

// Bind-Types definieren
$typeString = "issssidssis";

$stmt->bind_param(
    $typeString,
    $user_id,           // i
    $titel,             // s
    $beschreibung,      // s
    $status,            // s
    $kategorie,         // s
    $wochenbericht_id,  // i
    $stunden,           // d
    $stunden_typ,       // s
    $week_id,           // s
    $archiviert,        // i
    $archiviert_am      // s
);

// Ausführen
try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    $stmt->close();

    $msg = 'Aufgabe konnte nicht gespeichert werden.';
    if (
        isset($maxTitleLength) &&
        $maxTitleLength !== null &&
        stripos($e->getMessage(), 'Data too long for column') !== false
    ) {
        $msg = "Titel ist zu lang (max. {$maxTitleLength} Zeichen).";
    }

    json_response(['success' => false, 'msg' => $msg], 400);
}

$newId = $stmt->insert_id;
$stmt->close();

// Erfolgsantwort an das Frontend
json_response([
    'success'       => true,
    'id'            => $newId,
    'titel'         => $titel,
    'beschreibung'  => $beschreibung,
    'kategorie'     => $kategorie,
    'status'        => $status,
    'status_label'  => ucfirst(str_replace('_',' ',$status)),
    'stunden'       => $stunden,
    'stunden_typ'   => $stunden_typ,
    'archiviert'    => $archiviert,
    'archiviert_am' => $archiviert_am
]);

