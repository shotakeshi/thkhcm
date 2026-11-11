# Landing Page Year End Party 2026: trải nghiệm kể chuyện liền mạch

- Ngày: 2026-11-02
- Trạng thái: chờ duyệt lại sau chỉnh sửa (chưa triển khai, chưa commit). Đã sửa theo phản hồi: cổng xé giấy giữ ở lúc tải trang (quyết định 3, mục 5); `program.image` giữ đi cùng Dress code (quyết định 9); form xác nhận bỏ ô tên, chỉ còn radio (quyết định 10, mục 4.2–4.4).
- Phân loại: architectural (gộp 3 trang thành 1, đổi luồng dữ liệu vé, thêm lớp token dùng chung)

## 1. Mục tiêu

Biến landing page thành một trang cuộn duy nhất kể câu chuyện:
nhìn lại một năm → nhận lời mời → xác nhận tham gia → nhận vé → chuẩn bị trang phục → theo dõi lịch trình.

Ba việc phải đạt:

1. Sắp xếp lại 6 section theo đúng thứ tự trên.
2. Chuyển tiếp mềm giữa các section (thiết kế cả điểm kết thúc section trước và điểm bắt đầu section sau).
3. Typography đồng nhất: tối đa 2 font cho thang chữ, token dùng chung, responsive Desktop/Tablet/Mobile.

Nguyên tắc: giữ nguyên nội dung, cấu trúc dữ liệu và chức năng hiện có; không thêm hoặc bớt nội dung ngoài những chuỗi nêu ở mục 2; không thiết kế lại toàn bộ.

## 2. Quyết định đã chốt

| # | Quyết định | Nguồn |
|---|---|---|
| 1 | Vé chỉ hiện khi RSVP mới nhất của `uuid` là "tham gia". Chặn ở server: HTML không chứa `luckyDrawCode` và số bàn khi chưa đủ điều kiện. | Người dùng chọn "Chỉ sau RSVP" |
| 2 | Người "không tham gia" vẫn thấy dress code và lịch trình; chỉ ẩn vé. | Người dùng chọn "Chỉ ẩn vé" |
| 3 | Cổng xé giấy giữ nguyên là lớp phủ toàn màn hình lúc tải trang, che cả trang kể cả Recap; xé xong thì lộ Recap ở đầu trang. Không dời vào section Thiệp mời, không ẩn riêng section 3–6. | Người dùng yêu cầu giữ như hiện nay (thay cho đề xuất dời cổng) |
| 4 | Bật lại cờ `sessionStorage` `yep26-opened` (đang bị comment trong `views/invite.php`). | Duyệt thiết kế |
| 5 | Pinyon Script giữ làm chữ ký thương hiệu, chỉ cho chữ "Year End Party". Thang chữ chỉ có 2 font. | Duyệt thiết kế |
| 6 | Thêm nút "Đổi phản hồi" (chuỗi mới duy nhất) cho người đã trả lời. | Duyệt thiết kế |
| 7 | Dải nền liền thiệp → vé trên mobile được thay bằng seam. | Duyệt thiết kế |
| 8 | Một `<h1>` duy nhất cho cả trang (tiêu đề Recap); `.card-heading` đổi sang `<h2>`, giữ kiểu hiển thị. | Duyệt thiết kế |
| 9 | Ảnh `program.image` đi cùng section Dress code (yêu cầu 05 có "hình ảnh tham khảo"); section Lịch trình chỉ gồm chữ và timeline. Ảnh đang cấu hình là ảnh nền thiệp; người dùng sẽ tự thay bằng ảnh trang phục qua `config/event.php` sau. | Người dùng xác nhận giữ |
| 10 | Form xác nhận bỏ ô tên, chỉ còn nhóm radio "Tham gia được" / "Rất tiếc, tôi không thể đến". Tên ghi vào log lấy từ danh sách mời theo `id`. Không có `id` hợp lệ (link chung hoặc `id` sai) thì ẩn cả form lẫn section Xác nhận. | Người dùng yêu cầu bỏ ô tên; chọn "Ẩn form" cho link không có `id` |

