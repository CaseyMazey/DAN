<?php

function loadWochenberichtTasks(mysqli $conn, array $report): array
{
    $isCurrent =
        ($report['abgegeben'] ?? 'N') === 'N'
        && ($report['signiert'] ?? 'N') === 'N';

    if ($isCurrent) {
        $stmt = $conn->prepare("
            SELECT titel, beschreibung, kommentar, kategorie,
                   status, stunden, stunden_typ, erstellt_am
            FROM aufgaben
            WHERE azubi_id = ?
              AND (archiviert IS NULL OR archiviert = 0)
              AND status IN ('in_bearbeitung','fertig')
            ORDER BY erstellt_am ASC
        ");
        $stmt->bind_param("i", $report['azubi_id']);
    } else {
        $stmt = $conn->prepare("
            SELECT titel, beschreibung, kommentar, kategorie,
                   status, stunden, stunden_typ, erstellt_am
            FROM wochenbericht_aufgaben
            WHERE bericht_id = ?
            ORDER BY erstellt_am ASC, id ASC
        ");
        $stmt->bind_param("i", $report['id']);
    }

    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $tasks;
}
