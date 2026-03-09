<?php
session_start();
include('C:\xampp\htdocs\DAN\backend\db.php');

$email = $_POST['email'] ?? '';
$passwort = $_POST['passwort'] ?? '';

if (empty($email) || empty($passwort)) {
    die("Bitte alle Felder ausfüllen!");
}

$stmt = $conn->prepare("SELECT id, vorname, nachname, passwort_hash, rolle FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($passwort, $user['passwort_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['vorname'] . " " . $user['nachname'];
        $_SESSION['rolle'] = $user['rolle'];

        $targetDashboard = 'dashboard.php';
        if ($user['rolle'] === 'ausbilder') {
            $targetDashboard = 'dashboard_ausbilder.php';
        } elseif ($user['rolle'] === 'azubi') {
            $targetDashboard = 'dashboard_azubi.php';
        }

        header("Location: {$targetDashboard}");
        exit;
    } else {
        echo "âŒ Falsches Passwort.";
    }
} else {
    echo "âŒ Benutzer nicht gefunden.";
}

$conn->close();
?>


