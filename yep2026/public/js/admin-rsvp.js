// Đổi/xoá xác nhận tham dự trên trang admin (gọi admin-rsvp.php).
// Cập nhật tại chỗ, không tải lại trang: trạng thái gửi thư "Lỗi"/"Cần kiểm tra" chỉ có trên màn hình.
// Phần RSVP của row chia theo cột (data-rsvp-slot = status | time | actions), thay riêng từng slot
// để không đụng phần gửi thư nằm cùng cột.
// Bọc trong IIFE để không trùng tên hằng/hàm toàn cục với app.js.
(() => {
    const ADMIN_TOKEN = document.body.dataset.adminToken ?? '';

    const SLOTS = ['status', 'time', 'actions'];

    const COUNTERS = {
        countAttending: 'attending',
        countDeclined: 'declined',
        countNoReply: 'none',
    };

    function renderSummary() {
        const statuses = [...document.querySelectorAll('[data-rsvp-slot="status"]')]
            .map((el) => el.dataset.rsvpStatus);

        Object.entries(COUNTERS).forEach(([id, status]) => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = statuses.filter((value) => value === status).length;
            }
        });

        const selfCount = document.getElementById('selfRegisteredCount');

        if (selfCount) {
            selfCount.textContent = document.querySelectorAll('#selfRegisteredList .person').length;
        }
    }

    /**
     * Gọi admin-rsvp.php. Không throw, luôn trả về {ok, html?, error?}.
     */
    async function requestChange(uuid, action, attendance) {
        const body = new FormData();
        body.append('token', ADMIN_TOKEN);
        body.append('uuid', uuid);
        body.append('action', action);

        if (attendance) {
            body.append('attendance', attendance);
        }

        let response;

        try {
            response = await fetch('admin-rsvp.php', {method: 'POST', body});
        } catch (error) {
            return {ok: false, error: 'Không kết nối được máy chủ: ' + error.message};
        }

        try {
            return await response.json();
        } catch {
            return {ok: false, error: `Máy chủ trả về lỗi không xác định (HTTP ${response.status}). Tải lại trang để xem trạng thái hiện tại.`};
        }
    }

    /**
     * html null => khách không còn trong data.json (record bị xoá ngoài trang admin) => bỏ row.
     */
    function applyHtml(row, html) {
        if (html === null) {
            row.remove();
        } else {
            SLOTS.forEach((slot) => {
                const element = row.querySelector(`[data-rsvp-slot="${slot}"]`);

                if (element && typeof html[slot] === 'string') {
                    element.outerHTML = html[slot];
                }
            });
        }

        renderSummary();
    }

    function setBusy(row, busy) {
        const actions = row.querySelector('[data-rsvp-slot="actions"]');

        actions?.classList.toggle('is-busy', busy);
        actions?.querySelectorAll('.rsvp-action').forEach((button) => {
            button.disabled = busy;
        });
    }

    async function onActionClick(button) {
        if (!confirm(button.dataset.confirm)) {
            return;
        }

        const row = button.closest('[data-uuid]');

        setBusy(row, true);

        const result = await requestChange(row.dataset.uuid, button.dataset.action, button.dataset.attendance);

        if (!result.ok) {
            alert(result.error ?? 'Không lưu được xác nhận');
        }

        // Lỗi có kèm html (khách chưa xác nhận, đã bị xoá ở nơi khác) => vẫn hiện trạng thái mới nhất
        if (result.html !== undefined) {
            applyHtml(row, result.html);
        } else {
            setBusy(row, false);
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.rsvp-action');

        if (button && !button.disabled) {
            onActionClick(button);
        }
    });

    renderSummary();
})();
