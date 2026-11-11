// ===== Landing "A Year to Remember": album =====
// Ánh sáng nền theo vùng cuộn (dawn/light) nằm ở story.js.
// Thời lượng/easing của hiệu ứng nằm trong css/recap.css. File này chỉ gắn trạng thái và giá trị theo vị trí cuộn.
(function () {
    'use strict';

    const RECAP = {
        swingMs: 1200,        // một nhịp đung đưa của ảnh trên dây (≥ thời lượng album-swing trong recap.css)
    };

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ===== Album khoảnh khắc: nút trước/sau + kéo bằng chuột =====
    function initAlbum() {
        const album = document.querySelector('.album');
        const prev = document.querySelector('[data-album="-1"]');
        const next = document.querySelector('[data-album="1"]');

        if (!album) {
            return;
        }

        const step = () => {
            const item = album.querySelector('.album-item');
            const gap = parseFloat(getComputedStyle(album).columnGap) || 0;

            return item ? item.offsetWidth + gap : album.clientWidth * .8;
        };

        const syncButtons = () => {
            prev.disabled = album.scrollLeft <= 4;
            next.disabled = album.scrollLeft >= album.scrollWidth - album.clientWidth - 4;
        };

        [prev, next].forEach((btn) => btn.addEventListener('click', () => {
            album.scrollBy({left: step() * Number(btn.dataset.album), behavior: reduced ? 'auto' : 'smooth'});
        }));

        album.addEventListener('scroll', syncButtons, {passive: true});
        window.addEventListener('resize', syncButtons);
        syncButtons();

        // Ảnh treo trên dây đung đưa một nhịp khi album cuộn; chạy hết nhịp mới nhận nhịp tiếp
        if (!reduced) {
            let swing = 0;

            album.addEventListener('scroll', () => {
                if (swing) {
                    return;
                }

                album.classList.add('is-swing');
                swing = setTimeout(() => {
                    album.classList.remove('is-swing');
                    swing = 0;
                }, RECAP.swingMs);
            }, {passive: true});
        }

        // Cảm ứng đã cuộn ngang tự nhiên; chỉ thêm kéo cho chuột
        let drag = null;

        album.addEventListener('pointerdown', (e) => {
            if (e.pointerType !== 'mouse' || e.button !== 0) {
                return;
            }

            drag = {x: e.clientX, left: album.scrollLeft, moved: false};
        });

        const endDrag = () => {
            if (!drag) {
                return;
            }

            const left = album.scrollLeft;
            drag = null;
            album.classList.remove('is-drag');
            // Bật lại snap rồi giữ nguyên vị trí để snap mượt tới ảnh gần nhất
            album.scrollLeft = left;
        };

        window.addEventListener('pointermove', (e) => {
            if (!drag) {
                return;
            }

            // Nút trái đã nhả mà không có pointerup (nhả ngoài cửa sổ, sự kiện bị nuốt) => dừng kéo, không cuộn theo chuột
            if ((e.buttons & 1) === 0) {
                endDrag();
                return;
            }

            const dx = e.clientX - drag.x;

            if (!drag.moved && Math.abs(dx) > 4) {
                drag.moved = true;
                album.classList.add('is-drag');
            }

            if (drag.moved) {
                album.scrollLeft = drag.left - dx;
            }
        });

        window.addEventListener('pointerup', endDrag);
        // Trình duyệt huỷ pointer (bắt đầu kéo-thả ảnh gốc, mất focus...) thì không có pointerup
        window.addEventListener('pointercancel', endDrag);
    }

    initAlbum();
})();
