<?php
$host = "127.0.0.1";
$user = "danuser";
$pass = "meinpasswort123";     
$dbname = "dan";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>


