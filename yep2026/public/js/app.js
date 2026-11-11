// ===== Cấu hình & trạng thái =====
const SEND_DELAY_MS = 1000;

const STATUS = {
    PENDING: 'pending',
    SENDING: 'sending',
    SENT: 'sent',
    TEST: 'test',
    FAILED: 'failed',
    // Có thể đã gửi (SMTP treo, hoặc gửi xong nhưng không lưu được nhật ký) => không tự gửi lại
    UNCERTAIN: 'uncertain',
};

// true khi nhật ký gửi (sent.json) hỏng => khoá mọi nút gửi để tránh gửi trùng
const SENDING_LOCKED = document.body.dataset.sendingLocked === 'true';

// send.php bắt buộc token admin
const ADMIN_TOKEN = document.body.dataset.adminToken ?? '';

const rows = [...document.querySelectorAll('.person[data-email]')];

const queueState = {
    running: false,
    done: 0,
    total: 0,
};

// ===== Helpers =====
// Các section có thể bị ẩn/comment trong HTML => mọi truy cập phần tử phải chịu được null.
function getElement(id) {
    return document.getElementById(id);
}

function setText(id, text) {
    const element = getElement(id);

    if (element) {
        element.textContent = text;
    }
}

function setDisabled(id, disabled) {
    const element = getElement(id);

    if (element) {
        element.disabled = disabled;
    }
}

