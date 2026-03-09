<?php
require_once __DIR__ . '/mail_helper.php';

$h = fn($str) => htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');

$defaultLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
    . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . '/index.php?register=1&token=demotoken';

$data = [
    'to'         => trim($_GET['to'] ?? 'dan@c4re.de'),
    'klasse'     => trim($_GET['klasse'] ?? 'Demo-Klasse'),
    'jahrgang'   => trim($_GET['jahrgang'] ?? '2025'),
    'validUntil' => trim($_GET['validUntil'] ?? date('d.m.Y H:i', strtotime('+7 days'))),
    'link'       => trim($_GET['link'] ?? $defaultLink),
];

$htmlTemplate = file_get_contents(__DIR__ . '/email_templates/invite.html');
$textTemplate = file_get_contents(__DIR__ . '/email_templates/invite.txt');

if ($htmlTemplate === false || $textTemplate === false) {
    http_response_code(500);
    echo 'Templates invite.html / invite.txt konnten nicht geladen werden.';
    exit;
}

$htmlBody = str_replace(
    ['{{KLASSE}}', '{{JAHRGANG}}', '{{Gültig_BIS}}', '{{LINK}}'],
    [$data['klasse'], $data['jahrgang'], $data['validUntil'], $data['link']],
    $htmlTemplate
);

$plainText = str_replace(
    ['{{KLASSE}}', '{{JAHRGANG}}', '{{Gültig_BIS}}', '{{LINK}}'],
    [$data['klasse'], $data['jahrgang'], $data['validUntil'], $data['link']],
    $textTemplate
);

$status = null;
if (isset($_GET['send'])) {
    $status = sendInviteMail(
        $data['to'],
        'Einladung zur DAN Plattform (Test)',
        $htmlBody,
        $plainText
    );
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Testmail Einladung</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px 12px; border-radius: 6px; margin-bottom: 16px; }
        .ok { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .fail { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; margin-bottom: 12px; }
        button { padding: 10px 16px; }
        iframe { width: 100%; height: 650px; border: 1px solid #ddd; border-radius: 6px; }
        pre { background: #f6f6f6; padding: 12px; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Testmail Einladung</h1>

    <?php if ($status !== null): ?>
        <div class="status <?= $status ? 'ok' : 'fail' ?>">
            <?= $status ? 'Testmail wurde versendet.' : 'Versand fehlgeschlagen. Siehe PHP Error Log.' ?>
        </div>
    <?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <label>E-Mail Empfänger</label>
        <input type="email" name="to" value="<?= $h($data['to']) ?>" required>

        <label>Klasse</label>
        <input type="text" name="klasse" value="<?= $h($data['klasse']) ?>">

        <label>Jahrgang</label>
        <input type="text" name="jahrgang" value="<?= $h($data['jahrgang']) ?>">

        <label>Gültig bis</label>
        <input type="text" name="validUntil" value="<?= $h($data['validUntil']) ?>">

        <label>Registrierungslink</label>
        <input type="text" name="link" value="<?= $h($data['link']) ?>">

        <button type="submit" name="send" value="1">Testmail senden</button>
        <button type="submit">Nur Vorschau aktualisieren</button>
    </form>

    <h3>HTML Vorschau</h3>
    <iframe srcdoc="<?= $h($htmlBody) ?>"></iframe>

    <h3>Text-Version</h3>
    <pre><?= $h($plainText) ?></pre>
</body>
</html>


