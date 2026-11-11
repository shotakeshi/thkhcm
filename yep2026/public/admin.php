<?php

require dirname(__DIR__) . '/bootstrap.php';

header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

$adminToken = $_GET['token'] ?? null;

if (!isAdminToken($adminToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden');
}

try {
    $guests = guestStore()->all();
    // Record sai cấu trúc trong data.json: không hiện trong danh sách, khách đó không xác nhận/tra cứu được
    $invalidRecords = guestStore()->invalidRecords();
    $employees = loadEmployees();
} catch (RuntimeException $e) {
    error_log('admin.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Không đọc được dữ liệu khách, vui lòng thử lại sau.');
}
$mailTestRecipient = mailTestRecipient();

$missingUuidEmails = array_keys(array_filter(
    $employees,
    fn (array $employee) => employeeUuid($employee) === null
));
// data.json theo uuid nên uuid không thể trùng; email trùng (kể cả giữa khách có sẵn và khách tự đăng ký)
// thì chỉ một khách hiện trong danh sách nhân viên
$duplicateEmails = findDuplicateValues($guests, 'email');
$duplicateCodes = findDuplicateValues($employees, 'luckyDrawCode');

$sentLogError = null;
try {
    $sent = sentLog()->all();
} catch (RuntimeException $e) {
    error_log('admin.php: ' . $e->getMessage());
    $sent = [];
    $sentLogError = $e->getMessage();
}

// Xác nhận tham dự nằm cùng record khách trong data.json
$rsvpEntries = array_map('invitation', $guests);

// Khách tự đăng ký (guestType "1"): chưa có mã Lucky Draw, số bàn và thư mời
$selfRegistered = array_filter(
    $rsvpEntries,
    fn (array $entry) => $entry['guest']['guestType'] === \App\Rsvp\GuestStore::GUEST_SELF_REGISTERED
);
$missingTicketEmails = array_keys(array_filter(
    $employees,
    fn (array $employee) => isAttending($rsvpEntries[$employee['uuid']]['rsvp'] ?? null)
        && !canDownloadTicket($employee)
));

// Một cột của phần xác nhận tham dự trong row; không có entry => để trống
$rsvpSlot = fn (?array $entry, string $slot): string => $entry !== null
    ? renderPartial('admin-rsvp.php', ['entry' => $entry, 'slot' => $slot])
    : '';

// Cột Vé: mã Lucky Draw, số bàn và nút sửa (khách tự đăng ký luôn thiếu tới khi admin nhập)
$ticketCell = fn (array $guest): string => renderPartial('admin-ticket.php', ['guest' => $guest]);

// Tiêu đề cột dùng chung cho các danh sách (ẩn trên mobile)
$peopleHead = '<div class="people-head" aria-hidden="true">'
    . '<span></span><span>Khách</span><span>Vé</span><span>Trạng thái</span><span>Thời gian</span><span>Thao tác</span>'
    . '</div>';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Thư mời YEP 2026</title>
    <link href="<?= asset('css/fonts-admin.css') ?>" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" >
    <link href="css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body
    data-sending-locked="<?= $sentLogError !== null ? 'true' : 'false' ?>"
    data-admin-token="<?= e($adminToken) ?>"
>
<header class="nav-bar">
    <div class="nav-inner">
        <span class="nav-brand">
            <img src="images/thk-logo.png" alt="THK Holdings Vietnam" width="131" height="24">
        </span>
        <span class="nav-meta">Year End Party 2026</span>
    </div>
</header>

<main class="page">
    <?php if ($mailTestRecipient !== ''): ?>
        <div class="test-banner">
            Chế độ thử nghiệm đang bật.
        </div>
    <?php endif; ?>

    <?php if ($sentLogError !== null): ?>
        <div class="test-banner is-danger" role="alert">
            Đã khoá gửi email vì không đọc được nhật ký gửi. Chi tiết lỗi đã được ghi vào log máy chủ.
        </div>
    <?php endif; ?>

    <?php if ($missingUuidEmails !== []): ?>
        <div class="test-banner is-danger" role="alert">
            <?= count($missingUuidEmails) ?> nhân viên thiếu hoặc sai định dạng
            <code>uuid</code>, sẽ không gửi được thư mời.
            <br><small><?= e(implode(', ', $missingUuidEmails)) ?></small>
        </div>
    <?php endif; ?>

    <?php if ($duplicateEmails !== []): ?>
        <div class="test-banner is-danger" role="alert">
            Trùng <code>email</code> trong data.json, các khách này
            sẽ không xác nhận tham dự hay tra cứu vé được.
            <?php foreach ($duplicateEmails as $email => $uuids): ?>
                <br><small><?= e($email) ?>: <?= e(implode(', ', $uuids)) ?></small>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($duplicateCodes !== []): ?>
        <div class="test-banner" role="alert">
            Trùng <code>luckyDrawCode</code>, kiểm tra lại data.json.
            <?php foreach ($duplicateCodes as $code => $emails): ?>
                <br><small><?= e(strtoupper($code)) ?>: <?= e(implode(', ', $emails)) ?></small>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($invalidRecords !== []): ?>
        <div class="test-banner is-danger" role="alert">
            <?= count($invalidRecords) ?> record trong <code>data.json</code> sai cấu trúc, đang bị ẩn;
            các khách này không xác nhận tham dự hay tra cứu vé được.
            <?php foreach ($invalidRecords as $key => $reason): ?>
                <br><small><?= e($key) ?>: <?= e($reason) ?></small>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($missingTicketEmails !== []): ?>
        <div class="test-banner" role="alert">
            <?= count($missingTicketEmails) ?> khách đã xác nhận tham gia nhưng thiếu
            <code>luckyDrawCode</code> hoặc <code>tableNumber</code>, chưa tải được vé.
            <br><small><?= e(implode(', ', $missingTicketEmails)) ?></small>
        </div>
    <?php endif; ?>

    <?php $rsvpEnabled = isRsvpEnabled(); ?>
    <section class="hero">
        <h1 class="hero-title">Year End Party 2026</h1>

        <div class="rsvp-switch">
            <label class="form-check form-switch" for="rsvpEnabledSwitch">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="rsvpEnabledSwitch"
                    <?= $rsvpEnabled ? 'checked' : '' ?>
                >
                <span class="form-check-label">
                    Form xác nhận tham dự và tra cứu vé trên trang chính:
                    <strong id="rsvpEnabledState"><?= $rsvpEnabled ? 'Đang mở' : 'Đang ẩn' ?></strong>
                </span>
            </label>
        </div>

        <div class="hero-actions">
            <?php
            //<button id="btnBulk" class="btn-pill"></button>
            //<button id="btnRetryFailed" class="btn-link-apple"></button>
             ?>
        </div>

		<?php
//        <div class="legend" role="status" aria-live="polite">
//            <span class="legend-item">
//                <span class="legend-dot" style="background: var(--green)"></span>Đã gửi<strong id="countSent">0</strong>
//            </span>
//            <span class="legend-item">
//                <span class="legend-dot" style="background: var(--red)"></span>Lỗi<strong id="countFailed">0</strong>
//            </span>
//            <span class="legend-item">
//                <span class="legend-dot" style="background: #c7c7cc"></span>Chưa gửi<strong id="countPending">0</strong>
//            </span>
//        </div>
	  	?>

        <div class="legend" role="status" aria-live="polite">
            <span class="legend-label">Xác nhận tham dự</span>
            <span class="legend-item">
                <span class="legend-dot" style="background: var(--green)"></span>Tham gia<strong id="countAttending">0</strong>
            </span>
            <span class="legend-item">
                <span class="legend-dot" style="background: var(--red)"></span>Không tham gia<strong id="countDeclined">0</strong>
            </span>
            <span class="legend-item">
                <span class="legend-dot" style="background: #c7c7cc"></span>Chưa phản hồi<strong id="countNoReply">0</strong>
            </span>
            <a class="btn-link-apple legend-link" href="admin-export.php?token=<?= e(rawurlencode($adminToken)) ?>">
                <i class="bi bi-download"></i> Tải CSV
            </a>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2 class="section-title">Nhân viên</h2>
            <span class="section-note"><?= count($employees) ?> người</span>
        </div>

        <?php if (count($employees) === 0): ?>
            <div class="people empty-state">
                Chưa có nhân viên nào. Thêm email vào data.json rồi tải lại trang.
            </div>
        <?php else: ?>
            <?= $peopleHead ?>
            <ul class="people">
                <?php $index = 0; foreach ($employees as $email => $employee): ?>
                    <?php $entry = $rsvpEntries[$employee['uuid']] ?? null; ?>
                    <li class="person"
                        data-uuid="<?= e($employee['uuid']) ?>"
                        data-email="<?= e($email) ?>"
                        data-status="<?= isset($sent[$email]) ? 'sent' : 'pending' ?>"
                        data-sent-at="<?= e($sent[$email] ?? '') ?>">
                        <div class="person-index"><?= ++$index ?></div>
                        <div class="person-main">
                            <div class="person-name">
                                <?= e($employee['name'] ?? '') ?>
                                <span class="person-code"><?= e($employee['code'] ?? '') ?></span>
                            </div>
                            <div class="person-email"><?= e($email) ?></div>
                        </div>
                        <?= $ticketCell($employee) ?>
                        <div class="person-status">
<!--                            <div class="status-cell"></div>-->
                            <?= $rsvpSlot($entry, 'status') ?>
                        </div>
                        <div class="person-times">
                            <div class="time-row time-sent" hidden></div>
                            <?= $rsvpSlot($entry, 'time') ?>
                        </div>
                        <div class="person-actions">
                           <?php
//                            <button
//                                class="icon-btn btn-resend"
//                                title="Gửi lại thư mời"
/*                                aria-label="Gửi lại thư mời cho <?= e($email) ?>"*/
//                            >
//                                <i class="bi bi-arrow-clockwise"></i>
//                            </button>
                           ?>
                            <?= $rsvpSlot($entry, 'actions') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($selfRegistered !== []): ?>
        <section class="section">
            <div class="section-head">
                <h2 class="section-title">Khách tự đăng ký</h2>
                <span class="section-note"><span id="selfRegisteredCount"><?= count($selfRegistered) ?></span> người</span>
            </div>
            <p class="section-hint"></p>
            <?= $peopleHead ?>
            <ul class="people" id="selfRegisteredList">
                <?php $index = 0; foreach ($selfRegistered as $uuid => $entry): ?>
                    <li class="person" data-uuid="<?= e($uuid) ?>">
                        <div class="person-index"><?= ++$index ?></div>
                        <div class="person-main">
                            <div class="person-name"><?= e(stringField($entry['guest'], 'name')) ?></div>
                            <div class="person-email"><?= e($entry['guest']['email']) ?></div>
                        </div>
                        <?= $ticketCell($entry['guest']) ?>
                        <div class="person-status"><?= $rsvpSlot($entry, 'status') ?></div>
                        <div class="person-times"><?= $rsvpSlot($entry, 'time') ?></div>
                        <!-- Khách tự đăng ký chưa có thư mời: chừa ô của nút Gửi lại để các nút thẳng cột -->
                        <div class="person-actions"><span class="icon-btn-spacer"></span><?= $rsvpSlot($entry, 'actions') ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>

<dialog class="ticket-dialog" id="ticketDialog" aria-labelledby="ticketDialogTitle">
    <form class="ticket-form" id="ticketForm" novalidate>
        <h2 class="ticket-dialog-title" id="ticketDialogTitle">Cập nhật vé tham dự</h2>
        <p class="ticket-dialog-guest" id="ticketDialogGuest"></p>
        <label class="ticket-field">
            <span>Mã Lucky Draw</span>
            <input type="text" name="luckyDrawCode" maxlength="50" autocomplete="off" spellcheck="false">
        </label>
        <label class="ticket-field">
            <span>Số bàn</span>
            <input type="text" name="tableNumber" maxlength="50" autocomplete="off" spellcheck="false">
        </label>
        <p class="ticket-dialog-hint">Khách chỉ tải được vé khi có đủ cả hai.</p>
        <p class="ticket-dialog-error" id="ticketDialogError" role="alert" hidden></p>
        <div class="ticket-dialog-actions">
            <button type="button" class="btn-link-apple" data-ticket-cancel>Huỷ</button>
            <button type="submit" class="btn-pill">Lưu</button>
        </div>
    </form>
</dialog>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/admin-settings.js') ?>"></script>
<script src="<?= asset('js/admin-rsvp.js') ?>"></script>
<script src="<?= asset('js/admin-ticket.js') ?>"></script>
</body>
</html>
