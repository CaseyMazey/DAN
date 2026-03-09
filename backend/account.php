<?php
// =============================================================
// ACCOUNT.PHP – AUFGERÄUMTE, STABILE VERSION
// =============================================================

// -------------------------------------------------------------
// SESSION & ZUGRIFF
// -------------------------------------------------------------
$current_page = 'account.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$user_id = (int)$_SESSION['user_id'];
$rolle   = $_SESSION['rolle'] ?? '';

// -------------------------------------------------------------
// FEEDBACK (IMMER DEFINIERT)
// -------------------------------------------------------------
$success_msg = null;
$error_msg   = null;

// -------------------------------------------------------------
// HILFSFUNKTION: USER LADEN
// -------------------------------------------------------------
function loadUser(mysqli $conn, int $id): array {
    $stmt = $conn->prepare("
        SELECT u.id, u.vorname, u.nachname, u.email, u.rolle, u.abteilung,
               u.klasse_id, u.ausbildungsjahr,
               u.signature_type, k.name AS klasse_name
        FROM users u
        LEFT JOIN klassen k ON u.klasse_id = k.id
        WHERE u.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    return $data;
}

// -------------------------------------------------------------
// SIGNATUR SETUP
// -------------------------------------------------------------
$upload_dir = dirname(__DIR__) . '/uploads';

$allowed_signature_exts = ['png', 'jpg', 'jpeg'];
$active_signature_base  = "$upload_dir/user_{$user_id}_signature";
$upload_variant_base    = "$upload_dir/user_{$user_id}_signature_upload";
$drawn_variant_path     = "$upload_dir/user_{$user_id}_signature_drawn.png";

$clearActiveSignatureFiles = function () use ($allowed_signature_exts, $active_signature_base) {
    foreach ($allowed_signature_exts as $ext) {
        @unlink("$active_signature_base.$ext");
    }
};

$findFileVariant = function (string $base, array $exts): ?string {
    foreach ($exts as $ext) {
        if (is_file("$base.$ext")) return "$base.$ext";
    }
    return null;
};

$activateSignatureFromPath = function (
    string $path,
    string $type
) use (
    $conn,
    $user_id,
    $allowed_signature_exts,
    $active_signature_base,
    $clearActiveSignatureFiles
): bool {
    if (!is_file($path)) return false;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_signature_exts, true)) return false;

    $clearActiveSignatureFiles();
    copy($path, "$active_signature_base.$ext");

    $imgData = file_get_contents($path);
    if ($imgData === false) return false;

    $null = null;
    $stmt = $conn->prepare("
        UPDATE users
        SET signature_img = ?, signature_type = ?, signature_updated = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('bsi', $null, $type, $user_id);
    $stmt->send_long_data(0, $imgData);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
};

