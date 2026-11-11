// ===== Cấu hình ảnh vé =====
// Ảnh vé vẽ lại bằng canvas theo bố cục vé ngang trên PC (xuất 2x), không chụp màn hình website.
// Vé giấy (paper.webp) nổi trên nền wine, cùng bảng màu --ticket-ink / --ticket-bronze trong public/css/invite.css.
const TICKET_IMAGE = {
    width: 1200,
    height: 630,
    scale: 2,
    ticketWidth: 1040,
    stubWidth: 250,
    radius: 20,
    notch: 12,
    images: {
        paper: 'images/paper.webp',
    },
    colors: {
        paper: '#f3e6c8',
        ink: '#3b1517',
        bronze: '#8a5a22',
        bronzeLine: 'rgba(138, 90, 34, .8)',
        bronzeLineThin: 'rgba(138, 90, 34, .45)',
        wine: '#3b1517',
        goldSoft: '#f2c670',
        goldRing: 'rgba(242, 198, 112, .6)',
        label: '#ffffff',
        // Nền wine: radial từ tâm sáng ra mép tối như nền trang
        bgStops: ['#4a1a22', '#3b1517', '#241019'],
    },
    fonts: {
        sans: "'Be Vietnam Pro'",
        serif: "'Fraunces'",
        digits: "'Fraunces'",
        script: "'Pinyon Script'",
    },
};

const TOAST_MS = 2600;

let toastTimer = null;

// ===== Vẽ ảnh vé =====
// Đường dẫn trống (ảnh tuỳ chọn đã bỏ trong TICKET_IMAGE.images) => null, phần vẽ bỏ qua ảnh đó
function loadImage(src) {
    if (!src) {
        return Promise.resolve(null);
    }

    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error(`Không tải được ảnh ${src}`));
        img.src = src;
    });
}

function fontSpec(family, size, weight = 400, style = 'normal') {
    return `${style} ${weight} ${size}px ${family}`;
}

/**
 * Chiều cao dòng "normal" như CSS (ascent + descent của font).
 */
function lineHeightOf(ctx, font, size) {
    ctx.font = font;
    const m = ctx.measureText('Ấg');

    return m.fontBoundingBoxAscent !== undefined ? m.fontBoundingBoxAscent + m.fontBoundingBoxDescent : size * 1.25;
}

function textWidth(ctx, text, spacing) {
    let width = 0;

    for (const ch of text) {
        width += ctx.measureText(ch).width;
    }

    return width + spacing * Math.max(0, [...text].length - 1);
}

/**
 * Vẽ 1 dòng chữ, y là tâm dòng (hoặc đường baseline nếu baseline = 'alphabetic'). Có letter-spacing thì vẽ từng ký tự.
 */
function drawText(ctx, text, x, y, {font, color, align = 'center', spacing = 0, baseline = 'middle'}) {
    ctx.font = font;
    ctx.fillStyle = color;
    ctx.textBaseline = baseline;

    if (spacing === 0) {
        ctx.textAlign = align;
        ctx.fillText(text, x, y);
        return;
    }

    const width = textWidth(ctx, text, spacing);
    let cx = align === 'center' ? x - width / 2 : x;
    ctx.textAlign = 'left';

    for (const ch of text) {
        ctx.fillText(ch, cx, y);
        cx += ctx.measureText(ch).width + spacing;
    }
}

/**
 * Thu nhỏ cỡ chữ cho vừa maxWidth (tên / mã / số bàn dài), từng bước 1px tới minSize.
 * Tới minSize vẫn tràn => thu tiếp theo tỉ lệ để chữ không đè sang cuống vé
 * (giống fitTicketName trong motion.js).
 */
function fitFontSize(ctx, text, fontFor, size, minSize, maxWidth, spacingEm) {
    while (size > minSize) {
        ctx.font = fontFor(size);

        if (textWidth(ctx, text, size * spacingEm) <= maxWidth) {
            return size;
        }

        size -= 1;
    }

    ctx.font = fontFor(size);
    const width = textWidth(ctx, text, size * spacingEm);

    return width > maxWidth ? Math.max(1, Math.floor(size * maxWidth / width)) : size;
}

