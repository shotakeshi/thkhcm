# Landing Year End Party 2026: kể chuyện liền mạch — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gộp Recap, Thiệp mời, Xác nhận, Vé, Dress code, Lịch trình thành một trang `/` cuộn liền mạch, có seam chuyển tiếp và typography dùng chung token.

**Architecture:** `public/index.php` dựng một view duy nhất ghép các partial theo thứ tự cố định; RSVP đi qua `public/rsvp.php` (POST, JSON khi có JS, PRG 303 khi không JS) và quyết định vé hiện hay không ngay ở server qua `RsvpLog::latestFor`. CSS dùng lớp token trong `theme.css`; JS mới `story.js` lo reveal chung, seam, tiến độ và gửi RSVP bằng `fetch`.

**Tech Stack:** PHP 8 thuần (không framework), PSR-4 `App\` → `src/`, CSS/JS thuần (không build), PHPMailer không đụng tới.

**Spec:** [docs/superpowers/specs/2026-11-02-landing-storytelling-design.md](../specs/2026-11-02-landing-storytelling-design.md)

## Global Constraints

- **Không commit, push, tạo branch** (quy tắc của người dùng). Mỗi task kết thúc bằng `git diff --stat` để rà, không có bước commit.
- **Không xoá file** ngoài `storage/rsvp.json`, `storage/rsvp.json.lock` do chính các bước kiểm thử tạo ra (spec mục 10.10 đã duyệt). `views/recap.php`, `views/rsvp.php`, `views/partials/program.php` chỉ xoá ở Task 8 sau khi người dùng xác nhận.
- Năm file đang có thay đổi đã stage (`config/event.php`, `public/css/invite.css`, `public/js/motion.js`, `views/invite.php`, `views/partials/program.php`) phải giữ nguyên các thay đổi đó, chỉ sửa tiếp trên đó.
- Giữ nguyên mọi chuỗi giao diện hiện có. Chuỗi mới duy nhất: "Đổi phản hồi".
- Font: `--font-heading` = `'Fraunces', 'Cormorant Garamond', Georgia, serif`; `--font-body` = `'Be Vietnam Pro', system-ui, sans-serif`; `--font-script` = `'Pinyon Script', cursive` chỉ cho chữ "Year End Party". Alias: `--font-serif`, `--font-display` → `--font-heading`; `--font` → `--font-body`.
- Thang chữ (SP ≤640px / PC ≥641px): `--text-hero` 2.75rem / `clamp(3.5rem, 8vw, 7.5rem)`; `--text-section-title` 2.25rem / `clamp(2.5rem, 5.5vw, 5rem)`; `--text-subheading` 1.25rem / `clamp(1.25rem, 2.2vw, 1.875rem)`; `--text-body` 1rem / `clamp(1rem, 1.1vw, 1.125rem)`; `--text-caption` .875rem / `clamp(.875rem, .95vw, .95rem)`; `--text-label` .75rem / `clamp(.75rem, .9vw, .85rem)`. Cho phép chỉnh ±15% bằng mắt; thứ tự hero > section-title > subheading > body > caption > label giữ nguyên.
- Trọng lượng: `--weight-heading` 500, `--weight-body` 400, `--weight-label` 500, `--weight-strong` 600. Giãn chữ: `--tracking-hero` .08em, `--tracking-heading` .02em, `--tracking-label` .26em. Line-height: `--line-height-heading` 1.1, `--line-height-body` 1.65.
- Reveal: `--reveal-dur` .7s, `--reveal-stagger` .1s, `--reveal-ease` `var(--ease-out)`; chỉ animate `opacity` và `transform`.
- Vé: HTML không được chứa `luckyDrawCode` hay số bàn khi RSVP mới nhất của `uuid` không phải "tham gia".
- Cổng xé giấy: giữ là lớp phủ lúc tải trang; cờ `sessionStorage` `yep26-opened`; fail-safe 4 giây; `has-cover`, `is-waiting`, `is-skip` giữ tên.
- JS mới bọc IIFE; global duy nhất thêm là `window.storyReady`. `window.recapReady` bị bỏ.
- PHP: thụt 4 dấu cách, comment tiếng Việt, mọi output qua `e()`.
- Ảnh vé PNG trong `ticket.js` vẽ bằng canvas với font/cỡ cố định: **không** đổi theo token.

## Review Focus

1. `?id=` viết HOA toàn bộ: sau khi RSVP "tham gia", vé vẫn hiện (so khớp `uuid` không phân biệt hoa thường) — test ở Task 1 và Task 3.
2. `storage/rsvp.json` hỏng (JSON sai): trang vẫn 200, không có vé, form vẫn hiện, ghi `error_log` — test ở Task 3.
3. Chuyển phản hồi "tham gia" → "không": JSON của lần "không" không chứa `luckyDrawCode`, và `ticketHtml` chỉ còn seam — test ở Task 3.
4. Gửi liên tiếp hai lần (yes → no → yes): bản ghi mới nhất thắng, mỗi lần gửi thêm đúng một dòng — test ở Task 3.
5. `sessionStorage` bị chặn (chế độ riêng tư): `form.submit()` dự phòng redirect về `#ve`/`#xac-nhan` không bị cổng phủ lại — test ở Task 4.

## Chuẩn bị kiểm thử chung

Chạy một lần, dùng lại ở mọi task. `S` là thư mục scratchpad; các file kiểm thử tạm nằm ở đó, không nằm trong repo.

```bash
S=/private/tmp/claude-501/-Users-thangpq--claude-spares-spare-q3IsoN/6dd8ff33-3f56-404e-ab3d-90f7f68be269/scratchpad
cd /Users/thangpq/Repository/projects/YEP2026
ls storage            # ghi lại: lúc đầu chỉ có data.json
php -S localhost:8000 -t public   # chạy nền (run_in_background)
UUID=$(php -r 'require "bootstrap.php"; foreach (loadEmployees() as $e) { if (employeeUuid($e) !== null && trim((string) ($e["luckyDrawCode"] ?? "")) !== "" && trim((string) ($e["tableNumber"] ?? "")) !== "") { echo employeeUuid($e); break; } }')
CODE=$(php -r 'require "bootstrap.php"; echo findInvitee($argv[1])["luckyDrawCode"];' "$UUID")
```

