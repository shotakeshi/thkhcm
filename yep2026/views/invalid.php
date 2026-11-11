<?php
/**
 * Link không hợp lệ: id sai định dạng hoặc không có trong data.json.
 * $noticeUnavailable => không đọc được dữ liệu, báo lỗi tạm thời thay vì link sai.
 *
 * @var array $event
 * @var bool|null $noticeUnavailable
 */

$noticeUnavailable ??= false;
$pageTitleKey = $noticeUnavailable ? 'invalid.unavailable_title' : 'invalid.title';
$pageTitleArgs = [];
$noticeTextKey = $noticeUnavailable ? 'invalid.unavailable_text' : 'invalid.text';
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
</head>
<body>
<?php require __DIR__ . '/partials/lang-switch.php'; ?>

<div class="scene scene-ticket">
    <div class="paper" aria-hidden="true"></div>
    <main class="notice rise-in">
        <img
            class="notice-logo"
            src="<?= e($event['company_logo']) ?>"
            alt="<?= e($event['company_name']) ?>"
            width="657"
            height="120"
        >
        <div class="notice-script"><?= e($event['name']) ?></div>
        <div class="notice-rule" aria-hidden="true"></div>
        <h1 class="notice-title heading-primary"<?= i18n($pageTitleKey) ?>><?= e(message($pageTitleKey)) ?></h1>
        <p class="notice-text"<?= i18n($noticeTextKey) ?>><?= e(message($noticeTextKey)) ?></p>
        <a class="btn btn-primary notice-btn" href="./"<?= i18n('invalid.back') ?>><?= e(message('invalid.back')) ?></a>
    </main>
</div>

<?php require __DIR__ . '/partials/i18n-script.php'; ?>
</body>
</html>