## 3. Hiện trạng đã xác minh

- Ba trang riêng: `/` ([views/invite.php](../../../views/invite.php): cổng xé giấy → thiệp → vé nếu có `?id=` hợp lệ → [program.php](../../../views/partials/program.php)), `/rsvp.php` ([views/rsvp.php](../../../views/rsvp.php)), `/recap.php` ([views/recap.php](../../../views/recap.php), 8 chương).
- `rsvp.php` ghi `storage/rsvp.json` qua `App\Rsvp\RsvpLog`: log chỉ thêm dòng, mỗi dòng `{name, attending, email, uuid, submitted_at}`; một người có thể có nhiều dòng. Chưa có hàm tra theo `uuid`. Form hiện có ô nhập tên (bắt buộc, tối đa 100 ký tự, điền sẵn từ `?id=`) và radio tham gia / không; link chung hoặc `id` sai vẫn gửi được bằng tên nhập tay.
- Vé hiện nay hiện cho mọi `?id=` hợp lệ, không phụ thuộc RSVP.
- Ba font: Be Vietnam Pro, Fraunces, Pinyon Script (`.ticket-script`, `.program-script`, `.notice-script`).
- Khoảng 127 khai báo `font-size` rải trong `invite.css`, `recap.css`, `rsvp.css`. Custom property khai báo ở nhiều file với giá trị khác nhau: `--ease-out`, `--gutter`, `--paper` (`#faf6ec` và `#efe4d0`), `--font-body`, `--font-display`.
- Không có class trùng thật giữa ba file CSS (chỉ `.is-in` và `.has-pc` trùng tên, dùng độc lập). Quy tắc phần tử toàn cục (`html`, `body`, `a`, `img`, `:focus-visible`) lệch nhau giữa các file.
- JS: `motion.js` (cổng, đếm số bàn, video nền), `recap.js` (reveal, timeline, album), `ticket.js` (tải vé; gắn `[data-ticket-download]` một lần lúc nạp). Tên top-level của ba file không trùng nhau.
- Cổng hiện nay: `.tear-cover` là `position: fixed`, `z-index: 20`, rộng bằng khung điện thoại căn giữa (375px × `--s`) và full chiều rộng ở PC; `.has-cover body { overflow: hidden }` và `.tear-canvas { touch-action: none }` khoá cuộn khi cổng phủ; `initInviteCover()` đặt `scene.inert = true` cho tới khi xé xong.
- `.progress` (z-index 50), `.rail` (40) và liên kết `.skip` (100) của Recap đều `position: fixed` và nằm trên z-index của cổng.
- `program.php`, `motion.js`, `invite.css`, `config/event.php`, `views/invite.php` đang có thay đổi đã stage nhưng chưa commit: giữ nguyên, chỉ chỉnh tiếp trên đó.

## 4. Kiến trúc trang

### 4.1 Thứ tự section

```
<div class="tear-cover">         Cổng xé giấy: phủ toàn màn hình lúc tải trang (như hiện nay)
<main class="recap">            Recap (ngoài .scene, vì có phần tử position: fixed)
  seam
<div class="scene">
  #thiep       Thiệp mời
  seam
  #xac-nhan    Xác nhận tham gia
  seam
  #ve          Vé (có điều kiện)
  seam
  #lich-trinh  Chương trình (ảnh, lịch trình, dress code trong một khung)
</div>
```

Id các chương Recap giữ nguyên (`mo-dau`, `khoi-dau`, …). `.progress` và `.rail` của Recap đặt ngoài `.scene`, vì `.scene` có `container-type: inline-size` (tạo containing block cho phần tử fixed bên trong).

### 4.2 Ma trận trạng thái

