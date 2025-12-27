<?php

namespace Archetype\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    /**
     * Sends an email to recipients individually (privacy-friendly).
     *
     * @param string[] $dest
     */
    public static function send(array $dest, string $subject, string $type, string $content): void
    {
        $mail = new PHPMailer(true);

        Logs::info('email', 'SMTP_HOST=' . ($_ENV['SMTP_HOST'] ?? ''));
        Logs::info('email', 'SMTP_PORT=' . ($_ENV['SMTP_PORT'] ?? ''));
        Logs::info('email', 'SMTP_SECURE=' . ($_ENV['SMTP_SECURE'] ?? ''));
        Logs::info('email', 'SMTP_USER=' . ($_ENV['SMTP_USER'] ?? ''));

        try {
            $host = (string)($_ENV['SMTP_HOST'] ?? '');
            $user = (string)($_ENV['SMTP_USER'] ?? '');
            $pass = (string)($_ENV['SMTP_PASS'] ?? '');
            $port = (int)($_ENV['SMTP_PORT'] ?? 0);
            $secure = strtolower(trim((string)($_ENV['SMTP_SECURE'] ?? '')));

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;

            if ($secure === 'none' || $secure === '') {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = $secure; // 'tls' (587) or 'ssl' (465)
            }

            $mail->Port = $port;

            // Sender must match the authenticated mailbox (or a permitted alias).
            $mail->setFrom($user, 'Archetype API');

            $mail->isHTML($type === 'html');
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->AltBody = strip_tags($content);

            foreach ($dest as $recipient) {
                $recipient = trim((string)$recipient);
                if ($recipient === '') {
                    continue;
                }
                $mail->clearAddresses();
                $mail->addAddress($recipient);
                $mail->send();
            }

            if (class_exists(Logs::class)) {
                Logs::info('email', "Email '{$subject}' sent to " . count($dest) . " recipients.");
            }
        } catch (Exception $e) {
            $msg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            if (class_exists(Logs::class)) {
                Logs::error('email', $msg, $e);
            }
            throw new \RuntimeException($msg, 0, $e);
        }
    }
}
