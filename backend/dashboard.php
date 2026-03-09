<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$rolle = $_SESSION['rolle'] ?? '';
if ($rolle === 'ausbilder') {
    header('Location: dashboard_ausbilder.php');
    exit;
}

if ($rolle === 'azubi') {
    header('Location: dashboard_azubi.php');
    exit;
}

header('Location: ../index.php');
exit;