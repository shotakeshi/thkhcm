<?php
/**
 * Ruột thẻ Find Your Ticket: form email + kết quả tra cứu gần nhất.
 * Dùng cho lần render đầu và cho phản hồi JSON (lookupHtml) của public/find-ticket.php.
 *
 * @var string $lookupDraft email vừa nhập
 * @var array|null $lookupError lỗi nhập liệu / hệ thống (messageRef())
 * @var array|null $lookupMessage kết quả không có vé: chưa RSVP, không tham gia (messageRef())
 * @var bool $lookupFound đã tìm thấy vé (vé hiện ở slot #ve)
 */

$lookupDraft ??= '';
$lookupError ??= null;
$lookupMessage ??= null;
$lookupFound ??= false;
?>
<form class="rsvp-form" method="post" action="find-ticket.php#tim-ve" novalidate data-ticket-lookup-form>
    <div class="rsvp-field">
        <input
            class="rsvp-input"
            id="lookup-email"
            type="email"
            name="email"
            value="<?= e($lookupDraft) ?>"
            placeholder="<?= e(message('email_placeholder')) ?>"
            aria-label="<?= e(message('email_label')) ?>"
            <?= i18n(null, [], ['placeholder' => 'email_placeholder', 'aria-label' => 'email_label']) ?>
            autocomplete="email"
            maxlength="254"
            required
            <?= $lookupError !== null ? 'aria-invalid="true" aria-describedby="lookup-email-error"' : '' ?>
        >
        <?php if ($lookupError !== null): ?>
            <p class="rsvp-error" id="lookup-email-error" role="alert"<?= i18n(...$lookupError) ?>>
                <?= e(message(...$lookupError)) ?>
            </p>
        <?php endif; ?>
    </div>

    <button
        class="rsvp-submit"
        type="submit"
        data-busy-label="<?= e(message('lookup.submitting')) ?>"
        <?= i18n('lookup.submit', [], ['data-busy-label' => 'lookup.submitting']) ?>
    ><?= e(message('lookup.submit')) ?></button>
</form>

<?php if ($lookupMessage !== null): ?>
    <p class="rsvp-result" role="status"<?= i18n(...$lookupMessage) ?>><?= e(message(...$lookupMessage)) ?></p>
<?php elseif ($lookupFound): ?>
    <p class="rsvp-result" role="status"<?= i18n('lookup.found') ?>><?= e(message('lookup.found')) ?></p>
<?php endif; ?>