Không in `$CODE` hay tên khách ra màn hình báo cáo.

## Cấu trúc file

| File | Việc |
|---|---|
| `src/Rsvp/RsvpLog.php` | thêm `latestFor` |
| `bootstrap.php` | thêm `rsvpLog`, `latestRsvp`, `canDownloadTicket`, `renderPartial` |
| `public/index.php`, `public/rsvp.php`, `public/recap.php` | controller trang gộp; endpoint RSVP; redirect |
| `views/invite.php` | khung trang gộp, script đầu trang (cổng) |
| `views/partials/head.php` | nạp CSS theo danh sách `$stylesheets` (mặc định `theme`, `invite`) |
| `views/partials/recap.php`, `rsvp.php`, `rsvp-card.php`, `program-data.php`, `dress-code.php`, `schedule.php`, `seam.php`, `ticket-slot.php` | partial mới (nội dung chuyển từ `recap.php`, `rsvp.php`, `program.php`) |
| `views/partials/ticket.php`, `ticket-overlays.php` | dùng `canDownloadTicket`, gọi độc lập |
| `public/js/story.js` | mới: reveal chung, seam, tiến độ, RSVP `fetch` |
| `public/js/motion.js`, `recap.js`, `ticket.js` | sửa theo spec mục 5, 8 |
| `public/css/theme.css`, `invite.css`, `recap.css`, `rsvp.css`, `story.css` | token, ánh xạ typography, layout section, seam |

Ghi chú lệch nhỏ so với spec (hành vi không đổi): thêm `rsvp-card.php` (phần ruột thẻ, để `rsvpHtml` dựng từ đúng partial lần render đầu); JSON `ticketHtml` luôn là chuỗi (nội dung slot `#ve`, gồm seam), không là `null` khi không tham gia, để đổi "tham gia → không" gỡ được vé đã chèn.

---

### Task 1: Lớp dữ liệu RSVP và helper

**Files:**
- Modify: `src/Rsvp/RsvpLog.php`, `bootstrap.php`
- Test (ngoài repo): `$S/rsvp-log-test.php`

**Interfaces:**
- Produces:
  - `App\Rsvp\RsvpLog::latestFor(string $uuid): ?array` — duyệt `all()` từ cuối, trả dòng đầu tiên có `uuid` khớp không phân biệt hoa thường, không có thì `null`; ném `RuntimeException` khi log hỏng (từ `all()`).
  - `rsvpLog(): \App\Rsvp\RsvpLog` — một chỗ khởi tạo với `STORAGE_PATH . '/rsvp.json'`.
  - `latestRsvp(array $employee): ?array` — `employeeUuid` null → `null`; `RuntimeException` → `error_log('latestRsvp: …')` và `null`.
  - `canDownloadTicket(array $employee): bool` — `luckyDrawCode` và `tableNumber` (sau `trim`) đều khác rỗng.
  - `renderPartial(string $file, array $vars = []): string` — `$file` tương đối `views/partials/`; `extract($vars)`, `ob_start()`, `require`, trả chuỗi.

- [ ] **Step 1: Viết test `$S/rsvp-log-test.php`** (tự dựng file log tạm, không đụng `storage/`):

```php
<?php
require '/Users/thangpq/Repository/projects/YEP2026/bootstrap.php';
use App\Rsvp\RsvpLog;

$f = sys_get_temp_dir() . '/rsvp-latest-' . getmypid() . '.json';
@unlink($f);
$log = new RsvpLog($f);
$a = '11111111-1111-4111-8111-111111111111';
$b = '22222222-2222-4222-8222-222222222222';

assert($log->latestFor($a) === null);                       // file chưa tồn tại
$log->add(['name' => 'A', 'attending' => true,  'email' => null, 'uuid' => $a]);
$log->add(['name' => 'B', 'attending' => false, 'email' => null, 'uuid' => $b]);
$log->add(['name' => 'A', 'attending' => false, 'email' => null, 'uuid' => $a]);
assert($log->latestFor($a)['attending'] === false);         // dòng mới nhất thắng
assert($log->latestFor(strtoupper($a))['attending'] === false); // không phân biệt hoa thường
assert($log->latestFor($b)['attending'] === false);
assert($log->latestFor('33333333-3333-4333-8333-333333333333') === null);

file_put_contents($f, '{bad');
try { $log->latestFor($a); assert(false); } catch (RuntimeException) {}

assert(canDownloadTicket(['luckyDrawCode' => 'X1', 'tableNumber' => '3']) === true);
assert(canDownloadTicket(['luckyDrawCode' => 'X1', 'tableNumber' => ' ']) === false);
assert(canDownloadTicket([]) === false);
@unlink($f); @unlink($f . '.lock');
echo "OK\n";
```

- [ ] **Step 2: Chạy, xác nhận fail** — `php -d zend.assertions=1 -d assert.exception=1 $S/rsvp-log-test.php`. Expected: `Call to undefined method App\Rsvp\RsvpLog::latestFor()`.
- [ ] **Step 3: Cài `latestFor` trong `src/Rsvp/RsvpLog.php`** (đặt sau `all()`; comment tiếng Việt nêu "dòng mới nhất của uuid là phản hồi hiệu lực").
- [ ] **Step 4: Cài bốn helper trong `bootstrap.php`** (đặt gần `inviteUrl`/`loadEvent`). `renderPartial` nạp file bằng `require BASE_PATH . '/views/partials/' . $file`.
- [ ] **Step 5: Chạy lại test.** Expected: `OK`. Chạy `php -l bootstrap.php && php -l src/Rsvp/RsvpLog.php`: `No syntax errors detected`.
- [ ] **Step 6: Rà** `git diff --stat` — chỉ hai file này đổi trong task.

---

### Task 2: Partial và khung trang gộp

**Files:**
- Create: `views/partials/recap.php`, `rsvp.php`, `rsvp-card.php`, `program-data.php`, `dress-code.php`, `schedule.php`, `seam.php`, `ticket-slot.php`
- Modify: `views/invite.php`, `views/partials/head.php`, `ticket.php`, `ticket-overlays.php`, `public/index.php`
- Giữ nguyên (chưa xoá): `views/recap.php`, `views/rsvp.php`, `views/partials/program.php`

