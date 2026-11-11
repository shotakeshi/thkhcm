<?php
$program = $event['program'] ?? [];
$schedule = array_values(array_filter(
    $program['schedule'] ?? [],
    fn ($item) => stringField($item, 'title') !== ''
));
$dressCode = stringField($event, 'dress_code');
$dressCodeNote = stringField($program, 'dress_code_note');

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
$programTitle = stringField($program, 'title');
$dateLabel = stringField($program, 'date_label');
$dressCodeLabel = stringField($program, 'dress_code_label');
$dateText = (new DateTimeImmutable($event['date']))->format('d.m.Y');
