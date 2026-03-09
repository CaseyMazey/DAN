<?php
/**
 * Hilfsfunktionen rund um Stunden-Config und Auswertungen.
 */

/**
 * Liefert das Stunden-Limit aus der stunden_config Tabelle oder Default-Werte.
 */
function getStundenConfig(mysqli $conn): array
{
    $defaults = [
        'max_woche' => 42.0,
        'max_betrieb' => 32.0,
        'max_berufsschule' => 10.0,
    ];

    $result = $conn->query("SELECT max_woche, max_betrieb, max_berufsschule FROM stunden_config ORDER BY id ASC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return [
            'max_woche' => (float)$row['max_woche'],
            'max_betrieb' => (float)$row['max_betrieb'],
            'max_berufsschule' => (float)$row['max_berufsschule'],
        ];
    }

    return $defaults;
}

/**
 * Stellt sicher, dass stunden_config_user vorhanden ist und die benoetigten Spalten (inkl. max_woche) hat.
 * Gibt true zurueck, sobald eine max_woche Spalte vorhanden ist (ggf. nach Migration).
 */
function ensureStundenConfigUserSchema(mysqli $conn): bool
{
    // Falls die Tabelle noch nicht existiert, neu anlegen
    $conn->query("
        CREATE TABLE IF NOT EXISTS stunden_config_user (
            user_id INT PRIMARY KEY,
            max_woche DECIMAL(6,2) NOT NULL DEFAULT 42.00,
            max_betrieb DECIMAL(6,2) NOT NULL DEFAULT 32.00,
            max_berufsschule DECIMAL(6,2) NOT NULL DEFAULT 10.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $hasMaxWoche = false;
    if ($res = $conn->query("SHOW COLUMNS FROM stunden_config_user LIKE 'max_woche'")) {
        $hasMaxWoche = $res->num_rows > 0;
        $res->free();
    }

    if (!$hasMaxWoche) {
        // Altes Schema migrieren, falls die Spalte noch fehlt.
        $conn->query("
            ALTER TABLE stunden_config_user
            ADD COLUMN max_woche DECIMAL(6,2) NOT NULL DEFAULT 42.00
            AFTER user_id
        ");

        if ($res = $conn->query("SHOW COLUMNS FROM stunden_config_user LIKE 'max_woche'")) {
            $hasMaxWoche = $res->num_rows > 0;
            $res->free();
        }

        if ($hasMaxWoche) {
            // Existierende Datensaetze mit einem sinnvollen Startwert fuellen
            $conn->query("
                UPDATE stunden_config_user
                SET max_woche = COALESCE(max_betrieb, 0) + COALESCE(max_berufsschule, 0)
            ");
        }
    }

    return $hasMaxWoche;
}

/**
 * User-spezifische Stundenkonfiguration (fallback auf Defaults).
 */
function getStundenConfigUser(mysqli $conn, int $userId): array
{
    $defaults = [
        'max_woche' => 42.0,
        'max_betrieb' => 32.0,
        'max_berufsschule' => 10.0,
    ];

    $hasMaxWoche = ensureStundenConfigUserSchema($conn);

    $selectSql = $hasMaxWoche
        ? "
            SELECT max_woche, max_betrieb, max_berufsschule
            FROM stunden_config_user
            WHERE user_id = ?
            LIMIT 1
        "
        : "
            SELECT 
                COALESCE(max_betrieb, 0) + COALESCE(max_berufsschule, 0) AS max_woche,
                max_betrieb,
                max_berufsschule
            FROM stunden_config_user
            WHERE user_id = ?
            LIMIT 1
        ";

    $stmt = $conn->prepare($selectSql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return [
                'max_woche' => (float)$row['max_woche'],
                'max_betrieb' => (float)$row['max_betrieb'],
                'max_berufsschule' => (float)$row['max_berufsschule'],
            ];
        }
        $stmt->close();
    }

    // Falls noch kein Datensatz existiert: Defaults persistieren, damit spätere Updates greifen
    $stmtIns = $conn->prepare("
        INSERT INTO stunden_config_user (user_id, max_woche, max_betrieb, max_berufsschule)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            max_woche = VALUES(max_woche),
            max_betrieb = VALUES(max_betrieb),
            max_berufsschule = VALUES(max_berufsschule)
    ");
    if ($stmtIns) {
        $stmtIns->bind_param("iddd", $userId, $defaults['max_woche'], $defaults['max_betrieb'], $defaults['max_berufsschule']);
        $stmtIns->execute();
        $stmtIns->close();
    }

    return $defaults;
}

/**
 * Gibt das Datum (Y-m-d) fuer Wochenbeginn (Montag) und Ende (Sonntag) Zurück.
 */
function getCurrentWeekRange(): array
{
    $start = date('Y-m-d', strtotime('monday this week'));
    $end = date('Y-m-d', strtotime($start . ' +6 days'));
    return [$start, $end];
}

/**
 * Summiert die bereits verbuchten Stunden dieser Woche (Status in Arbeit oder erledigt).
 */
function getWeeklyHoursUsage(mysqli $conn, int $userId): array
{
    [$weekStart, $weekEnd] = getCurrentWeekRange();
    $usage = [
        'betrieb' => 0.0,
        'berufsschule' => 0.0,
    ];

    $stmt = $conn->prepare("
        SELECT 
            CASE
                WHEN kategorie = 'unterweisung' THEN COALESCE(stunden_typ, 'betrieb')
                WHEN kategorie = 'berufsschule' THEN 'berufsschule'
                ELSE 'betrieb'
            END AS konto,
            SUM(stunden) AS summe
        FROM aufgaben
        WHERE azubi_id = ?
          AND stunden IS NOT NULL AND stunden > 0
          AND status IN ('in_bearbeitung','fertig')
          AND DATE(geaendert_am) BETWEEN ? AND ?
        GROUP BY konto
    ");

    if ($stmt) {
        $stmt->bind_param("iss", $userId, $weekStart, $weekEnd);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $key = $row['konto'] === 'berufsschule' ? 'berufsschule' : 'betrieb';
            $usage[$key] = (float)$row['summe'];
        }
        $stmt->close();
    }

    return [
        'betrieb' => $usage['betrieb'],
        'berufsschule' => $usage['berufsschule'],
        'gesamt' => $usage['betrieb'] + $usage['berufsschule'],
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
    ];
}


