<?php

namespace App\Rsvp;

use App\Storage\JsonFile;
use RuntimeException;

/**
 * Danh sách khách mời chuẩn bị trước (storage/data.json), chỉ đọc:
 * { "uuid": { email, name, position, luckyDrawCode, tableNumber } }.
 * Ứng dụng không bao giờ ghi file này.
 */
class GuestList
{
    private JsonFile $file;

    /**
     * Kết quả all() đã đọc: file chỉ đọc nên đọc một lần cho mỗi instance (mỗi request).
     *
     * @var array<string, array>|null
     */
    private ?array $guests = null;

    public function __construct(string $path)
    {
        $this->file = new JsonFile($path, 'danh sách khách mời');
    }

    /**
     * Khách theo uuid (chữ thường), mỗi khách kèm 'uuid' và 'email' đã chuẩn hoá.
     * Mục sai uuid hoặc thiếu email bị bỏ qua (ghi log) để không cấp vé nhầm.
     * Đọc lỗi => throw, không lưu kết quả (lần gọi sau đọc lại).
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        if ($this->guests !== null) {
            return $this->guests;
        }

        $guests = [];

        foreach ($this->file->read() as $key => $guest) {
            $uuid = strtolower(trim((string) $key));
            $email = is_array($guest) ? normalizeEmail($guest['email'] ?? '') : '';

            if (!isValidUuid($uuid) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("GuestList: bỏ qua khách {$key}: uuid hoặc email không hợp lệ");
                continue;
            }

            $guests[$uuid] = ['uuid' => $uuid, 'email' => $email] + $guest;
        }

        return $this->guests = $guests;
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->all()[strtolower(trim($uuid))] ?? null;
    }

    /**
     * Email phải là duy nhất: trùng ở nhiều uuid => throw thay vì chọn đại một khách.
     *
     * @param string $email đã chuẩn hoá bằng normalizeEmail()
     */
    public function findByEmail(string $email): ?array
    {
        $matches = array_values(array_filter(
            $this->all(),
            fn (array $guest) => $guest['email'] === $email
        ));

        if (count($matches) > 1) {
            throw new RuntimeException("data.json: email {$email} bị trùng ở " . count($matches) . ' khách');
        }

        return $matches[0] ?? null;
    }
}