**Interfaces:**
- Consumes: Task 1 (`latestRsvp`, `canDownloadTicket`, `renderPartial`).
- Produces (mỗi partial là một file `require`/`renderPartial`, biến vào cuối dòng):
  - `recap.php` (`$event`, `$recap`): `.skip`, `.progress`, `.rail`, `<main class="recap">` với 8 chương. Chuyển nguyên từ `views/recap.php` dòng 13–47 (closure `$chapters`, `$isTodo`, `$text`, `$photo`, `$chapterNo`, `$layouts`) và 79–333. Đổi: `<main>` → `<main class="recap">`; thêm class `heading-primary` cho `h1.hero-title` và mọi `h2.chapter-title`; nút cuối `href="./"` → `href="#thiep"`. Không giữ `<html>`, `<head>`, `<script>`.
  - `seam.php` (`$from` ?string, `$to` ?string): `<div class="seam" aria-hidden="true" style="--seam-from: …; --seam-to: …">` chứa `<span class="seam-thread"></span>` và 6 `<span class="particle seam-particle" style="left: N%; animation-delay: …s; animation-duration: …s">` (vị trí/độ trễ cố định trong mảng, cùng cách `ticket.php` dòng 18–21). Giá trị `$from`/`$to` null thì không đặt biến.
  - `program-data.php` (`$event`): đặt `$schedule`, `$dressCode`, `$dressCodeNote`, `$dressColors`, `$hasDress`, `$hasSchedule`, `$programImage`, `$programTitle`, `$dateLabel`, `$dressCodeLabel`, `$dateText`. Chuyển nguyên logic lọc lịch trình và kiểm tra mã màu hex từ `program.php` dòng 9–31 (giữ regex `^#(?:[0-9a-f]{3}|[0-9a-f]{6})$`).
  - `rsvp-card.php` (`$event`, `$employee`, `$rsvp` ?array, `$rsvpErrors` array, `$rsvpDraft` ?string): chỉ phần ruột `.rsvp-card`. `$rsvp !== null` → `.rsvp-done` (chuỗi cảm ơn giữ nguyên từ `views/rsvp.php` dòng 75–79, chọn theo `$rsvp['attending']`) + `<details class="rsvp-change" [open khi $rsvpErrors !== []]><summary>Đổi phản hồi</summary>` chứa form; `$rsvp === null` → chỉ form. Form: `<form class="rsvp-form" method="post" action="rsvp.php?id=<?= e(uuid) ?>" novalidate data-rsvp-form>`, fieldset radio `attending` (`yes`: "Tham gia được", `no`: "Rất tiếc, tôi không thể đến", check theo `$rsvpDraft`), lỗi `attending` và `form` như `views/rsvp.php`, nút "Xác nhận". Không có ô tên.
  - `rsvp.php` (`$event`, `$employee`, `$rsvp`, `$rsvpErrors`, `$rsvpDraft`): `<section class="page page-rsvp" id="xac-nhan" aria-labelledby="rsvp-title">` gồm eyebrow (`name year`), `<h2 class="rsvp-title heading-primary" id="rsvp-title">Xác nhận tham dự</h2>`, câu hạn phản hồi (chỉ khi `$rsvp === null` và có `rsvp_deadline`), `dl.rsvp-facts` (thời gian, địa điểm), `<div class="rsvp-card" id="rsvp-card" data-rsvp-card>` + `require rsvp-card.php`. Mỗi khối con có `data-reveal` và `style="--d: N"`. Bỏ `.rsvp-bg`, `.rsvp-logo`.
  - `ticket-slot.php` (`$event`, `$employee`, `$showTicket` bool): nạp `program-data.php`; `$showTicket` → seam (xác nhận→vé) + `ticket.php`; sau đó, nếu `$hasDress || $hasSchedule`, một seam (→ section kế tiếp).
  - `dress-code.php`, `schedule.php` (`$event` + biến của `program-data.php`): trả sớm khi `!$hasDress` / `!$hasSchedule`. `dress-code.php`: `<section class="page page-dress" id="dress-code">` → `.stage` → `.program.has-photo` → ảnh `$programImage` (nếu có), `h2.program-dress-label` (nhãn `dress_code_label`, Section Heading), `p.program-dress-value`, `p.program-dress-note`, `ul.program-swatches`. `schedule.php`: `<section class="page page-schedule" id="lich-trinh">` → `.stage` → `.program` → `h2.program-heading` (`span.program-script` tên sự kiện + `span.program-title` tiêu đề), `.program-date`, `ol.program-list` (kèm `data-progress`). Thay `class="pv"` bằng `data-reveal`; giữ `style="--d: N"`.
- `ticket.php`: thay dòng 15 `$canDownload = …` bằng `canDownloadTicket($employee)`; `ticket-overlays.php`: tự tính `$canDownload = canDownloadTicket($employee)` (không phụ thuộc biến của `ticket.php`).
- `head.php`: `$stylesheets = $stylesheets ?? ['theme', 'invite'];` rồi lặp `<link href="css/{name}.css?v=filemtime">`. `invalid.php` không đổi.
- `public/index.php`: giữ nguyên logic tìm `$employee` và 404 `invalid.php`; thêm `$recap = require BASE_PATH . '/config/recap.php';`, `$rsvp = $employee !== null ? latestRsvp($employee) : null;`, `$showTicket = $employee !== null && ($rsvp['attending'] ?? false) === true;`.
- `views/invite.php` — thứ tự cố định:

```
<html lang="vi" [data-opened]>  head (head.php với $stylesheets = theme, invite, recap, rsvp, story) + script đầu trang (Task 4)
<body>
  .tear-cover (giữ nguyên markup)
  partials/recap.php
  seam(from: var(--ink), to: transparent)           ← ngoài .scene
  <div class="scene">
    section.page-invite  (đổi <main class="invite" id="thiep"> thành <section class="invite" id="thiep">;
                          .card-heading h1 → h2; giữ ghi chú "Vui lòng mở link…" khi $uuid === null)
    nếu $employee !== null:
        seam, partials/rsvp.php, <div id="ve" data-ticket-slot> + ticket-slot.php </div>
    ngược lại: nếu ($hasDress || $hasSchedule) seam
    dress-code.php; seam (chỉ khi cả $hasDress và $hasSchedule); schedule.php
  </div>
  nếu $showTicket: ticket-overlays.php
  <script> motion.js, recap.js, story.js (cuối), ticket.js chỉ khi $showTicket && canDownloadTicket($employee) (thêm data-ticket-js)
```