| Link | RSVP mới nhất | Xác nhận | Vé | Chương trình |
|---|---|---|---|---|
| không có `id` | không áp dụng | không có (ẩn cả section và seam của nó) | không có; giữ ghi chú "Vui lòng mở link trong email mời…" trong section Thiệp mời | hiện |
| `id` hợp lệ | chưa có | form chỉ có radio | ẩn | hiện |
| `id` hợp lệ | tham gia | thông báo "Cảm ơn bạn đã xác nhận!…" + "Đổi phản hồi" | hiện | hiện |
| `id` hợp lệ | không tham gia | thông báo "Cảm ơn bạn đã phản hồi. Rất tiếc…" + "Đổi phản hồi" | ẩn | hiện |
| `id` sai | không áp dụng | trả 404 `views/invalid.php` như hiện nay | | |

Chuỗi thông báo giữ nguyên từ `views/rsvp.php`. Không thêm placeholder "vé bị khoá" hay placeholder thay form. Ghi chú trong section Thiệp mời giữ nguyên chữ hiện có, không sửa.

### 4.3 Luồng request

1. **GET `/` (và `/?id=…`)**: `public/index.php` giữ logic tìm `$employee`; thêm tra RSVP mới nhất theo `uuid`; truyền `$rsvp` (mảng hoặc null) vào view. Vé chỉ được `require` khi `$employee !== null && ($rsvp['attending'] ?? false) === true`.
2. **POST `rsvp.php?id=…` có header `Accept: application/json`** (gửi từ JS): chỉ validate `attending` (`yes`/`no`), không còn trường `name`; ghi log như hiện có. Phản hồi JSON:
   - `200 {"ok": true, "attending": bool, "rsvpHtml": "…", "ticketHtml": "…", "overlaysHtml": "…"|null, "ticketScript": "js/ticket.js?v=…"|null}`
   - `ticketHtml` luôn là chuỗi: nội dung slot `#ve` (vé và seam nếu tham gia; chỉ seam nếu không), để đổi "tham gia → không" gỡ được vé đã chèn. `overlaysHtml` và `ticketScript` là `null` khi không có vé hoặc vé chưa tải được.
   - `422 {"ok": false, "errors": {…}, "rsvpHtml": "…"}` (form kèm lỗi).
   - Các `*Html` được dựng bằng chính các partial dùng cho lần render đầu (output buffering), không viết markup lần hai.
3. **POST `rsvp.php?id=…` không JS**: thành công → 303 về `./?id=…#ve` (nếu tham gia và có `id` hợp lệ) hoặc `./?id=…#xac-nhan`; lỗi validate → render lại trang đầy đủ với status 422 và `data-opened` trên `<html>` để bỏ qua cổng. POST không có `id` hợp lệ → 404 `views/invalid.php`, không ghi log (form không được dựng trong trường hợp này, nên chỉ gặp khi request tự tạo).
4. **GET `rsvp.php`** → 302 về `./?id=…#xac-nhan` (giữ `id` nếu có). **GET `recap.php`** → 302 về `./`.

### 4.4 Dữ liệu

- `RsvpLog::latestFor(string $uuid): ?array`: duyệt `all()` từ cuối, trả dòng đầu tiên có `uuid` khớp (so sánh chữ thường).
- Thêm helper `rsvpLog(): RsvpLog` trong `bootstrap.php` (một chỗ khởi tạo với `STORAGE_PATH . '/rsvp.json'`) để `index.php` và `rsvp.php` dùng chung.
- Lỗi đọc log (`RuntimeException`) → `error_log`, coi như chưa có RSVP (ẩn vé, hiện form). Fail-closed cho vé.
- Form chỉ còn trường `attending`. Dòng log vẫn là `{name, attending, email, uuid, submitted_at}`: `name` lấy từ `$employee['name']` (danh sách mời), không nhận tên từ client; bỏ `RSVP_NAME_MAX` và `errors['name']`.
- Không đổi `data.json`, `RsvpLog::add`, mail, admin.

### 4.5 File

