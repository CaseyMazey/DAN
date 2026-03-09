<?php
session_start();
include('C:\xampp\htdocs\DAN\backend\db.php');

$mode = isset($_GET['register']) ? 'register' : 'login';
$invite_token = trim($_GET['token'] ?? '');
$invite_data = null;
$invite_error = '';
$message = '';

// Erfolgsmeldung nach Weiterleitung von der Registrierung
if (isset($_GET['registered'])) {
    $message = "<div class='alert alert-success'>Registrierung erfolgreich! Du kannst dich jetzt einloggen.</div>";
}

/**
 * Liefert die maximale Zeichenlänge einer Spalte (VARCHAR).
 * Gibt null zurück, wenn die Info nicht ermittelt werden kann.
 */
function getColumnMaxLength(mysqli $conn, string $table, string $column): ?int {
    $stmt = $conn->prepare("
        SELECT CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    if (!$stmt) return null;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $stmt->bind_result($len);
    $stmt->fetch();
    $stmt->close();
    return $len !== null ? (int)$len : null;
}

if ($mode === 'register' && $invite_token !== '') {
    $stmt = $conn->prepare("SELECT * FROM einladungen WHERE token = ? AND angenommen = 'N' LIMIT 1");
    $stmt->bind_param("s", $invite_token);
    $stmt->execute();
    $res = $stmt->get_result();
    $inv = $res->fetch_assoc();
    $stmt->close();

    if ($inv) {
        $created = strtotime($inv['erstellt_am']);
        if ($created !== false && $created >= strtotime('-7 days')) {
            $invite_data = $inv;
        } else {
            $invite_error = 'Einladungslink ist abgelaufen. Bitte fordere eine neue Einladung an.';
        }
    } else {
        $invite_error = 'Einladungslink ist ungÃ¼ltig oder wurde bereits genutzt.';
    }
}

if ($invite_error) {
    $message = "<div class='alert alert-danger'>{$invite_error}</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'register') {
        $inviteFromPost = null;
        if (!empty($_POST['invite_token'])) {
            $tokenInPost = trim($_POST['invite_token']);
            $stmt = $conn->prepare("SELECT * FROM einladungen WHERE token = ? AND angenommen = 'N' LIMIT 1");
            $stmt->bind_param("s", $tokenInPost);
            $stmt->execute();
            $res = $stmt->get_result();
            $invPost = $res->fetch_assoc();
            $stmt->close();
            if ($invPost && strtotime($invPost['erstellt_am']) >= strtotime('-7 days')) {
                $inviteFromPost = $invPost;
                $invite_token = $tokenInPost;
                $invite_data = $invite_data ?? $invPost;
            } else {
                $message = "<div class='alert alert-danger'>Einladungslink ist abgelaufen oder ungÃ¼ltig.</div>";
            }
        }

        if ($message === '') {
            // --- Registrierung ---
            $vorname  = trim($_POST['vorname'] ?? '');
            $nachname = trim($_POST['nachname'] ?? '');
            $plainPw  = $_POST['passwort'] ?? '';
            // Spaltenlimits aus DB (Fallback 50)
            $maxVorname  = getColumnMaxLength($conn, 'users', 'vorname')  ?? 50;
            $maxNachname = getColumnMaxLength($conn, 'users', 'nachname') ?? 50;
            $maxEmail    = getColumnMaxLength($conn, 'users', 'email')    ?? 50;

            if ($inviteFromPost) {
                $email     = trim($inviteFromPost['email']);
                $rolle     = 'azubi';
                $klasse_id = (int)$inviteFromPost['klasse_id'];
            } else {
                $email     = trim($_POST['email'] ?? '');
                $rolle     = $_POST['rolle'] ?? 'azubi';
                $klasse_id = 0;
            }

            // Feld-Validierungen
            if ($vorname === '' || $nachname === '' || $email === '' || $plainPw === '') {
                $message = "<div class='alert alert-danger'>Bitte alle Pflichtfelder ausfüllen.</div>";
            } elseif (mb_strlen($vorname) > $maxVorname || mb_strlen($nachname) > $maxNachname) {
                $message = "<div class='alert alert-danger'>Vorname/Nachname maximal {$maxVorname} Zeichen.</div>";
            } elseif (
                mb_strlen($email) > $maxEmail ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !preg_match('/^[^@]+@[^@]+\\.[^@]+$/', $email)
            ) {
                $message = "<div class='alert alert-danger'>Bitte eine gültige E-Mail-Adresse eingeben.</div>";
            } elseif (mb_strlen($plainPw) > 50) {
                $message = "<div class='alert alert-danger'>Passwort maximal 50 Zeichen.</div>";
            }

            if ($message === '') {
                $passwort = password_hash($plainPw, PASSWORD_DEFAULT);

                $classInfo = null;
                if ($klasse_id > 0) {
                    $stmtClass = $conn->prepare("SELECT abteilung, jahrgang FROM klassen WHERE id = ? LIMIT 1");
                    $stmtClass->bind_param("i", $klasse_id);
                    $stmtClass->execute();
                    $classInfo = $stmtClass->get_result()->fetch_assoc();
                    $stmtClass->close();
                }
                $abteilungNeu    = $classInfo['abteilung'] ?? '';
                $ausbildungsjahr = (int)($classInfo['jahrgang'] ?? 0);

                // prüfen, ob E-Mail existiert
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "<div class='alert alert-danger'>E-Mail ist bereits registriert!</div>";
                } else {
                    if ($klasse_id > 0) {
                        $stmt = $conn->prepare("INSERT INTO users (vorname, nachname, email, passwort_hash, rolle, klasse_id, abteilung, ausbildungsjahr, erstellt_am) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssssisi", $vorname, $nachname, $email, $passwort, $rolle, $klasse_id, $abteilungNeu, $ausbildungsjahr);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO users (vorname, nachname, email, passwort_hash, rolle, erstellt_am) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssss", $vorname, $nachname, $email, $passwort, $rolle);
                    }
                    if ($stmt->execute()) {
                        if ($inviteFromPost) {
                            $invId = (int)$inviteFromPost['id'];
                            $stmtClose = $conn->prepare("UPDATE einladungen SET angenommen = 'Y' WHERE id = ?");
                            $stmtClose->bind_param("i", $invId);
                            $stmtClose->execute();
                            $stmtClose->close();
                        }
                        // Nach erfolgreicher Registrierung direkt auf Login umleiten
                        header('Location: index.php?registered=1');
                        exit;
                    } else {
                        $message = "<div class='alert alert-danger'>Registrierung fehlgeschlagen.</div>";
                    }
                }
            }
        }
    } else {
        // --- Login ---
        $email = trim($_POST['email']);
        $passwort = $_POST['passwort'];

        $stmt = $conn->prepare("SELECT id, vorname, nachname, passwort_hash, rolle FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($passwort, $user['passwort_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['rolle'] = $user['rolle'];
                $_SESSION['name'] = $user['vorname'] . " " . $user['nachname'];

                $targetDashboard = 'backend/dashboard.php';
                if ($user['rolle'] === 'ausbilder') {
                    $targetDashboard = 'backend/dashboard_ausbilder.php';
                } elseif ($user['rolle'] === 'azubi') {
                    $targetDashboard = 'backend/dashboard_azubi.php';
                }
                header("Location: {$targetDashboard}");
                exit;
            } else {
                $message = "<div class='alert alert-danger'>Falsches Passwort!</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Benutzer nicht gefunden!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>DAN Digitaler Ausbildungsnachweis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        background: linear-gradient(180deg, #e9f2fb, #ffffff);
        min-height: 100vh;
    }
    .card {
        border-radius: 15px;
    }
    .logo {
        width: 250px;
        display: block;
        margin: 20px auto 15px;
    }
    h3 {
        color: #0d6efd;
        font-weight: 600;
    }
    .form-label {
        font-weight: 500;
    }
    .btn-primary {
        background-color: #0d6efd;
        border: none;
    }
    .btn-primary:hover {
        background-color: #0b5ed7;
    }
</style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <img src="assets/logo1.png" alt="DAN Logo" class="logo">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">
                        <?= $mode === 'register' ? 'Neuen Account erstellen' : 'Anmelden' ?>
                    </h3>
                    <?= $message ?>

                    <form method="post">
                        <?php if ($mode === 'register'): ?>
                            <?php if ($invite_data): ?>
                                <input type="hidden" name="invite_token" value="<?= htmlspecialchars($invite_token) ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Vorname</label>
                                <input type="text" name="vorname" class="form-control" required maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nachname</label>
                                <input type="text" name="nachname" class="form-control" required maxlength="50">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">E-Mail</label>
                            <?php $emailReadonly = ($mode === 'register' && $invite_data); ?>
                            <input type="email"
                                name="email"
                                class="form-control"
                                maxlength="50"
                                pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                                title="Bitte eine gültige E-Mail-Adresse eingeben"
                                <?= $emailReadonly ? 'readonly' : 'required' ?>>

                            <?php if ($emailReadonly): ?>
                                <div class="form-text">Diese E-Mail stammt aus der Einladung und ist festgelegt.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Passwort</label>
                            <input type="password" name="passwort" class="form-control" required maxlength="50">
                        </div>

                        <?php if ($mode === 'register'): ?>
                            <?php if ($invite_data): ?>
                                <div class="mb-3">
                                    <label class="form-label">Rolle</label>
                                    <input type="text" class="form-control" value="Azubi (durch Einladung festgelegt)" disabled>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label class="form-label">Rolle</label>
                                    <select name="rolle" class="form-select" required>
                                        <option value="azubi">Azubi</option>
                                        <option value="ausbilder">Ausbilder</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?= $mode === 'register' ? 'Account erstellen' : 'Einloggen' ?>
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <?php if ($mode === 'register'): ?>
                            <a href="?login" class="text-decoration-none">Schon registriert? Hier einloggen</a>
                        <?php else: ?>
                            <a href="?register" class="text-decoration-none">Noch kein Account? Jetzt registrieren</a>
                        <?php endif; ?>
                    </div>

                    <p class="text-center text-muted mt-4 mb-0"> DAN 2025  Digitaler Ausbildungsnachweis</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
