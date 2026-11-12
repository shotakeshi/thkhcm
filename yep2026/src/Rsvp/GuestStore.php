<?php

namespace App\Rsvp;

use App\Storage\JsonFile;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Nguồn dữ liệu duy nhất của khách mời, vé và xác nhận tham dự (storage/data.json):
 * { "uuid": { guestType, email, name, luckyDrawCode, tableNumber, attendance, submittedAt, updatedAt?, revealedAt? } }
 * - guestType "0": khách có sẵn trong danh sách; "1": khách tự đăng ký (uuid do server sinh).
 * - attendance: null (chưa xác nhận) | "attending" | "declined"; submittedAt null khi chưa xác nhận.
 * - updatedAt: chỉ có khi admin đổi trạng thái (public/admin-rsvp.php); admin sửa vé
 *   (luckyDrawCode, tableNumber qua public/admin-ticket.php) không đổi updatedAt.
 * - revealedAt: thời điểm khách bấm "lấy mã bàn" lần đầu (reveal()); thiếu hoặc null => chưa lấy mã,
 *   mọi chỗ hiển thị vé cho khách phải ẩn mã Lucky Draw và số bàn. Ghi một lần, không bao giờ đổi.
 *
 * Đọc: không khoá, vì file chỉ bị thay bằng rename() nguyên tử. Ghi: mọi thay đổi đi qua mutate(),
 * trong exclusive lock của JsonFile::update(): đọc lại bản mới nhất, đổi đúng một record, kiểm tra các
 * record khác không bị đụng tới rồi mới ghi file tạm + rename.
 *
 * Record sai cấu trúc không bao giờ bị bỏ đi khi ghi. Tra cứu trúng record đó (theo uuid hoặc email)
 * => throw (báo lỗi tạm thời) thay vì coi là "không có", để không tạo record trùng uuid/email.
 */
final class GuestStore
{
    public const ATTENDING = 'attending';
    public const DECLINED = 'declined';

    public const GUEST_LISTED = '0';
    public const GUEST_SELF_REGISTERED = '1';

    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    private JsonFile $file;

    /**
     * Nội dung file đã đọc trong request này; ghi xong thì xoá để lần sau đọc lại.
     *
     * @var array<string, mixed>|null
     */
    private ?array $records = null;

    public function __construct(string $path)
    {
        $this->file = new JsonFile($path, 'danh sách khách mời (data.json)', required: true);
    }

    /**
     * Khách hợp lệ theo uuid (thứ tự trong file), mỗi khách kèm 'uuid' và 'email' đã chuẩn hoá.
     * Record sai cấu trúc bị bỏ qua ở đây (xem invalidRecords()).
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        $guests = [];

        foreach ($this->records() as $uuid => $record) {
            if (self::recordError($uuid, $record) === null) {
                $guests[$uuid] = self::present($uuid, $record);
            }
        }

        return $guests;
    }

    /**
     * Record sai cấu trúc: [key => lý do], cho trang admin.
     *
     * @return array<string, string>
     */
    public function invalidRecords(): array
    {
        $invalid = [];

        foreach ($this->records() as $uuid => $record) {
            $error = self::recordError($uuid, $record);

            if ($error !== null) {
                $invalid[(string) $uuid] = $error;
            }
        }

        return $invalid;
    }

    /**
     * Khách của link mời. Không có => null; record sai cấu trúc => throw.
     */
    public function findByUuid(string $uuid): ?array
    {
        return self::lookupUuid($this->records(), $uuid);
    }

    /**
     * Khách theo email (cả hai guestType). Không có => null; trùng ở nhiều uuid hoặc trúng record
     * sai cấu trúc => throw, không chọn đại một khách.
     *
     * @param string $email đã chuẩn hoá bằng normalizeEmail()
     */
    public function findByEmail(string $email): ?array
    {
        return self::lookupEmail($this->records(), $email);
    }

    /**
     * Xác nhận qua link mời ?id=: chỉ ghi attendance/submittedAt của đúng record đó.
     * Đã xác nhận trước đó => trả record hiện tại, không ghi đè. uuid không có => null.
     *
     * @return array{guest: array, created: bool}|null
     */
    public function submitByUuid(string $uuid, string $attendance): ?array
    {
        self::assertAttendance($attendance);

        return $this->mutate(function (array &$records) use ($uuid, $attendance): array {
            $guest = self::lookupUuid($records, $uuid);

            return $guest === null ? [null, null] : self::respond($records, $guest, $attendance);
        });
    }

