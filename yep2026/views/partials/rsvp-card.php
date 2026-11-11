<?php
/**
 * Ruột thẻ xác nhận tham dự.
 * - Chưa xác nhận: form Full Name / Email / Attendance. Có link mời => tên và
 *   email điền sẵn từ dữ liệu khách, chỉ đọc.
 * - Đã xác nhận: trạng thái hiện tại, không cho đổi lựa chọn.
 * Dùng cho lần render đầu và cho phản hồi JSON (rsvpHtml) của public/rsvp.php.
 *
 * @var array $event
 * @var array|null $employee khách của link mời, null => khách tự đăng ký
 * @var array|null $rsvp record trong rsvp.json, null => chưa xác nhận
 * @var array $rsvpErrors lỗi theo field: 'name', 'email', 'attendance', 'form'
 * @var array $rsvpDraft giá trị vừa gửi ('name', 'email', 'attendance') để giữ lại khi form lỗi
 * @var string|null $rsvpNotice thông báo thêm khi đã xác nhận trước đó
 */

use App\Rsvp\RsvpLog;

$rsvpNotice ??= null;
$options = [
    RsvpLog::ATTENDING => message('rsvp.attending'),
    RsvpLog::DECLINED  => message('rsvp.declined'),
];
$isInvited = $employee !== null;
// Fragment đưa người dùng không JS về đúng section khi form báo lỗi (fetch bỏ
// fragment, 303 có fragment riêng)
$rsvpAction = 'rsvp.php'
    . ($isInvited ? inviteQuery((string) employeeUuid($employee)) : '')
    . '#xac-nhan';
$fields = [
    'name' => [
        'label'        => message('rsvp.name_label'),
        'type'         => 'text',
        'value'        => $isInvited ? stringField($employee, 'name') : ($rsvpDraft['name'] ?? ''),
        'placeholder'  => message('rsvp.name_placeholder'),
        'autocomplete' => 'name',
        'maxlength'    => NAME_MAX_LENGTH,
    ],
    'email' => [
        'label'        => message('email_label'),
        'type'         => 'email',
        'value'        => $isInvited ? stringField($employee, 'email') : ($rsvpDraft['email'] ?? ''),
        'placeholder'  => message('email_placeholder'),
        'autocomplete' => 'email',
        'maxlength'    => 254,
    ],
];
$attendanceDraft = $rsvpDraft['attendance'] ?? null;
?>
<?php if ($rsvp !== null): ?>
    <div class="rsvp-done" role="status">
        <svg
            class="rsvp-done-icon"
            aria-hidden="true"
            width="28"
            height="28"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
        ><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        <?php if ($rsvpNotice !== null): ?>
            <p class="rsvp-note"><?= e($rsvpNotice) ?></p>
        <?php endif; ?>
        <?php if (isAttending($rsvp)): ?>
            <p class="rsvp-thanks"><?= e(message('rsvp.thanks_attending', ['event' => $event['title']])) ?></p>
            <dl class="rsvp-summary">
                <div><dt><?= e(message('rsvp.name_label')) ?></dt><dd><?= e(stringField($employee ?? [], 'name')) ?></dd></div>
                <div><dt><?= e(message('email_label')) ?></dt><dd><?= e(stringField($employee ?? [], 'email')) ?></dd></div>
                <div><dt><?= e(message('rsvp.status_label')) ?></dt><dd><?= e(message('rsvp.status_attending')) ?></dd></div>
            </dl>
            <p class="rsvp-note">
                <?= e(message('rsvp.recorded')) ?>
                <?= e(message(canDownloadTicket($employee ?? []) ? 'rsvp.ticket_below' : 'rsvp.ticket_pending')) ?>
            </p>
        <?php else: ?>
            <p class="rsvp-thanks"><?= e(message('thanks_declined')) ?></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php if (isset($rsvpErrors['form'])): ?>
        <p class="rsvp-alert" role="alert"><?= e($rsvpErrors['form']) ?></p>
    <?php endif; ?>

    <form class="rsvp-form" method="post" action="<?= e($rsvpAction) ?>" novalidate data-rsvp-form>
        <?php foreach ($fields as $key => $field): ?>
            <div class="rsvp-field">
                <input
                    class="rsvp-input"
                    id="rsvp-<?= $key ?>"
                    type="<?= $field['type'] ?>"
                    name="<?= $key ?>"
                    value="<?= e($field['value']) ?>"
                    placeholder="<?= e($field['placeholder']) ?>"
                    aria-label="<?= e($field['label']) ?>"
                    autocomplete="<?= $field['autocomplete'] ?>"
                    maxlength="<?= $field['maxlength'] ?>"
                    required
                    <?= $isInvited ? 'readonly' : '' ?>
                    <?= isset($rsvpErrors[$key]) ? 'aria-invalid="true" aria-describedby="rsvp-' . $key . '-error"' : '' ?>
                >
                <?php if (isset($rsvpErrors[$key])): ?>
                    <p class="rsvp-error" id="rsvp-<?= $key ?>-error"><?= e($rsvpErrors[$key]) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <fieldset
            class="rsvp-field"
            aria-label="<?= e(message('rsvp.attendance_label')) ?>"
            <?= isset($rsvpErrors['attendance']) ? 'aria-describedby="rsvp-attendance-error"' : '' ?>
        >
            <?php foreach ($options as $value => $label): ?>
                <label class="rsvp-option">
                    <input
                        type="radio"
                        name="attendance"
                        value="<?= $value ?>"
                        required<?= $attendanceDraft === $value ? ' checked' : '' ?>
                    >
                    <span><?= e($label) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if (isset($rsvpErrors['attendance'])): ?>
                <p class="rsvp-error" id="rsvp-attendance-error"><?= e($rsvpErrors['attendance']) ?></p>
            <?php endif; ?>
        </fieldset>

        <p class="rsvp-hint"><?= e(message('rsvp.hint')) ?></p>
        <button class="rsvp-submit" type="submit" data-busy-label="<?= e(message('rsvp.submitting')) ?>"><?= e(message('rsvp.submit')) ?></button>
    </form>
<?php endif; ?>
