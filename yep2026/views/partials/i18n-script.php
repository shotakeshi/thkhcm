<?php
/**
 * Từ điển mọi ngôn ngữ (lang/{locale}/messages.php, dạng phẳng) + public/js/i18n.js.
 * Đặt trước các script khác: motion.js, lightbox.js, ticket.js đọc nội dung qua window.yepI18n.
 */

$i18nData = [
    'defaultLocale' => DEFAULT_LOCALE,
    'cookie'        => LOCALE_COOKIE,
    'messages'      => localized(fn (string $locale) => flattenMessages(translations($locale))),
];
?>
<script type="application/json" id="i18n-data"><?= json_encode(
    $i18nData,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
) ?></script>
<script src="<?= e(asset('js/i18n.js')) ?>"></script>
