// ===== Thông số motion (theo bảng thông số motion của thiết kế) =====
// Hiệu ứng CSS (thời lượng, độ trễ, easing) nằm ở mục "Motion" trong css/invite.css.
const MOTION = {
    fadeMs: 380,
    tear: {
        notchMin: 34,         // vết xé có sẵn: sâu max(34px, 5% H)
        notchRatio: .05,
        pulseSpeed: 2.2,      // mép khe hé thêm theo nhịp ~2.85s khi chờ
        pulseGap: 2.2,
        stepPx: 7,            // mỗi bước xé tối đa 7px
        dragGain: 1.2,        // kéo 1px => xé 1.2px
        steerMin: .2,         // đầu vết xé bám x con trỏ trong 20–80% chiều rộng
        steerMax: .8,
        autoAt: .5,           // qua 50% chiều cao => tự xé nốt
        autoSpeed: 1.7,       // tốc độ tự xé: 1.7 H/s
        gapIdle: .1,
        gapTear: .085,        // mỗi mép lùi 8.5% khoảng cách tới đầu vết xé
        gapMax: .11,          // tối đa 11% W
        bitEvery: 24,         // cứ 24px rơi 1–2 mảnh vụn
        gravity: 2.1,         // trọng lực 2.1 H/s²
        fallSec: 1.35,
        doneSec: 2.4,
        tapMovePx: 8,         // chạm nhanh (< 8px, < 400ms) => tự xé hết
        tapMs: 400,
    },
    counter: {
        delayMs: 1500,        // đếm số bàn cùng lúc badge phóng to
        stepMs: 60,
        maxSteps: 8,
    },
};

const OPENED_KEY = 'yep26-opened';

const root = document.documentElement;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// ===== Lớp giấy xé mở thiệp =====
class TearCover {
    constructor(cover, {onReveal, onDone}) {
        this.cover = cover;
        this.cv = cover.querySelector('.tear-canvas');
        this.ctx = this.cv.getContext('2d');
        this.hint = cover.querySelector('.tear-hint');
        this.hintText = cover.querySelector('.tear-hint-text');
        this.onReveal = onReveal;
        this.onDone = onDone;
        this.reduced = prefersReducedMotion();
        this.phase = 'idle';
        this.pts = [];
        this.bits = [];
        this.pieces = [];
        this.drag = null;
        this.fallT = 0;
        this.last = 0;

        this.resize();
        this.initNotch();

        this.buildTexture();

        if (window.ResizeObserver) {
            this.ro = new ResizeObserver(() => {
                this.resize();
                this.buildTexture();

                if (this.phase === 'idle') {
                    this.initNotch();
                }
            });
            this.ro.observe(this.cv);
        }

        this.cv.addEventListener('pointerdown', (e) => this.down(e));
        this.cv.addEventListener('pointermove', (e) => this.move(e));
        this.cv.addEventListener('pointerup', () => this.up());
        this.cv.addEventListener('pointercancel', () => this.up());

        // Bàn phím: Enter/Space mở thiệp. Nội dung phía sau đang inert nên phím rơi về document;
        // riêng nút chuyển ngôn ngữ (nằm trên lớp giấy) vẫn nhận focus => phím trên nút đó không mở thiệp
        this.onKey = (e) => {
            if (e.target instanceof Element && e.target.closest('button, a, input, select, textarea')) {
                return;
            }

            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.open();
            }
        };
        document.addEventListener('keydown', this.onKey);