function onEvent(id, eventName, handler) {
    getElement(id)?.addEventListener(eventName, handler);
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getRowsByStatus(status) {
    return rows.filter((row) => row.dataset.status === status);
}

/**
 * Các dòng gửi hàng loạt được: bỏ qua đã gửi và "cần kiểm tra" (có thể đã nhận rồi).
 */
function getUnsentRows() {
    return rows.filter((row) => ![STATUS.SENT, STATUS.UNCERTAIN].includes(row.dataset.status));
}

// ===== API =====

/**
 * Gọi send.php. Không throw, luôn trả về {ok, error?, sent_at?, test?, uncertain?}.
 */
async function requestSendEmail(email, force = false) {
    const body = new FormData();
    body.append('email', email);
    body.append('token', ADMIN_TOKEN);

    if (force) {
        body.append('force', '1');
    }

    let response;

    try {
        response = await fetch('send.php', {method: 'POST', body});
    } catch (error) {
        return {ok: false, error: 'Không kết nối được máy chủ: ' + error.message};
    }

    try {
        return await response.json();
    } catch {
        // Server trả về không phải JSON => không biết đã gửi tới đâu
        return {
            ok: false,
            uncertain: true,
            error: `Máy chủ trả về lỗi không xác định (HTTP ${response.status}). Kiểm tra hộp thư trước khi gửi lại.`,
        };
    }
}

/**
 * send.php trả 409 kèm sent_at khi email đã được gửi trước đó.
 */
function isAlreadySent(result) {
    return !result.ok && Boolean(result.sent_at);
}

// ===== Render =====
function statusHtml(status, info) {
    switch (status) {
        case STATUS.SENDING:
            return '<span class="status status-sending"><span class="spinner-border spinner-border-sm"></span>Đang gửi</span>';
        case STATUS.SENT:
            return '<span class="status status-sent"><i class="bi bi-check-circle-fill"></i>Đã gửi</span>'
                + `<span class="status-meta">${escapeHtml(info)}</span>`;
        case STATUS.TEST:
            return '<span class="status status-test"><i class="bi bi-check-circle"></i>Đã gửi thử</span>';
        case STATUS.FAILED:
            return '<span class="status status-failed"><i class="bi bi-exclamation-circle-fill"></i>Gửi lỗi</span>'
                + `<span class="status-meta" title="${escapeHtml(info)}">${escapeHtml(info)}</span>`;
        case STATUS.UNCERTAIN:
            return '<span class="status status-uncertain"><i class="bi bi-question-circle-fill"></i>Cần kiểm tra</span>'
                + `<span class="status-meta" title="${escapeHtml(info)}">${escapeHtml(info)}</span>`;
        default:
            return '<span class="status status-pending"><span class="status-dot"></span>Chưa gửi</span>';
    }
}

/**
 * info: thời gian gửi (sent) hoặc lý do lỗi (failed, uncertain).
 */
function renderRowStatus(row, status, info = '') {
    row.dataset.status = status;

    if (status === STATUS.SENT) {
        row.dataset.sentAt = info;
    }

    const cell = row.querySelector('.status-cell');

    if (cell) {
        cell.innerHTML = statusHtml(status, info);
    }
}

function renderSummary() {
    const total = rows.length;
    const sentCount = getRowsByStatus(STATUS.SENT).length;
    // "Lỗi" gồm cả các dòng cần kiểm tra
    const failedCount = getRowsByStatus(STATUS.FAILED).length + getRowsByStatus(STATUS.UNCERTAIN).length;

    setText('countSent', sentCount);
    setText('countFailed', failedCount);
    setText('countPending', total - sentCount - failedCount);
}

/**
 * Nhãn nút gửi hàng loạt: đang gửi x/y, đã gửi hết, hoặc số người chưa nhận.
 */
function renderBulkButton(unsentCount) {
    const btnBulk = getElement('btnBulk');

    if (!btnBulk) {
        return;
    }

    const {running, done, total} = queueState;
    const allSent = getRowsByStatus(STATUS.SENT).length === rows.length;

    if (SENDING_LOCKED) {
        btnBulk.innerHTML = '<i class="bi bi-lock-fill"></i>Đã khoá gửi';
    } else if (running) {
        btnBulk.innerHTML = `<span class="spinner-border spinner-border-sm"></span>Đang gửi ${done + 1}/${total}`;
    } else if (allSent) {
        btnBulk.innerHTML = '<i class="bi bi-check2"></i>Đã gửi cho tất cả';
    } else if (unsentCount === 0) {
        btnBulk.textContent = 'Không còn ai cần gửi';
    } else {
        btnBulk.textContent = `Gửi cho ${unsentCount} người chưa nhận`;
    }

    btnBulk.disabled = SENDING_LOCKED || running || unsentCount === 0;
}

/**
 * Bật/tắt và đặt nhãn các nút theo trạng thái hàng đợi.
 */
function renderControls() {
    const blocked = SENDING_LOCKED || queueState.running;
    // Chỉ gửi lại thư lỗi chắc chắn chưa đi; dòng "cần kiểm tra" phải gửi lại thủ công từng dòng
    const failedCount = getRowsByStatus(STATUS.FAILED).length;

    renderBulkButton(getUnsentRows().length);

    setText('btnRetryFailed', `Gửi lại ${failedCount} thư lỗi`);
    setDisabled('btnRetryFailed', blocked || failedCount === 0);

    rows.forEach((row) => {
        const button = row.querySelector('.btn-resend');

        if (button) {
            button.disabled = blocked;
        }
    });
}

function renderPage() {
    renderSummary();
    renderControls();
}

// ===== Gửi email =====

/**
 * Cập nhật dòng theo kết quả từ send.php.
 */
function applySendResult(row, result) {
    if (result.ok) {
        renderRowStatus(row, result.test ? STATUS.TEST : STATUS.SENT, result.sent_at ?? '');
    } else if (isAlreadySent(result)) {
        renderRowStatus(row, STATUS.SENT, result.sent_at);
    } else if (result.uncertain) {
        renderRowStatus(row, STATUS.UNCERTAIN, result.error);
    } else {
        renderRowStatus(row, STATUS.FAILED, result.error);
    }

    renderPage();
}

/**
 * Gửi email của 1 dòng trong hàng đợi, không hỏi xác nhận.
 */
async function sendRow(row) {
    renderRowStatus(row, STATUS.SENDING);
    applySendResult(row, await requestSendEmail(row.dataset.email));
}

/**
 * Gửi lại email của 1 dòng. Nếu đã gửi hoặc có thể đã gửi trước đó thì hỏi xác nhận trước.
 */
async function sendWithConfirm(row) {
    const email = row.dataset.email;

    if (row.dataset.status === STATUS.UNCERTAIN && !confirm(`${email} có thể đã nhận thư. Vẫn gửi lại?`)) {
        return;
    }

    renderRowStatus(row, STATUS.SENDING);

    let result = await requestSendEmail(email);

    if (isAlreadySent(result) && confirm(`${email} đã nhận thư lúc ${result.sent_at}. Gửi lại?`)) {
        result = await requestSendEmail(email, true);
    }

    applySendResult(row, result);
}

// ===== Hàng đợi gửi hàng loạt =====
function startQueue(total) {
    queueState.running = true;
    queueState.done = 0;
    queueState.total = total;
    renderControls();
}

function finishQueue() {
    queueState.running = false;
    renderControls();
}

/**
 * Gửi tuần tự: gửi xong 1 email (có response) thì chờ SEND_DELAY_MS rồi mới gửi email tiếp theo.
 */
async function runQueue(queue) {
    startQueue(queue.length);

    for (const row of queue) {
        await sendRow(row);
        queueState.done++;

        const isLastRow = queueState.done === queue.length;

        if (!isLastRow) {
            renderControls();
            await sleep(SEND_DELAY_MS);
        }
    }

    finishQueue();
}

// ===== Event handlers =====
function onBulkClick() {
    const queue = getUnsentRows();

    if (queue.length > 0 && confirm(`Gửi thư mời cho ${queue.length} người chưa nhận?`)) {
        runQueue(queue);
    }
}

function onRetryFailedClick() {
    runQueue(getRowsByStatus(STATUS.FAILED));
}

async function onRowResendClick(event) {
    const button = event.currentTarget;
    const row = button.closest('.person');

    button.disabled = true;
    await sendWithConfirm(row);
    button.disabled = queueState.running;
}

// ===== Khởi tạo =====
function init() {
    rows.forEach((row) => {
        renderRowStatus(row, row.dataset.status, row.dataset.sentAt);
        row.querySelector('.btn-resend')?.addEventListener('click', onRowResendClick);
    });

    onEvent('btnBulk', 'click', onBulkClick);
    onEvent('btnRetryFailed', 'click', onRetryFailedClick);

    renderPage();
}

init();
