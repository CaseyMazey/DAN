<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (($_SESSION['rolle'] ?? '') !== 'ausbilder') {
    header('Location: dashboard.php');
    exit;
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['name'] ?? '';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$trainerStats = ['klassen' => 0, 'berichte' => 0, 'offen' => 0];

$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM klassen WHERE ausbilder_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$trainerStats['klassen'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare('
    SELECT COUNT(*) AS cnt
    FROM wochenberichte wb
    JOIN klassen k ON wb.klasse_id = k.id
    WHERE k.ausbilder_id = ?
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$trainerStats['berichte'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM wochenberichte wb
    JOIN klassen k ON wb.klasse_id = k.id
    WHERE k.ausbilder_id = ? AND wb.signiert = 'N'
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$trainerStats['offen'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

ob_start();
?>

<div class="d-flex flex-column gap-2 mb-4">
    <h1 class="mb-0">Willkommen, <?= $h($user_name) ?>!</h1>
    <div class="text-muted">Uebersicht deiner Klassen und Berichte.</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Meine Klassen</h5>
                <p class="mb-3">Anzahl Klassen: <?= (int)$trainerStats['klassen'] ?></p>
                <a href="klassen.php" class="btn btn-primary">Klassen öffnen</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Wochenberichte</h5>
                <p class="mb-1">Gesamt: <?= (int)$trainerStats['berichte'] ?></p>
                <p class="mb-3">Nicht signiert: <?= (int)$trainerStats['offen'] ?></p>
                <a href="wochenbericht_ausbilder.php" class="btn btn-primary">Berichte ansehen</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Account</h5>
                <p class="mb-3 text-muted">Profil & Einstellungen</p>
                <a href="account.php" class="btn btn-primary">Zum Account</a>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
$current_page = 'dashboard.php';
include __DIR__ . '/layout.php';


