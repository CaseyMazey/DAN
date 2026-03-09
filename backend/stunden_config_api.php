<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Nicht eingeloggt']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    handleConfigUpdate($conn, $userId);
} else {
    echo json_encode(buildSummaryPayload($conn, $userId));
}

function handleConfigUpdate(mysqli $conn, int $userId): void
{
    $maxWoche = parseHours($_POST['max_woche'] ?? '');
    $maxBetrieb = parseHours($_POST['max_betrieb'] ?? '');
    $maxSchule = parseHours($_POST['max_berufsschule'] ?? '');

    if ($maxWoche <= 0 || $maxBetrieb < 0 || $maxSchule < 0) {
        echo json_encode(['success' => false, 'msg' => 'Bitte sinnvolle positive Werte angeben.']);
        return;
    }

    if ($maxBetrieb + $maxSchule > $maxWoche + 0.001) {
        echo json_encode(['success' => false, 'msg' => 'Summe aus Betrieb und Berufsschule darf das Wochenlimit nicht ueberschreiten.']);
        return;
    }

    $hasMaxWoche = ensureStundenConfigUserSchema($conn);

    $ok = false;

    if ($hasMaxWoche) {
        $stmt = $conn->prepare("
            INSERT INTO stunden_config_user (user_id, max_woche, max_betrieb, max_berufsschule)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                max_woche = VALUES(max_woche),
                max_betrieb = VALUES(max_betrieb),
                max_berufsschule = VALUES(max_berufsschule)
        ");
        if ($stmt) {
            $stmt->bind_param("iddd", $userId, $maxWoche, $maxBetrieb, $maxSchule);
            $ok = $stmt->execute();
            $stmt->close();
        }
    } else {
        // Fallback: Schema konnte nicht erweitert werden, zumindest Betrieb/Schule sichern
        $stmt = $conn->prepare("
            INSERT INTO stunden_config_user (user_id, max_betrieb, max_berufsschule)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                max_betrieb = VALUES(max_betrieb),
                max_berufsschule = VALUES(max_berufsschule)
        ");
        if ($stmt) {
            $stmt->bind_param("idd", $userId, $maxBetrieb, $maxSchule);
            $ok = $stmt->execute();
            $stmt->close();
        }
    }

    if ($ok) {
        echo json_encode(buildSummaryPayload($conn, $userId) + ['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Speichern nicht möglich (DB-Fehler).']);
    }
}

function buildSummaryPayload(mysqli $conn, int $userId): array
{
    $config = getStundenConfigUser($conn, $userId);
    $usage = getWeeklyHoursUsage($conn, $userId);

    return [
        'success' => true,
        'config' => $config,
        'usage' => $usage,
    ];
}

function parseHours(string $value): float
{
    $normalized = str_replace(',', '.', trim($value));
    return round((float)$normalized, 2);
}