        this.tick = (ts) => this.frame(ts);
        this.raf = requestAnimationFrame(this.tick);
    }

    // Toạ độ vẽ theo kích thước layout, độ phân giải theo devicePixelRatio (tối đa 2)
    resize() {
        const rect = this.cv.getBoundingClientRect();
        this.W = Math.max(1, this.cv.clientWidth || rect.width);
        this.H = Math.max(1, this.cv.clientHeight || rect.height);

        const dpr = Math.min(2, window.devicePixelRatio || 1);
        this.cv.width = Math.max(1, Math.round(rect.width * dpr));
        this.cv.height = Math.max(1, Math.round(rect.height * dpr));
        this.dpr = this.cv.width / this.W;
        this.ctx.setTransform(this.dpr, 0, 0, this.cv.height / this.H, 0, 0);

        // Nội dung theo ngôn ngữ (lang/: cover.*): đổi khoá data-i18n, i18n.js dịch lại cả khi khách đổi ngôn ngữ
        if (this.reduced) {
            this.hintText.dataset.i18n = 'cover.hint_tap';
        } else {
            this.hintText.dataset.i18n = this.W > 640 ? 'cover.hint_drag' : 'cover.hint_swipe';
        }

        window.yepI18n?.apply(this.hintText);
    }

    // Texture giấy: nền kem + sợi giấy + vignette nhẹ, vẽ một lần vào canvas phụ
    buildTexture() {
        const c = document.createElement('canvas');
        c.width = this.cv.width;
        c.height = this.cv.height;

        const x = c.getContext('2d');
        x.fillStyle = '#faf6ec';
        x.fillRect(0, 0, c.width, c.height);
        x.lineWidth = this.dpr * .6;

        const fibers = Math.round(this.W * this.H / 90);

        for (let i = 0; i < fibers; i++) {
            const px = Math.random() * c.width;
            const py = Math.random() * c.height;
            const a = Math.random() * Math.PI;
            const l = (2 + Math.random() * 6) * this.dpr;
            x.strokeStyle = Math.random() < .5 ? 'rgba(120,105,80,.05)' : 'rgba(255,255,255,.35)';
            x.beginPath();
            x.moveTo(px, py);
            x.lineTo(px + Math.cos(a) * l, py + Math.sin(a) * l);
            x.stroke();
        }

        const g = x.createRadialGradient(c.width / 2, c.height / 2, Math.min(c.width, c.height) * .3, c.width / 2, c.height / 2, Math.max(c.width, c.height) * .75);
        g.addColorStop(0, 'rgba(0,0,0,0)');
        g.addColorStop(1, 'rgba(60,50,30,.07)');
        x.fillStyle = g;
        x.fillRect(0, 0, c.width, c.height);
        this.tex = c;
    }

    mkPt(x, y) {
        return {x, y, f: 1 + Math.random() * 2.4, fib: Math.random() < .45 ? 2 + Math.random() * 5 : 0, fa: (Math.random() - .5) * 1.2};
    }

    // Vết xé có sẵn ở giữa mép trên
    initNotch() {
        this.x0 = this.W * .5;
        this.pts = [this.mkPt(this.x0, 0)];

        const depth = Math.max(MOTION.tear.notchMin, this.H * MOTION.tear.notchRatio);
        let x = this.x0;

        for (let y = 6; y <= depth; y += 6) {
            x += (Math.random() - .5) * 3.5;
            this.pts.push(this.mkPt(x, y));
        }
    }

    tip() {
        return this.pts[this.pts.length - 1];
    }

    gapAt(y, t) {
        const tip = this.tip();
        const k = this.phase === 'idle' ? MOTION.tear.gapIdle : MOTION.tear.gapTear;
        let g = Math.min(Math.max(0, tip.y - y) * k, this.W * MOTION.tear.gapMax);

        if (this.phase === 'idle' && !this.reduced) {
            const pulse = Math.pow(Math.max(0, Math.sin(t * MOTION.tear.pulseSpeed)), 10) * MOTION.tear.pulseGap;
            g += pulse * Math.max(0, 1 - y / tip.y);
        }

        return g;
    }

    advance(dist, px, auto) {
        let rem = dist;
        let tip = this.tip();

        while (rem > .01) {
            const step = Math.min(rem, MOTION.tear.stepPx);
            rem -= step;

            const tx = Math.min(this.W * MOTION.tear.steerMax, Math.max(this.W * MOTION.tear.steerMin, px));
            const steer = Math.max(-step * .8, Math.min(step * .8, (tx - tip.x) * .14));
            const p = this.mkPt(tip.x + steer + (Math.random() - .5) * 3.4, tip.y + step);
            this.pts.push(p);
            tip = p;
            this.since = (this.since || 0) + step;

            if (this.since > MOTION.tear.bitEvery) {
                this.since = 0;
                this.emitBits(tip.x, tip.y, Math.random() < .4 ? 2 : 1, 6);
            }
        }

        if (!auto && tip.y > this.H * MOTION.tear.autoAt) {
            this.startAuto();
        }
    }

    emitBits(x, y, n, spread) {
        for (let i = 0; i < n; i++) {
            const s = 2.5 + Math.random() * 4.5;
            const m = 4 + Math.floor(Math.random() * 2);
            const shape = [];

            for (let j = 0; j < m; j++) {
                const a = j / m * Math.PI * 2 + (Math.random() - .5) * .6;
                const r = s * (.6 + Math.random() * .5);
                shape.push([Math.cos(a) * r, Math.sin(a) * r]);
            }

            this.bits.push({x: x + (Math.random() - .5) * spread, y, vx: (Math.random() - .5) * 110, vy: -60 - Math.random() * 90, r: Math.random() * 6.28, vr: (Math.random() - .5) * 9, shape});
        }
    }

    setHintVisible(visible) {
        this.hint.hidden = !visible;
    }

    startAuto() {
        if (this.phase !== 'idle' && this.phase !== 'tearing') {
            return;
        }

        const tip = this.tip();
        this.phase = 'auto';
        this.autoX = Math.min(this.W * .7, Math.max(this.W * .3, tip.x + (Math.random() - .5) * this.W * .12));
        this.setHintVisible(false);
    }

    // Xé hết: tách thành 2 mảnh rơi theo trọng lực
    startFall() {
        const tip = this.tip();
        const cap = this.W * MOTION.tear.gapMax;
        const L = [];
        const R = [];

        for (const p of this.pts) {
            const g = Math.min(cap, Math.max(0, tip.y - p.y) * MOTION.tear.gapTear);
            L.push({x: p.x - g, y: p.y, f: p.f, fib: p.fib, fa: p.fa});
            R.push({x: p.x + g, y: p.y, f: p.f, fib: p.fib, fa: p.fa});
        }

        const end = L[L.length - 1].y;
        const sc = Math.sqrt(this.W / 375);
        this.pieces = [
            {poly: [{x: 0, y: 0}].concat(L, [{x: 0, y: end}]), edge: L, side: -1, pv: {x: this.W * .25, y: this.H * .55}, x: 0, y: 0, r: 0, vx: -170 * sc, vy: -90, vr: -.85},
            {poly: R.concat([{x: this.W, y: end}, {x: this.W, y: 0}]), edge: R, side: 1, pv: {x: this.W * .75, y: this.H * .55}, x: 0, y: 0, r: 0, vx: 170 * sc, vy: -80, vr: .8},
        ];

        for (let i = 0; i < 14; i++) {
            const p = this.pts[Math.floor(Math.random() * this.pts.length)];
            this.emitBits(p.x, p.y, 1, 4);
        }

        this.phase = 'falling';
        this.fallT = 0;
        this.reveal();
    }

    reveal() {
        this.cover.classList.add('is-revealed');
        this.onReveal();
    }

    finish() {
        this.phase = 'done';
        cancelAnimationFrame(this.raf);
        document.removeEventListener('keydown', this.onKey);

        if (this.ro) {
            this.ro.disconnect();
        }

        this.onDone();
    }

    open() {
        if (this.phase !== 'idle' && this.phase !== 'tearing') {
            return;
        }

        // Giảm chuyển động: không xé, lớp giấy chỉ mờ đi
        if (this.reduced) {
            this.phase = 'fading';
            this.setHintVisible(false);
            this.cover.classList.add('is-fading');
            this.reveal();
            setTimeout(() => this.finish(), MOTION.fadeMs);
            return;
        }

        this.startAuto();
    }

    local(e) {
        const rect = this.cv.getBoundingClientRect();

        return {x: (e.clientX - rect.left) * this.W / rect.width, y: (e.clientY - rect.top) * this.H / rect.height};
    }

    down(e) {
        if (this.phase !== 'idle' && this.phase !== 'tearing') {
            return;
        }

        const p = this.local(e);
        this.drag = {x: p.x, y: p.y, t: performance.now(), moved: 0};

        try {
            this.cv.setPointerCapture(e.pointerId);
        } catch {
            // Một số trình duyệt không cho capture pointer đã kết thúc
        }
    }

    // Kéo xuống mới xé; kéo ngang hoặc kéo lên không xé
    move(e) {
        const d = this.drag;

        if (!d) {
            return;
        }

        const p = this.local(e);
        const dy = p.y - d.y;
        d.moved += Math.hypot(p.x - d.x, dy);
        d.x = p.x;
        d.y = p.y;

        if (this.reduced || dy <= 0) {
            return;
        }

        if (this.phase === 'idle') {
            this.phase = 'tearing';
            this.setHintVisible(false);
        }

        if (this.phase === 'tearing') {
            this.advance(dy * MOTION.tear.dragGain, p.x, false);
        }
    }

    up() {
        const d = this.drag;
        this.drag = null;

        if (d && d.moved < MOTION.tear.tapMovePx && performance.now() - d.t < MOTION.tear.tapMs) {
            this.open();
        }
    }

    update(dt) {
        if (this.phase === 'auto') {
            this.advance(this.H * MOTION.tear.autoSpeed * dt, this.autoX, true);

            if (this.tip().y >= this.H + 6) {
                this.startFall();
            }
        }

        if (this.phase === 'falling' || this.phase === 'settling') {
            this.fallT += dt;

            const G = this.H * MOTION.tear.gravity;

            for (const pc of this.pieces) {
                pc.vy += G * dt;
                pc.x += pc.vx * dt;
                pc.y += pc.vy * dt;
                pc.r += pc.vr * dt;
            }

            if (this.phase === 'falling' && this.fallT > MOTION.tear.fallSec) {
                this.phase = 'settling';
                this.pieces = [];
            }

            if (this.phase === 'settling' && (this.bits.length === 0 || this.fallT > MOTION.tear.doneSec)) {
                this.finish();
                return;
            }
        }

        for (const b of this.bits) {
            b.vy += 900 * dt;
            b.vx *= 1 - .6 * dt;
            b.x += b.vx * dt;
            b.y += b.vy * dt;
            b.r += b.vr * dt;
        }

        this.bits = this.bits.filter((b) => b.y < this.H + 30);
    }

    frame(ts) {
        const dt = this.last ? Math.min(.033, (ts - this.last) / 1000) : .016;
        this.last = ts;
        this.update(dt);

        if (this.phase === 'done') {
            return;
        }

        this.draw(ts / 1000);

        // Nền màu giấy của .tear-cover chỉ để che trang trước khi canvas vẽ xong khung đầu
        if (!this.ready) {
            this.ready = true;
            this.cover.classList.add('is-ready');
        }

        this.raf = requestAnimationFrame(this.tick);
    }

    fillPaper(path, blur, oy) {
        const c = this.ctx;
        c.save();

        if (blur > 0) {
            c.shadowColor = 'rgba(20,36,70,.30)';
            c.shadowBlur = blur;
            c.shadowOffsetY = oy;
        }

        c.fillStyle = '#faf6ec';
        path();
        c.fill();
        c.restore();

        if (this.tex) {
            c.save();
            path();
            c.clip();
            c.drawImage(this.tex, 0, 0, this.W, this.H);
            c.restore();
        }
    }

    // Bóng của tờ giấy chỉ lộ ra trong khe xé => đổ bóng từ dải hẹp dọc mép rách thay vì cả tờ (blur cả màn hình rất tốn)
    drawEdgeShadow(E, side) {
        const c = this.ctx;
        c.save();
        c.shadowColor = 'rgba(20,36,70,.30)';
        c.shadowBlur = 16;
        c.shadowOffsetY = 2;
        c.fillStyle = '#faf6ec';
        c.beginPath();
        E.forEach((q, k) => (k ? c.lineTo(q.x, q.y) : c.moveTo(q.x, q.y)));

        for (let j = E.length - 1; j >= 0; j--) {
            c.lineTo(E[j].x + side * 24, E[j].y);
        }

        c.closePath();
        c.fill();
        c.restore();
    }

    // Mép rách: lõi giấy trắng + viền mảnh + sợi giấy chĩa vào khe
    drawFringe(E, side) {
        const c = this.ctx;
        c.beginPath();
        E.forEach((q, k) => (k ? c.lineTo(q.x, q.y) : c.moveTo(q.x, q.y)));

        for (let j = E.length - 1; j >= 0; j--) {
            c.lineTo(E[j].x + side * E[j].f, E[j].y);
        }

        c.closePath();
        c.fillStyle = 'rgba(255,255,255,.92)';
        c.fill();
        c.strokeStyle = 'rgba(168,156,134,.45)';
        c.lineWidth = .6;
        c.beginPath();
        E.forEach((q, k) => (k ? c.lineTo(q.x, q.y) : c.moveTo(q.x, q.y)));
        c.stroke();
        c.strokeStyle = 'rgba(255,255,255,.9)';
        c.lineWidth = .7;
        c.beginPath();

        for (const q of E) {
            if (!q.fib) {
                continue;
            }

            c.moveTo(q.x, q.y);
            c.lineTo(q.x - side * q.fib * Math.cos(q.fa), q.y + q.fib * Math.sin(q.fa));
        }

        c.stroke();
    }

    // Mép giấy gần chỗ bắt đầu xé cong ngược lên, lộ mặt sau
    drawCurl(E, side) {
        const c = this.ctx;
        const tip = E[E.length - 1];
        const cMax = Math.min(16, Math.abs(E[0].x - this.pts[0].x) * .55);

        if (cMax < .8) {
            return;
        }

        const len = Math.min(tip.y * .6, this.H * .32);
        const inner = [];
        c.beginPath();

        for (let i = 0; i < E.length && E[i].y <= len; i++) {
            const q = E[i];

            if (i === 0) {
                c.moveTo(q.x, q.y);
            } else {
                c.lineTo(q.x, q.y);
            }

            inner.push({x: q.x + side * cMax * Math.pow(1 - q.y / len, 1.3), y: q.y});
        }

        if (inner.length < 2) {
            return;
        }

        for (let j = inner.length - 1; j >= 0; j--) {
            c.lineTo(inner[j].x, inner[j].y);
        }

        c.closePath();

        const gr = c.createLinearGradient(E[0].x, 0, E[0].x + side * cMax, 0);
        gr.addColorStop(0, '#fffdf8');
        gr.addColorStop(.6, '#eee8dc');
        gr.addColorStop(1, '#ddd5c6');
        c.fillStyle = gr;
        c.fill();
        c.save();
        c.strokeStyle = 'rgba(60,48,28,.20)';
        c.lineWidth = 1.2;
        c.shadowColor = 'rgba(60,48,28,.25)';
        c.shadowBlur = 4;
        c.beginPath();
        inner.forEach((q, k) => (k ? c.lineTo(q.x, q.y) : c.moveTo(q.x, q.y)));
        c.stroke();
        c.restore();
    }

    drawSheet(t) {
        const c = this.ctx;
        const L = [];
        const R = [];

        for (const p of this.pts) {
            const g = this.gapAt(p.y, t);
            L.push({x: p.x - g, y: p.y, f: p.f, fib: p.fib, fa: p.fa});
            R.push({x: p.x + g, y: p.y, f: p.f, fib: p.fib, fa: p.fa});
        }

        const W = this.W;
        const H = this.H;
        const path = () => {
            c.beginPath();
            c.moveTo(0, 0);

            for (const q of L) {
                c.lineTo(q.x, q.y);
            }

            for (let i = R.length - 1; i >= 0; i--) {
                c.lineTo(R[i].x, R[i].y);
            }

            c.lineTo(W, 0);
            c.lineTo(W, H);
            c.lineTo(0, H);
            c.closePath();
        };

        this.drawEdgeShadow(L, -1);
        this.drawEdgeShadow(R, 1);
        this.fillPaper(path, 0, 0);
        c.save();
        path();
        c.clip();
        this.drawCurl(L, -1);
        this.drawCurl(R, 1);
        c.restore();
        this.drawFringe(L, -1);
        this.drawFringe(R, 1);
    }

    drawPiece(pc) {
        const c = this.ctx;
        const lift = Math.min(1, this.fallT / .5);
        c.save();
        c.translate(pc.pv.x + pc.x, pc.pv.y + pc.y);
        c.rotate(pc.r);

        // Nửa giấy nhấc lên (+4%), bóng đổ đậm dần
        const s = 1 + lift * .04;
        c.scale(s, s);
        c.translate(-pc.pv.x, -pc.pv.y);

        const path = () => {
            c.beginPath();
            pc.poly.forEach((q, k) => (k ? c.lineTo(q.x, q.y) : c.moveTo(q.x, q.y)));
            c.closePath();
        };

        this.fillPaper(path, 16 + lift * 26, 2 + lift * 14);
        this.drawFringe(pc.edge, pc.side);
        c.restore();
    }

    drawBits() {
        const c = this.ctx;

        for (const b of this.bits) {
            c.save();
            c.translate(b.x, b.y);
            c.rotate(b.r);
            c.shadowColor = 'rgba(20,36,70,.25)';
            c.shadowBlur = 3;
            c.shadowOffsetY = 1;
            c.fillStyle = '#fbf8f1';
            c.beginPath();
            b.shape.forEach((q, k) => (k ? c.lineTo(q[0], q[1]) : c.moveTo(q[0], q[1])));
            c.closePath();
            c.fill();
            c.restore();
        }
    }

    draw(t) {
        const c = this.ctx;
        c.clearRect(0, 0, this.W, this.H);

        if (['idle', 'tearing', 'auto', 'fading'].includes(this.phase)) {
            this.drawSheet(t);
        } else if (this.phase === 'falling') {
            this.pieces.forEach((pc) => this.drawPiece(pc));
        }

        this.drawBits();
    }
}

