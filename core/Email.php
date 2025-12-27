<?php

namespace Archetype\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    /**
     * Sends an email to recipients (individually to respect privacy).
     */
    public static function send(array $dest, string $subject, string $type, string $content): void
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']; // Attention: Env.php attend SMTP_USER, pas EMAIL_USER
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
            $mail->Port       = $_ENV['SMTP_PORT'];

            // Recipients
            $mail->setFrom($_ENV['SMTP_USER'], 'Archetype API');

            // Content
            $mail->isHTML($type === 'html');
            $mail->Subject = $subject;
            $mail->Body    = $content;
            $mail->AltBody = strip_tags($content);

            // Send to each recipient individually
            foreach ($dest as $recipient) {
                $mail->clearAddresses();
                $mail->addAddress(trim($recipient));
                $mail->send();
            }
            
            if (class_exists(Logs::class)) {
                Logs::info('email', "Email '$subject' sent to " . count($dest) . " recipients.");
            }

        } catch (Exception $e) {
            $msg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            if (class_exists(Logs::class)) {
                Logs::error('email', $msg, $e);
            }
            throw new \Exception($msg);
        }
    }
}