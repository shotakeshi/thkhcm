<?php
/**
 * Nội dung slot #ve: vé (kèm seam phía trên) khi $showTicket, rồi seam nối tới
 * section Chương trình.
 * Không tham gia => chỉ còn seam, nên HTML không chứa mã Lucky Draw hay số bàn.
 * Dùng cho lần render đầu và cho phản hồi JSON (ticketHtml) của public/rsvp.php.
 *
 * @var array $event
 * @var array $employee
 * @var bool $showTicket
 */

require __DIR__ . '/program-data.php';

if ($showTicket) {
    echo renderPartial('seam.php');
    require __DIR__ . '/ticket.php';
}

if ($hasProgram) {
    echo renderPartial('seam.php');
}
