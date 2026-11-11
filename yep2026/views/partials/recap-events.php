<?php
// Danh sách sự kiện của chương 03–04 (partials/recap.php).
// Bố cục cắt dán theo số ảnh: config/recap-collage.php.
$collageLayouts = loadConfig('recap-collage');

// Đổi vị trí (hàng đầu, cột đầu, số hàng, số cột) sang giá trị grid-area của CSS
$gridArea = fn (array $position): string => "{$position['top_row']}"
    . " / {$position['left_column']}"
    . " / span {$position['row_count']}"
    . " / span {$position['column_count']}";
?>
<div class="events is-<?= e($style) ?>">
    <?php foreach ($events as $eventIndex => $memory): ?>
        <?php
        $photos = array_values($memory['photos']);
        $photoCount = count($photos);
        $layout = $collageLayouts[$style][$photoCount] ?? null;
        $collageMode = $layout === null
            ? 'is-flow'
            : ($layout['grid'] === null ? 'is-solo' : 'is-grid');
        $photoSlots = ($layout ?? $collageLayouts[$style][5])['items'];
        $grid = $layout['grid'] ?? null;
        $soloSlot = $collageMode === 'is-solo' ? $photoSlots[0] : null;
        ?>
        <article class="event <?= $eventIndex % 2 ? 'is-flip' : '' ?>">
            <header class="event-head" data-reveal>
                <?php if (trim($memory['time']) !== ''): ?>
                    <p class="event-time"><?= $text($memory['time']) ?></p>
                <?php endif; ?>
                <h3 class="event-title"><?= $text($memory['title']) ?></h3>
                <p class="body"><?= $text($memory['story']) ?></p>
            </header>

            <div
                class="collage <?= $collageMode ?>"<?php if ($soloSlot && isset($soloSlot['width_pixels'])): ?>
                style="--photo-width: <?= (int) $soloSlot['width_pixels'] ?>px"<?php endif; ?><?php if ($grid): ?>
                style="
                    --grid-rows: <?= $grid['desktop']['rows'] ?>;
                    --grid-aspect-ratio: <?= $grid['desktop']['aspect_ratio'] ?>;
                    --grid-rows-mobile: <?= $grid['mobile']['rows'] ?>;
                    --grid-aspect-ratio-mobile: <?= $grid['mobile']['aspect_ratio'] ?>
                "<?php endif; ?>
            >
                <ul class="collage-list">
                    <?php foreach ($photos as $photoIndex => $eventPhoto): ?>
                        <?php $slot = $photoSlots[$photoIndex % count($photoSlots)]; ?>
                        <li
                            class="collage-item is-<?= e($slot['frame']) ?> <?= isset($slot['decoration']) ? 'has-' . e($slot['decoration']) : '' ?> <?= isset($slot['fit']) ? 'fit-' . e($slot['fit']) : '' ?>"
                            data-reveal
                            style="
                                --d: <?= $photoIndex ?>;
                                --tilt: <?= $slot['tilt_degrees'] ?>deg;
                                --photo-aspect-ratio: <?= isset($slot['width_pixels'])
                                    ? (int) $slot['width_pixels'] . ' / ' . (int) $slot['height_pixels']
                                    : $slot['aspect_ratio'] ?><?php if ($collageMode === 'is-grid'): ?>;
                                --grid-area: <?= $gridArea($slot['desktop']) ?>;
                                --grid-area-mobile: <?= $gridArea($slot['mobile']) ?><?php endif; ?>
                            "
                        >
                            <figure class="photo-frame f-<?= e($slot['frame']) ?>">
                                <?= $photo($eventPhoto) ?>
                            </figure>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php foreach ($layout['floating_decorations'] ?? [] as $decoration): ?>
                    <span
                        class="collage-<?= e($decoration['type']) ?>"
                        aria-hidden="true"
                        style="
                            --left: <?= $decoration['left'] ?>;
                            --top: <?= $decoration['top'] ?>;
                            --size: <?= $decoration['scale'] ?? $decoration['width_percent'] ?>
                        "
                    ></span>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
