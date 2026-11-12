<?php

/**
 * Admin bật/tắt form xác nhận tham dự và tra cứu vé (gọi từ js/admin-settings.js).
 * POST: token, rsvpEnabled = 1|0.
 * Trả JSON {ok, rsvpEnabled, error?}: rsvpEnabled là trạng thái hiện hành sau khi xử lý.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Log\Logger;
use App\Settings\Settings;

header('X-Robots-Tag: noindex, nofollow');

ensurePostRequest();

if (!isAdminToken($_POST['token'] ?? null)) {
    respondJson(403, ['ok' => false, 'error' => 'Không có quyền đổi cài đặt']);
}

$value = $_POST['rsvpEnabled'] ?? null;

if (!in_array($value, ['1', '0'], true)) {
    respondJson(422, ['ok' => false, 'error' => 'Yêu cầu không hợp lệ', 'rsvpEnabled' => isRsvpEnabled()]);
}

try {
    settings()->set(Settings::RSVP_ENABLED, $value === '1');
} catch (RuntimeException $e) {
    Logger::error($e, ['rsvpEnabled' => $value === '1']);
    respondJson(500, [
        'ok'          => false,
        'error'       => 'Không lưu được cài đặt, vui lòng thử lại sau',
        'rsvpEnabled' => isRsvpEnabled(),
    ]);
}

respondJson(200, ['ok' => true, 'rsvpEnabled' => isRsvpEnabled()]);
