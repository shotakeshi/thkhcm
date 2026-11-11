<?php
// Dữ liệu (giờ, màu, ảnh): config/event.php (program); chữ hiển thị: lang/ (program.*).
$program = $event['program'] ?? [];
// Mốc có tên trống ở ngôn ngữ đang chọn => ẩn
$schedule = array_values(array_filter(
    $program['schedule'] ?? [],
    fn ($item) => stringField($item, 'key') !== ''
        && trim(message('program.items.' . stringField($item, 'key'))) !== ''
));
$dressCode = trim(message('program.dress_code'));
$dressCodeNote = trim(message('program.dress_code_note'));

$dressColors = array_values(array_filter(
    array_map(
        fn ($color) => strtolower(trim((string) $color)),
        $program['dress_code_colors'] ?? []
    ),
    fn ($color) => preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $color) === 1
));
$hasDress = $dressCode !== '' || $dressColors !== [];
$hasSchedule = $schedule !== [];
$hasProgram = $hasDress || $hasSchedule;

$programImage = stringField($program, 'image');
$programTitle = trim(message('program.title'));
$dateLabel = trim(message('program.date_label'));
$dressCodeLabel = trim(message('program.dress_code_label'));
$dateText = (new DateTimeImmutable($event['date']))->format('d/m/Y');
