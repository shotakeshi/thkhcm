<?php

/**
 * Admin đổi trạng thái hoặc xoá xác nhận tham dự của một khách (gọi từ js/admin-rsvp.js).
 * POST: token, uuid (uuid của dòng trên admin.php), action = attendance|delete,
 *       attendance (khi action = attendance).
 * Trả JSON {ok, html, error?}: html là phần RSVP mới của row theo cột
 * {status, time, actions}, null khi khách không còn trong data.json.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Log\Logger;
use App\Rsvp\GuestStore;

const ACTIONS = ['attendance', 'delete'];

header('X-Robots-Tag: noindex, nofollow');

ensurePostRequest();

if (!isAdminToken($_POST['token'] ?? null)) {
    respondJson(403, ['ok' => false, 'error' => 'Không có quyền sửa xác nhận tham dự']);
}

$uuid = is_string($_POST['uuid'] ?? null) ? strtolower(trim($_POST['uuid'])) : '';
$action = $_POST['action'] ?? null;
$attendance = $_POST['attendance'] ?? null;

if (
    !isValidUuid($uuid)
    || !in_array($action, ACTIONS, true)
    || ($action === 'attendance' && !in_array($attendance, [GuestStore::ATTENDING, GuestStore::DECLINED], true))
) {
    respondJson(422, ['ok' => false, 'error' => 'Yêu cầu không hợp lệ']);
}

/**
 * Phần RSVP hiện tại của row theo cột (views/partials/admin-rsvp.php),
 * null khi khách không còn trong danh sách.
 *
 * @return array{status: string, time: string, actions: string}|null
 */
function rsvpRowHtml(?array $entry): ?array
{
    if ($entry === null) {
        return null;
    }

    $html = [];

    foreach (['status', 'time', 'actions'] as $slot) {
        $html[$slot] = trim(renderPartial('admin-rsvp.php', ['entry' => $entry, 'slot' => $slot]));
    }

    return $html;
}

/**
 * Khách hiện tại trong data.json theo dạng invitation(), null khi không còn.
 */
function currentEntry(string $uuid): ?array
{
    $guest = guestStore()->findByUuid($uuid);

    return $guest !== null ? invitation($guest) : null;
}

try {
    if (currentEntry($uuid) === null) {
        respondJson(404, ['ok' => false, 'error' => 'Không tìm thấy khách, hãy tải lại trang', 'html' => null]);
    }

    // Kiểm tra trạng thái và ghi nằm trong cùng lock của GuestStore
    $before = match ($action) {
        'attendance' => guestStore()->changeAttendance($uuid, $attendance),
        'delete'     => guestStore()->resetAttendance($uuid),
    };

    $entry = currentEntry($uuid);

    if ($before === null) {
        // Khách chưa xác nhận hoặc record vừa bị xoá ở nơi khác: trả trạng thái hiện tại
        respondJson(409, [
            'ok'    => false,
            'error' => 'Khách chưa có xác nhận tham dự để sửa',
            'html'  => rsvpRowHtml($entry),
        ]);
    }

    $after = $action === 'delete' ? 'deleted' : $attendance;

    if ($before !== $after) {
        Logger::info('Admin đổi xác nhận tham dự', ['uuid' => $uuid, 'from' => $before, 'to' => $after]);
    }
} catch (RuntimeException $e) {
    Logger::error($e, ['uuid' => $uuid, 'action' => $action]);
    respondJson(500, ['ok' => false, 'error' => 'Không lưu được xác nhận, vui lòng thử lại sau']);
}

respondJson(200, ['ok' => true, 'html' => rsvpRowHtml($entry)]);
