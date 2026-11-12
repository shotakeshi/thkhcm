<?php
// Nội dung chữ: lang/{vi,en}/recap.php (khoá recap.*); ảnh: config/recap.php
$isTodo = fn (?string $value): bool => str_starts_with(trim((string) $value), '[');

$text = fn (?string $value): string => $isTodo($value)
    ? '<span class="todo">' . e($value) . '</span>'
    : e($value);

// Nội dung theo khoá recap.* (đã escape); phần tử chứa nó gắn i18n('recap.…') để đổi ngôn ngữ tại chỗ
$copy = fn (string $key): string => e(message("recap.{$key}"));

// Ảnh thật nếu có src, ngược lại là khung ảnh chờ kèm mô tả cần chụp.
// $noteKey: khoá chú thích ảnh trong lang/ (tuỳ chọn, không có => alt rỗng)
$photo = function (array $item, ?string $noteKey = null) use ($isTodo): string {
    $hasNote = $noteKey !== null && findMessage($noteKey) !== null;
    $note = $hasNote ? message($noteKey) : '';
    $src = trim((string) ($item['src'] ?? $item['image'] ?? ''));

    if ($src !== '') {
        $isAlt = $hasNote && !$isTodo($note);

        // data-lightbox: ảnh nội dung, click để xem phóng to (js/lightbox.js)
        return '<img src="' . e($src) . '" alt="' . e($isAlt ? $note : '') . '"'
            . ($isAlt ? i18n(null, [], ['alt' => $noteKey]) : '')
            . ' loading="lazy" decoding="async" data-lightbox>';
    }

    $placeholderArgs = ['note' => localized(fn (string $locale) => trim(
        $hasNote ? message($noteKey, [], $locale) : '',
        '[]'
    ))];

    return '<span class="ph" role="img" aria-label="'
        . e(message('recap.photo_placeholder', $placeholderArgs)) . '"'
        . i18n(null, $placeholderArgs, ['aria-label' => 'recap.photo_placeholder']) . '>'
        . '<span class="ph-note"' . ($hasNote ? i18n($noteKey) : '') . '>' . e($note) . '</span></span>';
};
?>
<a class="skip" href="#khoi-dau"<?= i18n('recap.skip') ?>><?= $copy('skip') ?></a>

<div class="progress" aria-hidden="true"><span class="progress-bar"></span></div>