    /**
     * Xác nhận từ form không có link mời. Email đã có (khách có sẵn hoặc đã tự đăng ký) => dùng đúng
     * record đó, không đổi tên/email/vé; chưa có => tạo khách tự đăng ký với uuid v4 mới.
     * Tìm email và tạo record nằm trong cùng một lock nên request đồng thời không tạo trùng email.
     *
     * @param string $email đã chuẩn hoá bằng normalizeEmail()
     * @param string $name tên khách tự nhập, chỉ dùng khi tạo khách tự đăng ký
     * @return array{guest: array, created: bool}
     */
    public function submitByEmail(string $email, string $name, string $attendance): array
    {
        self::assertAttendance($attendance);

        return $this->mutate(function (array &$records) use ($email, $name, $attendance): array {
            $guest = self::lookupEmail($records, $email);

            if ($guest !== null) {
                return self::respond($records, $guest, $attendance);
            }

            $uuid = self::newUuid($records);
            $records[$uuid] = [
                'guestType'     => self::GUEST_SELF_REGISTERED,
                'name'          => $name,
                'email'         => $email,
                'luckyDrawCode' => '',
                'tableNumber'   => '',
                'attendance'    => $attendance,
                'submittedAt'   => self::now(),
                'revealedAt'    => null,
            ];

            return [['guest' => self::present($uuid, $records[$uuid]), 'created' => true], $uuid];
        });
    }

    /**
     * Admin đổi xác nhận của khách đã phản hồi, kèm updatedAt. Trạng thái không đổi => không ghi.
     *
     * @return string|null trạng thái trước khi đổi, null khi khách chưa xác nhận hoặc không còn
     */
    public function changeAttendance(string $uuid, string $attendance): ?string
    {
        self::assertAttendance($attendance);

        return $this->mutate(function (array &$records) use ($uuid, $attendance): array {
            $guest = self::lookupUuid($records, $uuid);
            $before = $guest['attendance'] ?? null;

            if ($before === null || $before === $attendance) {
                return [$before, null];
            }

            $records[$guest['uuid']]['attendance'] = $attendance;
            $records[$guest['uuid']]['updatedAt'] = self::now();

            return [$before, $guest['uuid']];
        });
    }

    /**
     * Admin xoá xác nhận để khách xác nhận lại (cả khách có sẵn và khách tự đăng ký):
     * attendance/submittedAt về null, bỏ updatedAt; record, link ?id= và vé giữ nguyên.
     *
     * @return string|null trạng thái trước khi xoá, null khi khách chưa xác nhận hoặc không còn
     */
    public function resetAttendance(string $uuid): ?string
    {
        return $this->mutate(function (array &$records) use ($uuid): array {
            $guest = self::lookupUuid($records, $uuid);
            $before = $guest['attendance'] ?? null;

            if ($before === null) {
                return [null, null];
            }

            $key = $guest['uuid'];
            $records[$key]['attendance'] = null;
            $records[$key]['submittedAt'] = null;
            unset($records[$key]['updatedAt']);

            return [$before, $key];
        });
    }

    /**
     * Admin sửa mã Lucky Draw và số bàn (đã trim, rỗng = chưa có). Không đụng attendance/updatedAt.
     * $expected là giá trị admin đang thấy; khác giá trị hiện tại (vừa bị sửa ở nơi khác) => không ghi đè.
     * Mã Lucky Draw mới trùng khách khác (không phân biệt hoa thường) => không ghi.
     *
     * @param array{luckyDrawCode: string, tableNumber: string} $ticket
     * @param array{luckyDrawCode: string, tableNumber: string} $expected
     * @return string|null 'saved' | 'unchanged' | 'conflict' | 'duplicate', null khi không còn khách
     */
    public function updateTicket(string $uuid, array $ticket, array $expected): ?string
    {
        return $this->mutate(function (array &$records) use ($uuid, $ticket, $expected): array {
            $guest = self::lookupUuid($records, $uuid);

            if ($guest === null) {
                return [null, null];
            }

            $current = self::ticketOf($guest);

            if ($current !== self::ticketOf($expected)) {
                return ['conflict', null];
            }

            $ticket = self::ticketOf($ticket);

            if ($ticket === $current) {
                return ['unchanged', null];
            }

            // Chỉ kiểm tra trùng khi đổi mã, để sửa số bàn không bị chặn bởi mã trùng có sẵn trong file
            $code = $ticket['luckyDrawCode'];

            if (
                $code !== ''
                && strtolower($code) !== strtolower($current['luckyDrawCode'])
                && self::isLuckyDrawCodeTaken($records, $code, $guest['uuid'])
            ) {
                return ['duplicate', null];
            }

            $records[$guest['uuid']]['luckyDrawCode'] = $code;
            $records[$guest['uuid']]['tableNumber'] = $ticket['tableNumber'];

            return ['saved', $guest['uuid']];
        });
    }

