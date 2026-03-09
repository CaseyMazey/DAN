<?php
include('db.php');

// Beispiel: Ausbilder-ID (später über Login)
$ausbilder_id = 2;

// Alle Klassen des Ausbilders abrufen
$klassen_result = $conn->query("SELECT * FROM klassen WHERE ausbilder_id = $ausbilder_id");

echo "<h2>Meine Klassen</h2>";
if ($klassen_result->num_rows > 0) {
    while ($klasse = $klassen_result->fetch_assoc()) {
        echo "<h3>Klasse: ".$klasse['name']." (Jahrgang ".$klasse['jahrgang'].")</h3>";

        // Alle Wochenberichte dieser Klasse abrufen
        $berichte_result = $conn->query("SELECT wb.*, u.vorname, u.nachname FROM wochenberichte wb
                                         JOIN users u ON wb.azubi_id = u.id
                                         WHERE wb.klasse_id = ".$klasse['id']."
                                         ORDER BY wb.startdatum DESC");

        if ($berichte_result->num_rows > 0) {
            echo "<ul>";
            while ($bericht = $berichte_result->fetch_assoc()) {
                $status = $bericht['signiert'] ? "âœ… Signiert" : ($bericht['abgegeben'] ? "Abgegeben" : "In Bearbeitung");
                echo "<li>";
                echo $bericht['vorname']." ".$bericht['nachname']." | Woche: ".$bericht['startdatum']." bis ".$bericht['enddatum']." | Status: $status ";
                echo "<a href='bericht_details.php?id=".$bericht['id']."'>Details</a> ";
                
                // Signieren-Button, falls noch nicht signiert
                if (!$bericht['signiert']) {
                    echo "<form method='post' style='display:inline'>
                            <input type='hidden' name='bericht_id' value='".$bericht['id']."'>
                            <button type='submit' name='signieren'>Signieren</button>
                          </form>";
                }
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "Keine Wochenberichte vorhanden.";
        }
    }
} else {
    echo "Keine Klassen gefunden.";
}

// Signieren-Logik
if (isset($_POST['signieren'])) {
    $bericht_id = intval($_POST['bericht_id']);
    $conn->query("UPDATE wochenberichte SET signiert = 1, signiert_am = NOW() WHERE id = $bericht_id");
    echo "<script>location.href='".$_SERVER['PHP_SELF']."';</script>";
}

$conn->close();
?>