Đầu `invite.php` đặt `$rsvpErrors ??= []; $rsvpDraft ??= null; $opened ??= false;` và `require partials/program-data.php`.

- [ ] **Step 1: Test hiện trạng (fail)** — server chạy; `curl -s localhost:8000/ | grep -c 'id="xac-nhan"'`. Expected hiện tại: `0`.
- [ ] **Step 2: Tạo `program-data.php`, `seam.php`, `renderPartial` đã có; viết `dress-code.php`, `schedule.php`** theo Interfaces. Chạy `php -l` từng file.
- [ ] **Step 3: Tạo `recap.php`** bằng cách chuyển nội dung (không viết lại): sao chép khối PHP đầu và thân `<body>` của `views/recap.php`, áp các đổi nêu trên. `php -l`.
- [ ] **Step 4: Tạo `rsvp-card.php`, `rsvp.php`, `ticket-slot.php`; sửa `ticket.php`, `ticket-overlays.php`, `head.php`.** `php -l`.
- [ ] **Step 5: Sửa `index.php` và `invite.php`** theo sơ đồ. `php -l`.
- [ ] **Step 6: Chạy kiểm tra thứ tự và ma trận trạng thái** (log RSVP chưa tồn tại):
  - `curl -s localhost:8000/ | grep -o 'id="\(mo-dau\|thiep\|xac-nhan\|ve\|dress-code\|lich-trinh\)"'` → `mo-dau`, `thiep`, `dress-code`, `lich-trinh` theo đúng thứ tự, không có `xac-nhan` và `ve`.
  - `curl -s "localhost:8000/?id=$UUID" | grep -o 'id="\(mo-dau\|thiep\|xac-nhan\|ve\|dress-code\|lich-trinh\)"'` → đủ 6 id đúng thứ tự (`ve` là slot rỗng ngoài seam).
  - `curl -s "localhost:8000/?id=$UUID" | grep -c "$CODE"` → `0`; `grep -c 'name="name"'` trên cùng output → `0`; `grep -c 'name="attending"'` → `2`.
  - `curl -s -o /dev/null -w '%{http_code}' "localhost:8000/?id=sai"` → `404`.
- [ ] **Step 7: Rà** `git diff --stat`; xác nhận 5 file đã stage vẫn giữ thay đổi cũ (`git diff --cached --stat` không đổi so với đầu phiên).

---

### Task 3: Endpoint RSVP và redirect

**Files:**
- Modify: `public/rsvp.php`, `public/recap.php`

**Interfaces:**
- Consumes: Task 1 helper; Task 2 partial (`rsvp-card.php`, `ticket-slot.php`, `ticket-overlays.php`, `invite.php` biến `$rsvpErrors`, `$rsvpDraft`, `$opened`).
- Produces:
  - `GET rsvp.php[?id=…]` → 302 `./?id=<uuid>#xac-nhan` nếu `id` là invitee hợp lệ, ngược lại `./#xac-nhan`.
  - `GET recap.php` → 302 `./`.
  - `POST rsvp.php?id=…`: `findInvitee` null → 404 (`Accept` JSON → `{"ok":false}`, ngược lại `views/invalid.php`), không ghi log. Chỉ validate `attending ∈ {yes,no}`. Ghi `rsvpLog()->add(['name' => trim($employee['name']), 'attending' => bool, 'email' => $employee['email'] ?? null, 'uuid' => employeeUuid($employee)])`.
    - Thành công + `Accept: application/json` → 200 `{"ok":true,"attending":bool,"rsvpHtml":string,"ticketHtml":string,"overlaysHtml":string|null,"ticketScript":string|null}`. `overlaysHtml`, `ticketScript` (`"js/ticket.js?v=" . filemtime(...)`) chỉ có khi attending và `canDownloadTicket`.
    - Thành công không JSON → 303 `./?id=<uuid>#ve` nếu tham gia, `#xac-nhan` nếu không.
    - Lỗi (validate hoặc ghi log): 422; JSON → `{"ok":false,"errors":{…},"rsvpHtml":string}`; không JSON → render `views/invite.php` đầy đủ với `$opened = true`, `$rsvpErrors`, `$rsvpDraft`.
    - `rsvpHtml` = `renderPartial('rsvp-card.php', ['event' => …, 'employee' => …, 'rsvp' => <dòng vừa ghi>, 'rsvpErrors' => …, 'rsvpDraft' => …])`; `ticketHtml` = `renderPartial('ticket-slot.php', ['event' => …, 'employee' => …, 'showTicket' => $attending === 'yes'])`; `overlaysHtml` = `renderPartial('ticket-overlays.php', …)`.

- [ ] **Step 1: Test fail** — `curl -s -o /dev/null -w '%{http_code}' -H 'Accept: application/json' -d attending=yes "localhost:8000/rsvp.php?id=$UUID"` với code cũ trả `422` (thiếu `name`). Expected sau khi cài: `200`.
- [ ] **Step 2: Viết lại `public/rsvp.php`** theo Interfaces. Xoá `RSVP_NAME_MAX` và mọi xử lý `name`. Nhận dạng JSON bằng `str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')`.
- [ ] **Step 3: Sửa `public/recap.php`** thành redirect.
- [ ] **Step 4: Chạy bộ kiểm tra** (log chưa tồn tại lúc bắt đầu; ghi chú `ls storage` trước):
  - GET: `curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "localhost:8000/rsvp.php?id=$UUID"` → `302 http://localhost:8000/?id=…#xac-nhan`; `…/recap.php` → `302 http://localhost:8000/`.
  - POST không `id` và `id=sai`: HTTP `404`; `ls storage` không có `rsvp.json`.
  - POST thiếu `attending`: JSON `422`, `"errors":{"attending":"Vui lòng chọn bạn có tham gia được hay không."}`.
  - POST `yes` (JSON, lưu `$S/yes.json`): `200`; `grep -c "$CODE" $S/yes.json` ≥ `1`; `ticketScript` khác `null`.
  - POST `no` (lưu `$S/no.json`): `200`; `grep -c "$CODE" $S/no.json` = `0`; `ticketScript` và `overlaysHtml` là `null`; `ticketHtml` chứa `class=\"seam` và không chứa `page-ticket`.
  - Dòng cuối của `storage/rsvp.json` có `"attending": false` và `name` đúng tên danh sách mời (đối chiếu bằng `php -r`, không in tên).
  - **Review Focus 4**: gửi `yes`, `no`, `yes` liên tiếp → `rsvp.json` có đúng 3 dòng của `uuid`; `curl -s "localhost:8000/?id=$UUID" | grep -c "$CODE"` ≥ `1`.
  - **Review Focus 1**: `curl -s "localhost:8000/?id=$(echo $UUID | tr a-f A-F)" | grep -c "$CODE"` ≥ `1`.
  - Không JS: `curl -s -o /dev/null -w '%{http_code} %{redirect_url}' -d attending=yes "localhost:8000/rsvp.php?id=$UUID"` → `303 …#ve`; với `no` → `303 …#xac-nhan`; thiếu `attending` → `422`, thân chứa "Vui lòng chọn bạn có tham gia được hay không." và `<html lang="vi" data-opened>`.
  - **Review Focus 2**: sao lưu `storage/rsvp.json` về `$S/rsvp.bak`, ghi `{bad` vào file, `curl -s -o $S/page.html -w '%{http_code}' "localhost:8000/?id=$UUID"` → `200`; `grep -c "$CODE" $S/page.html` → `0`; `grep -c 'name="attending"' $S/page.html` → `2`. Khôi phục file từ `$S/rsvp.bak`.
