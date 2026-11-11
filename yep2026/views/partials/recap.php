<?php
$isTodo = fn (?string $value): bool => str_starts_with(trim((string) $value), '[');

$text = fn (?string $value): string => $isTodo($value)
    ? '<span class="todo">' . e($value) . '</span>'
    : e($value);

// Ảnh thật nếu có src, ngược lại là khung ảnh chờ kèm mô tả cần chụp
$photo = function (array $item) use ($isTodo): string {
    $note = (string) ($item['note'] ?? '');
    $src = trim((string) ($item['src'] ?? $item['image'] ?? ''));

    if ($src !== '') {
        $alt = $isTodo($note) ? '' : $note;

        // data-lightbox: ảnh nội dung, click để xem phóng to (js/lightbox.js)
        return '<img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy" decoding="async" data-lightbox>';
    }

    return '<span class="ph" role="img" aria-label="Ảnh chờ bổ sung: ' . e(trim($note, '[]')) . '">'
        . '<span class="ph-note">' . e($note) . '</span></span>';
};
?>
<a class="skip" href="#khoi-dau">Bỏ qua phần mở đầu</a>

<div class="progress" aria-hidden="true"><span class="progress-bar"></span></div>

<?php // data-light-zone: vùng nội dung, story.js bật ánh sáng "light" khi cuộn vào ?>
<main class="recap" data-light-zone>
    <!-- 01 · Mở đầu: vào vùng nội dung, ánh sáng nền sáng dần -->
    <header class="hero" id="mo-dau" data-chapter>
        <div class="hero-inner">
            <p class="hero-sub chapter-title" data-reveal style="--d: 1">Hành trình một năm đáng nhớ</p>
            <p class="hero-lead" data-reveal style="--d: 2"><?= $text($recap['hero']['lead']) ?></p>
            <?php // Reveal ở lớp bọc: transition hover của .cta không bị transition reveal đè ?>
            <div data-reveal style="--d: 3">
                <a class="cta" href="#khoi-dau">
                    <span>Bắt đầu hành trình</span>
                    <svg
                        aria-hidden="true"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ><path d="M12 5v14M6 13l6 6 6-6"/></svg>
                </a>
            </div>
        </div>
    </header>

    <section class="chapter beginning" id="khoi-dau" data-chapter>
        <div class="wrap beginning-grid">
            <div class="beginning-text">
                <p class="chapter-intro" data-reveal><?= $text($recap['beginning']['intro']) ?></p>
                <p class="body" data-reveal><?= $text($recap['beginning']['direction']) ?></p>
            </div>

            <div class="diary" data-reveal="diary">
                <div class="diary-page diary-left">
                    <p class="diary-date"><?= $text($recap['beginning']['date']) ?></p>
                    <figure class="polaroid tilt-l">
                        <?= $photo([
                            'src' => $recap['beginning']['image'],
                            'note' => $recap['beginning']['image_note'],
                        ]) ?>
                    </figure>
                </div>
                <div class="diary-page diary-right">
                    <p class="diary-head">Những điều chúng ta tự hứa</p>
                    <ol class="diary-goals">
                        <?php foreach ($recap['beginning']['goals'] as $goal): ?>
                            <li><?= $text($goal) ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="diary-foot"><?= $text($recap['beginning']['first_steps']) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="chapter celebrations" id="ngay-hoi" data-chapter>
        <div class="wrap">
            <div class="chapter-head">
                <h2 class="chapter-title heading-primary" data-reveal="title">
                    Một năm rực rỡ sắc màu
                </h2>
                <p class="chapter-intro" data-reveal>
                    <?= $text($recap['celebrations']['intro']) ?>
                </p>
            </div>

            <?php
            $events = $recap['celebrations']['events'];
            $style = 'party';
            require __DIR__ . '/recap-events.php';
            ?>
        </div>
    </section>

    <section class="chapter trips" id="chuyen-di" data-chapter>
        <div class="wrap">
            <div class="chapter-head">
                <h2 class="chapter-title heading-primary" data-reveal="title">
                    Những chuyến đi<br>cùng nhau
                </h2>
                <p class="chapter-intro" data-reveal><?= $text($recap['trips']['intro']) ?></p>
            </div>

            <?php
            $events = $recap['trips']['events'];
            $style = 'trip';
            require __DIR__ . '/recap-events.php';
            ?>
        </div>
    </section>

    <section class="chapter people" id="con-nguoi" data-chapter>
        <div class="wrap">
            <div class="chapter-head">
                <h2 class="chapter-title heading-primary" data-reveal="title">
                    Những con người<br>tạo nên hành trình
                </h2>
                <p class="chapter-intro" data-reveal><?= $text($recap['people']['intro']) ?></p>
            </div>

            <ul class="salon">
                <?php foreach ($recap['people']['photos'] as $photoIndex => $peoplePhoto): ?>
                    <?php $salonSlot = $photoIndex % 7 + 1; ?>
                    <li class="salon-item s<?= $salonSlot ?>" data-reveal>
                        <?php // Tranh chính (s1) và s4 là ảnh giấy xé, còn lại khung viền trắng ?>
                        <figure
                            class="<?= in_array($salonSlot, [1, 4], true) ? 'f-torn' : 'f-white' ?>"
                            tabindex="0"
                        >
                            <?= $photo($peoplePhoto) ?>
                            <figcaption class="salon-cap">
                                <?= $text($peoplePhoto['note']) ?>
                            </figcaption>
                        </figure>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="chapter moments" id="khoanh-khac" data-chapter>
        <div class="wrap moments-head">
            <div>
                <h2 class="chapter-title heading-primary" data-reveal="title">
                    Những khoảnh khắc<br>không quên
                </h2>
                <p class="chapter-intro" data-reveal><?= $text($recap['moments']['intro']) ?></p>
            </div>
            <div class="album-nav">
                <button type="button" class="album-btn" data-album="-1" aria-label="Ảnh trước">
                    <svg
                        aria-hidden="true"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ><path d="M15 6l-6 6 6 6"/></svg>
                </button>
                <button type="button" class="album-btn" data-album="1" aria-label="Ảnh tiếp theo">
                    <svg
                        aria-hidden="true"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>

        <ul class="album" tabindex="0" aria-label="Album khoảnh khắc, cuộn ngang để xem thêm">
            <?php foreach ($recap['moments']['photos'] as $photoIndex => $momentPhoto): ?>
                <li class="album-item">
                    <figure class="polaroid <?= $photoIndex % 2 ? 'tilt-r' : 'tilt-l' ?>">
                        <?= $photo($momentPhoto) ?>
                        <figcaption><?= $text($momentPhoto['note']) ?></figcaption>
                    </figure>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="chapter closing" id="chuong-moi" data-chapter>
        <div class="wrap closing-inner">
            <div class="letter" data-reveal>
                <p><?= $text($recap['closing']['looking_back']) ?></p>
                <p><?= $text($recap['closing']['thanks']) ?></p>
                <p><?= $text($recap['closing']['next_year']) ?></p>
            </div>

            <p class="closing-message" data-reveal><?= $text($recap['closing']['message']) ?></p>
        </div>
    </section>
</main>
