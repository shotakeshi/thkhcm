<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Rsvp\RsvpLog;

$event = loadEvent();
$id = $_GET['id'] ?? '';
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// Xác nhận tham dự nằm trong trang chính: mở thẳng rsvp.php thì về đúng section
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $query = is_string($id) && isValidUuid(trim($id)) ? inviteQuery(strtolower(trim($id))) : '';
    header('Location: ./' . $query . '#xac-nhan', true, 302);
    exit;
}

$draft = [
    'name'       => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
    'email'      => is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '',
    'attendance' => is_string($_POST['attendance'] ?? null) ? $_POST['attendance'] : '',
];
$errors = [];
$status = 422;
$invitation = null;

if ($id !== '') {
    // Link mời: tên và email lấy từ dữ liệu khách, bỏ qua giá trị form gửi lên
    try {
        $invitation = findInvitation($id);
    } catch (RuntimeException $e) {
        error_log('rsvp.php: ' . $e->getMessage());
        $status = 500;
        $errors['form'] = message('rsvp.errors.save_failed');
    }

    if ($invitation === null && $errors === []) {
        if ($wantsJson) {
            respondJson(404, ['ok' => false]);
        }

        http_response_code(404);
        require BASE_PATH . '/views/invalid.php';
        exit;
    }
}

if ($errors === []) {
    $guest = $invitation['guest'] ?? null;
    $name = $guest !== null ? stringField($guest, 'name') : $draft['name'];
    $email = $guest !== null ? $guest['email'] : emailInput($draft['email']);

    if ($guest === null && $name === '') {
        $errors['name'] = message('rsvp.errors.name_required');
    } elseif ($guest === null && mb_strlen($name) > NAME_MAX_LENGTH) {
        $errors['name'] = message('rsvp.errors.name_too_long', ['max' => NAME_MAX_LENGTH]);
    }

    if ($guest === null && $draft['email'] === '') {
        $errors['email'] = message('errors.email_required');
    } elseif ($email === null) {
        $errors['email'] = message('errors.email_invalid');
    }

    if (!in_array($draft['attendance'], [RsvpLog::ATTENDING, RsvpLog::DECLINED], true)) {
        $errors['attendance'] = message('rsvp.errors.attendance_required');
    }
}

if ($errors === []) {
    try {
        $result = rsvpLog()->submit($email, $name, $draft['attendance']);
    } catch (RuntimeException $e) {
        error_log('rsvp.php: ' . $e->getMessage());
        $status = 500;
        $errors['form'] = message('rsvp.errors.save_failed');
    }
}

if ($errors === []) {
    $showTicket = isAttending($result['rsvp']) && canDownloadTicket($result['guest']);
    // Đã xác nhận trước đó: không ghi đè, chỉ hiện lại trạng thái hiện có
    $notice = $result['created'] ? null : message('rsvp.already_responded');

    if ($wantsJson) {
        respondJson(200, [
            'ok'       => true,
            'created'  => $result['created'],
            'rsvpHtml' => renderPartial('rsvp-card.php', [
                'event'      => $event,
                'employee'   => $result['guest'],
                'rsvp'       => $result['rsvp'],
                'rsvpErrors' => [],
                'rsvpDraft'  => [],
                'rsvpNotice' => $notice,
            ]),
        ] + ticketPayload($event, $result['guest'], $showTicket));
    }

    // PRG: tải lại trang không gửi trùng; link cá nhân hiện trạng thái và vé
    header('Location: ./' . inviteQuery($result['uuid']) . ($showTicket ? '#ve' : '#xac-nhan'), true, 303);
    exit;
}

// Lỗi: giữ trạng thái hiện có của link mời (nếu có) và giá trị vừa nhập
$employee = $invitation['guest'] ?? null;
$rsvp = $invitation['rsvp'] ?? null;

if ($wantsJson) {
    respondJson($status, [
        'ok'       => false,
        'errors'   => $errors,
        'rsvpHtml' => renderPartial('rsvp-card.php', [
            'event'      => $event,
            'employee'   => $employee,
            'rsvp'       => $rsvp,
            'rsvpErrors' => $errors,
            'rsvpDraft'  => $draft,
            'rsvpNotice' => null,
        ]),
    ]);
}

http_response_code($status);

$recap = loadConfig('recap');
$showTicket = $employee !== null && isAttending($rsvp) && canDownloadTicket($employee);
$rsvpErrors = $errors;
$rsvpDraft = $draft;
$opened = true;

require BASE_PATH . '/views/invite.php';