- [ ] **Step 5: Rà** `git diff --stat` (chỉ hai file này đổi thêm).

---

### Task 4: Cổng xé giấy phủ lúc tải trang

**Files:**
- Modify: `views/invite.php` (script đầu trang), `public/js/motion.js`, `public/css/invite.css`

**Interfaces:**
- Produces:
  - Script đầu trang (thay khối hiện có): `fit()` như cũ; luôn `root.classList.add('js')`; `opened` đọc từ `sessionStorage['yep26-opened'] === '1'` (bỏ comment, giữ `try/catch`); bỏ qua cổng (thêm `is-skip`, `return`) khi `opened` hoặc `root.hasAttribute('data-opened')` hoặc `location.hash` ∈ {`#thiep`,`#xac-nhan`,`#ve`,`#dress-code`,`#lich-trinh`}; ngược lại thêm `has-cover`, `is-waiting`. Sau 4 giây: `!window.yepMotionReady` → gỡ `has-cover`, `is-waiting` rồi `document.dispatchEvent(new CustomEvent('yep:cover-open'))`; `!window.storyReady` → gỡ `js`.
  - `motion.js`: `initInviteCover()` đặt `inert` cho `document.querySelectorAll('.recap, .scene')`; `onReveal` gỡ `inert` của cả hai, gỡ `is-waiting`, ghi `sessionStorage`, rồi `document.dispatchEvent(new CustomEvent('yep:cover-open'))`; bỏ `setTimeout(… 'is-skip', MOTION.settleMs)` (và hằng `settleMs` nếu không còn dùng). Hàm mới `initInviteReveal()`: `.page-invite.classList.add('has-reveal')` rồi `revealOnScroll(page)`; gọi trong danh sách khởi tạo cuối file. Nghe `yep:ticket-ready` → `initTicketReveal()`.
  - `invite.css`: `.tear-cover { z-index: 200 }` (phải lớn hơn `.skip` 100 của Recap); thay khối `.is-waiting .intro, .is-waiting .shimmer::after` bằng `.page-invite.has-reveal:not(.is-in) .intro, .page-invite.has-reveal:not(.is-in) .shimmer::after { animation-play-state: paused !important; }`. Giữ `.is-skip …`.

- [ ] **Step 1: Cài** ba phần trên. `node --check public/js/motion.js` → không lỗi.
- [ ] **Step 2: Kiểm tra trên Browser pane** (xoá `sessionStorage` trước): `/`:
  - thấy lớp giấy phủ, không cuộn được (`document.body` `overflow: hidden`), `document.querySelector('.recap').inert === true`, `.progress`/`.rail` không đè lên giấy;
  - bấm nút "Hoặc nhấn để mở" → giấy biến mất, `inert` về `false`, `html` không còn `is-waiting`, `sessionStorage['yep26-opened'] === '1'`; tải lại → không còn cổng;
  - cuộn tới Thiệp mời: các phần tử `.intro` chạy hiệu ứng vào ở lần đầu thấy.
- [ ] **Step 3: Review Focus 5** — xoá `sessionStorage`, mở `/?id=<UUID>#ve`: `html` có `is-skip`, không có `has-cover`. Mô phỏng chặn storage (`Storage.prototype.setItem = () => { throw 1 }` trước khi xé), xé xong: không lỗi console.
- [ ] **Step 4: JS lỗi** — chặn `motion.js` (đổi tên tạm trong DevTools/Network block): sau 4 giây `has-cover` biến mất, nội dung đọc được.
- [ ] **Step 5: Rà** `git diff --stat`; xác nhận các hunk đã stage của `motion.js`/`invite.css`/`invite.php` còn nguyên (`git diff --cached` không đổi).

---

### Task 5: story.js, recap.js, ticket.js

**Files:**
- Create: `public/js/story.js`
- Modify: `public/js/recap.js`, `public/js/ticket.js`