// ===== Page 1: thiệp mời =====
function initInviteCover() {
    const cover = document.querySelector('.tear-cover');

    if (!cover || !root.classList.contains('has-cover')) {
        cover?.remove();
        return;
    }

    const covered = document.querySelectorAll('.skip, .recap, .scene');

    // Nội dung dưới lớp giấy (liên kết bỏ qua, Recap và các section sau) chưa tương tác được cho tới khi xé
    covered.forEach((el) => {
        el.inert = true;
    });

    new TearCover(cover, {
        onReveal() {
            covered.forEach((el) => {
                el.inert = false;
            });

            root.classList.remove('is-waiting');
            // Hiệu ứng vào của Recap đang chờ lớp giấy rời (story.js)
            document.dispatchEvent(new CustomEvent('yep:cover-open'));

            // Giữ cờ "đã mở" suốt phiên: quay lại thiệp không phải xé lại
            try {
                sessionStorage.setItem(OPENED_KEY, '1');
            } catch {
                // sessionStorage bị chặn (chế độ riêng tư) => lần sau xé lại
            }
        },
        onDone() {
            cover.remove();
            root.classList.remove('has-cover');
        },
    });
}

// Mốc 0, 0.05 … 1 để IntersectionObserver báo cả khi chỉ cuộn một phần
const RATIO_STEPS = Array.from({ length: 21 }, (_, i) => i / 20);

