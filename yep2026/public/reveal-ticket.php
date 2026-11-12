<?php
require dirname(__DIR__) . '/bootstrap.php';

use App\Log\Logger;

header('X-Robots-Tag: noindex, nofollow');

ensurePostRequest();

$uuid = is_string($_POST['uuid'] ?? null) ? strtolower(trim($_POST['uuid'])) : '';

if (!isValidUuid($uuid)) {
    respondJson(422, ['ok' => false, 'isRevealed' => false]);
}

try {
    $result = guestStore()->reveal($uuid);
} catch (RuntimeException $e) {
    Logger::error($e, ['uuid' => $uuid]);
    respondJson(500, ['ok' => false, 'isRevealed' => false]);
}

if ($result === null) {
    respondJson(404, ['ok' => false, 'isRevealed' => false]);
}

// Chưa xác nhận tham gia hoặc chưa được gán mã: vé chưa hiện
if ($result['status'] !== 'revealed') {
    respondJson(409, ['ok' => false, 'isRevealed' => false]);
}

if ($result['created']) {
    Logger::info('Khách lấy mã bàn', ['uuid' => $uuid]);
}

respondJson(200, [
    'ok'            => true,
    'isRevealed'    => true,
    'luckyDrawCode' => stringField($result['guest'], 'luckyDrawCode'),
    'tableNumber'   => stringField($result['guest'], 'tableNumber'),
]);
