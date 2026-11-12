<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Log\Logger;

$event = loadEvent();
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

ensureRsvpEnabled($wantsJson);

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
    $error = messageRef('errors.email_required');
} elseif ($email === null) {
    $status = 422;
    $error = messageRef('errors.email_invalid');
} else {
    // Tra cứu trực tiếp trong data.json (khách có sẵn và khách tự đăng ký)
    try {
        $guest = guestStore()->findByEmail($email);
        $found = $guest !== null ? invitation($guest) : null;
    } catch (RuntimeException $e) {
        Logger::error($e);
        $status = 500;
        $error = messageRef('lookup.errors.failed');
    }
}

if ($error === null && $found === null) {
    $message = messageRef('lookup.not_found');
} elseif ($error === null && $found['rsvp'] === null) {
    $message = messageRef('lookup.not_responded');
} elseif ($error === null && !isAttending($found['rsvp'])) {
    $message = messageRef('lookup.declined');
} elseif ($error === null && !canDownloadTicket($found['guest'])) {
    // Chưa có mã Lucky Draw hoặc số bàn: chưa hiện vé
    $message = messageRef('lookup.ticket_pending');
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
