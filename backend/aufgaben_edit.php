<?php
session_start();
require_once __DIR__ . '/db.php';

/* -------------------------------------------------------------
   JSON-AUSGABE KONFIGURIEREN
   -------------------------------------------------------------
   - Keine PHP-Warnings/Notices, damit JSON nie kaputt ist
   - UTF-8 setzen
------------------------------------------------------------- */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * Entfernt ungültige UTF-8 Zeichen aus Strings/Arrays
 * Verhindert JSON-Kodierungsfehler.
 */
function sanitize_utf8($value)
{
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
 * Einheitliche JSON-Antwort
 * - setzt HTTP-Code
 * - bereinigt UTF-8
 * - beendet Script IMMER sauber
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    $payload = sanitize_utf8($payload);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // Fallback falls Encoding scheitert
    echo $json !== false
        ? $json
        : '{"success":false,"msg":"Interner Kodierungsfehler"}';

    exit;
}

/**
 * Liefert die maximale Zeichenzahl einer Spalte (z. B. VARCHAR-Länge).
 * Gibt null zurück, wenn die Information nicht ermittelt werden kann.
 */
function getColumnMaxLength(mysqli $conn, string $table, string $column): ?int
{
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

/* -------------------------------------------------------------
   LOGIN-PRÜFUNG
------------------------------------------------------------- */
if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'msg' => 'Nicht eingeloggt'], 401);
}

$user_id = (int)$_SESSION['user_id'];

