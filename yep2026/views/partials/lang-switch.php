<?php
/**
 * Nút chuyển ngôn ngữ VI / EN, cố định góc trên bên phải (css: invite.css "Language switcher").
 * public/js/i18n.js bắt click [data-locale], đổi nội dung tại chỗ và cập nhật aria-pressed.
 * Không JS: nút không có tác dụng, trang giữ ngôn ngữ server render.
 */
?>
<div
    class="lang-switch"
    role="group"
    aria-label="<?= e(message('lang.label')) ?>"
    <?= i18n(null, [], ['aria-label' => 'lang.label']) ?>
>
    <?php foreach (LOCALES as $code => $name): ?>
        <button
            class="lang-switch-btn"
            type="button"
            lang="<?= e($code) ?>"
            title="<?= e($name) ?>"
            aria-label="<?= e($name) ?>"
            aria-pressed="<?= $code === locale() ? 'true' : 'false' ?>"
            data-locale="<?= e($code) ?>"
        ><?= e(strtoupper($code)) ?></button>
    <?php endforeach; ?>
</div>
