<?php
/**
 * Cột Vé của một row trên admin.php: mã Lucky Draw, số bàn trong data.json (thiếu => "—") và nút sửa.
 * Dùng cho lần render đầu và phản hồi JSON (html.ticket) của public/admin-ticket.php.
 * data-code/data-table là giá trị hiện tại, js/admin-ticket.js gửi kèm để không ghi đè thay đổi mới hơn.
 *
 * @var array $guest khách trong data.json (GuestStore)
 */

$code = stringField($guest, 'luckyDrawCode');
$table = stringField($guest, 'tableNumber');
$name = stringField($guest, 'name') ?: $guest['email'];
?>
<div class="person-ticket" data-ticket-slot>
    <div class="ticket-values">
        <div title="Mã Lucky Draw"><?= $code !== '' ? e($code) : '<span class="ticket-empty">—</span>' ?></div>
        <div title="Số bàn"><?= $table !== '' ? 'Bàn ' . e($table) : '<span class="ticket-empty">—</span>' ?></div>
    </div>
    <button type="button" class="icon-btn ticket-edit"
            data-code="<?= e($code) ?>"
            data-table="<?= e($table) ?>"
            data-name="<?= e($name) ?>"
            title="Sửa mã Lucky Draw và số bàn"
            aria-label="<?= e("Sửa mã Lucky Draw và số bàn: {$name}") ?>">
        <i class="bi bi-pencil"></i>
    </button>
</div>
