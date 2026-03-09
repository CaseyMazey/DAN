<?php
// -------------------------------------------------------------
// SESSION STARTEN & DB LADEN
// -------------------------------------------------------------
session_start();
require_once __DIR__ . '/db.php';

// Immer JSON zurückgeben
header('Content-Type: application/json; charset=utf-8');

// Keine Warnungen oder PHP Notices im Output (würden JSON zerstören)
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * Entfernt ungültige UTF-8 Zeichen – verhindert kaputte JSON-Ausgabe.
 */
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
 * Universelle JSON-Ausgabe-Funktion.
 * Verhindert doppelten Code und sorgt für saubere Antworten.
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);

    // Alle Daten UTF-8 reinigen
    $payload = sanitize_utf8($payload);

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // Fallback, wenn JSON-Encoding schief geht
    if ($json === false) {
        echo '{"success":false,"msg":"Interner Kodierungsfehler"}';
    } else {
        echo $json;
    }
    exit;
}

// -------------------------------------------------------------
// LOGIN-PRÜFUNG
// -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    // 401 = "Unauthorized"
    json_response(['success' => false, 'msg' => 'Nicht eingeloggt'], 401);
}

$user_id = (int)$_SESSION['user_id'];

// -------------------------------------------------------------
// EINGABE VALIDIEREN
// -------------------------------------------------------------
$task_id = (int)($_POST['id'] ?? 0);

if ($task_id <= 0) {
    // 400 = Bad Request
    json_response(['success' => false, 'msg' => 'Ungültige ID'], 400);
}

// -------------------------------------------------------------
// AUFGABE ARCHIVIEREN
// archiviert = 1   → in Archiv verschoben
// archiviert_am     → Zeitstempel
// geaendert_am      → Letzte Änderung aktualisieren
// Nur wenn Aufgabe dem User gehört
// -------------------------------------------------------------
$update = $conn->prepare("
    UPDATE aufgaben
    SET archiviert = 1,
        archiviert_am = NOW(),
        geaendert_am = NOW()
    WHERE id = ? AND azubi_id = ?
");

// Prüfen, ob SQL korrekt vorbereitet wurde
if (!$update) {
    json_response(['success' => false, 'msg' => 'Datenbankfehler (Prepare)'], 500);
}

$update->bind_param('ii', $task_id, $user_id);
$update->execute();

// Wenn keine Zeile geändert wurde → keine Berechtigung / nicht existent / schon archiviert
if ($update->affected_rows === 0) {
    $update->close();
    json_response(['success' => false, 'msg' => 'Aufgabe nicht gefunden oder bereits archiviert'], 404);
}

$update->close();

// -------------------------------------------------------------
// ARCHIVIERTE AUFGABE ERNEUT LADEN
// um dem Frontend z. B. archiviert_am mitzugeben
// -------------------------------------------------------------
$select = $conn->prepare("
    SELECT id, titel, kategorie, status, archiviert_am
    FROM aufgaben
    WHERE id = ? AND azubi_id = ?
    LIMIT 1
");

if (!$select) {
    json_response(['success' => false, 'msg' => 'Datenbankfehler (Select)'], 500);
}

$select->bind_param('ii', $task_id, $user_id);
$select->execute();
$row = $select->get_result()->fetch_assoc();
$select->close();

// Sollte praktisch nie passieren – aber sicherheitshalber:
if (!$row) {
    json_response(['success' => false, 'msg' => 'Aufgabe konnte nach dem Archivieren nicht geladen werden'], 500);
}

// -------------------------------------------------------------
// ERFOLGS-ANTWORT
// -------------------------------------------------------------
json_response([
    'success'       => true,
    'id'            => (int)$row['id'],
    'titel'         => $row['titel'],
    'kategorie'     => $row['kategorie'],
    'status'        => $row['status'],
    'archiviert'    => 1,
    'archiviert_am' => $row['archiviert_am']
]);