// Trang nằm dưới thiệp chạy hiệu ứng khi cuộn tới: thêm .is-in lúc vào khung nhìn, onEnter chạy mỗi lần vào.
// Trả về observer để caller ngắt khi phần tử bị thay (null khi trình duyệt không có IntersectionObserver).
function revealOnScroll(page, onEnter) {
    if (!('IntersectionObserver' in window)) {
        page.classList.add('is-in');
        onEnter?.();
        return null;
    }

    // Vào khung nhìn (≥35%) => chạy hiệu ứng từ đầu; chỉ còn <20% => reset để lần cuộn lại chạy lại
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.intersectionRatio >= 0.35 && !page.classList.contains('is-in')) {
                page.classList.add('is-in');
                onEnter?.();
            } else if (entry.intersectionRatio < 0.2 && page.classList.contains('is-in')) {
                page.classList.remove('is-in');
            }
        }
    }, { threshold: RATIO_STEPS });

    observer.observe(page);

    return observer;
}

// ===== Vé nằm dưới thiệp =====
// Vé bị thay mỗi lần gửi RSVP / tra cứu lại => ngắt observer của vé cũ (đã rời DOM) trước khi gắn cho vé mới
let ticketObserver = null;

function initTicketReveal() {
    ticketObserver?.disconnect();
    ticketObserver = null;

    const ticket = document.querySelector('.page-ticket');

    if (ticket) {
        // .has-reveal chỉ bật khi js chạy được: không có js thì vé hiện sẵn
        ticket.classList.add('has-reveal');
        ticketObserver = revealOnScroll(ticket, initTableCounter);
        fitTicketName();
        document.fonts?.ready.then(fitTicketName);
    }
}

