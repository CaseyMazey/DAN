<?php
session_start();

// Alle Session-Variablen löschen
$_SESSION = [];

// Session zerstören
session_destroy();

// Zur Login-Seite weiterleiten
header("Location: /DAN/index.php");
exit;
?>


