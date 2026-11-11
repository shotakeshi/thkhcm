<?php

/**
 * Admin sửa mã Lucky Draw và số bàn của một khách (gọi từ js/admin-ticket.js).
 * POST: token, uuid, luckyDrawCode, tableNumber (rỗng = chưa có),
 *       expectedLuckyDrawCode, expectedTableNumber (giá trị admin đang thấy, để không ghi đè thay đổi mới hơn).
 * Trả JSON {ok, html, error?}: html là phần của row bị ảnh hưởng
 * {ticket (cột Vé), status (badge "Chưa có vé")},
 * null khi khách không còn trong danh sách.
 */

require dirname(__DIR__) . '/bootstrap.php';

const TICKET_MAX_LENGTH = 50;

header('X-Robots-Tag: noindex, nofollow');

ensurePostRequest();

if (!isAdminToken($_POST['token'] ?? null)) {
    respondJson(403, ['ok' => false, 'error' => 'Không có quyền sửa vé']);
}

/**
 * Chuỗi POST đã trim; sai kiểu => null.
 */
function postedString(string $key): ?string
{
    $value = $_POST[$key] ?? null;

    return is_string($value) ? trim($value) : null;
}

/**
 * Giá trị mới admin nhập: UTF-8 hợp lệ, không ký tự điều khiển, tối đa TICKET_MAX_LENGTH ký tự; sai => null.
 */
function ticketInput(string $key): ?string
{
    $value = postedString($key);

    return $value !== null
        && preg_match('/^\P{C}*$/u', $value) === 1
        && mb_strlen($value) <= TICKET_MAX_LENGTH
        ? $value
        : null;
}

/**
 * Phần của row theo khách hiện tại, null khi khách không còn trong danh sách.
 *
 * @return array{ticket: string, status: string}|null
 */
function ticketRowHtml(?array $guest): ?array
{
    if ($guest === null) {
        return null;
    }

    return [
        'ticket' => trim(renderPartial('admin-ticket.php', ['guest' => $guest])),
        // Badge "Chưa có vé" phụ thuộc vào vé
        'status' => trim(renderPartial('admin-rsvp.php', ['entry' => invitation($guest), 'slot' => 'status'])),
    ];
}

$uuid = is_string($_POST['uuid'] ?? null) ? strtolower(trim($_POST['uuid'])) : '';
$ticket = ['luckyDrawCode' => ticketInput('luckyDrawCode'), 'tableNumber' => ticketInput('tableNumber')];
$expected = [
    'luckyDrawCode' => postedString('expectedLuckyDrawCode'),
    'tableNumber'   => postedString('expectedTableNumber'),
];

if (!isValidUuid($uuid) || in_array(null, $expected, true)) {
    respondJson(422, ['ok' => false, 'error' => 'Yêu cầu không hợp lệ']);
}

if (in_array(null, $ticket, true)) {
    respondJson(422, [
        'ok'    => false,
        'error' => 'Mã Lucky Draw và số bàn tối đa ' . TICKET_MAX_LENGTH . ' ký tự, không chứa ký tự điều khiển',
    ]);
}

try {
    // Kiểm tra giá trị hiện tại, mã trùng và ghi nằm trong cùng lock của GuestStore
    $result = guestStore()->updateTicket($uuid, $ticket, $expected);
    $html = ticketRowHtml(guestStore()->findByUuid($uuid));
} catch (RuntimeException $e) {
    error_log('admin-ticket.php: ' . $e->getMessage());
    respondJson(500, ['ok' => false, 'error' => 'Không lưu được vé, vui lòng thử lại sau']);
}

match ($result) {
    null => respondJson(404, ['ok' => false, 'error' => 'Không tìm thấy khách, hãy tải lại trang', 'html' => $html]),
    'conflict' => respondJson(409, [
        'ok'    => false,
        'error' => 'Vé của khách vừa được sửa ở nơi khác, danh sách đã cập nhật giá trị mới. Bấm Lưu lần nữa để ghi đè.',
        'html'  => $html,
    ]),
    'duplicate' => respondJson(422, [
        'ok'    => false,
        'error' => "Mã Lucky Draw {$ticket['luckyDrawCode']} đã được dùng cho khách khác",
        'html'  => $html,
    ]),
    default => null,
};

if ($result === 'saved') {
    error_log(sprintf(
        'admin-ticket: khách %s luckyDrawCode "%s" -> "%s", tableNumber "%s" -> "%s"',
        $uuid,
        $expected['luckyDrawCode'],
        $ticket['luckyDrawCode'],
        $expected['tableNumber'],
        $ticket['tableNumber']
    ));
}

respondJson(200, ['ok' => true, 'html' => $html]);
