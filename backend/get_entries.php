<?php
include('db.php');

// Users auslesen
$result_users = $conn->query("SELECT * FROM users");
echo "<h2>Users</h2>";
if ($result_users->num_rows > 0) {
    echo "<ul>";
    while ($row = $result_users->fetch_assoc()) {
        echo "<li>ID: ".$row['id']." | Name: ".$row['vorname']." ".$row['nachname']." | Email: ".$row['email']." | Rolle: ".$row['rolle']." | Klasse ID: ".$row['klasse_id']."</li>";
    }
    echo "</ul>";
} else {
    echo "Keine User gefunden.";
}

// Klassen auslesen
$result_klassen = $conn->query("SELECT * FROM klassen");
echo "<h2>Klassen</h2>";
if ($result_klassen->num_rows > 0) {
    echo "<ul>";
    while ($row = $result_klassen->fetch_assoc()) {
        echo "<li>ID: ".$row['id']." | Name: ".$row['name']." | Jahrgang: ".$row['jahrgang']." | Ausbilder ID: ".$row['ausbilder_id']." | Erstellt am: ".$row['erstellt_am']."</li>";
    }
    echo "</ul>";
} else {
    echo "Keine Klassen gefunden.";
}

$conn->close();
?>


