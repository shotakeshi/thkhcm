<?php

namespace App\Mail;

use App\Storage\JsonFile;

/**
 * Lưu các email đã gửi thành công vào file JSON: { "email": "thời gian gửi" }.
 * Không đọc/ghi được nhật ký thì throw để dừng gửi.
 */
class SentLog
{
    private JsonFile $file;

    public function __construct(string $path)
    {
        $this->file = new JsonFile($path, 'nhật ký gửi');
    }

    public function all(): array
    {
        return $this->file->read();
    }

    /**
     * Gọi trước khi gửi email để không gửi xong rồi mới phát hiện
     * không ghi được nhật ký.
     */
    public function ensureWritable(): void
    {
        $this->file->ensureWritable();
    }

    public function markSent(string $email): string
    {
        $this->ensureWritable();
        $sentAt = date('Y-m-d H:i:s');

        $this->file->update(function (array &$sent) use ($email, $sentAt): void {
            $sent[$email] = $sentAt;
        });

        return $sentAt;
    }
}
