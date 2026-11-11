<?php
/**
 * Link không hợp lệ: id sai định dạng hoặc không có ở data.json lẫn rsvp.json.
 * $noticeUnavailable => không đọc được dữ liệu, báo lỗi tạm thời thay vì link sai.
 *
 * @var array $event
 * @var bool|null $noticeUnavailable
 */

$noticeUnavailable ??= false;
$pageTitle = message($noticeUnavailable ? 'invalid.unavailable_title' : 'invalid.title');
$noticeText = message($noticeUnavailable ? 'invalid.unavailable_text' : 'invalid.text');
?>
<!doctype html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
</head>
<body>
<div class="scene scene-ticket">
    <div class="paper" aria-hidden="true"></div>

    <img
        class="brand"
        src="<?= e($event['company_logo']) ?>"
        alt="<?= e($event['company_name']) ?>"
        width="657"
        height="120"
    >

    <main class="notice rise-in">
        <div class="notice-icon" aria-hidden="true">
            <svg
                width="28"
                height="28"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.4"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="3" y="5" width="18" height="14" rx="2"/>
                <path d="M3.5 6.5l8.5 6 8.5-6"/>
                <path class="notice-icon-x" d="M16.5 15.5l3 3M19.5 15.5l-3 3"/>
            </svg>
        </div>
        <div class="notice-script"><?= e($event['name']) ?></div>
        <div class="notice-rule" aria-hidden="true"></div>
        <h1 class="notice-title heading-primary"><?= e($pageTitle) ?></h1>
        <p class="notice-text"><?= e($noticeText) ?></p>
        <a class="btn btn-primary notice-btn" href="./"><?= e(message('invalid.back')) ?></a>
    </main>
</div>
</body>
</html>
