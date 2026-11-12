<?php
/**
 * Section Xác nhận tham gia. Có link mời ($employee !== null): form điền sẵn
 * thông tin khách hoặc trạng thái đã xác nhận; không có id: form tự đăng ký.
 *
 * @var array $event
 * @var array|null $employee
 * @var array|null $rsvp
 * @var array $rsvpErrors
 * @var array $rsvpDraft
 * @var string|null $rsvpNotice
 */

$deadline = stringField($event, 'rsvp_deadline');
// Hạn phản hồi theo định dạng ngày của từng ngôn ngữ (đổi ngôn ngữ tại chỗ cần đủ các bản)
$leadArgs = $deadline !== ''
    ? ['deadline' => localized(fn (string $locale) => formatDate(new DateTimeImmutable($deadline), $locale))]
    : null;
?>
<section class="page page-rsvp" id="xac-nhan" aria-labelledby="rsvp-title">
    <div class="rsvp-wrap">
        <header class="rsvp-intro">
            <h2 class="rsvp-title heading-primary" id="rsvp-title"<?= i18n('rsvp.title') ?> data-reveal style="--reveal-order: 1">
                <?= e(message('rsvp.title')) ?>
            </h2>

            <?php if ($rsvp === null && $leadArgs !== null): ?>
                <p class="rsvp-lead"<?= i18n('rsvp.lead', $leadArgs) ?> data-reveal style="--reveal-order: 2">
                    <?= e(message('rsvp.lead', $leadArgs)) ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="rsvp-card" id="rsvp-card" data-rsvp-card data-reveal style="--reveal-order: 4">
            <?php require __DIR__ . '/rsvp-card.php'; ?>
        </div>
    </div>
</section>