| Việc | File |
|---|---|
| Sửa | `public/index.php`, `public/rsvp.php`, `public/recap.php` (thành redirect), `bootstrap.php` (helper), `src/Rsvp/RsvpLog.php` (`latestFor`), `views/invite.php` (khung trang), `views/partials/head.php` (nạp CSS theo danh sách), `views/partials/ticket.php` và `ticket-overlays.php` (gọi được độc lập để dựng HTML trả về) |
| Thêm | `views/partials/recap.php`, `rsvp.php`, `schedule.php`, `seam.php`; `public/css/story.css`; `public/js/story.js` |
| Chuyển nội dung | `views/recap.php` → `partials/recap.php`; `views/rsvp.php` → `partials/rsvp.php`; `partials/program.php` → `schedule.php` (một khung gồm ảnh, lịch trình, dress code; giữ nguyên logic lọc lịch trình và kiểm tra mã màu hex) |
| CSS | `theme.css` (token, quy tắc toàn cục), `invite.css`, `recap.css`, `rsvp.css` (bỏ phần cấp trang), `story.css` (seam, reveal chung) |
| JS | `motion.js` (cổng), `recap.js` (bọc IIFE, dùng reveal chung), `ticket.js` (gắn bằng event delegation, nạp được sau khi trang đã tải) |

File cũ (`views/recap.php`, `views/rsvp.php`, `partials/program.php`) chỉ bị xoá sau khi nội dung đã chuyển hết, không còn nơi nào tham chiếu, và người dùng xác nhận (riêng `program.php` đang được stage).

### 4.6 Nội dung section Chương trình (từ program.php)

Một khung `.program` duy nhất, thứ tự: ảnh → đầu mục + timeline → dress code. `program-data.php` đặt `$hasProgram = $hasDress || $hasSchedule`; cả hai trống thì ẩn section.

- **Ảnh**: `program.image` là hình tham khảo, nằm đầu khung (cột trái trên PC) và đi theo khung dù lịch trình hay dress code trống.
- **Lịch trình**: `program.title` là Section Heading; tên sự kiện (chữ ký Pinyon Script) và khối ngày (`date_label` + ngày) giữ ở phần đầu; danh sách mốc thành timeline dọc. Lịch trình trống thì ẩn đầu mục và danh sách.
- **Dress code** (cuối khung): nhãn `dress_code_label` là heading cấp 3 (h3) dưới h2 của khung; `dress_code` là Subheading; `dress_code_note` là Caption; bảng màu `dress_code_colors` giữ nguyên. Trống thì ẩn từng khối như hiện nay; cả mô tả và bảng màu trống thì ẩn block dress code.

## 5. Cổng xé giấy

Cổng giữ nguyên cơ chế hiện có: lớp phủ `position: fixed` lúc tải trang, khoá cuộn cho tới khi xé xong. Vì thứ tự trang đổi (Recap lên đầu), thứ nằm dưới lớp giấy khi xé xong là Recap ở đầu trang chứ không phải thiệp; section 2–6 nằm bên dưới Recap nên không cần ẩn riêng.

Giữ nguyên:

- `position: fixed`, `.has-cover body { overflow: hidden }`, `touch-action: none` trên canvas, `TearCover`, `MOTION.tear`, nội dung và chữ gợi ý trong `.tear-cover`. Không có xung đột vuốt với cuộn, vì trang vốn bị khoá cuộn khi cổng phủ.
- Trạng thái khởi tạo do script inline trong `<head>` đặt trên `<html>` (tên `has-cover`, `is-waiting`, `is-skip`), fail-safe 4 giây (`window.yepMotionReady`), `onReveal`/`onDone`.

Thay đổi:

