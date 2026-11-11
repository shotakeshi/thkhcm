// ===== Landing "A Year to Remember": chương đang xem, album =====
// Ánh sáng nền theo vùng cuộn (dawn/light) nằm ở story.js.
// Thời lượng/easing của hiệu ứng nằm trong css/recap.css. File này chỉ gắn trạng thái và giá trị theo vị trí cuộn.
(function () {
    'use strict';

    const RECAP = {
        activeLine: .45,      // chương đang xem = chương có mép trên vượt 45% chiều cao màn hình
        swingMs: 1200,        // một nhịp đung đưa của ảnh trên dây (≥ thời lượng album-swing trong recap.css)
    };

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ===== Chương đang xem theo vị trí cuộn =====
    function initScroll() {
        const chapters = [...document.querySelectorAll('[data-chapter]')];

        let active = null;
        let queued = false;

        const update = () => {
            queued = false;
            const vh = window.innerHeight;

            let current = chapters[0];
            chapters.forEach((ch) => {
                if (ch.getBoundingClientRect().top <= vh * RECAP.activeLine) {
                    current = ch;
                }
            });

            if (current !== active) {
                active = current;
            }
        };

        const request = () => {
            if (!queued) {
                queued = true;
                requestAnimationFrame(update);
            }
        };

        window.addEventListener('scroll', request, {passive: true});
        window.addEventListener('resize', request);
        update();
    }

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

        window.addEventListener('pointermove', (e) => {
            if (!drag) {
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

        window.addEventListener('pointerup', () => {
            if (!drag) {
                return;
            }

            const left = album.scrollLeft;
            drag = null;
            album.classList.remove('is-drag');
            // Bật lại snap rồi giữ nguyên vị trí để snap mượt tới ảnh gần nhất
            album.scrollLeft = left;
        });
    }

    initScroll();
    initAlbum();
})();
