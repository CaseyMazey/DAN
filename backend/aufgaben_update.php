<?php
session_start();
include('db.php');

/* -------------------------------------------------------------
   JSON-AUSGABE EINRICHTEN
   -------------------------------------------------------------
   Wir geben immer JSON zurück, damit AJAX zuverlässig arbeiten kann.
------------------------------------------------------------- */
header('Content-Type: application/json');

/* -------------------------------------------------------------
   LOGIN-PRÜFUNG
------------------------------------------------------------- */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'msg'=>'Nicht eingeloggt']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* -------------------------------------------------------------
   KLASSEN-ID FÜR AUTOMATISCHE WOCHENBERICHTE LADEN
   -------------------------------------------------------------
   Diese Info wird benötigt, falls beim Statuswechsel ein neuer 
   Wochenbericht angelegt werden muss.
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
   POST-DATEN VALIDIEREN
------------------------------------------------------------- */
$task_id    = (int)($_POST['id'] ?? 0);
$new_status = $_POST['status'] ?? '';

// Status darf nur diese drei Werte haben
$allowed_status = ['offen','in_bearbeitung','fertig'];

if ($task_id <= 0 || !in_array($new_status, $allowed_status, true)) {
    echo json_encode(['success'=>false, 'msg'=>'Ungültige Daten']);
    exit;
}

/* -------------------------------------------------------------
   STANDARDWERTE FÜR STATUSVERARBEITUNG
------------------------------------------------------------- */
$wochenbericht_id = 0;   // zu welchem Wochenbericht gehört die Aufgabe?
$week_id          = null; // Format 2025-06
$archiviert       = 0;    // Statuswechsel archiviert nichts!
$archiviert_am    = null;

/* -------------------------------------------------------------
   AUTOMATISCHE WOCHENBERICHT-ZUORDNUNG
   Nur bei "in Bearbeitung" oder "fertig"
------------------------------------------------------------- */
if (in_array($new_status, ['in_bearbeitung','fertig'], true)) {

    // Start/Ende der aktuellen Woche
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end   = date('Y-m-d', strtotime('friday this week'));

    // ISO-Week-ID wie 2025-07
    $week_id = date('o-W', strtotime($week_start));

    // Prüfen, ob Wochenbericht existiert
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

    // Falls es keinen Wochenbericht gibt → neuen erstellen
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
   STATUSERKLÄRUNG
-------------------------------------------------------------
   Wenn eine Aufgabe fertig ist:
      → Woche wird gesetzt
      → aber niemals archiviert

   Wenn wieder "offen":
      → Diese Aufgabe ist keiner Woche mehr zugeordnet.
------------------------------------------------------------- */

// Status: fertig → nie archivieren
if ($new_status === 'fertig') {

    $archiviert = 0;
    $archiviert_am = null;

    // Falls Week-ID aus irgendeinem Grund leer wäre
    if (!$week_id) {
        $week_id = date('o-W');
    }

// Status: offen → Aufgabe aus Wochenbericht entfernen
} elseif ($new_status === 'offen') {

    $wochenbericht_id = 0;
    $week_id = null;
}

/* -------------------------------------------------------------
   DATENBANK-UPDATE
------------------------------------------------------------- */
$stmt = $conn->prepare("
    UPDATE aufgaben
    SET 
        status          = ?, 
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
    'sisisii',
    $new_status,
    $wochenbericht_id,
    $week_id,
    $archiviert,
    $archiviert_am,
    $task_id,
    $user_id
);

/* -------------------------------------------------------------
   JSON-ANTWORT FÜR FRONTEND
------------------------------------------------------------- */
try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    $stmt->close();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg'     => 'Fehler beim Aktualisieren der Aufgabe'
    ]);
    exit;
}

$stmt->close();

echo json_encode([
    'success'          => true,
    'id'               => $task_id,
    'status'           => $new_status,
    'wochenbericht_id' => $wochenbericht_id,
    'archiviert'       => $archiviert,
    'archiviert_am'    => $archiviert_am,
    'week_id'          => $week_id
]);

?>