- **z-index**: nâng `.tear-cover` lên trên `.skip` (100) của Recap, vì `.progress` (50), `.rail` (40) và `.skip` đều `fixed` và hiện đang nằm trên cổng. Giá trị cụ thể chọn khi triển khai (đề xuất 200).
- **inert**: `initInviteCover()` đặt `inert` cho cả `.recap` và `.scene` (hiện chỉ có `.scene`) cho tới khi xé xong, để Tab và đọc màn hình không vào được nội dung dưới giấy; `onReveal` gỡ cả hai.
- **Reveal của Recap chờ cổng**: reveal chung (mục 7.2) không thêm `is-in` khi `<html>` còn `is-waiting`, nếu không phần đầu Recap chạy xong hiệu ứng ngay dưới lớp giấy trước khi người dùng thấy. `onReveal` gỡ `is-waiting` rồi phát `CustomEvent('yep:cover-open')`; `story.js` nghe sự kiện này để quét lại các phần tử đang trong khung nhìn.
- **Bật lại cờ `sessionStorage` `yep26-opened`** (đang bị comment trong `views/invite.php`): đã mở trong phiên thì bỏ qua cổng (`is-skip`).
- **Điều kiện bỏ qua cổng** (ngoài cờ trên): `location.hash` là `#thiep`, `#xac-nhan`, `#ve` hoặc `#lich-trinh` (trường hợp `sessionStorage` bị chặn nên redirect 303 sau `form.submit()` không thấy cờ), hoặc `<html data-opened>` do server đặt khi render lại lỗi validate.
- Nút "Xem thiệp mời" ở cuối Recap trỏ về `#thiep` (anchor trong trang) thay vì `./`.

Chưa quyết, mặc định giữ nguyên: chữ gợi ý trên cổng là "Vuốt xuống để mở thiệp", nhưng xé xong lộ Recap chứ không phải thiệp. Đổi chữ là thay đổi nội dung nên không làm khi chưa được yêu cầu.

## 6. Typography và design tokens

Đặt trong `theme.css` (nơi đã khai báo token màu và nạp ở mọi trang).

### 6.1 Font

| Token | Giá trị | Dùng cho |
|---|---|---|
| `--font-heading` | Fraunces, Cormorant Garamond, Georgia, serif | mọi tiêu đề, tên khách, số lớn |
| `--font-body` | Be Vietnam Pro, system-ui, sans-serif | nội dung, nhãn, nút |
| `--font-script` | Pinyon Script, cursive | chỉ chữ "Year End Party" (ngoài thang chữ) |

Tên cũ trỏ về token mới (cùng cách `invite.css` đang trỏ về `theme.css`): `--font-serif`, `--font-display` → `--font-heading`; `--font` → `--font-body`.

### 6.2 Thang cỡ chữ

SP (≤640px) dùng giá trị cố định vì khung thiệp/vé đã phóng bằng `zoom: var(--s)` (dùng `vw` trong khung đó sẽ bị nhân đôi). PC (≥641px) co giãn bằng `clamp`.

| Token | Cấp | SP | PC | Thay cho |
|---|---|---|---|---|
| `--text-hero` | Main Heading | 2.75rem | clamp(3.5rem, 8vw, 7.5rem) | `.hero-title`, `.card-heading` |
| `--text-section-title` | Section Heading | 2.25rem | clamp(2.5rem, 5.5vw, 5rem) | `.chapter-title`, `.rsvp-title`, `.ticket-name`, tiêu đề Dress code và Lịch trình |
| `--text-subheading` | Subheading | 1.25rem | clamp(1.25rem, 2.2vw, 1.875rem) | `.chapter-intro`, `.hero-sub`, `.milestone-title`, `.program-dress-value`, `.card-name` |
| `--text-body` | Body | 1rem | clamp(1rem, 1.1vw, 1.125rem) | `.body`, `.rsvp-lead`, `.program-item`, nút và lựa chọn radio |
| `--text-caption` | Caption | .875rem | clamp(.875rem, .95vw, .95rem) | `.program-dress-note`, `.ticket-note`, `.rsvp-facts dd span` |
| `--text-label` | Label | .75rem | clamp(.75rem, .9vw, .85rem) | `.eyebrow`, `.card-eyebrow`, `.rsvp-label`, `.rsvp-facts dt` |

Nút và lựa chọn radio dùng `--text-body` (Label 12px quá nhỏ cho thành phần tương tác); đây là điều chỉnh có chủ ý so với mô tả "Label gồm cả thành phần giao diện".

### 6.3 Trọng lượng, giãn chữ, line-height

