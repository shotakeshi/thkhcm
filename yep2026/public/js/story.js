// ===== Landing một trang: reveal chung, ánh sáng nền theo vùng cuộn, seam giữa các section, tiến độ cuộn, gửi RSVP bằng fetch =====
// Thời lượng/easing/stagger của hiệu ứng nằm trong css (token --reveal-* ở theme.css). File này chỉ gắn trạng thái và giá trị theo vị trí cuộn.
(function () {
    'use strict';

    // Cờ cho script đầu trang: không có cờ sau 4 giây => gỡ `js` để nội dung hiện sẵn
    window.storyReady = true;

    const root = document.documentElement;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clamp = (v, min = 0, max = 1) => Math.min(max, Math.max(min, v));
    const REVEAL = {rootMargin: '0px 0px -12% 0px', threshold: .15};

    // ===== Hiện dần khi cuộn tới =====
    function initReveal() {
        const items = document.querySelectorAll('[data-reveal]');
        const show = (el) => el.classList.add('is-in');

        if (reduced || !('IntersectionObserver' in window)) {
            items.forEach(show);
            return;
        }

        // Tiêu đề bị clip-path che hết => diện tích giao bằng 0, nên theo dõi qua phần tử cha
        const watched = new Map();
        items.forEach((el) => {
            const target = el.dataset.reveal === 'title' ? el.parentElement : el;
            watched.set(target, [...(watched.get(target) || []), el]);
        });

        const io = new IntersectionObserver((entries) => {
            // Lớp giấy xé còn phủ: chưa hiện, yep:cover-open sẽ quét lại các phần tử đang trong khung nhìn
            if (root.classList.contains('is-waiting')) {
                return;
            }

            entries.forEach((entry) => {
                if (entry.isIntersecting && watched.has(entry.target)) {
                    watched.get(entry.target).forEach(show);
                    watched.delete(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, REVEAL);

        const observeAll = () => watched.forEach((_, target) => io.observe(target));

        observeAll();
        document.addEventListener('yep:cover-open', () => {
            io.disconnect();
            observeAll();
        }, {once: true});
    }

    // ===== Ánh sáng nền (story.css .dawn): "light" khi giữa khung nhìn nằm trong vùng nội dung, ngoài vùng đó là "dawn" =====
    // Chỉ theo dõi một dải giữa màn hình => tại chỗ giao hai section chỉ có một trạng thái, không nhấp nháy.
    // CSS lo chuyển tiếp mềm giữa hai trạng thái; không có IntersectionObserver thì giữ "dawn".
    function initLight() {
        const zone = document.querySelector('[data-light-zone]');

        if (!zone || !('IntersectionObserver' in window)) {
            return;
        }

        new IntersectionObserver(([entry]) => {
            root.dataset.light = entry.isIntersecting ? 'light' : 'dawn';
        }, {rootMargin: '-45% 0px -45% 0px'}).observe(zone);
    }

    // ===== Giá trị theo vị trí cuộn: tiến độ trang, seam =====
    function initScrollEffects() {
        const bar = document.querySelector('.progress-bar');
        let queued = false;

        // Phần tử liền kề của seam; seam đầu/cuối của slot vé thì lấy section liền kề slot
        const neighbor = (seam, side) => {
            let el = seam[`${side}ElementSibling`];

            if (!el && seam.parentElement?.hasAttribute('data-ticket-slot')) {
                el = seam.parentElement[`${side}ElementSibling`];
            }

            return el;
        };

        const progressOf = (rect, vh) => clamp((vh - rect.top) / (vh + rect.height));

        const update = () => {
            queued = false;
            const vh = window.innerHeight;
            const max = root.scrollHeight - vh;
            // Đọc hết vị trí trước khi ghi style: đọc xen kẽ ghi buộc trình duyệt tính lại style ngay trong vòng lặp
            const measure = (selector) => [...document.querySelectorAll(selector)].map((el) => [el, el.getBoundingClientRect()]);
            const seams = measure('.seam');
            const tracks = measure('[data-progress]');

            if (bar) {
                bar.style.transform = `scaleX(${max > 0 ? clamp(window.scrollY / max) : 0})`;
            }

            seams.forEach(([seam, rect]) => {
                const live = rect.bottom > -vh && rect.top < vh * 2;
                const before = neighbor(seam, 'previous');
                const after = neighbor(seam, 'next');

                seam.classList.toggle('is-live', live);
                before?.classList.add('seam-before');
                after?.classList.add('seam-after');

                if (live) {
                    const p = progressOf(rect, vh).toFixed(3);
                    seam.style.setProperty('--seam-p', p);
                    // Section phía trên mờ dần khi seam đi qua (story.css đọc --seam-out)
                    before?.style.setProperty('--seam-out', p);
                }
            });

            tracks.forEach(([el, rect]) => {
                // Trục timeline: vẽ tới điểm 60% chiều cao màn hình
                if (rect.bottom > -vh && rect.top < vh * 2) {
                    el.style.setProperty('--seam-p', clamp((vh * .6 - rect.top) / rect.height).toFixed(3));
                }
            });
        };

        const request = () => {
            if (!queued) {
                queued = true;
                requestAnimationFrame(update);
            }
        };

        window.addEventListener('scroll', request, {passive: true});
        window.addEventListener('resize', request);
        // Seam mới được chèn (vé hiện/ẩn) hoặc cổng vừa mở => tính lại
        document.addEventListener('yep:ticket-ready', request);
        document.addEventListener('yep:cover-open', request);
        update();
    }

    // ===== Xác nhận tham gia + tra cứu vé: gửi bằng fetch, thay thẻ + vé; lỗi mạng/không phải JSON => gửi form thường =====
    function loadTicketScript(src) {
        if (document.querySelector('script[data-ticket-js]')) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = src;
            script.dataset.ticketJs = '';
            script.onload = resolve;
            script.onerror = resolve;
            document.body.append(script);
        });
    }

    // Chờ phần tử có class is-in (tối đa timeout ms)
    function whenIn(el, timeout = 2000) {
        if (el.classList.contains('is-in')) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const observer = new MutationObserver(() => {
                if (el.classList.contains('is-in')) {
                    observer.disconnect();
                    resolve();
                }
            });

            observer.observe(el, {attributes: true, attributeFilter: ['class']});
            setTimeout(() => {
                observer.disconnect();
                resolve();
            }, timeout);
        });
    }

    // Form gửi bằng fetch: thẻ chứa form (thay bằng HTML server trả về) và khoá HTML trong JSON
    const ASYNC_FORMS = [
        {attr: 'data-rsvp-form', card: 'rsvp-card', html: 'rsvpHtml'},
        {attr: 'data-ticket-lookup-form', card: 'ticket-lookup-card', html: 'lookupHtml'},
    ];

    function focusFirst(container, selector) {
        const el = container.querySelector(selector);

        if (el) {
            el.tabIndex = -1;
            el.focus();
        }
    }

    async function applyFormResponse(config, response, data) {
        const card = document.getElementById(config.card);
        card.innerHTML = data[config.html];
        // HTML server render theo cookie ngôn ngữ; khách đổi ngôn ngữ trong lúc chờ phản hồi => dịch lại cho khớp
        window.yepI18n?.apply(card);

        if (!response.ok || !data.ok) {
            // 422/500: form kèm lỗi, đưa focus tới thông báo lỗi đầu tiên
            focusFirst(card, '.rsvp-alert, .rsvp-error');

            return;
        }

        if (config.card === 'rsvp-card') {
            // Đã trả lời => câu nhắc hạn phản hồi không còn đúng (khớp quy tắc render phía server)
            document.querySelector('#xac-nhan .rsvp-lead')?.remove();
        }

        const slot = document.querySelector('[data-ticket-slot]');
        slot.innerHTML = data.ticketHtml;

        document.querySelector('.toast-region')?.remove();

        if (data.overlaysHtml) {
            document.body.insertAdjacentHTML('beforeend', data.overlaysHtml);
        }

        if (data.ticketScript) {
            await loadTicketScript(data.ticketScript);
        }

        document.dispatchEvent(new CustomEvent('yep:ticket-ready'));

        const ticket = slot.querySelector('.page-ticket');

        if (!(data.attending && ticket)) {
            // Nút gửi đã bị thay nên focus rơi về body: đưa focus tới kết quả (cũng giúp trình đọc màn hình đọc kết quả)
            focusFirst(card, '.rsvp-done, .rsvp-result');

            return;
        }

        ticket.scrollIntoView({behavior: reduced ? 'auto' : 'smooth', block: 'start'});

        const name = ticket.querySelector('.ticket-name');

        if (name) {
            // Vé chỉ hiện (visibility) khi vào khung nhìn => chờ .is-in rồi mới đưa focus
            await whenIn(ticket);
            name.tabIndex = -1;
            name.focus({preventScroll: true});
        }
    }

    function initAsyncForms() {
        document.addEventListener('submit', async (event) => {
            const form = event.target;
            const config = form instanceof HTMLFormElement
                ? ASYNC_FORMS.find((item) => form.hasAttribute(item.attr))
                : null;

            if (!config) {
                return;
            }

            event.preventDefault();

            const button = form.querySelector('[type="submit"]');

            // Chống gửi đôi khi bấm liên tiếp: khoá nút tới khi có phản hồi
            if (button?.disabled) {
                return;
            }

            const label = button?.textContent;

            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = button.dataset.busyLabel || label;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {Accept: 'application/json'},
                });
                const data = await response.json();

                if (typeof data[config.html] !== 'string') {
                    throw new Error(`Phản hồi không có ${config.html}`);
                }

                await applyFormResponse(config, response, data);
            } catch (error) {
                console.error(error);
                // Gửi form thường (đường không JS): server vẫn chống ghi trùng
                HTMLFormElement.prototype.submit.call(form);
            } finally {
                // Thành công thì form đã bị thay; còn trong DOM (lỗi) thì mở khoá lại
                if (button?.isConnected) {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                    button.textContent = label;
                    // Khách đổi ngôn ngữ trong lúc gửi => nhãn vừa trả lại là ngôn ngữ cũ
                    window.yepI18n?.apply(button);
                }
            }
        });

        // Khách đổi ngôn ngữ trong lúc gửi: i18n.js đặt lại nhãn nút về "Xác nhận" / "Tìm vé" dù nút vẫn đang khoá
        // => trả lại nhãn đang gửi (data-busy-label đã được dịch sang ngôn ngữ mới)
        document.addEventListener('yep:locale-change', () => {
            document.querySelectorAll('[type="submit"][aria-busy="true"]').forEach((button) => {
                if (button.dataset.busyLabel) {
                    button.textContent = button.dataset.busyLabel;
                }
            });
        });
    }

    function initAttendeesHeight() {
        const box = document.querySelector('.page-attendees .attendees');
        const schedule = document.querySelector('.page-schedule .program');

        if (!box || !schedule || !('ResizeObserver' in window)) {
            return;
        }

        const sync = () => {
            const scale = box.offsetWidth ? box.getBoundingClientRect().width / box.offsetWidth : 1;
            const height = schedule.getBoundingClientRect().height / (scale || 1);

            if (height > 0) {
                box.style.setProperty('--attendees-max', `${Math.round(height)}px`);
                box.classList.add('is-capped');
            }
        };

        sync();
        new ResizeObserver(sync).observe(schedule);
    }

    initReveal();
    initLight();
    initScrollEffects();
    initAsyncForms();
    initAttendeesHeight();
})();