    /**
     * Khách lấy mã bàn: ghi revealedAt chỉ khi đang chưa có; đã có => giữ nguyên, trả lại đúng record (idempotent).
     * Kiểm tra và ghi nằm trong cùng lock nên hai tab bấm cùng lúc chỉ ghi revealedAt một lần.
     * Chỉ khách đã xác nhận tham gia và đã được gán mã Lucky Draw + số bàn.
     *
     * @return array{status: string, guest: array, created: bool}|null
     *         status 'revealed' | 'not_attending' | 'pending' (chưa có mã); created: lần này mới ghi revealedAt;
     *         null khi không có khách
     */
    public function reveal(string $uuid): ?array
    {
        return $this->mutate(function (array &$records) use ($uuid): array {
            $guest = self::lookupUuid($records, $uuid);

            if ($guest === null) {
                return [null, null];
            }

            $status = match (true) {
                $guest['attendance'] !== self::ATTENDING => 'not_attending',
                !canDownloadTicket($guest) => 'pending',
                default => 'revealed',
            };

            if ($status !== 'revealed' || isTicketRevealed($guest)) {
                return [['status' => $status, 'guest' => $guest, 'created' => false], null];
            }

            $key = $guest['uuid'];
            $records[$key]['revealedAt'] = self::now();

            return [['status' => $status, 'guest' => self::present($key, $records[$key]), 'created' => true], $key];
        });
    }

    /**
     * Lý do record sai cấu trúc, hợp lệ => null. Dùng chung cho runtime và script migration.
     */
    public static function recordError(mixed $key, mixed $record): ?string
    {
        if (!is_string($key) || !isValidUuid($key) || $key !== strtolower($key)) {
            return 'uuid không hợp lệ (cần uuid viết thường)';
        }

        if (!is_array($record)) {
            return 'record không phải object';
        }

        $guestType = $record['guestType'] ?? null;

        if (!in_array($guestType, [self::GUEST_LISTED, self::GUEST_SELF_REGISTERED], true)) {
            return 'guestType phải là "0" hoặc "1"';
        }

        if (!is_string($record['email'] ?? null) || emailInput($record['email']) === null) {
            return 'email không hợp lệ';
        }

        $name = $record['name'] ?? null;

        if (!is_string($name) || ($guestType === self::GUEST_SELF_REGISTERED && trim($name) === '')) {
            return 'name không hợp lệ';
        }

        foreach (['luckyDrawCode', 'tableNumber'] as $field) {
            if (isset($record[$field]) && !is_string($record[$field]) && !is_int($record[$field])) {
                return "{$field} không hợp lệ";
            }
        }

        if (!array_key_exists('attendance', $record) || !array_key_exists('submittedAt', $record)) {
            return 'thiếu attendance hoặc submittedAt';
        }

        $attendance = $record['attendance'];

        if (!in_array($attendance, [null, self::ATTENDING, self::DECLINED], true)) {
            return 'attendance không hợp lệ';
        }

        if ($attendance === null && ($record['submittedAt'] !== null || isset($record['updatedAt']))) {
            return 'chưa xác nhận nhưng có submittedAt/updatedAt';
        }

        if ($attendance !== null && !self::isTimestamp($record['submittedAt'])) {
            return 'đã xác nhận nhưng submittedAt không hợp lệ';
        }

        if (isset($record['updatedAt']) && !self::isTimestamp($record['updatedAt'])) {
            return 'updatedAt không hợp lệ';
        }

        if (isset($record['revealedAt']) && !self::isTimestamp($record['revealedAt'])) {
            return 'revealedAt không hợp lệ';
        }

        return null;
    }

    public static function isTimestamp(mixed $value): bool
    {
        return is_string($value) && $value !== '' && strtotime($value) !== false;
    }