/* -------------------------------------------------------------
   BENÃ–TIGT: Klasse des Users (für Wochenberichte)
------------------------------------------------------------- */
$klasse_stmt = $conn->prepare("
    SELECT klasse_id 
    FROM users 
    WHERE id = ? 
    LIMIT 1
");
$klasse_stmt->bind_param('i', $user_id);
$klasse_stmt->execute();
$klasse_res = $klasse_stmt->get_result()->fetch_assoc();
$klasse_stmt->close();

$klasse_id = isset($klasse_res['klasse_id']) ? (int)$klasse_res['klasse_id'] : 0;

/* -------------------------------------------------------------
   POST-DATEN EINLESEN
------------------------------------------------------------- */
$task_id       = (int)($_POST['id'] ?? 0);
$titel         = trim($_POST['titel'] ?? '');
$beschreibung  = trim($_POST['beschreibung'] ?? '');
$kategorie     = $_POST['kategorie'] ?? '';
$status        = $_POST['status'] ?? 'offen';

// Komma â†’ Punkt erlauben
$stundenInput  = str_replace(',', '.', $_POST['stunden'] ?? '');
$stunden       = ($stundenInput === '') ? 0 : round((float)$stundenInput, 2);
$stunden_typ   = $_POST['stunden_typ'] ?? null;
/* -------------------------------------------------------------
   VALIDIERUNG DER EINGABEN
------------------------------------------------------------- */
if ($task_id <= 0) {
    json_response(['success' => false, 'msg' => 'Ungueltige Aufgabe'], 400);
}

if ($titel === '' || $kategorie === '') {
    json_response(['success' => false, 'msg' => 'Titel und Kategorie muessen ausgefuellt werden'], 400);
}

// Limits: Titel 30 Zeichen, Beschreibung 150 Zeichen
if (mb_strlen($titel) > 30) {
    json_response(['success' => false, 'msg' => 'Titel maximal 30 Zeichen.'], 400);
}

if ($beschreibung !== '' && mb_strlen($beschreibung) > 150) {
    json_response(['success' => false, 'msg' => 'Beschreibung maximal 150 Zeichen.'], 400);
}

// Max. Laenge des Titels aus DB (falls enger definiert)
$maxTitleLength = getColumnMaxLength($conn, 'aufgaben', 'titel');
if ($maxTitleLength !== null && mb_strlen($titel) > $maxTitleLength) {
    json_response([
        'success' => false,
        'msg'     => "Titel ist zu lang (max. {$maxTitleLength} Zeichen)."
    ], 400);
}


/* -------------------------------------------------------------
   UNTERWEISUNGEN HABEN BESONDERE REGELN
------------------------------------------------------------- */
if ($kategorie === 'unterweisung') {

    if ($stunden <= 0) {
        json_response(['success' => false, 'msg' => 'Bitte Stunden für die Schulung angeben.'], 400);
    }

    if (!in_array($stunden_typ, ['betrieb','berufsschule'], true)) {
        json_response(['success' => false, 'msg' => 'Bitte auswählen, ob Schulung Betrieb oder Berufsschule belastet.'], 400);
    }

} else {
    // Normale Aufgaben: Stunden optional, nie negativ
    $stunden = max(0, $stunden);

    // Stundentyp bei normalen Aufgaben automatisch setzen
    $stunden_typ = ($kategorie === 'berufsschule') ? 'berufsschule' : 'betrieb';
}

/* -------------------------------------------------------------
   STANDARDWERTE
------------------------------------------------------------- */
$wochenbericht_id = 0;
$week_id          = null;
$archiviert       = 0;
$archiviert_am    = null;

/* -------------------------------------------------------------
   AUTOMATISCHE WOCHENBERICHT-ZUORDNUNG
   Nur wenn Aufgabe "in Bearbeitung" oder "fertig" ist
------------------------------------------------------------- */
if (in_array($status, ['in_bearbeitung','fertig'], true)) {

    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end   = date('Y-m-d', strtotime('friday this week'));
    $week_id    = date('o-W', strtotime($week_start));

    // prüfen ob Wochenbericht bereits existiert
    $stmt = $conn->prepare("
        SELECT id 
        FROM wochenberichte
        WHERE azubi_id = ?
          AND startdatum = ?
          AND enddatum = ?
        LIMIT 1
    ");
    $stmt->bind_param('iss', $user_id, $week_start, $week_end);
    $stmt->execute();
    $wb = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $wochenbericht_id = $wb['id'] ?? 0;

    // sonst neu anlegen
    if ($wochenbericht_id == 0) {

        $insert = $conn->prepare("
            INSERT INTO wochenberichte 
                (azubi_id, klasse_id, startdatum, enddatum, erstellt_am, abgegeben, signiert)
            VALUES 
                (?, ?, ?, ?, NOW(), 'N', 'N')
        ");
        $insert->bind_param('iiss', $user_id, $klasse_id, $week_start, $week_end);
        $insert->execute();
        $wochenbericht_id = $insert->insert_id;
        $insert->close();
    }
}

/* -------------------------------------------------------------
   FALL: Aufgabe ist "fertig"
   - wird NICHT archiviert
   - benötigt eine week_id
------------------------------------------------------------- */
if ($status === 'fertig') {
    $archiviert = 0;
    $archiviert_am = null;

    if (!$week_id) {
        $week_id = date('o-W');
    }

/* -------------------------------------------------------------
   FALL: Aufgabe ist "offen"
   - KEIN Wochenbericht
------------------------------------------------------------- */
} elseif ($status === 'offen') {
    $wochenbericht_id = 0;
    $week_id = null;
}

/* -------------------------------------------------------------
   UPDATE AUSFÃœHREN
------------------------------------------------------------- */
$stmt = $conn->prepare("
    UPDATE aufgaben
    SET 
        titel           = ?,
        beschreibung    = ?,
        status          = ?,
        kategorie       = ?,
        stunden         = ?,
        stunden_typ     = ?,
        geaendert_am    = NOW(),
        wochenbericht_id= ?,
        week_id         = ?,
        archiviert      = ?,
        archiviert_am   = ?
    WHERE 
        id = ? 
        AND azubi_id = ?
");

$stmt->bind_param(
    'ssssdsisisii',
    $titel,
    $beschreibung,
    $status,
    $kategorie,
    $stunden,
    $stunden_typ,
    $wochenbericht_id,
    $week_id,
    $archiviert,
    $archiviert_am,
    $task_id,
    $user_id
);

/* -------------------------------------------------------------
   ERFOLG / FEHLER
------------------------------------------------------------- */
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

$stmt->close();

json_response([
    'success'          => true,
    'id'               => $task_id,
    'titel'            => $titel,
    'beschreibung'     => $beschreibung,
    'kategorie'        => $kategorie,
    'status'           => $status,
    'stunden'          => $stunden,
    'stunden_typ'      => $stunden_typ,
    'wochenbericht_id' => $wochenbericht_id,
    'archiviert'       => $archiviert,
    'archiviert_am'    => $archiviert_am,
    'week_id'          => $week_id
]);