**Interfaces:**
- Consumes: sự kiện `yep:cover-open` (Task 4), markup `data-rsvp-form`, `#rsvp-card`, `[data-ticket-slot]` (Task 2), JSON của Task 3.
- Produces:
  - `story.js` (IIFE; dòng đầu `window.storyReady = true`):
    - `initReveal()`: quan sát `[data-reveal]` (giữ `rootMargin: '0px 0px -12% 0px'`, `threshold: .15`; `data-reveal="title"` quan sát qua `parentElement`), thêm `is-in` và `el.dispatchEvent(new CustomEvent('yep:reveal', {bubbles: true}))`. Khi `html.is-waiting` còn: không thêm `is-in`, chờ `yep:cover-open` rồi quét lại. `prefers-reduced-motion` hoặc thiếu `IntersectionObserver` → thêm `is-in` cho tất cả ngay.
    - `initScrollEffects()`: một rAF handler cho `scroll`/`resize` — `.progress-bar` `scaleX`; `.rail` bật/tắt class `is-out` khi đáy `.recap` lên khỏi 50% khung nhìn; mỗi `.seam` và mỗi `[data-progress]` đặt `--seam-p` (0–1, `clamp((vh - rect.top) / (vh + rect.height))`); riêng `.seam` bật/tắt `is-live` khi trong khung ± 1 màn hình và đánh dấu phần tử liền trước bằng class `seam-before`, liền sau bằng `seam-after` (idempotent, dùng cho chuyền tay hai đầu ở Task 7). `document.querySelectorAll` gọi lại mỗi frame (seam có thể được chèn thêm).
    - `initRsvp()`: `submit` ủy quyền trên `document` cho `form[data-rsvp-form]`: `preventDefault`, khoá nút + `aria-busy`, `fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}})`. Phản hồi không phải JSON hợp lệ hoặc lỗi mạng → `HTMLFormElement.prototype.submit.call(form)`. `422` có `rsvpHtml` → thay `#rsvp-card` innerHTML, focus `.rsvp-error` đầu tiên. `ok` → thay `#rsvp-card`, thay `[data-ticket-slot]` innerHTML bằng `ticketHtml`, gỡ `.toast-region` cũ rồi `document.body.insertAdjacentHTML('beforeend', overlaysHtml)` nếu có; nạp `ticketScript` một lần (`script[data-ticket-js]`, chờ `load`); phát `document.dispatchEvent(new CustomEvent('yep:ticket-ready'))`; nếu attending: cuộn mượt (`auto` khi reduced-motion) tới `#ve`, focus `.ticket-name` (đặt `tabindex="-1"`).
  - `recap.js`: bọc IIFE; bỏ `window.recapReady`, `RECAP.reveal`, `initReveal`; trong `initScroll` bỏ biến `bar` và dòng `bar.style.transform`; thêm `document.addEventListener('yep:reveal', (e) => e.target.querySelectorAll('[data-count]').forEach(countUp))`. Giữ đếm số, timeline, ánh sáng, parallax, album.
  - `ticket.js`: bỏ hằng cấp cao `downloadButton`, `toast`; `showToast` tìm `.toast` lúc gọi; `onDownloadClick(button)` nhận nút; `document.addEventListener('click', (e) => { const button = e.target.closest('[data-ticket-download]'); if (button) onDownloadClick(button); })`. `TICKET_IMAGE` và hàm vẽ canvas không đổi.

- [ ] **Step 1: Viết `story.js`** theo Interfaces. `node --check public/js/story.js`.
- [ ] **Step 2: Sửa `recap.js`, `ticket.js`.** `node --check` cả hai.
- [ ] **Step 3: Kiểm tra trên Browser pane** (có `UUID`, log đã xoá/tạm sao lưu để chưa có RSVP; xác nhận `ls storage` trước):
  - `/` sau khi xé: các `[data-reveal]` đầu Recap hiện dần *sau* khi giấy rời (không hiện sẵn trước đó); `window.storyReady === true`; không có biến `recapReady`, `reduced`, `clamp` ở `window`.
  - `/?id=<UUID>`: chọn "Tham gia được" → Xác nhận → thẻ đổi sang lời cảm ơn + "Đổi phản hồi", vé hiện ở `#ve`, trang cuộn tới vé, focus ở tên khách; bấm "Tải vé" tải được file PNG; toast hiện.
  - Mở "Đổi phản hồi" → chọn "Rất tiếc…" → vé biến mất, `[data-ticket-slot]` chỉ còn seam, không còn `.toast-region`.
  - Không chọn radio → hiện lỗi `rsvp-error` (422) không rời trang.
  - Chặn `rsvp.php` trong Network (offline): form gửi theo đường thường (`form.submit`), không mất phản hồi.
  - Console không lỗi; `.progress-bar` tăng theo cuộn toàn trang; `.rail` ẩn khi qua khỏi Recap.
- [ ] **Step 4: Rà** `git diff --stat`.

---

### Task 6: Token, quy tắc toàn cục và ánh xạ typography

**Files:**
- Modify: `public/css/theme.css`, `invite.css`, `recap.css`, `rsvp.css`

**Interfaces:**
- Produces trong `theme.css`: toàn bộ biến ở Global Constraints (`--font-*`, `--text-*`, `--weight-*`, `--tracking-*`, `--line-height-*`, `--reveal-*`, thêm `--reveal-dur-title: 1.2s`), giá trị SP ở `:root`, giá trị PC trong `@media (min-width: 641px) { :root { … } }`; `--ease-out: cubic-bezier(.22, 1, .36, 1)` (một đường cong) và `--gutter: clamp(16px, 5vw, 72px)`; alias `--font-serif`/`--font-display` → `--font-heading`, `--font` → `--font-body`; một bộ quy tắc `*, *::before, *::after { box-sizing }`, `html` (scroll-behavior smooth khi `no-preference`, `-webkit-text-size-adjust`), `body` (margin 0, nền trong suốt, màu `--color-text`, `font-family: var(--font-body)`, `font-size: var(--text-body)`, `line-height: var(--line-height-body)`, `font-variation-settings: 'SOFT' 100, 'WONK' 1`, `overflow-x: hidden`), `a`, `img { max-width: 100% }`, `:focus-visible`. `.heading-primary` giữ `font-weight: 700` và `letter-spacing: .05em` (chữ tên khách trên vé khớp ảnh PNG).
- Xoá khỏi `invite.css`, `recap.css`, `rsvp.css`: khối `:root` trùng (`--font*`, `--ease-out`, `--gutter`, `--paper` cũ…) và các quy tắc `html`/`body`/`a`/`img`/`:focus-visible`/`*` đã gộp. Trong `recap.css` đổi `--paper` → `--paper-warm` (và mọi `var(--paper)` trong file).