| Token | Giá trị |
|---|---|
| `--weight-heading` | 500 |
| `--weight-body` | 400 |
| `--weight-label` | 500 |
| `--weight-strong` | 600 |
| `--tracking-hero` | .08em (viết hoa) |
| `--tracking-heading` | .02em (Section Heading, viết thường kiểu câu) |
| `--tracking-label` | .26em (viết hoa; gộp .26em và .32em hiện có) |
| `--line-height-heading` | 1.1 |
| `--line-height-body` | 1.65 |

### 6.4 Quy tắc

- Hero và Section Heading dùng `.heading-primary` (vàng kim loại), đúng ghi chú đã có trong `theme.css` ("H1, H2 mọi trang"). Hiện recap chưa theo.
- `.rsvp-title` bỏ `text-transform: uppercase` để cùng kiểu với các Section Heading khác.
- Một quy tắc `html`/`body`/`a`/`img`/`:focus-visible` duy nhất trong `theme.css`, dùng token (thay ba bản lệch nhau).
- Gộp custom property trùng: `--ease-out` (một đường cong), `--gutter`, `--paper` (recap đổi sang `--paper-warm`), `--font-*`.
- Các giá trị trong bảng 6.2–6.3 là giá trị khởi điểm. Khi triển khai được chỉnh bằng mắt trên trình duyệt (cho phép lệch khoảng ±15% mà không cần duyệt lại); thứ tự phân cấp hero > section-title > subheading > body > caption > label phải giữ nguyên.
- Bảng ánh xạ selector → token đầy đủ nằm trong kế hoạch triển khai.

## 7. Chuyển tiếp và motion

### 7.1 Seam

Partial `seam.php` dựng `<div class="seam" aria-hidden="true">` ở các ranh giới giữa hai section đang hiển thị, tối đa 5 (Recap→Thiệp, Thiệp→Xác nhận, Xác nhận→Vé hoặc Dress code, Vé→Dress code, Dress code→Lịch trình). Section ẩn thì bỏ seam của nó (link chung: Thiệp→Dress code). Mỗi seam có:

- **Nền tan mép**: cao `clamp(120px, 22vh, 220px)`, chồng lên hai section bên cạnh bằng margin âm; gradient từ màu đáy section trên sang màu đỉnh section dưới (`--seam-from`, `--seam-to` đặt inline). Recap→Thiệp chuyển từ `--ink` sang nền ảnh cố định (`body::before`). Trên mobile, `.page-bg` của thiệp và vé tự có mặt nạ tan mép trên/dưới, thay cho dải nền liền cũ.
- **Sợi chỉ vàng**: đường dọc 1px ở giữa, `transform: scaleY(var(--seam-p))`; `--seam-p` (0–1) do `story.js` tính từ vị trí seam trong khung nhìn (rAF, chỉ khi seam đang trong khung).
- **Hạt vàng**: dùng lại `.particle` và `@keyframes drift`; 3 hạt trên mobile, 6 hạt trên PC; chỉ chạy (`.is-live`) khi seam trong khung nhìn.
- **Chuyền tay hai đầu**: khi seam vào khung, phần tử cuối của section trên mờ còn khoảng 60% và dịch lên tối đa 16px; phần tử đầu của section dưới reveal trễ khoảng 0,15s sau khi sợi chỉ chạm tới.

Các ranh giới có thêm:

- Xác nhận→Vé: dấu ✓ của trạng thái "đã xác nhận" nối bằng sợi chỉ xuống vé; vé vào bằng `ticket-in`/`ticket-in-mobile` đã có. Người không tham gia: sợi chỉ đi thẳng xuống Dress code.
- Dress code→Lịch trình: hàng ô màu nối vào trục timeline.
- Lịch trình: trục dọc vàng + chấm mốc vẽ dần theo cuộn (tái dùng cách `.spine`/`.milestone-dot` của recap).

### 7.2 Reveal chung

