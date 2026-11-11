// ===== Song ngữ VI / EN: đổi ngôn ngữ tại chỗ, không tải lại trang =====
// Từ điển do server nhúng (views/partials/i18n-script.php, nguồn: lang/{locale}/messages.php).
// Phần tử có data-i18n="khoá" được thay nội dung; data-i18n-attr="thuộc-tính:khoá;…" thay thuộc tính;
// data-i18n-args='{"tên": giá trị | {"vi": …, "en": …}}' là tham số {tên} (do bootstrap.php i18n() sinh ra).
// Ngôn ngữ khách chọn lưu ở localStorage và cookie (server đọc cookie để render đúng ngôn ngữ, kể cả HTML trả về
// từ fetch RSVP / tra cứu vé). Script khác dùng window.yepI18n và sự kiện yep:locale-change.
(function () {
    'use strict';

    const STORAGE_KEY = 'yep26-locale';
    const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

    const root = document.documentElement;
    const source = document.getElementById('i18n-data');
    const data = source ? JSON.parse(source.textContent) : {defaultLocale: root.lang, messages: {}};
    const locales = Object.keys(data.messages);
    let current = locales.includes(root.lang) ? root.lang : data.defaultLocale;

    // ===== Tra nội dung =====
    // Thiếu khoá ở ngôn ngữ đang chọn => bản mặc định (tiếng Việt), vẫn thiếu => trả lại khoá (giống message() phía server)
    function lookup(key, locale) {
        const own = data.messages[locale]?.[key];

        if (own !== undefined) {
            return own;
        }

        const fallback = data.messages[data.defaultLocale]?.[key];

        if (fallback !== undefined) {
            console.warn(`i18n: "${locale}" thiếu khoá ${key}, dùng bản ${data.defaultLocale}`);
            return fallback;
        }

        console.warn(`i18n: thiếu khoá ${key}`);
        return key;
    }

    // Tham số theo ngôn ngữ ({"vi": …, "en": …}) hoặc dùng chung (chuỗi / số)
    function argValue(value, locale) {
        if (value !== null && typeof value === 'object') {
            return String(value[locale] ?? value[data.defaultLocale] ?? '');
        }

        return String(value);
    }

    function t(key, args = {}, locale = current) {
        return lookup(key, locale).replace(/\{(\w+)\}/g, (match, name) => (
            Object.prototype.hasOwnProperty.call(args, name) ? argValue(args[name], locale) : match
        ));
    }

    // ===== Áp nội dung lên DOM =====
    // Xuống dòng trong nội dung => <br> (khớp nl2br phía server), không dùng innerHTML
    function setText(el, text) {
        if (!text.includes('\n')) {
            el.textContent = text;
            return;
        }

        const nodes = [];

        text.split('\n').forEach((line, index) => {
            if (index > 0) {
                nodes.push(document.createElement('br'));
            }

            nodes.push(document.createTextNode(line));
        });

        el.replaceChildren(...nodes);
    }

    function argsOf(el) {
        if (!el.dataset.i18nArgs) {
            return {};
        }

        try {
            return JSON.parse(el.dataset.i18nArgs);
        } catch (error) {
            console.error(error);
            return {};
        }
    }

    function translateElement(el) {
        const args = argsOf(el);

        if (el.dataset.i18n) {
            setText(el, t(el.dataset.i18n, args));
        }

        (el.dataset.i18nAttr || '').split(';').forEach((pair) => {
            const [attr, key] = pair.split(':');

            if (attr && key) {
                el.setAttribute(attr, t(key, args));
            }
        });
    }

    // scope: phần tử vừa chèn (thẻ RSVP, vé…) hoặc cả trang
    function apply(scope = document) {
        const selector = '[data-i18n], [data-i18n-attr]';

        if (scope instanceof Element && scope.matches(selector)) {
            translateElement(scope);
        }

        scope.querySelectorAll(selector).forEach(translateElement);
    }

    function syncSwitches() {
        document.querySelectorAll('[data-locale]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.locale === current));
        });
    }

    // ===== Ghi nhớ lựa chọn =====
    function readStored() {
        try {
            const value = localStorage.getItem(STORAGE_KEY);

            return locales.includes(value) ? value : null;
        } catch {
            // localStorage bị chặn (chế độ riêng tư) => chỉ còn cookie
            return null;
        }
    }

    function persist(locale) {
        try {
            localStorage.setItem(STORAGE_KEY, locale);
        } catch {
            // localStorage bị chặn => cookie vẫn giữ lựa chọn
        }

        if (data.cookie) {
            document.cookie = `${data.cookie}=${locale}; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`;
        }
    }

    function setLocale(locale, {save = true} = {}) {
        if (!locales.includes(locale)) {
            return;
        }

        // Ghi cookie trước khi đổi giao diện: request kế tiếp (fetch RSVP) đã mang ngôn ngữ mới
        if (save) {
            persist(locale);
        }

        if (locale === current) {
            syncSwitches();
            return;
        }

        current = locale;
        root.lang = locale;
        apply();
        syncSwitches();
        document.dispatchEvent(new CustomEvent('yep:locale-change', {detail: {locale}}));
    }

    window.yepI18n = {
        get locale() {
            return current;
        },
        t,
        apply,
        setLocale,
    };

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-locale]') : null;

        if (button) {
            setLocale(button.dataset.locale);
        }
    });

    // Lần trước khách đã chọn nhưng cookie hết hạn / bị xoá (server render ngôn ngữ mặc định) => áp lại lựa chọn.
    // Chưa từng chọn => giữ ngôn ngữ server render, không tự ghi lựa chọn thay khách.
    const stored = readStored();

    if (stored) {
        setLocale(stored);
    } else {
        syncSwitches();
    }
})();
