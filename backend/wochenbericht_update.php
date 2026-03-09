<?php
session_start();

// saubere Einbindung der DB
include_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Nicht eingeloggt.";
    exit;
}

$user_id = intval($_SESSION['user_id']);
$wb_id   = intval($_POST['wb_id'] ?? 0);

if ($wb_id <= 0) {
    echo "UnGültige Wochenbericht-ID.";
    exit;
}

/* ---------------------------------------------------------
   1) Kommentare speichern
--------------------------------------------------------- */
if (isset($_POST['kommentar']) && is_array($_POST['kommentar'])) {
    foreach ($_POST['kommentar'] as $aufgabe_id => $kommentar) {
        $aufgabe_id = intval($aufgabe_id);
        $kommentar = trim($kommentar);

        $stmt = $conn->prepare("
            UPDATE aufgaben 
            SET kommentar=? 
            WHERE id=? AND wochenbericht_id=? AND azubi_id=?
        ");
        $stmt->bind_param("siii", $kommentar, $aufgabe_id, $wb_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}


/* ---------------------------------------------------------
   2) Abgabe speichern (normaler POST)
--------------------------------------------------------- */
if (isset($_POST['speichern'])) {
    $abgegeben = isset($_POST['abgegeben']) ? 'Y' : 'N';

    $stmt = $conn->prepare("
        UPDATE wochenberichte 
        SET abgegeben=?, 
            abgegeben_am = CASE WHEN ?='Y' THEN NOW() ELSE NULL END
        WHERE id=? AND azubi_id=?
    ");
    $stmt->bind_param("ssii", $abgegeben, $abgegeben, $wb_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: wochenbericht.php");
    exit;
}


/* ---------------------------------------------------------
   3) Signatur speichern (Profil-Signatur nutzen)
--------------------------------------------------------- */
if (isset($_POST['signieren'])) {

    // Profil-Signatur laden
    $stmt = $conn->prepare("
        SELECT signature_img 
        FROM users 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $imgData = $row['signature_img'] ?? null;

    if (empty($imgData)) {
        echo "Keine Profil-Signatur hinterlegt.";
        exit;
    }

    // Signatur im Wochenbericht speichern
    $null = null;
    $stmt = $conn->prepare("
        UPDATE wochenberichte 
        SET signiert = 'Y',
            signiert_am = NOW(),
            signatur_img = ?
        WHERE id = ? AND azubi_id = ?
    ");
    $stmt->bind_param("bii", $null, $wb_id, $user_id);
    $stmt->send_long_data(0, $imgData);
    $stmt->execute();
    $stmt->close();

    header("Location: wochenbericht.php");
    exit;
}



/* ---------------------------------------------------------
   4) Signatur Zurücknehmen
--------------------------------------------------------- */
if (isset($_POST['unsignieren'])) {
    $stmt = $conn->prepare("
        UPDATE wochenberichte 
        SET signiert='N',
            signiert_am=NULL,
            signatur_img=NULL
        WHERE id=? AND azubi_id=?
    ");
    $stmt->bind_param("ii", $wb_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: wochenbericht.php");
    exit;
}


/* ---------------------------------------------------------
   Fallback: Zurück zum Wochenbericht
--------------------------------------------------------- */
header("Location: wochenbericht.php");
exit;

?>


