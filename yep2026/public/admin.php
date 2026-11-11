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

$employees = loadEmployees();
$mailTestRecipient = mailTestRecipient();

$missingUuidEmails = array_keys(array_filter(
    $employees,
    fn (array $employee) => employeeUuid($employee) === null
));
// data.json theo uuid nên uuid không thể trùng; email trùng thì chỉ một khách hiện trong danh sách
$duplicateEmails = findDuplicateValues(guestList()->all(), 'email');
$duplicateCodes = findDuplicateValues($employees, 'luckyDrawCode');

$sentLogError = null;
try {
    $sent = sentLog()->all();
} catch (RuntimeException $e) {
    $sent = [];
    $sentLogError = $e->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Thư mời YEP 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            Tất cả email sẽ được chuyển đến
            <code><?= e($mailTestRecipient) ?></code> .
            Email chưa được gửi đến người nhận thực tế.
        </div>
    <?php endif; ?>

    <?php if ($sentLogError !== null): ?>
        <div class="test-banner is-danger" role="alert">
            Đã khoá gửi email vì không đọc được nhật ký gửi.
            <br><small><?= e($sentLogError) ?></small>
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

    <section class="hero">
        <h1 class="hero-title">Gửi thư mời Year End Party.</h1>

        <div class="hero-actions">
            <button id="btnBulk" class="btn-pill"></button>
            <button id="btnRetryFailed" class="btn-link-apple"></button>
        </div>

        <div class="legend" role="status" aria-live="polite">
            <span class="legend-item">
                <span class="legend-dot" style="background: var(--green)"></span>Đã gửi<strong id="countSent">0</strong>
            </span>
            <span class="legend-item">
                <span class="legend-dot" style="background: var(--red)"></span>Lỗi<strong id="countFailed">0</strong>
            </span>
            <span class="legend-item">
                <span class="legend-dot" style="background: #c7c7cc"></span>Chưa gửi<strong id="countPending">0</strong>
            </span>
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
            <ul class="people">
                <?php $index = 0; foreach ($employees as $email => $employee): ?>
                    <li class="person"
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
                        <div class="status-cell"></div>
                        <button
                            class="icon-btn btn-resend"
                            title="Gửi lại"
                            aria-label="Gửi lại cho <?= e($email) ?>"
                        >
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