// ===== Vé (PC): tên khách mời 1 dòng (nowrap trong invite.css), tràn thân vé thì thu nhỏ chữ cho vừa =====
function fitTicketName() {
    const name = document.querySelector('.ticket-name');

    if (!name) {
        return;
    }

    name.style.fontSize = '';

    // Mobile tên xuống dòng bình thường => giữ cỡ chữ theo token
    if (getComputedStyle(name).whiteSpace !== 'nowrap') {
        return;
    }

    // Fraunces có trục optical size: chữ nhỏ lại thì nét bè ra => co theo tỉ lệ vài lần mới vừa
    for (let i = 0; i < 4 && name.scrollWidth > name.clientWidth; i++) {
        const size = parseFloat(getComputedStyle(name).fontSize);
        name.style.fontSize = `${Math.floor(size * name.clientWidth / name.scrollWidth)}px`;
    }
}

// ===== Thiệp nằm dưới Recap: hiệu ứng vào chạy khi cuộn tới (không còn gắn với lúc xé giấy) =====
function initInviteReveal() {
    const invite = document.querySelector('.page-invite');

    if (!invite) {
        return;
    }

    // .has-reveal chỉ bật khi js chạy được: lỗi js thì hiệu ứng chạy ngay như bình thường
    invite.classList.add('has-reveal');
    revealOnScroll(invite);
}