Gộp `.pv` (invite) và `[data-reveal]` (recap) thành một cơ chế do `story.js` điều khiển, token `--reveal-dur: .7s`, `--reveal-stagger: .1s`, `--reveal-ease: var(--ease-out)`. Chỉ animate `opacity` và `transform`. Nội dung phải hiện đủ khi JS lỗi (giữ cơ chế `.js` gỡ sau 4 giây). Khi cổng xé giấy còn phủ (`<html class="is-waiting">`), reveal tạm hoãn và chạy lại khi nhận `yep:cover-open` (mục 5).

### 7.3 Thanh tiến độ và rail

`.progress` đo tiến độ toàn trang. `.rail` (8 chương) chỉ hiện khi đang ở trong khối Recap.

### 7.4 Hiệu năng và trợ năng

- `prefers-reduced-motion: reduce`: seam tĩnh, không hạt, reveal chỉ fade ≤ .4s (theo khối hiện có ở cuối `invite.css`).
- Hạt và sợi chỉ tạm dừng khi ngoài khung nhìn; không dùng `backdrop-filter` mới; `will-change` chỉ đặt trên phần tử đang chạy.
- Chữ vàng `background-clip: text` kèm `filter: drop-shadow` ở tiêu đề lớn cần kiểm tra chi phí vẽ và tương tác với reveal bằng `clip-path` của tiêu đề recap; nếu giật thì bỏ `drop-shadow` ở các tiêu đề đó.

## 8. JavaScript

- `story.js` (IIFE, nạp cuối): reveal chung (hoãn khi còn `is-waiting`, chạy lại khi nhận `yep:cover-open`), `--seam-p`, tiến độ trang, rail chỉ trong Recap, gửi RSVP bằng `fetch` và thay `rsvpHtml`/`ticketHtml`. Sau khi thay vé: nạp `ticketScript` nếu chưa có, phát `CustomEvent('yep:ticket-ready')`, cuộn mượt tới `#ve`, đưa focus vào tiêu đề vé.
- `motion.js`: cổng xé giấy (mục 5: `inert` cho `.recap` và `.scene`, phát `yep:cover-open`), đếm số bàn, video nền, nghe `yep:ticket-ready` để chạy lại `initTicketReveal`.
- `recap.js`: bọc IIFE (tránh rò `reduced`, `clamp`, `RECAP` ra global), dùng reveal chung thay cho `initReveal` riêng; giữ đếm số, timeline, album.
- `ticket.js`: gắn nút tải vé bằng event delegation trên `document` (hiện gắn một lần lúc nạp nên không bắt được nút chèn sau). Toast nằm trong `overlaysHtml`.
- Lỗi `fetch` hoặc không phải JSON → `form.submit()` thường (đường không JS), người dùng không mất phản hồi.

## 9. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| Reveal đầu Recap chạy xong dưới lớp giấy trước khi cổng mở | Hoãn reveal khi còn `is-waiting`, chạy lại khi nhận `yep:cover-open` (mục 5, 7.2) |
| `.progress`, `.rail`, `.skip` của Recap hiện đè lên cổng | Nâng z-index cổng, `inert` cho `.recap` (mục 5) |
| Nền ảnh mobile lệch sau khi bỏ dải liền thiệp→vé | Mặt nạ tan mép cho `.page-bg`, kiểm tra ở 375/414/640px |
| Cỡ chữ token khác cỡ cũ trong khung phóng `--s` làm vỡ dòng tiêu đề (ví dụ "YEAR END") | Chỉnh bằng mắt, cho phép ±15% |
| Hợp nhất quy tắc toàn cục làm đổi màu liên kết hoặc viền focus ở trang khác | Kiểm tra `invalid.php` và trang admin không nạp các CSS này; rà `a`, `:focus-visible` trước khi gộp |
| `fetch` + thay HTML làm mất gắn kết sự kiện | Event delegation, sự kiện `yep:ticket-ready`; có fallback `form.submit()` |
| Log RSVP hỏng hoặc không đọc được | Fail-closed: ẩn vé, hiện form, `error_log` |
| Hai cờ fail-safe (`yepMotionReady`, `recapReady`) cùng tồn tại | Một script inline gỡ `js`, `has-cover` đúng theo từng cờ sau 4 giây |