function drawCover(ctx, img, x, y, width, height) {
    const scale = Math.max(width / img.naturalWidth, height / img.naturalHeight);
    const w = img.naturalWidth * scale;
    const h = img.naturalHeight * scale;
    ctx.drawImage(img, x + (width - w) / 2, y + (height - h) / 2, w, h);
}

/**
 * Vé ngang: thân + cuống, khoét lỗ bán nguyệt ở đường xé (cạnh trên và dưới tại x = joint).
 */
function ticketPath(ctx, x, y, w, h, joint) {
    const r = TICKET_IMAGE.radius;
    const notch = TICKET_IMAGE.notch;

    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(joint - notch, y);
    ctx.arc(joint, y, notch, Math.PI, 0, true);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r);
    ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
    ctx.lineTo(joint + notch, y + h);
    ctx.arc(joint, y + h, notch, 0, Math.PI, true);
    ctx.lineTo(x + r, y + h);
    ctx.arcTo(x, y + h, x, y + h - r, r);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r);
    ctx.closePath();
}

// Khung bo góc đồng tâm với vé: bán kính = radius - inset (tối thiểu 4px)
function strokeFrame(ctx, x, y, w, h, color, inset) {
    ctx.strokeStyle = color;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.roundRect(x + inset + .5, y + inset + .5, w - inset * 2 - 1, h - inset * 2 - 1, Math.max(4, TICKET_IMAGE.radius - inset));
    ctx.stroke();
}

