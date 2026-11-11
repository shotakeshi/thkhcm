<?php

require dirname(__DIR__) . '/bootstrap.php';

$event = loadEvent();
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// Tra cứu nằm trong trang chính: mở thẳng file thì về đúng section
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./#tim-ve', true, 302);
    exit;
}

$draft = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
$email = emailInput($draft);
$status = 200;
$error = null;
$message = null;
$found = null;

if ($draft === '') {
    $status = 422;
    $error = message('errors.email_required');
} elseif ($email === null) {
    $status = 422;
    $error = message('errors.email_invalid');
} else {
    try {
        $found = rsvpLog()->findByEmail($email);
    } catch (RuntimeException $e) {
        error_log('find-ticket.php: ' . $e->getMessage());
        $status = 500;
        $error = message('lookup.errors.failed');
    }
}

// Chưa có khách hoặc chưa RSVP: cùng một thông báo, không tiết lộ email có trong danh sách hay không
if ($error === null && ($found['rsvp'] ?? null) === null) {
    $message = message('lookup.not_found');
} elseif ($error === null && !isAttending($found['rsvp'])) {
    $message = message('thanks_declined');
} elseif ($error === null && !canDownloadTicket($found['guest'])) {
    // Chưa có mã Lucky Draw hoặc số bàn: chưa hiện vé
    $message = message('lookup.ticket_pending');
}

$showTicket = $error === null && $message === null;
$guest = $showTicket ? $found['guest'] : null;

$lookupHtml = renderPartial('find-ticket-card.php', [
    'lookupDraft'   => $draft,
    'lookupError'   => $error,
    'lookupMessage' => $message,
    'lookupFound'   => $showTicket,
]);

if ($wantsJson) {
    respondJson($status, ['ok' => $error === null, 'lookupHtml' => $lookupHtml]
        + ticketPayload($event, $guest, $showTicket));
}

// Không JS: có vé => mở link cá nhân của khách; còn lại hiện kết quả ngay trong section
if ($showTicket) {
    header('Location: ./' . inviteQuery($found['uuid']) . '#ve', true, 303);
    exit;
}

http_response_code($status);

$recap = loadConfig('recap');
$employee = null;
$rsvp = null;
$lookupCardHtml = $lookupHtml;
$opened = true;

require BASE_PATH . '/views/invite.php';
