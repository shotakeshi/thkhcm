<?php
/**
 * Seam: dải chuyển tiếp trong suốt giữa hai section (sợi chỉ vàng, hạt vàng) trên nền chung của trang.
 * Tiến độ sợi chỉ (--seam-p) và trạng thái chạy hạt (.is-live) do
 * public/js/story.js đặt theo vị trí cuộn.
 */

// Hạt vàng: [left %, đường kính px, delay s, thời lượng s]. CSS ẩn bớt 3 hạt cuối trên điện thoại.
$seamParticles = [
    [18, 4, -1, 11], [34, 3, -4, 13], [50, 5, -7, 12], [66, 4, -2.5, 10], [80, 3, -5, 12], [92, 5, -8, 14],
];
?>
<div class="seam" aria-hidden="true">
    <span class="seam-thread"></span>
    <?php foreach ($seamParticles as [$x, $size, $delay, $duration]): ?>
        <span
            class="particle seam-particle"
            style="
                left: <?= $x ?>%;
                top: <?= 20 + ($x * 7 % 55) ?>%;
                width: <?= $size ?>px;
                height: <?= $size ?>px;
                animation-delay: <?= $delay ?>s;
                animation-duration: <?= $duration ?>s
            "
        ></span>
    <?php endforeach; ?>
</div>