async function renderTicketImage(data) {
    const {width: W, height: H, scale, ticketWidth, stubWidth, colors, fonts} = TICKET_IMAGE;
    const nameFont = (size) => fontSpec(fonts.sans, size, 600);
    const codeFont = (size) => fontSpec(fonts.sans, size, 600);
    const tableFont = (size) => fontSpec(fonts.sans, size, 600);
    const f = {
        label: fontSpec(fonts.sans, 12, 600),
        script: fontSpec(fonts.script, 46),
        year: fontSpec(fonts.digits, 20, 500),
        caption: fontSpec(fonts.sans, 20, 500),
        table: tableFont(68),
        name: nameFont(56),
        code: codeFont(34),
    };

    // Vé luôn tiếng Anh ở mọi ngôn ngữ (khớp views/partials/ticket.php)
    const sample = `${data.name}${data.code}YOUR PERSONAL TICKET GUEST LUCKY DRAW CODE TABLE NUMBER`;
    const [images] = await Promise.all([
        Promise.all(Object.values(TICKET_IMAGE.images).map(loadImage)),
        ...Object.values(f).map((font) => document.fonts.load(font, sample)),
    ]);
    const [paper] = images;

    const canvas = document.createElement('canvas');
    canvas.width = W * scale;
    canvas.height = H * scale;

    const ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);

    // Nền wine
    const bg = ctx.createRadialGradient(W / 2, H * .4, 0, W / 2, H * .4, W * .7);
    bg.addColorStop(0, colors.bgStops[0]);
    bg.addColorStop(.45, colors.bgStops[1]);
    bg.addColorStop(1, colors.bgStops[2]);
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    // Đo bố cục thân vé (padding 48 / 56 / 40 như CSS PC)
    const padX = 56;
    const bodyW = ticketWidth - stubWidth;
    const contentW = bodyW - padX * 2;

    const nameSize = fitFontSize(ctx, data.name, nameFont, 56, 28, contentW, .01);
    const codeSize = fitFontSize(ctx, data.code, codeFont, 34, 18, contentW, .16);

    const lh = {
        label: lineHeightOf(ctx, f.label, 12),
        script: 46 * 1.1,
        name: nameSize * 1.1,
        code: codeSize * 1.15,
        caption: 20 * 1.1,
    };
    const ticketH = Math.max(340, 48 + lh.script + 26 + lh.label + 8 + lh.name + 32 + lh.label + 8 + lh.code + 40);
    const contentH = lh.label + 28 + ticketH;

    const top = (H - contentH) / 2;
    const cx = W / 2;
    const x0 = (W - ticketWidth) / 2;
    const y0 = top + lh.label + 28;
    const joint = x0 + bodyW;

    drawText(ctx, 'YOUR PERSONAL TICKET', cx, top + lh.label / 2, {font: f.label, color: colors.label, spacing: 12 * .26});

    // Tờ giấy: đổ bóng, nền paper.webp, vignette nâu nhạt ở mép
    ctx.save();
    ctx.shadowColor = 'rgba(0, 0, 0, .55)';
    ctx.shadowBlur = 30;
    ctx.shadowOffsetY = 22;
    ctx.fillStyle = colors.paper;
    ticketPath(ctx, x0, y0, ticketWidth, ticketH, joint);
    ctx.fill();
    ctx.restore();

    ctx.save();
    ticketPath(ctx, x0, y0, ticketWidth, ticketH, joint);
    ctx.clip();

    if (paper) {
        drawCover(ctx, paper, x0, y0, ticketWidth, ticketH);
    }

    for (const [px, pw] of [[x0, bodyW], [joint, stubWidth]]) {
        const vg = ctx.createRadialGradient(px + pw / 2, y0 + ticketH / 2, 0, px + pw / 2, y0 + ticketH / 2, Math.hypot(pw, ticketH) / 2);
        vg.addColorStop(.55, 'rgba(120, 80, 30, 0)');
        vg.addColorStop(1, 'rgba(120, 80, 30, .22)');
        ctx.fillStyle = vg;
        ctx.fillRect(px, y0, pw, ticketH);
    }

    ctx.restore();

    // Khung đồng đôi ở thân vé, khung đơn ở cuống
    strokeFrame(ctx, x0, y0, bodyW, ticketH, colors.bronzeLine, 12);
    strokeFrame(ctx, x0, y0, bodyW, ticketH, colors.bronzeLineThin, 17);
    strokeFrame(ctx, joint, y0, stubWidth, ticketH, colors.bronzeLine, 12);

    // Đường răng cưa dọc: chấm Ø2.8, chu kỳ 10
    ctx.fillStyle = colors.bronzeLine;

    for (let y = y0 + 22 + 5; y < y0 + ticketH - 22; y += 10) {
        ctx.beginPath();
        ctx.arc(joint, y, 1.4, 0, Math.PI * 2);
        ctx.fill();
    }

    // Thân vé: script + năm cùng baseline, khách mời, tên, mã Lucky Draw
    const left = x0 + padX;
    let y = y0 + 48;
    const scriptBase = y + lh.script * .78;
    drawText(ctx, data.script, left, scriptBase, {font: f.script, color: colors.ink, align: 'left', baseline: 'alphabetic'});
    ctx.font = f.script;
    const scriptW = ctx.measureText(data.script).width;
    drawText(ctx, data.year, left + scriptW + 16, scriptBase, {font: f.year, color: colors.bronze, align: 'left', spacing: 20 * .5, baseline: 'alphabetic'});
    y += lh.script + 26;
    drawText(ctx, 'GUEST', left, y + lh.label / 2, {font: f.label, color: colors.bronze, align: 'left', spacing: 12 * .26});
    y += lh.label + 8;
    drawText(ctx, data.name, left, y + lh.name / 2, {font: nameFont(nameSize), color: colors.ink, align: 'left', spacing: nameSize * .01});
    y += lh.name + 32;
    drawText(ctx, 'LUCKY DRAW CODE', left, y + lh.label / 2, {font: f.label, color: colors.bronze, align: 'left', spacing: 12 * .26});
    y += lh.label + 8;
    drawText(ctx, data.code, left, y + lh.code / 2, {font: codeFont(codeSize), color: colors.ink, align: 'left', spacing: codeSize * .16});

    // Cuống vé: vòng wine số bàn + chú thích bên dưới, căn giữa theo chiều dọc
    const badge = 138;
    const stubCx = joint + stubWidth / 2;
    const stubTop = y0 + (ticketH - (badge + 14 + lh.caption)) / 2;
    const by = stubTop + badge / 2;

    ctx.save();
    ctx.shadowColor = 'rgba(60, 20, 10, .35)';
    ctx.shadowBlur = 6;
    ctx.shadowOffsetY = 2;
    ctx.beginPath();
    ctx.arc(stubCx, by, badge / 2, 0, Math.PI * 2);
    ctx.fillStyle = colors.wine;
    ctx.fill();
    ctx.restore();

    ctx.lineWidth = 1;
    ctx.strokeStyle = colors.goldSoft;
    ctx.globalAlpha = .6;
    ctx.beginPath();
    ctx.arc(stubCx, by, badge / 2 - .5, 0, Math.PI * 2);
    ctx.stroke();
    ctx.globalAlpha = 1;
    ctx.strokeStyle = colors.goldRing;
    ctx.beginPath();
    ctx.arc(stubCx, by, badge / 2 - 5.5, 0, Math.PI * 2);
    ctx.stroke();
    // Số bàn dạng chữ (vd "VIP A") có thể rộng hơn vòng tròn => thu nhỏ cho vừa bên trong vòng
    const tableSize = fitFontSize(ctx, data.table, tableFont, 68, 28, badge - 28, 0);
    drawText(ctx, data.table, stubCx, by, {font: tableFont(tableSize), color: colors.goldSoft});
    drawText(ctx, 'TABLE NUMBER', stubCx, stubTop + badge + 14 + lh.caption / 2, {font: f.label, color: colors.bronze, spacing: 12 * .26});

    return canvas;
}