// ===== Vé: đếm số bàn 00 → giá trị thật =====
let counterTimers = [];

function initTableCounter() {
    counterTimers.forEach((id) => {
        clearTimeout(id);
        clearInterval(id);
    });
    counterTimers = [];

    const badge = document.querySelector('[data-count-to]');
    const target = Number(badge?.dataset.countTo);

    if (!badge || !Number.isInteger(target) || target <= 0 || prefersReducedMotion()) {
        return;
    }

    const steps = Math.min(target, MOTION.counter.maxSteps);
    const format = (value) => String(value).padStart(2, '0');
    let step = 0;

    badge.textContent = format(0);

    counterTimers.push(setTimeout(() => {
        const timer = setInterval(() => {
            step++;
            badge.textContent = format(Math.round(target * step / steps));

            if (step >= steps) {
                clearInterval(timer);
            }
        }, MOTION.counter.stepMs);

        counterTimers.push(timer);
    }, MOTION.counter.delayMs));
}

// ===== Video nền thiệp: mỗi breakpoint một <video>, <source media> chỉ cho bản đang hiện tải file (views/invite.php) =====
// Trình duyệt chỉ xét media lúc chọn nguồn => sang breakpoint kia (xoay máy, đổi cỡ cửa sổ) thì nạp bản chưa có nguồn.
function initBgVideos() {
    const videos = document.querySelectorAll('video.bg-video');

    if (!videos.length) {
        return;
    }

    window.matchMedia('(max-width: 640px)').addEventListener?.('change', () => {
        videos.forEach((video) => {
            if (!video.currentSrc) {
                video.load();
            }
        });
    });
}

// ===== Khởi tạo =====
// Vé được chèn sau khi trang đã tải (story.js, sau khi gửi RSVP) => gắn lại hiệu ứng vé
document.addEventListener('yep:ticket-ready', initTicketReveal);
window.addEventListener('resize', fitTicketName);

window.yepMotionReady = true;
initInviteCover();
initTicketReveal();
initInviteReveal();
initBgVideos();
