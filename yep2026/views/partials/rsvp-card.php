<?php
/**
 * form xác nhận tham dự.
 * - Chưa xác nhận: form Full Name / Email / Attendance. Có link mời => tên và
 *   email điền sẵn từ dữ liệu khách, chỉ đọc.
 * - Đã xác nhận: trạng thái hiện tại, không cho đổi lựa chọn.
 * Dùng cho lần render đầu và cho phản hồi JSON (rsvpHtml) của public/rsvp.php.
 *
 * @var array $event
 * @var array|null $employee khách của link mời, null => khách tự đăng ký
 * @var array|null $rsvp record xác nhận trong data.json (invitation()['rsvp']), null => chưa xác nhận
 * @var array $rsvpErrors lỗi theo field: 'name', 'email', 'attendance', 'form' => messageRef()
 * @var array $rsvpDraft giá trị vừa gửi ('name', 'email', 'attendance') để giữ lại khi form lỗi
 * @var array|null $rsvpNotice thông báo thêm khi đã xác nhận trước đó (messageRef())
 *
 * Chữ lấy từ lang/ theo khoá; phần tử gắn i18n() để đổi ngôn ngữ tại chỗ.
 */

use App\Rsvp\GuestStore;

$rsvpNotice ??= null;
// Giá trị attendance => khoá nhãn
$options = [
    GuestStore::ATTENDING => 'rsvp.attending',
    GuestStore::DECLINED  => 'rsvp.declined',
];
$isInvited = $employee !== null;
// Fragment đưa người dùng không JS về đúng section khi form báo lỗi (fetch bỏ
// fragment, 303 có fragment riêng)
$rsvpAction = 'rsvp.php'
    . ($isInvited ? inviteQuery((string) employeeUuid($employee)) : '')
    . '#xac-nhan';
$fields = [
    'name' => [
        'label'        => 'rsvp.name_label',
        'type'         => 'text',
        'value'        => $isInvited ? stringField($employee, 'name') : ($rsvpDraft['name'] ?? ''),
        'placeholder'  => 'rsvp.name_placeholder',
        'autocomplete' => 'name',
        'maxlength'    => NAME_MAX_LENGTH,
    ],
    'email' => [
        'label'        => 'email_label',
        'type'         => 'email',
        'value'        => $isInvited ? stringField($employee, 'email') : ($rsvpDraft['email'] ?? ''),
        'placeholder'  => 'email_placeholder',
        'autocomplete' => 'email',
        'maxlength'    => 254,
    ],
];
$attendanceDraft = $rsvpDraft['attendance'] ?? null;
$thanksArgs = ['event' => $event['title']];
$ticketNoteKey = canDownloadTicket($employee ?? []) ? 'rsvp.ticket_below' : 'rsvp.ticket_pending';
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
            <p class="rsvp-note"<?= i18n(...$rsvpNotice) ?>><?= e(message(...$rsvpNotice)) ?></p>
        <?php endif; ?>
        <?php if (isAttending($rsvp)): ?>
            <p class="rsvp-thanks"<?= i18n('rsvp.thanks_attending', $thanksArgs) ?>>
                <?= e(message('rsvp.thanks_attending', $thanksArgs)) ?>
            </p>
            <dl class="rsvp-summary">
                <div>
                    <dt<?= i18n('rsvp.name_label') ?>><?= e(message('rsvp.name_label')) ?></dt>
                    <dd><?= e(stringField($employee ?? [], 'name')) ?></dd>
                </div>
                <div>
                    <dt<?= i18n('email_label') ?>><?= e(message('email_label')) ?></dt>
                    <dd><?= e(stringField($employee ?? [], 'email')) ?></dd>
                </div>
                <div>
                    <dt<?= i18n('rsvp.status_label') ?>><?= e(message('rsvp.status_label')) ?></dt>
                    <dd<?= i18n('rsvp.status_attending') ?>><?= e(message('rsvp.status_attending')) ?></dd>
                </div>
            </dl>
            <p class="rsvp-note">
                <span<?= i18n('rsvp.recorded') ?>><?= e(message('rsvp.recorded')) ?></span>
                <span<?= i18n($ticketNoteKey) ?>><?= e(message($ticketNoteKey)) ?></span>
            </p>
        <?php else: ?>
            <p class="rsvp-thanks"<?= i18n('thanks_declined') ?>><?= e(message('thanks_declined')) ?></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php if (isset($rsvpErrors['form'])): ?>
        <p class="rsvp-alert" role="alert"<?= i18n(...$rsvpErrors['form']) ?>><?= e(message(...$rsvpErrors['form'])) ?></p>
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
                    placeholder="<?= e(message($field['placeholder'])) ?>"
                    aria-label="<?= e(message($field['label'])) ?>"
                    <?= i18n(null, [], ['placeholder' => $field['placeholder'], 'aria-label' => $field['label']]) ?>
                    autocomplete="<?= $field['autocomplete'] ?>"
                    maxlength="<?= $field['maxlength'] ?>"
                    required
                    <?= $isInvited ? 'readonly' : '' ?>
                    <?= isset($rsvpErrors[$key]) ? 'aria-invalid="true" aria-describedby="rsvp-' . $key . '-error"' : '' ?>
                >
                <?php if (isset($rsvpErrors[$key])): ?>
                    <p class="rsvp-error" id="rsvp-<?= $key ?>-error"<?= i18n(...$rsvpErrors[$key]) ?>>
                        <?= e(message(...$rsvpErrors[$key])) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <fieldset
            class="rsvp-field"
            aria-label="<?= e(message('rsvp.attendance_label')) ?>"
            <?= i18n(null, [], ['aria-label' => 'rsvp.attendance_label']) ?>
            <?= isset($rsvpErrors['attendance']) ? 'aria-describedby="rsvp-attendance-error"' : '' ?>
        >
            <?php foreach ($options as $value => $labelKey): ?>
                <label class="rsvp-option">
                    <input
                        type="radio"
                        name="attendance"
                        value="<?= $value ?>"
                        required<?= $attendanceDraft === $value ? ' checked' : '' ?>
                    >
                    <span<?= i18n($labelKey) ?>><?= e(message($labelKey)) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if (isset($rsvpErrors['attendance'])): ?>
                <p class="rsvp-error" id="rsvp-attendance-error"<?= i18n(...$rsvpErrors['attendance']) ?>>
                    <?= e(message(...$rsvpErrors['attendance'])) ?>
                </p>
            <?php endif; ?>
        </fieldset>

        <p class="rsvp-hint"<?= i18n('rsvp.hint') ?>><?= e(message('rsvp.hint')) ?></p>
        <button
            class="rsvp-submit"
            type="submit"
            data-busy-label="<?= e(message('rsvp.submitting')) ?>"
            <?= i18n('rsvp.submit', [], ['data-busy-label' => 'rsvp.submitting']) ?>
        ><?= e(message('rsvp.submit')) ?></button>
    </form>
<?php endif; ?>
