<?php

/**
 * Khởi động chung cho mọi entry point trong public/: autoload, .env, múi giờ, header bảo mật
 * và các hàm dùng chung, xếp theo nhóm trách nhiệm:
 *   1. Môi trường & cấu hình      5. URL & asset
 *   2. Chuẩn hoá dữ liệu nhập     6. View & phản hồi HTTP
 *   3. Truy cập dữ liệu (storage) 7. Ngôn ngữ (vi / en)
 *   4. Quy tắc nghiệp vụ
 */

require __DIR__ . '/vendor/autoload.php';

const BASE_PATH = __DIR__;
const STORAGE_PATH = __DIR__ . '/storage';

/**
 * Độ dài tối đa của họ tên khách tự đăng ký (form và kiểm tra phía server).
 */
const NAME_MAX_LENGTH = 100;

/**
 * Domain email được phép nhập ở form xác nhận tham dự không có link mời (khớp chính xác, chữ thường).
 */
const RSVP_ALLOWED_EMAIL_DOMAINS = ['thk-hd.vn', 'core-tech.jp'];

/**
 * Ngôn ngữ của trang công khai: mã => tên viết bằng chính ngôn ngữ đó (nút chuyển ngôn ngữ).
 * Thêm ngôn ngữ: thêm một dòng ở đây và tạo lang/{mã}/ cùng bộ khoá với lang/vi/.
 */
const LOCALES = ['vi' => 'Tiếng Việt', 'en' => 'English'];

/**
 * Ngôn ngữ khi khách chưa chọn, đồng thời là bản dự phòng khi ngôn ngữ khác thiếu khoá.
 */
const DEFAULT_LOCALE = 'vi';

/**
 * Cookie giữ ngôn ngữ khách đã chọn để server render đúng ngôn ngữ (public/js/i18n.js ghi cùng localStorage).
 */
const LOCALE_COOKIE = 'yep26_locale';

// ===== 1. Môi trường & cấu hình =====

/**
 * Nạp file .env vào getenv(), không ghi đè biến môi trường đã có sẵn.
 */
