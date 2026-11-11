<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Mail\Mailer;
use App\Mail\MailTemplate;
use App\Mail\SentLog;

const SEND_TIME_LIMIT = 60;

/**
 * Bước đang xử lý, dùng để báo lỗi chính xác khi PHP dừng đột ngột
 * (timeout, fatal).
 * validating -> sending (đang nói chuyện với SMTP)
 *            -> recording (đã gửi xong, đang ghi nhật ký)
 */
$requestPhase = 'validating';

/**
 * Lỗi xảy ra sau khi có thể đã gửi mail => đánh dấu uncertain
 * để giao diện không tự gửi lại.
 */
function errorResponse(string $phase, string $message): array
{
    return match ($phase) {
        'sending' => [
            'ok'        => false,
            'uncertain' => true,
            'error'     => "{$message}. Không rõ email đã đi hay chưa, "
                . 'hãy kiểm tra hộp thư trước khi gửi lại.',
        ],
        'recording' => [
            'ok'        => false,
            'uncertain' => true,
            'error'     => "Đã gửi email nhưng không lưu được nhật ký: {$message}. Đừng gửi lại.",
        ],
        default => ['ok' => false, 'error' => $message],
    };
}

/**
 * Mọi lỗi (exception, fatal, timeout) đều trả JSON thay vì HTML để giao diện hiển thị đúng lý do.
 */
function registerJsonErrorHandlers(): void
{
    ini_set('display_errors', '0');
    set_time_limit(SEND_TIME_LIMIT);

    set_exception_handler(function (Throwable $e): void {
        global $requestPhase;

        error_log('send.php: ' . $e);
        sendJson(500, errorResponse($requestPhase, 'Lỗi máy chủ'));
    });

    register_shutdown_function(function (): void {
        global $requestPhase;

        $error = error_get_last();

        $fatalTypes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if ($error === null || !in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        error_log('send.php: ' . $error['message']);

        $message = str_contains($error['message'], 'Maximum execution time')
            ? 'Máy chủ SMTP không phản hồi sau ' . SEND_TIME_LIMIT . ' giây'
            : 'Lỗi máy chủ';

        sendJson(500, errorResponse($requestPhase, $message));
    });
}

function ensureAdminToken(): void
{
    if (!isAdminToken($_POST['token'] ?? null)) {
        respondJson(403, ['ok' => false, 'error' => 'Không có quyền gửi email']);
    }
}

/**
 * Lấy email từ request, chuẩn hoá lowercase và kiểm tra hợp lệ (cùng quy tắc với form RSVP / tra cứu vé).
 */
function readEmailInput(): string
{
    $email = emailInput($_POST['email'] ?? '');

    if ($email === null) {
        respondJson(422, ['ok' => false, 'error' => 'Email không hợp lệ']);
    }

    return $email;
}

/**
 * true khi người dùng đã xác nhận gửi lại email đã gửi trước đó.
 */
function isForceResend(): bool
{
    return ($_POST['force'] ?? '') === '1';
}

/**
 * Chặn gửi trùng, trừ khi người dùng đã xác nhận gửi lại.
 */
function ensureNotSentYet(SentLog $sentLog, string $email, bool $force): void
{
    $sentAt = $sentLog->all()[$email] ?? null;

    if ($sentAt !== null && !$force) {
        respondJson(409, [
            'ok' => false,
            'error' => 'Email đã được gửi trước đó',
            'sent_at' => $sentAt,
        ]);
    }
}

/**
 * uuid dùng cho link mời. Thiếu/sai uuid, trùng uuid hoặc chưa cấu hình APP_URL => không gửi (422).
 */
function readInviteUuid(string $email): string
{
    $uuid = employeeUuid(findEmployee($email) ?? []);

    if ($uuid === null) {
        error_log("send.php: {$email} chưa có uuid hợp lệ trong data.json");
        respondJson(422, [
            'ok' => false,
            'error' => 'Nhân viên chưa có trong danh sách khách mời hoặc thiếu mã thư mời',
        ]);
    }

    if (isset(findDuplicateValues(loadEmployees(), 'uuid')[$uuid])) {
        error_log("send.php: uuid {$uuid} bị trùng với nhân viên khác");
        respondJson(422, [
            'ok' => false,
            'error' => 'Mã thư mời của nhân viên này bị trùng với nhân viên khác',
        ]);
    }

    if (appUrl() === '') {
        error_log('send.php: chưa cấu hình APP_URL trong .env');
        respondJson(422, ['ok' => false, 'error' => 'Hệ thống chưa cấu hình đường dẫn thư mời']);
    }

    return $uuid;
}

/**
 * Tạo subject + body từ template. Email ngoài data.json thì dùng chính email làm tên.
 *
 * @return array{subject: string, body: string}
 */
function buildMail(string $email, string $uuid): array
{
    $employee = findEmployee($email) ?? [];

    return MailTemplate::fromFile(BASE_PATH . '/config/template.php')->render([
        'name'       => $employee['name'] ?? $email,
        'code'       => $employee['luckyDrawCode'] ?? '',
        'email'      => $email,
        'invite_url' => inviteUrl($uuid),
    ]);
}

/**
 * Gửi email qua SMTP. Chế độ test: chuyển về MAIL_TEST_TO và ghi email gốc vào subject.
 * Gửi lỗi thì trả 500, lý do chi tiết chỉ ghi log (Mailer).
 */
function sendMail(string $email, array $mail): void
{
    $recipient = $email;
    $subject = $mail['subject'];

    if (isMailTestMode()) {
        $recipient = mailTestRecipient();
        $subject = "[TEST -> {$email}] {$subject}";
    }

    $mailer = new Mailer(loadConfig('mail'));

    if (!$mailer->send($recipient, $subject, $mail['body'])) {
        respondJson(500, [
            'ok' => false,
            'error' => 'Gửi email thất bại',
        ]);
    }
}

header('Content-Type: application/json; charset=utf-8');
registerJsonErrorHandlers();

ensurePostRequest();
ensureAdminToken();

$email = readEmailInput();
$sentLog = sentLog();

ensureNotSentYet($sentLog, $email, isForceResend());

$uuid = readInviteUuid($email);

// Gửi thật thì phải ghi được nhật ký, kiểm tra trước để không gửi rồi mới phát hiện lỗi
if (!isMailTestMode()) {
    $sentLog->ensureWritable();
}

$mail = buildMail($email, $uuid);

$requestPhase = 'sending';
sendMail($email, $mail);

// Chế độ test không đánh dấu đã gửi
if (isMailTestMode()) {
    respondJson(200, ['ok' => true, 'test' => true]);
}

$requestPhase = 'recording';
$sentAt = $sentLog->markSent($email);

respondJson(200, ['ok' => true, 'sent_at' => $sentAt]);
