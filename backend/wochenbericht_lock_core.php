<?php
/**
 * Kernlogik zum Sperren/Finalisieren von Wochenberichten.
 * Wird von CLI-Skript und Lazy-Trigger (z.B. beim ersten Seitenaufruf) genutzt.
 */

require_once __DIR__ . '/db.php';

/**
 * Fuehrt den Lock fuer alle fälligen Wochen (bis zu 4 Wochen rueckwirkend) aus.
 *
 * @param mysqli $conn
 * @param DateTimeInterface|null $now Referenzzeit, default: jetzt Europe/Berlin
 * @param callable|null $logger Optional: function(string $msg)
 * @return array Zusammenfassung ['created'=>int, 'locked'=>int, 'weeks'=>array]
 */
function runWochenberichtLock(mysqli $conn, ?DateTimeInterface $now = null, ?callable $logger = null): array
{
    $log = function (string $msg) use ($logger): void {
        if ($logger) {
            $logger($msg);
        }
    };

    $tz = new DateTimeZone('Europe/Berlin');
    if (!$now) {
        $now = new DateTimeImmutable('now', $tz);
    } elseif (!$now instanceof DateTimeImmutable) {
        $now = DateTimeImmutable::createFromInterface($now);
    }
    $now = $now->setTimezone($tz);

    // Sicherstellen, dass Snapshot-Tabelle existiert
    $conn->query("
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
    ");
    $conn->query("ALTER TABLE wochenbericht_aufgaben ADD COLUMN IF NOT EXISTS kommentar TEXT");

    // Welche Wochen sind fällig? Bis zu 4 Wochen rueckwirkend, sofern der jeweilige Freitag 18:00 vorbei ist
    $weeks = [];
    for ($i = 0; $i < 4; $i++) {
        $ref = $now->modify("-{$i} week");
        $monday = $ref->modify('monday this week');
        $friday = $ref->modify('friday this week');
        $deadline = $friday->setTime(18, 0);
        if ($now < $deadline) {
            continue;
        }
        $key = $monday->format('o-W');
        $weeks[$key] = [
            'start' => $monday->format('Y-m-d'),
            'end'   => $friday->format('Y-m-d'),
            'key'   => $key,
        ];
    }

    if (empty($weeks)) {
        $log('Keine fälligen Wochen gefunden (vor Freitag 18:00).');
        return ['created' => 0, 'locked' => 0, 'weeks' => []];
    }

    $log('Fellige Wochen: ' . implode(', ', array_keys($weeks)));

    $userRes = $conn->query("SELECT id, klasse_id, vorname, nachname FROM users WHERE rolle = 'azubi'");
    $azubis = $userRes ? $userRes->fetch_all(MYSQLI_ASSOC) : [];

    $createdReports = 0;
    $lockedReports  = 0;

    foreach ($azubis as $user) {
        $azubiId  = (int)$user['id'];
        $klasseId = (int)($user['klasse_id'] ?? 0);
        $name     = trim(($user['vorname'] ?? '') . ' ' . ($user['nachname'] ?? ''));

        $log("Azubi {$name} (ID {$azubiId})");

        if ($klasseId <= 0) {
            $log("  ueberspringe: keine Klasse zugeordnet.");
            continue;
        }

        foreach ($weeks as $week) {
            $weekStart = $week['start'];
            $weekEnd   = $week['end'];
            $weekKey   = $week['key'];

            $stmt = $conn->prepare("
                SELECT id, abgegeben
                FROM wochenberichte
                WHERE azubi_id = ? AND startdatum = ? AND enddatum = ?
                LIMIT 1
            ");
            $stmt->bind_param('iss', $azubiId, $weekStart, $weekEnd);
            $stmt->execute();
            $wbRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($wbRow && ($wbRow['abgegeben'] ?? '') === 'Y') {
                $log("  Woche {$weekKey}: bereits abgegeben, ueberspringe.");
                continue;
            }

            if (!$wbRow) {
                $stmtIns = $conn->prepare("
                    INSERT INTO wochenberichte (azubi_id, klasse_id, startdatum, enddatum, erstellt_am, abgegeben, signiert)
                    VALUES (?, NULLIF(?,0), ?, ?, NOW(), 'N', 'N')
                ");
                $stmtIns->bind_param('iiss', $azubiId, $klasseId, $weekStart, $weekEnd);
                $stmtIns->execute();
                $wbId = $stmtIns->insert_id;
                $stmtIns->close();
                $createdReports++;
                $log("  Woche {$weekKey}: Bericht angelegt (ID {$wbId}).");
            } else {
                $wbId = (int)$wbRow['id'];
                $log("  Woche {$weekKey}: Bericht gefunden (ID {$wbId}).");
            }

            // Aufgaben der Woche laden (noch nicht mit dieser Woche verknuepft)
            $stmtTasks = $conn->prepare("
                SELECT id, titel, beschreibung, kommentar, kategorie, status, stunden, stunden_typ, erstellt_am
                FROM aufgaben
                WHERE azubi_id = ?
                  AND status IN ('in_bearbeitung','fertig')
                  AND (archiviert IS NULL OR archiviert = 0)
                  AND DATE(erstellt_am) BETWEEN ? AND ?
                  AND (week_id IS NULL OR week_id <> ?)
                ORDER BY erstellt_am ASC
            ");
            $stmtTasks->bind_param('isss', $azubiId, $weekStart, $weekEnd, $weekKey);
            $stmtTasks->execute();
            $tasks = $stmtTasks->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtTasks->close();

            if (!empty($tasks)) {
                // Alte Snapshots fuer diese Woche entfernen, um Duplikate zu vermeiden
                $stmtDel = $conn->prepare("DELETE FROM wochenbericht_aufgaben WHERE bericht_id = ?");
                $stmtDel->bind_param('i', $wbId);
                $stmtDel->execute();
                $stmtDel->close();

                $stmtInsTask = $conn->prepare("
                    INSERT INTO wochenbericht_aufgaben
                    (bericht_id, titel, beschreibung, kommentar, kategorie, status, stunden, stunden_typ, erstellt_am)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($tasks as $task) {
                    $titel   = $task['titel'] ?? '';
                    $desc    = $task['beschreibung'] ?? '';
                    $comm    = $task['kommentar'] ?? '';
                    $kat     = $task['kategorie'] ?? '';
                    $status  = $task['status'] ?? '';
                    $stunden = is_numeric($task['stunden'] ?? null) ? (float)$task['stunden'] : null;
                    $stTyp   = $task['stunden_typ'] ?? '';
                    $created = $task['erstellt_am'] ?? null;
                    $stmtInsTask->bind_param("isssssdss", $wbId, $titel, $desc, $comm, $kat, $status, $stunden, $stTyp, $created);
                    $stmtInsTask->execute();
                }
                $stmtInsTask->close();

                // Aufgaben mit Bericht und Woche verknuepfen
                $stmtUpdTask = $conn->prepare("UPDATE aufgaben SET wochenbericht_id = ?, week_id = ? WHERE id = ?");
                foreach ($tasks as $task) {
                    $tid = (int)$task['id'];
                    $stmtUpdTask->bind_param('isi', $wbId, $weekKey, $tid);
                    $stmtUpdTask->execute();
                }
                $stmtUpdTask->close();

                $log("  Woche {$weekKey}: Aufgaben gesichert: " . count($tasks));
            } else {
                $log("  Woche {$weekKey}: Keine neuen Aufgaben gefunden.");
            }

            // Bericht als abgegeben markieren
            $stmtLock = $conn->prepare("
                UPDATE wochenberichte
                SET abgegeben = 'Y',
                    abgegeben_am = COALESCE(abgegeben_am, NOW())
                WHERE id = ?
            ");
            $stmtLock->bind_param('i', $wbId);
            $stmtLock->execute();
            $stmtLock->close();

            $lockedReports++;
            $log("  Woche {$weekKey}: Bericht gesperrt.");
        }
    }

    $log("Fertig. Neue Berichte: {$createdReports}, gesperrt: {$lockedReports}");

    return ['created' => $createdReports, 'locked' => $lockedReports, 'weeks' => array_values($weeks)];
}
