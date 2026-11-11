// Công tắc bật/tắt form xác nhận tham dự và tra cứu vé trên trang admin (gọi admin-settings.php).
// Bọc trong IIFE để không trùng tên hằng/hàm toàn cục với app.js.
(() => {
    const ADMIN_TOKEN = document.body.dataset.adminToken ?? '';
    const input = document.getElementById('rsvpEnabledSwitch');
    const state = document.getElementById('rsvpEnabledState');

    if (!input) {
        return;
    }

    function render(enabled) {
        input.checked = enabled;

        if (state) {
            state.textContent = enabled ? 'Đang mở' : 'Đang ẩn';
        }
    }

    /**
     * Gọi admin-settings.php. Không throw, luôn trả về {ok, rsvpEnabled?, error?}.
     */
    async function requestChange(enabled) {
        const body = new FormData();
        body.append('token', ADMIN_TOKEN);
        body.append('rsvpEnabled', enabled ? '1' : '0');

        let response;

        try {
            response = await fetch('admin-settings.php', {method: 'POST', body});
        } catch (error) {
            return {ok: false, error: 'Không kết nối được máy chủ: ' + error.message};
        }

        try {
            return await response.json();
        } catch {
            return {ok: false, error: `Máy chủ trả về lỗi không xác định (HTTP ${response.status}).`};
        }
    }

    input.addEventListener('change', async () => {
        const wanted = input.checked;

        input.disabled = true;

        const result = await requestChange(wanted);

        if (!result.ok) {
            alert(result.error ?? 'Không lưu được cài đặt');
        }

        // Luôn hiện trạng thái thật của server (lỗi => quay về giá trị cũ)
        render(typeof result.rsvpEnabled === 'boolean' ? result.rsvpEnabled : !wanted);
        input.disabled = false;
    });
})();
