<?php
/**
 * Section Chương trình: một khung gồm hình tham khảo (program.image), lịch
 * trình (tên sự kiện, tiêu đề, ngày, các mốc) và dress code ở cuối.
 * Cần các biến từ program-data.php; lịch trình trống => bỏ đầu mục và danh
 * sách, dress code trống => bỏ block dress code.
 *
 * @var array $event
 */

if (!$hasProgram) {
    return;
}

// Độ trễ reveal tăng dần theo thứ tự xuất hiện: ảnh → đầu mục → các mốc → dress code
$delay = 0;
?>
<section class="page page-schedule" id="lich-trinh" aria-label="Chương trình">
    <div class="stage">
        <div class="program<?= $programImage !== '' ? ' has-photo' : '' ?>">
            <?php if ($programImage !== ''): ?>
                <div
                    class="program-photo"
                    data-reveal
                    style="--d: <?= $delay++ ?>"
                    aria-hidden="true"
                >
                    <img src="<?= e($programImage) ?>" alt="" loading="lazy" decoding="async">
                </div>
            <?php endif; ?>

            <div class="program-body">
                <?php if ($hasSchedule): ?>
                    <header class="program-head">
                        <h2 class="program-heading" data-reveal style="--d: <?= $delay++ ?>">
                            <?php if ($programTitle !== ''): ?>
                                <span class="program-title heading-primary"><?= e($programTitle) ?></span>
                            <?php endif; ?>
                        </h2>
                        <div class="program-date" data-reveal style="--d: <?= $delay++ ?>">
                            <?php if ($dateLabel !== ''): ?>
                                <span class="eyebrow program-date-label"><?= e($dateLabel) ?></span>
                            <?php endif; ?>
                            <span class="program-date-value tnum"><?= e($dateText) ?></span>
                        </div>
                    </header>

                    <ol class="program-list" data-progress>
                        <?php foreach ($schedule as $item): ?>
                            <li class="program-item" data-reveal style="--d: <?= $delay++ ?>">
                                <?php if (stringField($item, 'time') !== ''): ?>
                                    <span class="program-time tnum"><?= e($item['time']) ?></span>
                                <?php endif; ?>
                                <span class="program-name"><?= e($item['title']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if ($hasDress): ?>
                    <div class="program-dress" data-reveal style="--d: <?= $delay++ ?>">
                        <?php if ($dressCodeLabel !== ''): ?>
                            <h3 class="program-dress-label heading-primary"><?= e($dressCodeLabel) ?></h3>
                        <?php endif; ?>
                        <?php if ($dressCodeNote !== ''): ?>
                            <p class="program-dress-note"><?= e($dressCodeNote) ?></p>
                        <?php endif; ?>
                        <?php if ($dressColors !== []): ?>
                            <ul class="program-swatches" aria-label="Bảng màu trang phục">
                                <?php foreach ($dressColors as $color): ?>
                                    <li
                                        class="program-swatch"
                                        style="--swatch: <?= e($color) ?>"
                                        title="<?= e($color) ?>"
                                    ></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
