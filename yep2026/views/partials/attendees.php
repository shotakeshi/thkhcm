<?php
use App\Log\Logger;

try {
    $attendees = array_values(array_filter(
        guestStore()->all(),
        fn (array $guest) => isAttending($guest) && stringField($guest, 'name') !== ''
    ));
} catch (RuntimeException $e) {
    Logger::error($e);
    $attendees = [];
}

if ($attendees === []) {
    return;
}

usort(
    $attendees,
    fn (array $a, array $b) => strtotime($a['submittedAt']) <=> strtotime($b['submittedAt'])
);
?>
<section
    class="page page-attendees"
    id="nguoi-tham-gia"
    aria-label="<?= e(message('attendees.label')) ?>"
    <?= i18n(null, [], ['aria-label' => 'attendees.label']) ?>
>
    <div class="stage">
        <div class="program attendees">
            <div class="program-body">
                <header class="program-head">
                    <h2 class="program-heading" data-reveal style="--reveal-order: 0">
                        <span class="program-title heading-primary"<?= i18n('attendees.title') ?>>
                            <?= e(message('attendees.title')) ?>
                        </span>
                    </h2>
                    <div class="program-date" data-reveal style="--reveal-order: 1">
                        <span class="eyebrow program-date-label"<?= i18n('attendees.count_label') ?>>
                            <?= e(message('attendees.count_label')) ?>
                        </span>
                        <span class="program-date-value tnum"><?= count($attendees) ?></span>
                    </div>
                </header>

                <?php if ($attendees === []): ?>
                    <p class="attendee-empty" data-reveal style="--reveal-order: 2"<?= i18n('attendees.empty') ?>>
                        <?= e(message('attendees.empty')) ?>
                    </p>
                <?php else: ?>
                <ol class="attendee-list" data-reveal style="--reveal-order: 2">
                    <?php foreach ($attendees as $index => $attendee): ?>
                        <li class="attendee-item">
                            <span class="attendee-number"><?= $index + 1 ?></span>
                            <span class="attendee-name"><?= e(stringField($attendee, 'name')) ?></span>
                            <time class="attendee-time tnum" datetime="<?= e($attendee['submittedAt']) ?>">
                                <?= e(formatTimestamp($attendee['submittedAt'], 'd/m/Y H:i')) ?>
                            </time>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