- [ ] **Step 1: Test fail** — `grep -c 'font-size' public/css/*.css` ghi lại số khai báo `font-size` ban đầu của từng file (tổng 127 ở `invite`, `recap`, `rsvp`); `grep -c -- '--text-hero' public/css/theme.css` → `0`.
- [ ] **Step 2: Viết token + quy tắc toàn cục vào `theme.css`; gỡ phần trùng ở ba file khác.**
- [ ] **Step 3: Áp bảng ánh xạ dưới đây.** Với mỗi selector được ánh xạ: thay `font-size` (và `font-family`/`font-weight`/`letter-spacing`/`line-height` đã khai báo) bằng token tương ứng; xoá khai báo `font-size` trùng ở khối PC (≥641px) và `clamp()` riêng, vì token đã tự đổi theo breakpoint. Cấp Hero/Section/Subheading đặt đủ bộ `font-family: var(--font-heading)`, `font-weight: var(--weight-heading)`, `line-height: var(--line-height-heading)`; Label đặt thêm `font-family: var(--font-body)`, `font-weight: var(--weight-label)`, `letter-spacing: var(--tracking-label)`, `text-transform: uppercase`.

| Token | Selector (file) |
|---|---|
| `--text-hero` (+ `letter-spacing: var(--tracking-hero)`) | `.hero-title` (recap); `.card-heading` (invite; PC dùng `min(var(--text-hero), 11vh)` thay cho `min(6.4vw, 11vh, 7rem)`) |
| `--text-section-title` (+ `--tracking-heading`) | `.chapter-title` (recap); `.rsvp-title` (rsvp, bỏ `text-transform: uppercase`); `.ticket-name` (invite); `.program-dress-label` và `.program-title` (invite) |
| `--text-subheading` | `.chapter-intro`, `.hero-sub`, `.milestone-title`, `.diary-head`, `.stat-label`, `.label-title`, `.trial-hard`, `.turn`, `.closing-message` (recap); `.rsvp-facts dd`, `.rsvp-thanks` (rsvp); `.program-dress-value`, `.program-date-value`, `.card-name`, `.ticket-year`, `.ticket-table-caption` (invite) |
| `--text-body` | `body`; `.body`, `.diary-date`, `.milestone-time`, `.letter-sign` (recap); `.rsvp-lead`, `.rsvp-option`, `.rsvp-submit` (rsvp); `.program-item`, `.btn`, `.tear-btn` (invite) |
| `--text-caption` | `.polaroid figcaption`, `.ph-note`, `.salon-cap`, `.diary-foot`, `.label-note`, `.milestone-result span` (recap); `.rsvp-alert`, `.rsvp-error`, `.rsvp-facts dd span` (rsvp); `.card-address`, `.invite-note` (mọi khối của nó), `.ticket-note`, `.toast`, `.program-dress-note` (invite) |
| `--text-label` | `.chapter-no` (recap); `.rsvp-eyebrow`, `.rsvp-facts dt`, `.rsvp-label` (rsvp); `.eyebrow`, `.card-eyebrow`, `.card-label`, `.card-side`, `.ticket-label`, `.ticket-stub-label`, `.ticket-page-label`, `.program-date-label`, `.tear-hint-text` (invite) |

**Ngoại lệ có chủ đích** (giữ nguyên cỡ hiện tại, không thuộc thang chữ): chữ số/ngày trình bày lớn `.card-day`, `.card-month`, `.card-year`, `.ticket-code`, `.ticket-pending`, `.ticket-table`, `.stat-value`, `.finale`, `.diary-goals li::before`; chữ ký script `.program-script`, `.ticket-script` (dùng `--font-script`); `.notice-*` (dùng ở `invalid.php`, spec yêu cầu giao diện không đổi); CSS chết không còn nơi dùng (`.invite-title`, `.invite-year`, `.invite-name`, `.invite-guest`, `.invite-date-side`, `.invite-day`, `.invite-time`, `.invite-venue-name`, `.invite-venue-address`). Weight của chữ tên khách trên vé giữ 700 (khớp ảnh PNG).

- [ ] **Step 4: Gỡ `drop-shadow` nếu giật** — thêm `filter: none` cho `.hero-title.heading-primary`, `.chapter-title.heading-primary` chỉ khi kiểm tra Task 7 thấy giật khi cuộn/reveal `clip-path` (spec mục 7.4).
- [ ] **Step 5: Kiểm tra bằng lệnh** — liệt kê `font-size` không dùng token ngoài ngoại lệ:

```bash
for f in invite recap rsvp; do awk -v F=$f '/\{[[:space:]]*$/ && !/@keyframes/ {sel=$0; gsub(/^[[:space:]]+|[[:space:]]*\{$/,"",sel)} /^[[:space:]]*font-size:/ && !/var\(--text-/ {print F": "sel" -> "$0}' public/css/$f.css; done
```

Expected: chỉ các selector trong danh sách ngoại lệ (kể cả `body` rỗng) và `.invite-*` chết. `grep -n -- '--ease-out:\|--gutter:\|--font-body:\|--font-display:' public/css/*.css` → chỉ `theme.css`.
- [ ] **Step 6: Kiểm tra trực quan** ở 375, 414, 640, 768, 1440px: thứ tự cỡ hero > section > subheading > body > caption > label đúng; không vỡ dòng "YEAR END", không cuộn ngang; tên "Nguyễn Thị Phương Thảo" trên vé vừa khung ở 375px (nếu tràn, hạ giá trị SP của `--text-section-title` tối đa 15%, **không** thêm ngoại lệ riêng); `invalid.php` (`/?id=sai`) nhìn như trước.
- [ ] **Step 7: Rà** `git diff --stat`.

---

### Task 7: Layout section, seam và chuyển tiếp

**Files:**
- Create: `public/css/story.css`
- Modify: `public/css/invite.css`, `rsvp.css`, `recap.css`

