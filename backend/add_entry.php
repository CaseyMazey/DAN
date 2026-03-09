<?php
// -------------------------------------------------------------
// DATENBANK LADEN
// -------------------------------------------------------------
include 'db.php';

// -------------------------------------------------------------
// EINGABEN AUS FORMULAR HOLEN
// -------------------------------------------------------------
// trim() entfernt unnötige Leerzeichen, cast schützt bei IDs
$azubi_id    = isset($_POST['azubi_id']) ? (int)$_POST['azubi_id'] : 0;
$woche       = trim($_POST['woche'] ?? '');
$beschreibung = trim($_POST['beschreibung'] ?? '');

// -------------------------------------------------------------
// VALIDIERUNG
// -------------------------------------------------------------
if ($azubi_id <= 0 || $woche === '' || $beschreibung === '') {
    echo "Fehler: Bitte alle Felder ausfüllen.";
    exit;
}

// -------------------------------------------------------------
// EINTRAG SICHER SPEICHERN (Prepared Statement)
// -------------------------------------------------------------
// Vorteil:
// - schützt vor SQL-Injection
// - sauberer und robuster
$stmt = $conn->prepare("
    INSERT INTO wochenberichte (azubi_id, woche, beschreibung)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iss", $azubi_id, $woche, $beschreibung);

// -------------------------------------------------------------
// AUSFÜHREN & ERGEBNIS AUSGEBEN
// -------------------------------------------------------------
if ($stmt->execute()) {
    echo "Eintrag erfolgreich gespeichert!";
} else {
    echo "Fehler beim Speichern: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
