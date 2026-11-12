<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Log\Logger;

$event = loadEvent();
$id = $_GET['id'] ?? '';
$employee = null;
$rsvp = null;
$noticeUnavailable = false;

// Có id => phải là uuid có trong data.json (khách có sẵn hoặc khách tự đăng ký);
// không có id => thiệp chung với form tự đăng ký và tra cứu vé
if ($id !== '') {
    try {
        $invitation = findInvitation($id);
    } catch (RuntimeException $e) {
        Logger::error($e);
        $invitation = null;
        // Không đọc được dữ liệu: báo lỗi tạm thời thay vì báo link sai
        $noticeUnavailable = true;
    }

    if ($invitation === null) {
        http_response_code($noticeUnavailable ? 503 : 404);
        require BASE_PATH . '/views/invalid.php';
        exit;
    }

    $employee = $invitation['guest'];
    $rsvp = $invitation['rsvp'];
}

$recap = loadConfig('recap');

// Chỉ người đã xác nhận "attending" và đã có mã Lucky Draw + số bàn mới thấy vé
$showTicket = $employee !== null && isAttending($rsvp) && canDownloadTicket($employee);

require BASE_PATH . '/views/invite.php';