<?php // data-light-zone: vùng nội dung, story.js bật ánh sáng "light" khi cuộn vào ?>
<main class="recap" data-light-zone>
    <!-- 01 · Mở đầu: vào vùng nội dung, ánh sáng nền sáng dần -->
    <header class="hero" id="mo-dau">
        <div class="hero-inner">
            <p class="hero-sub chapter-title"<?= i18n('recap.hero.title') ?> data-reveal style="--reveal-order: 1">
                <?= $copy('hero.title') ?>
            </p>
            <p class="hero-lead"<?= i18n('recap.hero.lead') ?> data-reveal style="--reveal-order: 2">
                <?= $text(message('recap.hero.lead')) ?>
            </p>
            <?php // Reveal ở lớp bọc: transition hover của .cta không bị transition reveal đè ?>
            <div data-reveal style="--reveal-order: 3">
                <a class="cta" href="#khoi-dau">
                    <span<?= i18n('recap.hero.cta') ?>><?= $copy('hero.cta') ?></span>
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

    <section class="chapter beginning" id="khoi-dau">
        <div class="wrap beginning-grid">
            <div class="beginning-text">
                <p class="chapter-intro"<?= i18n('recap.beginning.intro') ?> data-reveal>
                    <?= $text(message('recap.beginning.intro')) ?>
                </p>
                <p class="body"<?= i18n('recap.beginning.direction') ?> data-reveal>
                    <?= $text(message('recap.beginning.direction')) ?>
                </p>
            </div>

            <div class="diary" data-reveal="diary">
                <div class="diary-page diary-left">
                    <p class="diary-date"<?= i18n('recap.beginning.date') ?>>
                        <?= $text(message('recap.beginning.date')) ?>
                    </p>
                    <figure class="polaroid tilt-l">
                        <?= $photo(['src' => $recap['beginning']['image']], 'recap.beginning.image_note') ?>
                    </figure>
                </div>
                <div class="diary-page diary-right">
                    <p class="diary-head"<?= i18n('recap.beginning.promises_title') ?>>
                        <?= $copy('beginning.promises_title') ?>
                    </p>
                    <ol class="diary-goals">
                        <?php foreach (messageList('recap.beginning.goals') as $goalIndex => $goal): ?>
                            <li<?= i18n("recap.beginning.goals.{$goalIndex}") ?>><?= $text($goal) ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="diary-foot"<?= i18n('recap.beginning.first_steps') ?>>
                        <?= $text(message('recap.beginning.first_steps')) ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="chapter celebrations" id="ngay-hoi">
        <div class="wrap">
            <div class="chapter-head">
                <h2 class="chapter-title heading-primary"<?= i18n('recap.celebrations.title') ?> data-reveal="title">
                    <?= $copy('celebrations.title') ?>
                </h2>
                <p class="chapter-intro"<?= i18n('recap.celebrations.intro') ?> data-reveal>
                    <?= $text(message('recap.celebrations.intro')) ?>
                </p>
            </div>

            <?php
            $events = $recap['celebrations']['events'];
            $style = 'party';
            require __DIR__ . '/recap-events.php';
            ?>
        </div>
    </section>

    <section class="chapter trips" id="chuyen-di">
        <div class="wrap">
            <div class="chapter-head">
                <h2 class="chapter-title heading-primary"<?= i18n('recap.trips.title') ?> data-reveal="title">
                    <?= $copy('trips.title') ?>
                </h2>
                <p class="chapter-intro"<?= i18n('recap.trips.intro') ?> data-reveal>
                    <?= $text(message('recap.trips.intro')) ?>
                </p>
            </div>

            <?php
            $events = $recap['trips']['events'];
            $style = 'trip';
            require __DIR__ . '/recap-events.php';
            ?>
        </div>
    </section>

    <section class="chapter people" id="con-nguoi">
        <div class="wrap">
            <div class="chapter-head">
                <?php // Xuống dòng (\n) trong nội dung => <br> (i18n.js giữ cách xuống dòng khi đổi ngôn ngữ) ?>
                <h2 class="chapter-title heading-primary"<?= i18n('recap.people.title') ?> data-reveal="title">
                    <?= nl2br($copy('people.title'), false) ?>
                </h2>
                <p class="chapter-intro"<?= i18n('recap.people.intro') ?> data-reveal>
                    <?= $text(message('recap.people.intro')) ?>
                </p>
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
                            <?php $noteKey = "recap.people.photos.{$photoIndex}"; ?>
                            <?= $photo($peoplePhoto, $noteKey) ?>
                            <figcaption class="salon-cap"<?= findMessage($noteKey) !== null ? i18n($noteKey) : '' ?>>
                                <?= $text(findMessage($noteKey)) ?>
                            </figcaption>
                        </figure>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="chapter moments" id="khoanh-khac">
        <div class="wrap moments-head">
            <div>
                <h2 class="chapter-title heading-primary"<?= i18n('recap.moments.title') ?> data-reveal="title">
                    <?= $copy('moments.title') ?>
                </h2>
                <p class="chapter-intro"<?= i18n('recap.moments.intro') ?> data-reveal>
                    <?= $text(message('recap.moments.intro')) ?>
                </p>
            </div>
            <div class="album-nav">
                <button
                    type="button"
                    class="album-btn"
                    data-album="-1"
                    aria-label="<?= $copy('moments.prev') ?>"
                    <?= i18n(null, [], ['aria-label' => 'recap.moments.prev']) ?>
                >
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
                <button
                    type="button"
                    class="album-btn"
                    data-album="1"
                    aria-label="<?= $copy('moments.next') ?>"
                    <?= i18n(null, [], ['aria-label' => 'recap.moments.next']) ?>
                >
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

        <ul
            class="album"
            tabindex="0"
            aria-label="<?= $copy('moments.album_label') ?>"
            <?= i18n(null, [], ['aria-label' => 'recap.moments.album_label']) ?>
        >
            <?php foreach ($recap['moments']['photos'] as $photoIndex => $momentPhoto): ?>
                <li class="album-item">
                    <figure class="polaroid <?= $photoIndex % 2 ? 'tilt-r' : 'tilt-l' ?>">
                        <?php $noteKey = "recap.moments.photos.{$photoIndex}"; ?>
                        <?= $photo($momentPhoto, $noteKey) ?>
                        <figcaption<?= findMessage($noteKey) !== null ? i18n($noteKey) : '' ?>>
                            <?= $text(findMessage($noteKey)) ?>
                        </figcaption>
                    </figure>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="chapter closing" id="chuong-moi">
        <div class="wrap closing-inner">
            <div class="letter" data-reveal>
                <?php foreach (['looking_back', 'thanks', 'next_year'] as $letterPart): ?>
                    <p<?= i18n("recap.closing.{$letterPart}") ?>><?= $text(message("recap.closing.{$letterPart}")) ?></p>
                <?php endforeach; ?>
            </div>

            <p class="closing-message"<?= i18n('recap.closing.message') ?> data-reveal>
                <?= $text(message('recap.closing.message')) ?>
            </p>
        </div>
    </section>
</main>
