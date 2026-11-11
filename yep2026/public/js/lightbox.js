// ===== Lightbox dùng chung: click ảnh có [data-lightbox] để xem phóng to =====
// Chỉ ảnh nội dung mới gắn data-lightbox (partials/recap.php, closure $photo); icon, logo, ảnh nền không gắn.
// Event delegation trên document nên ảnh render động vẫn hoạt động. Giao diện + fade nằm trong css/lightbox.css.
(function () {
    'use strict';

    const LIGHTBOX = {
        selector: 'img[data-lightbox]',
        dragTolerance: 6,   // px: con trỏ di chuyển quá ngưỡng này giữa pointerdown và click => đang kéo (album), không mở
        fadeMs: 260,        // ≥ thời lượng transition opacity của .lightbox trong lightbox.css
    };

    let dialog = null;
    let image = null;
    let closeTimer = 0;
    let pointerStart = null;

    // Dựng một <dialog> duy nhất khi cần lần đầu: top layer nên không đụng z-index của Recap/thiệp.
    // Nhãn theo ngôn ngữ (lang/: lightbox.*): data-i18n-attr để i18n.js dịch lại khi khách đổi ngôn ngữ
    function build() {
        dialog = document.createElement('dialog');
        dialog.className = 'lightbox';
        dialog.dataset.i18nAttr = 'aria-label:lightbox.label';

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'lightbox-close';
        close.dataset.i18nAttr = 'aria-label:lightbox.close';
        close.innerHTML = '<svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none"'
            + ' stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            + '<path d="M6 6l12 12M18 6L6 18"/></svg>';

        image = document.createElement('img');
        image.className = 'lightbox-img';
        image.decoding = 'async';

        dialog.append(close, image);
        document.body.append(dialog);
        window.yepI18n?.apply(dialog);

        close.addEventListener('click', hide);

        // Click/chạm vào vùng nền (không phải ảnh) => đóng
        dialog.addEventListener('click', (e) => {
            if (e.target !== image) {
                hide();
            }
        });

        // ESC: chặn đóng tức thì của <dialog> để chạy fade-out
        dialog.addEventListener('cancel', (e) => {
            e.preventDefault();
            hide();
        });
    }

    function show(source) {
        if (!dialog) {
            build();
        }

        clearTimeout(closeTimer);
        image.src = source.currentSrc || source.src;
        image.alt = source.alt;

        if (!dialog.open) {
            lockScroll(true);
            dialog.showModal();
        }

        // Ép reflow để trạng thái opacity 0 được áp trước, rồi mới thêm class cho transition fade-in chạy
        void dialog.offsetWidth;
        dialog.classList.add('is-open');
    }

    function hide() {
        if (!dialog || !dialog.open || !dialog.classList.contains('is-open')) {
            return;
        }

        dialog.classList.remove('is-open');
        closeTimer = setTimeout(() => {
            dialog.close();
            image.removeAttribute('src');
            lockScroll(false);
        }, LIGHTBOX.fadeMs);
    }

    // Khoá cuộn trang phía sau; bù độ rộng thanh cuộn để bố cục không giật ngang
    function lockScroll(lock) {
        const root = document.documentElement;

        if (lock) {
            root.style.setProperty('--lightbox-scrollbar', `${window.innerWidth - root.clientWidth}px`);
        }

        root.classList.toggle('is-lightbox-open', lock);
    }

    function lightboxImage(target) {
        return target instanceof Element ? target.closest(LIGHTBOX.selector) : null;
    }

    document.addEventListener('pointerdown', (e) => {
        pointerStart = {x: e.clientX, y: e.clientY};
    }, {passive: true});

    document.addEventListener('click', (e) => {
        const source = lightboxImage(e.target);

        if (!source || e.button !== 0) {
            return;
        }

        // Kéo album bằng chuột cũng sinh click khi nhả: bỏ qua
        if (pointerStart && Math.hypot(e.clientX - pointerStart.x, e.clientY - pointerStart.y) > LIGHTBOX.dragTolerance) {
            return;
        }

        e.preventDefault();
        show(source);
    });

    // Bàn phím: Enter/Space trên khung ảnh focus được (vd .salon figure[tabindex]) cũng mở preview
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }

        // Chỉ khung một ảnh (figure); không bắt Enter trên vùng chứa nhiều ảnh như .album[tabindex]
        const host = e.target;
        const source = host instanceof Element && host.matches('figure')
            ? host.querySelector(LIGHTBOX.selector)
            : null;

        if (source) {
            e.preventDefault();
            show(source);
        }
    });
})();