    public static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE)))->format(DATE_ATOM);
    }

    private function records(): array
    {
        if ($this->records === null) {
            $records = $this->file->read();
            self::assertStructure($records);
            $this->records = $records;
        }

        return $this->records;
    }

    /**
     * Mọi thao tác ghi: $change(&$records) trả [kết quả, uuid của record vừa đổi | null].
     * Sau khi đổi: chỉ record đó được khác đi và phải còn hợp lệ, không thì throw và không ghi gì.
     */
    private function mutate(callable $change): mixed
    {
        try {
            return $this->file->update(function (array &$records) use ($change): mixed {
                self::assertStructure($records);
                self::assertMigrated($records);

                $before = $records;
                [$result, $touched] = $change($records);
                self::assertOnlyTouched($before, $records, $touched);

                return $result;
            });
        } finally {
            $this->records = null;
        }
    }

    private static function assertStructure(array $records): void
    {
        if ($records !== [] && array_is_list($records)) {
            throw new RuntimeException('data.json sai định dạng: cần object theo uuid');
        }
    }

    /**
     * File còn record theo cấu trúc cũ (chưa có guestType) => chưa chạy migration, không cho ghi
     * để không tạo dữ liệu mới trên nền dữ liệu chưa hợp nhất với rsvp.json.
     */
    private static function assertMigrated(array $records): void
    {
        foreach ($records as $record) {
            if (is_array($record) && !array_key_exists('guestType', $record)) {
                throw new RuntimeException('data.json chưa được migrate (thiếu guestType), không ghi');
            }
        }
    }

    /**
     * Bảo vệ cuối trước khi ghi: không record nào ngoài $touched bị thêm, xoá hoặc sửa,
     * và record $touched (nếu còn) phải hợp lệ.
     */
    private static function assertOnlyTouched(array $before, array $after, ?string $touched): void
    {
        foreach ($before as $key => $record) {
            if ($key !== $touched && (!array_key_exists($key, $after) || $after[$key] !== $record)) {
                throw new RuntimeException("data.json: record {$key} bị thay đổi ngoài dự kiến, không ghi");
            }
        }

        foreach (array_keys($after) as $key) {
            if ($key !== $touched && !array_key_exists($key, $before)) {
                throw new RuntimeException("data.json: record {$key} được thêm ngoài dự kiến, không ghi");
            }
        }

        if ($touched !== null && array_key_exists($touched, $after)) {
            $error = self::recordError($touched, $after[$touched]);

            if ($error !== null) {
                throw new RuntimeException("data.json: record {$touched} sau khi sửa không hợp lệ ({$error}), không ghi");
            }
        }
    }

    /**
     * Ghi xác nhận lần đầu cho khách đã có record; đã xác nhận => giữ nguyên.
     */
    private static function respond(array &$records, array $guest, string $attendance): array
    {
        if ($guest['attendance'] !== null) {
            return [['guest' => $guest, 'created' => false], null];
        }

        $uuid = $guest['uuid'];
        $records[$uuid]['attendance'] = $attendance;
        $records[$uuid]['submittedAt'] = self::now();

        return [['guest' => self::present($uuid, $records[$uuid]), 'created' => true], $uuid];
    }

    private static function lookupUuid(array $records, string $uuid): ?array
    {
        $uuid = strtolower(trim($uuid));

        if (!isValidUuid($uuid)) {
            return null;
        }

        // Key viết hoa (sửa tay) vẫn phải bị phát hiện, không coi là "không có"
        foreach ($records as $key => $record) {
            if (is_string($key) && strtolower(trim($key)) === $uuid) {
                $error = self::recordError($key, $record);

                if ($error !== null) {
                    throw new RuntimeException("data.json: record {$key} không hợp lệ ({$error})");
                }

                return self::present($key, $record);
            }
        }

        return null;
    }

    private static function lookupEmail(array $records, string $email): ?array
    {
        if ($email === '') {
            return null;
        }

        $matches = array_filter(
            $records,
            fn (mixed $record) => is_array($record) && normalizeEmail($record['email'] ?? '') === $email
        );

        if (count($matches) > 1) {
            throw new RuntimeException("data.json: email {$email} bị trùng ở " . count($matches) . ' record: '
                . implode(', ', array_keys($matches)));
        }

        foreach ($matches as $key => $record) {
            $error = self::recordError($key, $record);

            if ($error !== null) {
                throw new RuntimeException("data.json: record {$key} không hợp lệ ({$error})");
            }

            return self::present($key, $record);
        }

        return null;
    }


    private static function ticketOf(array $record): array
    {
        return [
            'luckyDrawCode' => stringField($record, 'luckyDrawCode'),
            'tableNumber'   => stringField($record, 'tableNumber'),
        ];
    }

    private static function isLuckyDrawCodeTaken(array $records, string $code, string $exceptUuid): bool
    {
        $code = strtolower($code);

        foreach ($records as $key => $record) {
            $value = is_array($record) ? ($record['luckyDrawCode'] ?? '') : '';

            if (
                (string) $key !== $exceptUuid
                && (is_string($value) || is_int($value))
                && strtolower(trim((string) $value)) === $code
            ) {
                return true;
            }
        }

        return false;
    }

    private static function present(string $uuid, array $record): array
    {
        return ['uuid' => $uuid, 'email' => normalizeEmail($record['email'])] + $record;
    }

    private static function assertAttendance(string $attendance): void
    {
        if (!in_array($attendance, [self::ATTENDING, self::DECLINED], true)) {
            throw new RuntimeException("attendance không hợp lệ: {$attendance}");
        }
    }

    /**
     * UUID v4 chưa có trong file (so không phân biệt hoa thường).
     */
    private static function newUuid(array $records): string
    {
        $taken = array_change_key_case($records, CASE_LOWER);

        do {
            $bytes = random_bytes(16);
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        } while (isset($taken[$uuid]));

        return $uuid;
    }
}