// -------------------------------------------------------------
// POST-VERARBEITUNG
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------
    // PROFIL SPEICHERN
    // -------------------------
    if (isset($_POST['update_profile'])) {
        $vorname  = trim($_POST['vorname'] ?? '');
        $nachname = trim($_POST['nachname'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if ($vorname === '' || $nachname === '' || $email === '') {
            $error_msg = 'Bitte alle Pflichtfelder ausfüllen.';
        } else {
            $stmt = $conn->prepare("
                UPDATE users
                SET vorname = ?, nachname = ?, email = ?
                WHERE id = ?
            ");
            $stmt->bind_param('sssi', $vorname, $nachname, $email, $user_id);

            if ($stmt->execute()) {
                $success_msg = 'Profil erfolgreich aktualisiert.';
            } else {
                $error_msg = 'Fehler beim Aktualisieren.';
            }
            $stmt->close();
        }
    }

    // -------------------------
    // PASSWORT ÄNDERN
    // -------------------------
    if (isset($_POST['change_password'])) {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw     = $_POST['new_password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        if ($new_pw !== $confirm_pw) {
            $error_msg = 'Die neuen Passwörter stimmen nicht überein.';
        } else {
            $stmt = $conn->prepare("SELECT passwort_hash FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($current_pw, $row['passwort_hash'])) {
                $error_msg = 'Das alte Passwort ist falsch.';
            } else {
                $hash = password_hash($new_pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET passwort_hash = ? WHERE id = ?");
                $stmt->bind_param('si', $hash, $user_id);

                if ($stmt->execute()) {
                    $success_msg = 'Passwort erfolgreich geändert.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
                $stmt->close();
            }
        }
    }

    // -------------------------
    // SIGNATUR UPLOAD
    // -------------------------
    if (isset($_POST['upload_signature'], $_FILES['signature_file'])) {
        $file = $_FILES['signature_file'];
        $mime = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'Uploadfehler.';
        } elseif ($file['size'] > 1024 * 1024) {
            $error_msg = 'Maximal 1MB erlaubt.';
        } elseif (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
            $error_msg = 'Nur PNG oder JPG erlaubt.';
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = $mime === 'image/png' ? 'png' : 'jpg';
            $target = "$upload_variant_base.$ext";

            if (move_uploaded_file($file['tmp_name'], $target)
                && $activateSignatureFromPath($target, 'upload')) {
                $success_msg = 'Signatur gespeichert.';
            } else {
                $error_msg = 'Signatur konnte nicht gespeichert werden.';
            }
        }
    }

    // -------------------------
    // SIGNATUR ZEICHNEN (CANVAS)
    // -------------------------
    if (isset($_POST['save_drawn_signature'], $_POST['signature_data'])) {

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $data = $_POST['signature_data'];

        if (!str_starts_with($data, 'data:image/png;base64,')) {
            $error_msg = 'Ungültige Signaturdaten.';
        } else {
            $data = base64_decode(
                str_replace('data:image/png;base64,', '', $data)
            );

            if ($data === false) {
                $error_msg = 'Signatur konnte nicht dekodiert werden.';
            } else {
                file_put_contents($drawn_variant_path, $data);

                if ($activateSignatureFromPath($drawn_variant_path, 'drawn')) {
                    $success_msg = 'Gezeichnete Signatur gespeichert.';
                } else {
                    $error_msg = 'Signatur konnte nicht aktiviert werden.';
                }
            }
        }
    }


    // -------------------------
    // SIGNATUR LÖSCHEN
    // -------------------------
    if (isset($_POST['delete_signature'])) {
        foreach ([$active_signature_base, $upload_variant_base] as $base) {
            foreach ($allowed_signature_exts as $ext) {
                @unlink("$base.$ext");
            }
        }
        @unlink($drawn_variant_path);

        $stmt = $conn->prepare("
            UPDATE users
            SET signature_img = NULL, signature_type = 'none'
            WHERE id = ?
        ");
        $stmt->bind_param('i', $user_id);

        if ($stmt->execute()) {
            $success_msg = 'Signatur gelöscht.';
        } else {
            $error_msg = 'Fehler beim Löschen.';
        }
        $stmt->close();
    }
}

// -------------------------------------------------------------
// AKTUELLE DATEN LADEN
// -------------------------------------------------------------
$user = loadUser($conn, $user_id);

$signature_file = $findFileVariant($active_signature_base, $allowed_signature_exts);
$signature_url  = $signature_file ? '../uploads/' . basename($signature_file) : '';

$klasse_display = $user['klasse_name'] ?: ($user['klasse_id'] ?? '-');

// -------------------------------------------------------------
// SIGNATUR STATUS & VARIANTEN (IMMER DEFINIERT)
// -------------------------------------------------------------
$upload_variant = $findFileVariant($upload_variant_base, $allowed_signature_exts);
$drawn_variant  = is_file($drawn_variant_path) ? $drawn_variant_path : null;
$signature_type = $user['signature_type'] ?? 'none';

// -------------------------------------------------------------
// HTML START
// -------------------------------------------------------------
ob_start();
?>

<link href="css/page-account.css" rel="stylesheet">




<h1 class="mb-4">Mein Profil</h1>

<?php if ($success_msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- ===============================
         SPALTE 1  PROFILKARTE
    ================================ -->
    <div class="col-12 col-lg-4">

        <div class="pass-card-wrapper">
            

            <div class="card">
                <h2><?= htmlspecialchars($user['vorname'] . ' ' . $user['nachname']) ?></h2>
                <p><strong>E-Mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Rolle:</strong> <?= ucfirst($user['rolle']) ?></p>

                <?php if ($rolle === 'azubi'): ?>
                    <p><strong>Abteilung:</strong> <?= htmlspecialchars($user['abteilung'] ?: '-') ?></p>
                    <p><strong>Klasse:</strong> <?= htmlspecialchars($klasse_display ?: '-') ?></p>
                    <p><strong>Ausbildungsjahr:</strong> <?= htmlspecialchars($user['ausbildungsjahr'] ?: '-') ?></p>
                <?php endif; ?>

                <button class="btn btn-primary pass-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editProfileModal">
                    Profil bearbeiten
                </button>

            </div>
        </div>

    </div>

    <!-- ===============================
         SPALTE 2  SIGNATURVERWALTUNG
    ================================ -->
    
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="profile-darkmode-toggle">
                    <div class="toggle-switch">
                        <label class="switch-label" for="themeToggle">
                            <input type="checkbox" class="checkbox" id="themeToggle">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

            <!-- Aktive Signatur -->
            <div class="signature-box mb-4">
                <h5 class="mb-2">Aktive Signatur</h5>

                
                <?php if ($signature_file): ?>
                    <p class="text-success mb-1">Aktive Signatur vorhanden.</p>
                    <img src="<?= htmlspecialchars($signature_url) ?>"
                        alt="Meine Signatur"
                        class="signature-preview">
                <?php else: ?>
                    <p class="text-muted mb-1">Noch keine aktive Signatur.</p>
                <?php endif; ?>

                <div class="text-muted small">Typ: <?= htmlspecialchars($signature_type ?? 'none') ?></div>

                <form method="post" class="mt-2">
                    <button type="submit"
                            name="delete_signature"
                            value="1"
                            class="btn btn-outline-danger btn-sm">
                        Signatur löschen
                    </button>
                </form>
            </div>

            <!-- Signatur hochladen -->
            <div class="signature-box mb-4">
                <h5 class="mb-2">Signatur hochladen</h5>

                <form method="post" enctype="multipart/form-data" class="d-grid gap-2">
                    <input type="hidden" name="upload_signature" value="1">

                    <input type="file"
                        name="signature_file"
                        accept="image/png,image/jpeg"
                        class="form-control"
                        required>

                    <small class="text-muted">PNG oder JPG, max. 1MB.</small>

                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        Hochladen
                    </button>
                </form>
            </div>

            <!-- Signatur zeichnen -->
            <div class="signature-box mb-4">
                <h5 class="mb-2">Signatur zeichnen</h5>

                <button type="button"
                        id="profileShowCanvas"
                        class="btn btn-outline-primary btn-sm">
                    Zeichenfeld Öffnen
                </button>

                <div id="profileCanvasWrap" class="mt-2 d-none">
                    <canvas id="profileSignatureCanvas"
                            width="360" height="150">
                    </canvas>

                    <div class="d-flex gap-2 mt-2">
                        <button type="button"
                                id="profileClearSignature"
                                class="btn btn-outline-secondary btn-sm">
                            Zurücksetzen
                        </button>

                        <button type="button"
                                id="profileSubmitSignature"
                                class="btn btn-success btn-sm">
                            Speichern
                        </button>
                    </div>
                </div>
            </div>

            <!-- Signatur auswählen -->
            <?php if ($upload_variant && $drawn_variant): ?>
                <div class="signature-box mb-4">
                    <h5 class="mb-2">Signatur auswählen</h5>

                    <form method="post" class="d-grid gap-2">
                        <input type="hidden" name="select_signature_source" value="1">

                        <div class="form-check">
                            <input class="form-check-input"
                                type="radio"
                                name="signature_choice"
                                value="upload"
                                id="sigChoiceUpload"
                                <?= ($signature_type === 'upload') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sigChoiceUpload">
                                Hochgeladene Signatur
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                type="radio"
                                name="signature_choice"
                                value="drawn"
                                id="sigChoiceDrawn"
                                <?= ($signature_type === 'drawn') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sigChoiceDrawn">
                                Gezeichnete Signatur
                            </label>
                        </div>

                        <button type="submit" class="btn btn-outline-primary btn-sm mt-2">
                            Auswahl speichern
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card mt-4">

                    <!-- Passwort ändern -->
            <div class="signature-box mb-4">
                <h5 class="mb-3">Passwort ändern</h5>

                <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="change_password" value="1">

                    <div>
                        <label class="form-label">Altes Passwort</label>
                        <input type="password"
                            name="current_password"
                            class="form-control"
                            required>
                    </div>

                    <div>
                        <label class="form-label">Neues Passwort</label>
                        <input type="password"
                            name="new_password"
                            class="form-control"
                            required>
                    </div>

                    <div>
                        <label class="form-label">Neues Passwort bestätigen</label>
                        <input type="password"
                            name="confirm_password"
                            class="form-control"
                            required>
                    </div>

                    <button type="submit"
                            class="btn btn-outline-primary btn-sm align-self-start">
                        Passwort ändern
                    </button>
                </form>
            </div>
        </div>
    </div>
</div> <!-- row -->


<!-- MODAL: PROFIL BEARBEITEN -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

        <form method="post">
            <input type="hidden" name="update_profile" value="1">

            <div class="modal-header">
                <h5 class="modal-title">Profil bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                
                <div class="mb-3">
                    <label class="form-label">Vorname</label>
                    <input type="text"
                           class="form-control"
                           name="vorname"
                           value="<?= htmlspecialchars($user['vorname']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nachname</label>
                    <input type="text"
                           class="form-control"
                           name="nachname"
                           value="<?= htmlspecialchars($user['nachname']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-Mail</label>
                    <input type="email"
                           class="form-control"
                           name="email"
                           value="<?= htmlspecialchars($user['email']) ?>"
                           required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary w-50"
                        data-bs-dismiss="modal">
                        Abbrechen
                </button>
                <button type="submit"
                        class="btn btn-primary w-50">
                        Speichern
                </button>
            </div>
        </form>

    </div>
  </div>
</div>


<?php
$page_content = ob_get_clean();

$page_scripts = '<script src="js/account.js"></script>';


// -------------------------------------------------------------
// LAYOUT LADEN
// -------------------------------------------------------------
include __DIR__ . '/layout.php';