function loadEnv(string $file): void
{
    if (!is_file($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Mảng trả về từ config/{name}.php.
 */
function loadConfig(string $name): array
{
    return require BASE_PATH . "/config/{$name}.php";
}

function appUrl(): string
{
    return rtrim(getenv('APP_URL') ?: '', '/');
}

/**
 * Email nhận khi chạy chế độ test (MAIL_TEST_TO), rỗng nếu đang gửi thật.
 */
function mailTestRecipient(): string
{
    return getenv('MAIL_TEST_TO') ?: '';
}

function isMailTestMode(): bool
{
    return mailTestRecipient() !== '';
}

/**
 * Form xác nhận tham dự và tra cứu vé đang bật hay tắt.
 * Admin đổi được trên admin.php (storage/settings.json); chưa đổi lần nào thì theo RSVP_ENABLED
 * trong .env (true/false, 1/0, on/off, yes/no). Chưa đặt hoặc không hiểu được => bật.
 * settings.json hỏng không được làm sập trang công khai => ghi log và theo .env.
 */
function isRsvpEnabled(): bool
{
    try {
        $saved = settings()->all()[\App\Settings\Settings::RSVP_ENABLED] ?? null;
    } catch (RuntimeException $e) {
        \App\Log\Logger::warning($e, ['fallback' => 'RSVP_ENABLED trong .env']);
        $saved = null;
    }

    if (is_bool($saved)) {
        return $saved;
    }

    $value = getenv('RSVP_ENABLED');

    return $value === false
        ? true
        : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
}

// ===== 2. Chuẩn hoá & kiểm tra dữ liệu nhập =====

/**
 * Giá trị chuỗi đã trim của một field, thiếu field => ''.
 */
function stringField(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

/**
 * Chuẩn hoá email để so sánh/lưu: bỏ khoảng trắng đầu cuối, chữ thường.
 */
function normalizeEmail(mixed $value): string
{
    return is_string($value) ? strtolower(trim($value)) : '';
}

/**
 * Email người dùng nhập, đã chuẩn hoá; sai kiểu hoặc sai định dạng => null.
 */
function emailInput(mixed $value): ?string
{
    $email = normalizeEmail($value);

    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

/**
 * Email thuộc RSVP_ALLOWED_EMAIL_DOMAINS (không tính subdomain).
 *
 * @param string $email đã chuẩn hoá bằng emailInput()
 */
function isAllowedRsvpEmail(string $email): bool
{
    $domain = substr(strrchr($email, '@') ?: '', 1);

    return in_array($domain, RSVP_ALLOWED_EMAIL_DOMAINS, true);
}

function isValidUuid(string $value): bool
{
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    return preg_match($pattern, $value) === 1;
}

/**
 * uuid hợp lệ của nhân viên, null khi chưa điền hoặc sai định dạng.
 */
function employeeUuid(array $employee): ?string
{
    $uuid = stringField($employee, 'uuid');

    return isValidUuid($uuid) ? strtolower($uuid) : null;
}

// ===== 3. Truy cập dữ liệu (storage/*.json) =====

/**
 * Nguồn dữ liệu duy nhất của khách mời, vé và xác nhận tham dự (storage/data.json), dùng chung trong một request.
 */
function guestStore(): \App\Rsvp\GuestStore
{
    static $store = null;

    return $store ??= new \App\Rsvp\GuestStore(STORAGE_PATH . '/data.json');
}

function settings(): \App\Settings\Settings
{
    return new \App\Settings\Settings(STORAGE_PATH . '/settings.json');
}

function sentLog(): \App\Mail\SentLog
{
    return new \App\Mail\SentLog(STORAGE_PATH . '/sent.json');
}

/**
 * Khách có sẵn trong danh sách (guestType "0") theo email, dùng cho trang gửi thư mời: [email => khách kèm uuid].
 */
function loadEmployees(): array
{
    $employees = [];

    foreach (guestStore()->all() as $guest) {
        if ($guest['guestType'] === \App\Rsvp\GuestStore::GUEST_LISTED) {
            $employees[$guest['email']] ??= $guest;
        }
    }

    return $employees;
}

/**
 * Khách có sẵn trong danh sách theo email; khách tự đăng ký không nhận thư mời => null.
 */
function findEmployee(string $email): ?array
{
    $guest = guestStore()->findByEmail(normalizeEmail($email));

    return $guest !== null && $guest['guestType'] === \App\Rsvp\GuestStore::GUEST_LISTED ? $guest : null;
}

/**
 * Khách kèm RSVP theo dạng view dùng: ['uuid', 'guest', 'rsvp' => record|null (null => chưa xác nhận)].
 */
function invitation(array $guest): array
{
    return [
        'uuid'  => $guest['uuid'],
        'guest' => $guest,
        'rsvp'  => $guest['attendance'] !== null ? $guest : null,
    ];
}

/**
 * Khách của link công khai (?id=) kèm RSVP (invitation()), tìm trực tiếp theo uuid trong data.json.
 * Sai kiểu, sai định dạng hoặc không có uuid => null.
 * Không đọc được dữ liệu hoặc record hỏng => throw (caller báo lỗi tạm thời, không báo link sai).
 */
function findInvitation(mixed $id): ?array
{
    if (!is_string($id) || !isValidUuid(trim($id))) {
        return null;
    }

    $guest = guestStore()->findByUuid(trim($id));

    return $guest !== null ? invitation($guest) : null;
}

/**
 * Giá trị bị trùng của một field: ['giá trị' => [email, ...]], bỏ qua giá trị rỗng.
 */
function findDuplicateValues(array $employees, string $field): array
{
    $groups = [];

    foreach ($employees as $email => $employee) {
        $value = strtolower(stringField($employee, $field));

        if ($value !== '') {
            $groups[$value][] = $email;
        }
    }

    return array_filter($groups, fn (array $emails) => count($emails) > 1);
}

// ===== 4. Quy tắc nghiệp vụ =====

/**
 * ADMIN_TOKEN chưa cấu hình => luôn từ chối.
 */
function isAdminToken(mixed $token): bool
{
    $expected = getenv('ADMIN_TOKEN') ?: 'yep2026thk';

    return $expected !== '' && is_string($token) && hash_equals($expected, $token);
}

function isAttending(?array $rsvp): bool
{
    return ($rsvp['attendance'] ?? null) === \App\Rsvp\GuestStore::ATTENDING;
}

/**
 * Vé chỉ tải được khi đã có mã Lucky Draw và số bàn.
 */
function canDownloadTicket(array $employee): bool
{
    return stringField($employee, 'luckyDrawCode') !== ''
        && stringField($employee, 'tableNumber') !== '';
}

/**
 * Thông tin sự kiện từ config/event.php, kèm các phần ngày tháng tính sẵn.
 * Thứ, ngày dạng chữ theo ngôn ngữ: formatDate() / lang/ (date.*).
 */
function loadEvent(): array
{
    $event = loadConfig('event');
    $date = new DateTimeImmutable($event['date']);

    return $event + [
        'title'     => "{$event['name']} {$date->format('Y')}",
        'day'       => $date->format('d'),
        'month'     => $date->format('n'),
        'year'      => $date->format('Y'),
    ];
}

// ===== 5. URL & asset =====

/**
 * Query string của link mời cá nhân: ?id={uuid}.
 */
function inviteQuery(string $uuid): string
{
    return '?id=' . rawurlencode($uuid);
}

/**
 * Link thiệp mời cá nhân gửi trong email.
 */
function inviteUrl(string $uuid): string
{
    return appUrl() . '/' . inviteQuery($uuid);
}

/**
 * Đường dẫn file trong public/ kèm ?v= theo thời điểm sửa file để trình duyệt không dùng cache cũ.
 */
function asset(string $path): string
{
    return $path . '?v=' . filemtime(BASE_PATH . '/public/' . $path);
}

/**
 * Đường dẫn nền là video (mp4/webm) => render <video>, ngược lại dùng background-image.
 */
function isVideoAsset(?string $path): bool
{
    $ext = strtolower(pathinfo((string) parse_url((string) $path, PHP_URL_PATH), PATHINFO_EXTENSION));

    return in_array($ext, ['mp4', 'webm'], true);
}

// ===== 6. View & phản hồi HTTP =====

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Thời điểm ISO 8601 (submittedAt, updatedAt trong data.json) theo múi giờ ứng dụng.
 * Rỗng hoặc sai định dạng => ''.
 */
function formatTimestamp(mixed $value, string $format = 'd/m/Y H:i'): string
{
    $time = is_string($value) && $value !== '' ? strtotime($value) : false;

    return $time !== false ? date($format, $time) : '';
}

/**
 * Dựng một partial trong views/partials/ thành chuỗi (dùng cho phản hồi JSON và các chỗ gọi lại).
 */
function renderPartial(string $file, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();

    try {
        require BASE_PATH . '/views/partials/' . $file;
    } finally {
        $output = ob_get_clean();
    }

    return $output;
}

/**
 * Phần vé trong phản hồi JSON (RSVP, tra cứu vé): slot #ve, toast và script tải vé.
 * Không tham gia => slot chỉ còn seam, không có mã Lucky Draw, số bàn hay nút tải vé.
 */
function ticketPayload(array $event, ?array $guest, bool $showTicket): array
{
    $showTicket = $showTicket && $guest !== null;
    $overlays = $showTicket
        ? trim(renderPartial('ticket-overlays.php', ['employee' => $guest]))
        : '';

    return [
        'attending'    => $showTicket,
        'ticketHtml'   => renderPartial('ticket-slot.php', [
            'event' => $event, 'employee' => $guest, 'showTicket' => $showTicket,
        ]),
        'overlaysHtml' => $overlays !== '' ? $overlays : null,
        'ticketScript' => $showTicket && canDownloadTicket($guest) ? asset('js/ticket.js') : null,
    ];
}

/**
 * Ghi JSON ra response; header đã gửi (lỗi giữa chừng) thì chỉ ghi nội dung.
 */
function sendJson(int $status, array $payload): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function respondJson(int $status, array $payload): never
{
    sendJson($status, $payload);
    exit;
}

/**
 * Form xác nhận tham dự / tra cứu vé đang tắt (RSVP_ENABLED=false): endpoint không xử lý gì.
 * JSON => 403; còn lại => về trang chính.
 */
function ensureRsvpEnabled(bool $wantsJson): void
{
    if (isRsvpEnabled()) {
        return;
    }

    if ($wantsJson) {
        respondJson(403, ['ok' => false]);
    }

    header('Location: ./', true, 302);
    exit;
}

function ensurePostRequest(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondJson(405, ['ok' => false, 'error' => 'Method không hợp lệ']);
    }
}

/**
 * Header bảo mật cho mọi response: trình duyệt không tự đoán kiểu nội dung (MIME sniffing)
 * và trang khác không nhúng được thiệp/form vào iframe (clickjacking).
 */
function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}

// ===== 7. Ngôn ngữ (vi / en) =====
// Nội dung hiển thị nằm ở lang/{locale}/messages.php. Server render theo ngôn ngữ trong cookie; trình duyệt
// đổi ngôn ngữ tại chỗ (public/js/i18n.js) theo các thuộc tính data-i18n do i18n() sinh ra.

/**
 * Ngôn ngữ của request: cookie LOCALE_COOKIE nếu hợp lệ, ngược lại DEFAULT_LOCALE.
 */
function locale(): string
{
    $value = $_COOKIE[LOCALE_COOKIE] ?? null;

    return is_string($value) && isset(LOCALES[$value]) ? $value : DEFAULT_LOCALE;
}

/**
 * Toàn bộ nội dung của một ngôn ngữ (lang/{locale}/messages.php), nạp một lần mỗi request.
 */
function translations(string $locale): array
{
    static $loaded = [];

    return $loaded[$locale] ??= require BASE_PATH . "/lang/{$locale}/messages.php";
}

/**
 * Giá trị tại khoá 'nhóm.khoá' của đúng một ngôn ngữ (chuỗi hoặc danh sách), không có => null.
 */
function translationValue(string $key, string $locale): mixed
{
    $value = translations($locale);

    foreach (explode('.', $key) as $part) {
        $value = is_array($value) ? ($value[$part] ?? null) : null;
    }

    return $value;
}

/**
 * Nội dung theo khoá, chưa thay placeholder. Ngôn ngữ đang chọn thiếu khoá => bản DEFAULT_LOCALE;
 * cả hai đều thiếu => null (dùng cho nội dung tuỳ chọn như chú thích ảnh).
 */
function findMessage(string $key, ?string $locale = null): ?string
{
    $locale ??= locale();
    $value = translationValue($key, $locale);

    if (!is_string($value) && $locale !== DEFAULT_LOCALE) {
        $value = translationValue($key, DEFAULT_LOCALE);
    }

    return is_string($value) ? $value : null;
}

/**
 * Nội dung hiển thị theo khoá 'nhóm.khoá' ở ngôn ngữ $locale (mặc định: ngôn ngữ đang chọn), thay {placeholder}
 * bằng $replace. Giá trị thay có thể là [locale => chuỗi] khi khác nhau theo ngôn ngữ (vd ngày tháng).
 * Thiếu khoá => bản DEFAULT_LOCALE; vẫn thiếu => trả lại chính khoá. Cả hai trường hợp đều ghi log.
 */
function message(string $key, array $replace = [], ?string $locale = null): string
{
    $locale ??= locale();
    $value = findMessage($key, $locale);

    if ($value === null) {
        \App\Log\Logger::warning('Thiếu khoá trong lang/', ['key' => $key, 'locale' => $locale]);

        return $key;
    }

    if ($locale !== DEFAULT_LOCALE && !is_string(translationValue($key, $locale))) {
        \App\Log\Logger::notice('Thiếu khoá trong lang/, dùng bản ' . DEFAULT_LOCALE, ['key' => $key, 'locale' => $locale]);
    }

    $pairs = [];

    foreach ($replace as $name => $replacement) {
        if (is_array($replacement)) {
            $replacement = $replacement[$locale] ?? $replacement[DEFAULT_LOCALE] ?? '';
        }

        $pairs['{' . $name . '}'] = (string) $replacement;
    }

    return strtr($value, $pairs);
}

/**
 * Danh sách nội dung tại khoá (vd recap.beginning.goals) theo ngôn ngữ đang chọn, thiếu => bản DEFAULT_LOCALE.
 */
function messageList(string $key): array
{
    $value = translationValue($key, locale());

    if (!is_array($value)) {
        $value = translationValue($key, DEFAULT_LOCALE);
    }

    return is_array($value) ? $value : [];
}

/**
 * Thông báo dịch lúc hiển thị (lỗi form, kết quả tra cứu): giữ khoá + tham số thay vì chuỗi đã dịch để view
 * gắn data-i18n, trình duyệt dịch lại khi khách đổi ngôn ngữ. Hiển thị: message(...$ref), i18n(...$ref).
 */
function messageRef(string $key, array $replace = []): array
{
    return ['key' => $key, 'replace' => $replace];
}

/**
 * Giá trị theo từng ngôn ngữ: [locale => $format(locale)], dùng làm tham số của message() / i18n().
 */
function localized(callable $format): array
{
    $values = [];

    foreach (array_keys(LOCALES) as $locale) {
        $values[$locale] = $format($locale);
    }

    return $values;
}

/**
 * Ngày theo định dạng của ngôn ngữ (lang/: date.format), vd 18/12/2026 (d/m/Y cho mọi ngôn ngữ).
 */
function formatDate(DateTimeInterface $date, ?string $locale = null): string
{
    return message('date.format', [
        'd'     => $date->format('j'),
        'dd'    => $date->format('d'),
        'mm'    => $date->format('m'),
        'yyyy'  => $date->format('Y'),
        'month' => message('date.months.' . $date->format('n'), [], $locale),
    ], $locale);
}

/**
 * Thuộc tính data-i18n (có khoảng trắng đầu) cho phần tử hiển thị nội dung từ lang/, để public/js/i18n.js dịch
 * lại khi khách đổi ngôn ngữ mà không tải lại trang.
 * $key: nội dung chữ của phần tử (phần tử chỉ chứa chữ); null => không dịch nội dung.
 * $attrs: [thuộc tính => khoá], vd ['placeholder' => 'email_placeholder'].
 * $replace: tham số như message(), dùng chung cho nội dung và các thuộc tính.
 */
function i18n(?string $key, array $replace = [], array $attrs = []): string
{
    $html = $key !== null ? ' data-i18n="' . e($key) . '"' : '';

    if ($attrs !== []) {
        $pairs = [];

        foreach ($attrs as $attr => $attrKey) {
            $pairs[] = "{$attr}:{$attrKey}";
        }

        $html .= ' data-i18n-attr="' . e(implode(';', $pairs)) . '"';
    }

    if ($html !== '' && $replace !== []) {
        $html .= ' data-i18n-args="' . e(json_encode($replace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"';
    }

    return $html;
}

/**
 * Nội dung một ngôn ngữ dạng phẳng ['nhóm.khoá' => chuỗi] cho public/js/i18n.js.
 */
function flattenMessages(array $messages, string $prefix = ''): array
{
    $flat = [];

    foreach ($messages as $key => $value) {
        $path = $prefix . $key;

        if (is_array($value)) {
            $flat += flattenMessages($value, "{$path}.");
        } else {
            $flat[$path] = (string) $value;
        }
    }

    return $flat;
}

// ===== Khởi động =====

// Ghi PHP error, exception chưa catch và fatal error vào storage/logs/error-YYYY-MM-DD.log (src/Log/Logger.php)
\App\Log\Logger::register(STORAGE_PATH . '/logs');

loadEnv(BASE_PATH . '/.env');

// Không hiện lỗi (đường dẫn, stack trace) cho người dùng; chi tiết đã có trong log. Dev: APP_DEBUG=true trong .env
ini_set('display_errors', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Ho_Chi_Minh');

sendSecurityHeaders();
