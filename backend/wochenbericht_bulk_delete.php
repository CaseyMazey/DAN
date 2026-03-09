<?php
session_start();
require_once __DIR__ . '/db.php';

// -----------------------------
// Zugriff prüfen
// -----------------------------
if (!isset($_SESSION['user_id'], $_SESSION['rolle'])) {
    exit('Nicht eingeloggt.');
}

if ($_SESSION['rolle'] !== 'azubi') {
    exit('Keine Berechtigung.');
}

$userId = (int)$_SESSION['user_id'];

// -----------------------------
// IDs einlesen
// -----------------------------
$rawIds = $_POST['ids'] ?? '';

if ($rawIds === '') {
    exit('Keine IDs übergeben.');
}

$ids = array_values(array_unique(
    array_filter(
        array_map('intval', preg_split('/[,;]+/', $rawIds)),
        fn($v) => $v > 0
    )
));

if (empty($ids)) {
    exit('Keine gültigen IDs.');
}

// -----------------------------
// Nur NICHT signierte Berichte löschen
// -----------------------------
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "
    DELETE FROM wochenberichte
    WHERE id IN ($placeholders)
      AND azubi_id = ?
      AND signiert = 'N'
";

$stmt = $conn->prepare($sql);

// bind_param dynamisch bauen
$types = str_repeat('i', count($ids)) . 'i';
$params = array_merge($ids, [$userId]);

$stmt->bind_param($types, ...$params);
$stmt->execute();

$deleted = $stmt->affected_rows;
$stmt->close();

// -----------------------------
// Zurück zur Übersicht
// -----------------------------
header('Location: wochenbericht.php');
exit;
