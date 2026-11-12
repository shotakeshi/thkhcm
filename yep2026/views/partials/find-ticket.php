<?php
/**
 * Section Find Your Ticket: tra cứu vé bằng email cho khách đã xác nhận.
 * Chỉ dựng trên thiệp chung (không có id): link mời đã hiện sẵn vé của khách.
 *
 * @var string|null $lookupCardHtml kết quả tra cứu khi gửi form không JS
 */
?>
<section class="page page-rsvp page-find-ticket" id="tim-ve" aria-labelledby="find-ticket-title">
    <div class="rsvp-wrap">
        <header class="rsvp-intro">
            <h2 class="rsvp-title heading-primary" id="find-ticket-title"<?= i18n('lookup.title') ?> data-reveal style="--reveal-order: 0">
                <?= e(message('lookup.title')) ?>
            </h2>
            <p class="rsvp-lead"<?= i18n('lookup.lead') ?> data-reveal style="--reveal-order: 1">
                <?= e(message('lookup.lead')) ?>
            </p>
        </header>

        <div class="rsvp-card" id="ticket-lookup-card" data-reveal style="--reveal-order: 3">
            <?= $lookupCardHtml ?? renderPartial('find-ticket-card.php') ?>
        </div>
    </div>
</section>
