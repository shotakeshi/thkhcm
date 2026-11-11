<?php
/**
 * Vé tham dự cá nhân, nằm ngay dưới thiệp trong cùng một trang cuộn.
 * Thiếu Lucky Draw Code / số bàn => "To be announced", không cho tải vé.
 */

$name = stringField($employee, 'name');
$nameWords = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
$luckyDrawCode = stringField($employee, 'luckyDrawCode');
$tableNumber = stringField($employee, 'tableNumber');
$isTableNumeric = ctype_digit($tableNumber);
$tableText = $isTableNumeric ? sprintf('%02d', (int) $tableNumber) : $tableNumber;
$canDownload = canDownloadTicket($employee);

// Đốm gold: [x, y, đường kính, delay, thời lượng], toạ độ theo khung thiết kế 375×812
$particles = [
    [54, 210, 5, -1, 11], [312, 250, 4, -4, 13], [92, 470, 6, -7, 11], [288, 520, 4, -2.5, 9],
    [40, 640, 3, -5, 12], [330, 420, 5, -8, 14], [170, 120, 3, -3, 10], [228, 610, 4, -6, 11],
];
?>
<?php /* Vé luôn hiển thị tiếng Anh ở mọi ngôn ngữ (không dùng lang/), lang="en" để trình đọc màn hình đọc đúng */ ?>
<section class="page page-ticket scene-ticket" lang="en" aria-label="Admission Ticket">
    <div class="paper" aria-hidden="true"></div>
    <?php if (($event['invite']['ticket_background'] ?? '') !== ''): ?>
        <div
            class="page-bg is-ticket"
            aria-hidden="true"
            style="background-image: url('<?= e($event['invite']['ticket_background']) ?>')"
        ></div>
    <?php endif; ?>

    <div class="stage">
        <div class="particles intro" aria-hidden="true">
            <?php foreach ($particles as [$x, $y, $size, $delay, $duration]): ?>
                <span
                    class="particle"
                    style="
                        left: <?= round($x / 375 * 100, 2) ?>%;
                        top: <?= round($y / 812 * 100, 2) ?>%;
                        width: <?= $size ?>px;
                        height: <?= $size ?>px;
                        animation-delay: <?= $delay ?>s;
                        animation-duration: <?= $duration ?>s
                    "
                ></span>
            <?php endforeach; ?>
        </div>

        <div class="ticket-page">
        <div class="eyebrow ticket-page-label a-head">Your Personal Ticket</div>

        <div class="ticket">
            <section class="ticket-body" aria-label="Ticket Details">
                <div class="frame" aria-hidden="true"></div>
                <div class="frame is-thin" aria-hidden="true"></div>

                <div class="ticket-head a-head">
                    <span class="ticket-script"><?= e($event['name']) ?></span>
                    <span class="ticket-year tnum"><?= e($event['year']) ?></span>
                </div>

                <div class="eyebrow ticket-label">Guest</div>
                <h2 class="ticket-name">
                    <?php foreach ($nameWords as $index => $word): ?>
                        <span class="word heading-primary" style="--i: <?= $index ?>"><?= e($word) ?></span>
                    <?php endforeach; ?>
                </h2>

                <div class="ticket-rule" aria-hidden="true"></div>

                <div class="ticket-details">
                    <div class="ticket-code-block">
                        <div class="eyebrow ticket-label">Lucky Draw Code</div>
                        <?php if ($luckyDrawCode !== ''): ?>
                            <div class="ticket-code tnum"><?= e($luckyDrawCode) ?></div>
                        <?php else: ?>
                            <div class="ticket-pending">
                                <svg
                                    aria-hidden="true"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                ><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>
                                To be announced
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="ticket-stub" aria-label="Table Number">
                <div class="perf" aria-hidden="true"></div>
                <div class="frame" aria-hidden="true"></div>

                <div class="ticket-stub-text">
                    <div class="ticket-table-caption">
                        <?= $tableNumber !== '' ? 'Table Number' : 'To be announced' ?>
                    </div>
                </div>
                <div
                    class="ticket-table a-table tnum"
                    <?= $isTableNumeric ? 'data-count-to="' . (int) $tableNumber . '"' : '' ?>
                ><?= $tableNumber !== '' ? e($tableText) : '—' ?></div>
            </section>
        </div>

        <div class="ticket-actions a-back">
            <div class="ticket-buttons">
                <button class="btn btn-primary" type="button"
                    <?php if ($canDownload): ?>
                        data-ticket-download
                        data-name="<?= e($name) ?>"
                        data-code="<?= e($luckyDrawCode) ?>"
                        data-table="<?= e($tableText) ?>"
                        data-script="<?= e($event['name']) ?>"
                        data-year="<?= e($event['year']) ?>"
                    <?php else: ?>
                        disabled
                    <?php endif; ?>>
                    <svg
                        aria-hidden="true"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ><path d="M12 4v11"/><path d="M7 10.5l5 5 5-5"/><path d="M5 19.5h14"/></svg>
                    <span>Download Ticket</span>
                </button>
            </div>
        </div>
        </div>
    </div>
</section>
