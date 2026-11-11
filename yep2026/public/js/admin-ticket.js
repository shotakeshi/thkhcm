// Sửa mã Lucky Draw và số bàn trên trang admin (gọi admin-ticket.php).
// Cập nhật tại chỗ cột Vé (data-ticket-slot), badge "Chưa có vé" (data-rsvp-slot="status")
// và nút thao tác (data-rsvp-slot="actions") của row.
// Bọc trong IIFE để không trùng tên hằng/hàm toàn cục với app.js.
(() => {
    const ADMIN_TOKEN = document.body.dataset.adminToken ?? '';
    const dialog = document.getElementById('ticketDialog');
    const form = document.getElementById('ticketForm');

    if (!dialog || !form) {
        return;
    }

    const guestLabel = document.getElementById('ticketDialogGuest');
    const errorBox = document.getElementById('ticketDialogError');
    const fields = [form.elements.luckyDrawCode, form.elements.tableNumber];
    const submit = form.querySelector('[type="submit"]');

    // Row đang sửa và giá trị admin đang thấy (gửi kèm để server không ghi đè thay đổi mới hơn)
    let row = null;
    let expected = null;

    function showError(message) {
        errorBox.textContent = message ?? '';
        errorBox.hidden = !message;
    }

    function setBusy(busy) {
        fields.forEach((field) => {
            field.readOnly = busy;
        });
        submit.disabled = busy;
    }

    function readExpected(button) {
        return {luckyDrawCode: button.dataset.code ?? '', tableNumber: button.dataset.table ?? ''};
    }

    function open(button) {
        row = button.closest('[data-uuid]');
        expected = readExpected(button);
        form.elements.luckyDrawCode.value = expected.luckyDrawCode;
        form.elements.tableNumber.value = expected.tableNumber;
        guestLabel.textContent = button.dataset.name ?? '';
        showError(null);
        setBusy(false);
        dialog.showModal();
        form.elements.luckyDrawCode.select();
    }

    /**
     * Gọi admin-ticket.php. Không throw, luôn trả về {ok, html?, error?}.
     */
    async function requestChange(uuid) {
        const body = new FormData();
        body.append('token', ADMIN_TOKEN);
        body.append('uuid', uuid);
        body.append('luckyDrawCode', form.elements.luckyDrawCode.value);
        body.append('tableNumber', form.elements.tableNumber.value);
        body.append('expectedLuckyDrawCode', expected.luckyDrawCode);
        body.append('expectedTableNumber', expected.tableNumber);

        let response;

        try {
            response = await fetch('admin-ticket.php', {method: 'POST', body});
        } catch (error) {
            return {ok: false, error: 'Không kết nối được máy chủ: ' + error.message};
        }

        try {
            return await response.json();
        } catch {
            return {ok: false, error: `Máy chủ trả về lỗi không xác định (HTTP ${response.status}). Tải lại trang để xem giá trị hiện tại.`};
        }
    }

    /**
     * Thay cột Vé và badge trạng thái bằng html mới nhất.
     */
    function applyHtml(target, html) {
        const slots = {
            ticket: target.querySelector('[data-ticket-slot]'),
            status: target.querySelector('[data-rsvp-slot="status"]'),
        };

        Object.entries(slots).forEach(([key, element]) => {
            if (element && typeof html[key] === 'string') {
                element.outerHTML = html[key];
            }
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!row || submit.disabled) {
            return;
        }

        const target = row;

        setBusy(true);
        showError(null);

        const result = await requestChange(target.dataset.uuid);

        if (result.html) {
            applyHtml(target, result.html);
        }

        setBusy(false);

        if (result.ok) {
            dialog.close();
            return;
        }

        // Giá trị trên server đã khác (vừa bị sửa ở nơi khác): lần Lưu tiếp theo dựa trên giá trị mới
        const button = target.querySelector('.ticket-edit');

        if (button && target === row) {
            expected = readExpected(button);
        }

        showError(result.error ?? 'Không lưu được vé');
    });

    form.querySelector('[data-ticket-cancel]')?.addEventListener('click', () => dialog.close());

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.ticket-edit');

        if (button) {
            open(button);
        }
    });
})();
