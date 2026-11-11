<?php
/**
 * Toast báo đã tải vé. Chỉ dựng khi vé tải được.
 *
 * @var array $employee
 */

$canDownload = canDownloadTicket($employee);
?>
<?php if ($canDownload): ?>
    <?php /* Toast của vé luôn tiếng Anh (public/js/ticket.js) */ ?>
    <div class="toast-region" lang="en" role="status" aria-live="polite">
        <div class="toast" hidden>
            <svg
                class="toast-icon"
                aria-hidden="true"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            ><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            <span class="toast-text"></span>
        </div>
    </div>
<?php endif; ?>

