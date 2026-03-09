<?php
ob_clean(); 
// Entfernt evtl. vorher ausgestoßenen Output (z. B. BOM, Leerzeilen), 
// damit das JSON später nicht kaputt wird.

session_start();
// Session starten (benötigt für user_id)

require_once __DIR__ . '/db.php';
// Datenbankverbindung laden

// Immer reines JSON zurückgeben
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

// Fehler nicht im Browser anzeigen (verhindert kaputtes JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// -------------------------------------------------------------
// Prüfen, ob Benutzer eingeloggt ist
// -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Nicht eingeloggt']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$task_id = (int)($_POST['id'] ?? 0);

// -------------------------------------------------------------
// ID prüfen
// -------------------------------------------------------------
if ($task_id <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Ungültige ID']);
    exit;
}

// -------------------------------------------------------------
// SQL: Aufgabe löschen (nur wenn sie dem User gehört)
// -------------------------------------------------------------
$stmt = $conn->prepare("
    DELETE FROM aufgaben
    WHERE id = ? AND azubi_id = ?
");
$stmt->bind_param("ii", $task_id, $user_id);

// -------------------------------------------------------------
// Löschvorgang ausführen
// -------------------------------------------------------------
if (!$stmt->execute()) {
    // Fehler beim SQL-Befehl — aber sauber als JSON zurückgeben
    echo json_encode(['success' => false, 'msg' => 'SQL-Fehler beim Löschen']);
    exit;
}

// -------------------------------------------------------------
// Prüfen, ob eine Aufgabe gelöscht wurde
// (0 bedeutet: nicht vorhanden oder gehört anderem User)
// -------------------------------------------------------------
if ($stmt->affected_rows === 0) {
    echo json_encode(['success' => false, 'msg' => 'Aufgabe nicht gefunden']);
    exit;
}

// -------------------------------------------------------------
// Erfolg zurückgeben
// -------------------------------------------------------------
echo json_encode(['success' => true]);
exit;
