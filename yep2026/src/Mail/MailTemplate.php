<?php

namespace App\Mail;

/**
 * Template email có placeholder dạng {key}.
 */
class MailTemplate
{
    public function __construct(
        private string $subject,
        private string $body
    ) {
    }

    /**
     * Tạo template từ file PHP trả về ['subject' => ..., 'body' => ...].
     */
    public static function fromFile(string $file): self
    {
        $template = require $file;

        return new self($template['subject'], $template['body']);
    }

    /**
     * Thay placeholder bằng giá trị. Body là HTML nên giá trị được escape.
     *
     * @param array<string, string> $values ví dụ ['name' => 'Nguyễn Văn A']
     * @return array{subject: string, body: string}
     */
    public function render(array $values): array
    {
        $placeholders = [];

        foreach ($values as $key => $value) {
            $placeholders['{' . $key . '}'] = (string) $value;
        }

        $escapedPlaceholders = array_map(
            fn (string $value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $placeholders
        );

        return [
            'subject' => strtr($this->subject, $placeholders),
            'body'    => strtr($this->body, $escapedPlaceholders),
        ];
    }
}
