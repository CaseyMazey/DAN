<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendInviteMail(string $to, string $subject, string $htmlBody, string $plainText): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        // Debug-Ausgabe ins PHP-Error-Log (zum Deaktivieren auf 0 setzen)
        $mail->SMTPDebug  = 0;
        $mail->Debugoutput = function ($str) {
            error_log('PHPMailer debug: ' . trim($str));
        };

        // SMTP-Daten (c4re.de)
        $mail->Host       = 'smtp.c4re.de';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dan@c4re.de';
        $mail->Password   = '123Salami456!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        // Zertifikats-Check lockern, da CN vom Host abweicht (smtp.c4re.de -> *.dmsolutionsonline.de)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        // Absender
        $mail->setFrom('dan@c4re.de', 'DAN Plattform');

        // Logo einbetten (CID = danlogo)
        $mail->AddEmbeddedImage(
            $_SERVER['DOCUMENT_ROOT'] . '/DAN/assets/logo1.png',
            'danlogo',
            'logo1.png'
        );

        // Empfänger
        $mail->addAddress($to);

        // Mail-Einstellungen
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        // Inhalte
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainText;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


