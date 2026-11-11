<?php
/**
 * Phần xác nhận tham dự trong một row của admin.php, tách theo cột:
 * - 'status':  badge trạng thái (kèm "Chưa có vé")
 * - 'time':    thời gian xác nhận, thời gian admin sửa
 * - 'actions': nút đổi trạng thái, xoá (chỉ khi đã có xác nhận)
 * Mỗi slot có data-rsvp-slot để js/admin-rsvp.js thay riêng từng phần, không đụng
 * phần gửi thư cùng cột. Dùng cho lần render đầu và phản hồi JSON (html) của public/admin-rsvp.php.
 *
 * @var array $entry khách theo dạng invitation()
 * @var string $slot 'status' | 'time' | 'actions'
 */

use App\Rsvp\GuestStore;

$rsvp = $entry['rsvp'];
$status = $rsvp['attendance'] ?? 'none';
$labels = [
    GuestStore::ATTENDING => 'Tham gia',
    GuestStore::DECLINED  => 'Không tham gia',
    'none'             => 'Chưa phản hồi',
];
$name = stringField($entry['guest'], 'name') ?: $entry['guest']['email'];
?>
<?php if ($slot === 'status'): ?>
    <div class="rsvp-status" data-rsvp-slot="status" data-rsvp-status="<?= e($status) ?>">
        <span class="rsvp-badge is-<?= e($status) ?>" title="Xác nhận tham dự"><?= e($labels[$status]) ?></span>
        <?php if (isAttending($rsvp) && !canDownloadTicket($entry['guest'])): ?>
            <span class="rsvp-badge is-warning" title="Thiếu luckyDrawCode hoặc tableNumber trong data.json">Chưa có vé</span>
        <?php endif; ?>
    </div>
<?php elseif ($slot === 'time'): ?>
    <?php
    $submittedAt = formatTimestamp($rsvp['submittedAt'] ?? null, 'd/m H:i');
    $updatedAt = formatTimestamp($rsvp['updatedAt'] ?? null, 'd/m H:i');
    ?>
    <div class="rsvp-times" data-rsvp-slot="time">
        <?php if ($submittedAt !== ''): ?>
            <div class="time-row"><span class="time-label">Xác nhận</span><?= e($submittedAt) ?></div>
        <?php endif; ?>
        <?php if ($updatedAt !== ''): ?>
            <div class="time-row"><span class="time-label">Admin sửa</span><?= e($updatedAt) ?></div>
        <?php endif; ?>
    </div>
<?php elseif ($slot === 'actions'): ?>
    <?php
    $nextAttendance = isAttending($rsvp) ? GuestStore::DECLINED : GuestStore::ATTENDING;
    $toggleLabel = 'Đổi thành ' . mb_strtolower($labels[$nextAttendance]);
    ?>
    <span class="rsvp-actions" data-rsvp-slot="actions">
        <?php if ($rsvp !== null): ?>
            <button type="button" class="icon-btn rsvp-action"
                    data-action="attendance"
                    data-attendance="<?= e($nextAttendance) ?>"
                    data-confirm="<?= e("Đổi xác nhận của {$name} thành “{$labels[$nextAttendance]}”?") ?>"
                    title="<?= e($toggleLabel) ?>"
                    aria-label="<?= e("{$toggleLabel}: {$name}") ?>">
                <i class="bi bi-arrow-left-right"></i>
            </button>
            <button type="button" class="icon-btn rsvp-action is-danger"
                    data-action="delete"
                    data-confirm="<?= e("Xoá xác nhận của {$name}? Khách sẽ phải xác nhận lại qua link mời.") ?>"
                    title="Xoá xác nhận"
                    aria-label="<?= e("Xoá xác nhận: {$name}") ?>">
                <i class="bi bi-trash3"></i>
            </button>
        <?php endif; ?>
    </span>
<?php endif; ?>