// ===== Tải vé / hướng dẫn lưu ảnh =====
function ticketFileName(code) {
    return `${code.replace(/[^\w.-]+/g, '-')}.png`;
}

function downloadCanvas(canvas, fileName) {
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (!blob) {
                reject(new Error('Không tạo được file PNG'));
                return;
            }

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            resolve();
        }, 'image/png');
    });
}

/**
 * type: success | danger
 */
function showToast(type, html) {
    // Toast nằm trong overlaysHtml, có thể được chèn sau khi trang đã tải => tìm lúc dùng
    const toast = document.querySelector('.toast');

    if (!toast) {
        return;
    }

    clearTimeout(toastTimer);
    toast.classList.toggle('is-danger', type === 'danger');
    toast.querySelector('.toast-text').innerHTML = html;
    toast.hidden = false;
    toastTimer = setTimeout(() => {
        toast.hidden = true;
    }, TOAST_MS);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function onDownloadClick(downloadButton) {
    const data = downloadButton.dataset;

    // Không dùng disabled để nút không đổi sang kiểu "chưa tải được" trong lúc vẽ ảnh
    if (downloadButton.getAttribute('aria-busy') === 'true') {
        return;
    }

    downloadButton.setAttribute('aria-busy', 'true');

    try {
        const canvas = await renderTicketImage(data);

        const fileName = ticketFileName(data.code);
        await downloadCanvas(canvas, fileName);
        showToast('success', `Ticket image downloaded <span class="toast-file tnum">${escapeHtml(fileName)}</span>`);
    } catch (error) {
        console.error(error);
        showToast('danger', 'We couldn’t create your ticket image. Please try again.');
    } finally {
        downloadButton.removeAttribute('aria-busy');
    }
}

// ===== Khởi tạo =====
// Nút tải vé có thể được chèn sau khi trang đã tải (gửi RSVP bằng fetch) => bắt click trên document
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ticket-download]');

    if (button) {
        onDownloadClick(button);
    }
});