**Interfaces:**
- Consumes: markup Task 2; `--seam-p`, `is-live`, `is-in`, `rail.is-out` từ Task 5.
- Produces:
  - `story.css`: `.seam` (cao `clamp(120px, 22vh, 220px)`, `position: relative; z-index: 3`, `margin-block` âm bằng nửa chiều cao để chồng lên hai section, nền `linear-gradient(var(--seam-from, transparent), var(--seam-to, transparent))`); `.seam-thread` (1px vàng `--color-primary`, giữa, `transform: scaleY(var(--seam-p, 0))`, `transform-origin: top`); `.seam-particle` dùng lại `.particle` và `@keyframes drift`, `animation-play-state: paused` mặc định, `running` khi `.seam.is-live`; 3 hạt đầu trên SP (`:nth-child(n+5)` ẩn ở ≤640px, tính cả `.seam-thread`), 6 trên PC; `@media (prefers-reduced-motion: reduce)` seam tĩnh, không hạt, thread `scaleY(1)`.
  - `recap.css`: `.js [data-reveal]` dùng `transition: opacity var(--reveal-dur) var(--reveal-ease), transform var(--reveal-dur) var(--reveal-ease)` và `transition-delay: calc(var(--d, 0) * var(--reveal-stagger))`; biến thể `title` dùng `var(--reveal-dur-title)`; `.rail.is-out { opacity: 0; pointer-events: none; }`.
  - `story.css` (chuyền tay hai đầu): `.seam-before :is(.stage, .rsvp-wrap, .closing-inner) { opacity: calc(1 - var(--seam-p, 0) * .4); transform: translateY(calc(var(--seam-p, 0) * -16px)); }` (còn ~60%, dịch tối đa 16px); `.seam-after [data-reveal] { transition-delay: calc(.15s + var(--d, 0) * var(--reveal-stagger)); }`. Sợi chỉ nối: `.rsvp-done-icon::after` và `.program-swatches::after` là đường vàng 1px dọc kéo xuống đáy khối để chạm seam kế tiếp (✓ → vé; hàng ô màu → timeline Lịch trình).
  - `invite.css`: đổi `.page-program` → `.page-dress, .page-schedule` (dùng `:is()`); xoá `.has-reveal`/`.pv` của program; `.page-schedule .program-list` có trục dọc vàng bên trái (`::before`, `transform: scaleY(var(--seam-p, 0))`, vẽ dần theo cuộn nhờ `data-progress`) + chấm mốc theo `li` (tái dùng kiểu `.milestone-dot` của recap); `.page-dress .program-photo` cao theo ảnh tham khảo (giữ 136px SP / 200px PC) — ảnh tham khảo đặt trong khối Dress code; SP: `.page-bg` và `.page-bg.is-ticket` thêm `mask-image: linear-gradient(transparent, #000 12%, #000 88%, transparent)` (kèm `-webkit-mask-image`), xoá `.page-bg.is-ticket::after`.
  - `rsvp.css`: bỏ `.rsvp`, `.rsvp-bg*`, `.rsvp-logo`, `.rsvp-input*`; thêm `.page-rsvp` (padding dọc `clamp(48px, 7vh, 96px)`, `min-height: 100svh`, căn giữa theo cột `.rsvp-wrap`) và `.rsvp-change` (`summary` kiểu nút ghost dùng `--text-body`, mũi tên mặc định ẩn, vùng bấm ≥44px).
  - `.page-rsvp`, `#ve`, `.page-dress`, `.page-schedule`: nền trong suốt (nền toàn cục từ `body::before`).

- [ ] **Step 1: Viết `story.css`, sửa ba file CSS.**
- [ ] **Step 2: Kiểm tra trực quan** trên Browser pane ở 375, 414, 640, 768, 1440px, với ba trạng thái (`/`, `/?id=<UUID>` chưa RSVP, đã "tham gia"):
  - không còn đường cắt cứng ở 5 ranh giới; sợi chỉ vàng lớn dần theo cuộn; hạt chỉ chạy khi seam trong khung; section trên mờ nhẹ khi seam vào khung, section dưới reveal trễ ~0,15s;
  - trục timeline Lịch trình vẽ dần theo cuộn;
  - nền ảnh mobile thiệp/vé tan mép, không lộ dải đứt ở 375/414/640px;
  - Dress code có ảnh tham khảo, Lịch trình chỉ chữ + timeline;
  - `.rail` mờ đi khi ra khỏi Recap;
  - không cuộn ngang ở mọi độ rộng.
- [ ] **Step 3: `prefers-reduced-motion`** (DevTools emulate): seam tĩnh, reveal chỉ fade; cổng vẫn dùng được; không JS (tắt JS): không cổng, mọi nội dung hiện, form gửi theo PRG.
- [ ] **Step 4: Hiệu năng** — Performance panel, CPU throttle 4x, cuộn cả trang: không có long task lặp; nếu `drop-shadow` của tiêu đề recap giật, áp Task 6 Step 4.
- [ ] **Step 5: Rà** `git diff --stat`.

---

### Task 8: Nghiệm thu, dọn dẹp, xác nhận xoá file cũ

**Files:**
- Modify: `config/event.php` (chỉ comment nhắc `program.php` và `/rsvp.php`)
- Xoá (chỉ sau khi người dùng đồng ý): `views/recap.php`, `views/rsvp.php`, `views/partials/program.php`

- [ ] **Step 1: Chạy lại toàn bộ danh sách kiểm thử spec mục 10 (1–9)** theo các lệnh ở Task 2, 3, 4, 5 và kiểm tra trực quan ở Task 6, 7. Ghi kết quả đạt/không đạt từng mục.
- [ ] **Step 2: Nghiệm thu mục 12 của spec**: thứ tự section và vé theo ma trận; seam ở mọi ranh giới; thang chữ chỉ dùng `--font-heading`/`--font-body` (ngoài `--font-script`); tiêu đề cùng cấp cùng cỡ/weight/giãn chữ/line-height; RSVP + vé + tải vé đúng cả có JS lẫn không JS; responsive; reduced-motion; cổng.
- [ ] **Step 3: Xác nhận không còn tham chiếu** tới file cũ — `grep -rn "views/recap.php\|views/rsvp.php\|partials/program.php\|recap.php\|rsvp.php" views public bootstrap.php` chỉ còn `form action="rsvp.php"`, các redirect, và comment cần sửa; sửa comment trong `config/event.php` (dòng 12, 16, 38).
- [ ] **Step 4: Dọn kiểm thử** — so với `ls storage` ghi ở đầu, chỉ xoá `storage/rsvp.json`, `storage/rsvp.json.lock`, `storage/rsvp.json.tmp` nếu chúng do kiểm thử tạo ra. Tắt server `php -S`. Xoá `$S/*` không cần.
- [ ] **Step 5: Hỏi người dùng** có xoá ba file cũ không. Chỉ xoá khi được xác nhận (`views/partials/program.php` đang được stage: dùng `git rm` hoặc `rm` theo ý người dùng).
- [ ] **Step 6: Rà diff cuối** — `git diff` và `git diff --cached`; báo cáo trung thực những gì đã/không chạy (không có test suite tự động; kiểm thử bằng `php -l`, `node --check`, `curl`, Browser pane).
