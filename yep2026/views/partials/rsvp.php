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
$deadlineText = $deadline !== '' ? (new DateTimeImmutable($deadline))->format('d/m/Y') : '';
?>
<section class="page page-rsvp" id="xac-nhan" aria-labelledby="rsvp-title">
    <div class="rsvp-wrap">
        <header class="rsvp-intro">
            <h2 class="rsvp-title heading-primary" id="rsvp-title" data-reveal style="--d: 1">
                <?= e(message('rsvp.title')) ?>
            </h2>

            <?php if ($rsvp === null && $deadlineText !== ''): ?>
                <p class="rsvp-lead" data-reveal style="--d: 2">
                    <?= e(message('rsvp.lead', ['deadline' => $deadlineText])) ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="rsvp-card" id="rsvp-card" data-rsvp-card data-reveal style="--d: 4">
            <?php require __DIR__ . '/rsvp-card.php'; ?>
        </div>
    </div>
</section>
