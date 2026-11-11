<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class Mailer
{
    private array $config;

    private ?string $lastError = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        bool $isHtml = true
    ): bool {
        $this->lastError = null;
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];

            $mail->SMTPSecure = match ($this->config['encryption']) {
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                default => throw new RuntimeException(
                    'Unsupported SMTP encryption'
                ),
            };

            $mail->Port = $this->config['port'];
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = $this->config['timeout'];

            $mail->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );

            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML($isHtml);
            $mail->Body = $body;

            if ($isHtml) {
                $mail->AltBody = trim(strip_tags($body));
            }

            return $mail->send();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('Mail sending failed: ' . $e->getMessage());

            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
