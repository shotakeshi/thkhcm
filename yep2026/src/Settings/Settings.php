<?php

namespace App\Settings;

use App\Storage\JsonFile;

/**
 * Cài đặt do admin đổi lúc chạy, lưu trong file JSON: { "khoá": giá trị }.
 * Khoá chưa có => caller dùng giá trị mặc định (ví dụ từ .env).
 */
class Settings
{
    public const RSVP_ENABLED = 'rsvpEnabled';

    private JsonFile $file;

    public function __construct(string $path)
    {
        $this->file = new JsonFile($path, 'cài đặt');
    }

    public function all(): array
    {
        return $this->file->read();
    }

    public function set(string $key, mixed $value): void
    {
        $this->file->update(function (array &$settings) use ($key, $value): void {
            $settings[$key] = $value;
        });
    }
}