## 10. Kiểm thử

Repo không có test suite; kiểm tra bằng `php -S localhost:8000 -t public`, `curl` và trình duyệt.

1. `GET /?id=<uuid hợp lệ>` khi chưa RSVP: mã nguồn trang không chứa `luckyDrawCode` (ví dụ `YEP26-11`) và số bàn.
2. POST tham gia: JSON có `ticketHtml`; tải lại trang thì vé hiện; nút "Tải vé" tải được; dòng log mới có `name` đúng tên trong danh sách mời; HTML form không còn input `name`.
3. POST không tham gia: không có vé, vẫn có Dress code và Lịch trình; lần RSVP sau (đổi sang tham gia) làm vé hiện.
4. RSVP mới nhất thắng: log có nhiều dòng cùng `uuid`.
5. `GET /?id=sai` → 404; link chung không `id` → không có vé, không có section Xác nhận, ghi chú "Vui lòng mở link trong email mời…" vẫn hiện; POST `rsvp.php` không `id` hoặc `id` sai → 404 và `rsvp.json` không thêm dòng; `rsvp.php` và `recap.php` (GET) redirect đúng.
6. Không JS: gửi form → 303 về `#ve` / `#xac-nhan`; chưa chọn radio → 422 kèm thông báo "Vui lòng chọn bạn có tham gia được hay không."
7. Giao diện ở 375, 414, 640, 768, 1440px; tên dài ("Nguyễn Thị Phương Thảo") trên vé.
8. Cổng: xé, bấm nút, mở lại trong phiên (bỏ qua), `#ve`/`#xac-nhan` bỏ qua cổng, JS lỗi (4 giây tự mở), `prefers-reduced-motion`. Khi cổng phủ: không cuộn được, không thấy `.progress`/`.rail`, Tab không vào được nội dung bên dưới. Sau khi xé: phần đầu Recap chạy hiệu ứng vào (không chạy trước dưới lớp giấy).
9. `invalid.php` và trang admin không đổi giao diện.
10. Dọn dẹp: `storage/rsvp.json` hiện chưa tồn tại; sau thử nghiệm xoá đúng file do mình tạo.

## 11. Ngoài phạm vi

- Nội dung `config/recap.php` (nhiều chỗ còn placeholder `[Cần bổ sung…]`) và ảnh thật.
- Mail, admin, `data.json`.
- Bảo vệ CSRF cho form RSVP (hiện chưa có; mô hình đe doạ giữ như vé hiện nay: biết `uuid` là xem được).
- Lưu ý bảo mật đã nêu: `.env` đang được git theo dõi (`#.env` bị comment trong `.gitignore`), cần xoay SMTP và `ADMIN_TOKEN`.

## 12. Tiêu chí nghiệm thu

1. Trang `/` hiển thị đúng thứ tự: Recap → Thiệp mời → Xác nhận → Vé → Dress code → Lịch trình, section Xác nhận và vé theo mục 4.2.
2. Mỗi ranh giới giữa hai section có seam; không còn đường cắt cứng; không có hiệu ứng riêng lẻ chỉ cho một section.
3. Chỉ dùng `--font-heading` và `--font-body` cho thang chữ; mọi font-size của các cấp tiêu đề, body, caption, label lấy từ token `--text-*`.
4. Các tiêu đề cùng cấp có cùng cỡ, weight, giãn chữ và line-height.
5. Xác nhận tham gia và hiển thị vé hoạt động đúng cả khi có JS và không JS; tải vé vẫn hoạt động.
6. Responsive đạt ở Desktop, Tablet, Mobile; không cuộn ngang.
7. `prefers-reduced-motion` được tôn trọng; không giật rõ rệt khi CPU throttling 4x.
8. Cổng xé giấy phủ trang lúc tải như hiện nay; xé xong lộ Recap ở đầu trang; đã mở trong phiên thì không hiện lại.
