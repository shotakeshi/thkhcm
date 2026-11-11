# PROMPT: Landing page thiệp mời Year End Party 2026 – THK Holdings Vietnam

## 0. Bối cảnh codebase (bắt buộc đọc trước)
- Project PHP 8 thuần (không framework), autoload PSR-4 `App\` → `src/`, PHPMailer.
- `bootstrap.php` đã có: `loadEmployees()`, `findEmployee()`, `e()`, `loadEnv()`.
- `storage/data.json`: key = email, mỗi record hiện có `name`, `position`, `code`.
- `public/index.php` + `public/send.php` hiện là công cụ ADMIN gửi mail.
- Tái sử dụng hàm và convention sẵn có (config dạng `config/*.php` trả về array, cache-busting `filemtime`), không tạo pattern mới.
- `public/css/app.css` là stylesheet của trang admin (phong cách tối giản kiểu Apple, có dùng Bootstrap 5 + Bootstrap Icons). Đọc file này trước và tuân theo quy ước ở mục 6.1.

## 1. Mục tiêu
Landing page dạng thiệp mời điện tử cho YEP 2026, gồm 2 trang: Thiệp mời và Vé tham dự cá nhân.
Ưu tiên cảm xúc, hình ảnh, trải nghiệm mở thiệp trên mobile (mở từ Zalo/email).
Có motion graphic tinh tế tạo cảm giác mở một tấm thiệp cao cấp (chi tiết ở mục 9).
Không thiết kế kiểu dashboard hay website doanh nghiệp.

## 2. Tái cấu trúc route
- Trang admin gửi mail chuyển sang `/admin?token={ADMIN_TOKEN}`.
  - `ADMIN_TOKEN` đặt trong `.env`, so sánh bằng `hash_equals()`.
  - Sai/thiếu token → HTTP 403, không lộ nội dung admin.
  - `send.php` (hoặc endpoint gửi mail sau khi di chuyển) cũng BẮT BUỘC kiểm tra token, JS admin gửi kèm token.
  - Trang admin gửi header `Referrer-Policy: no-referrer` và `noindex`.
  - Giữ nguyên toàn bộ hành vi gửi mail hiện tại (test mode, sent log, khoá gửi khi log lỗi); sửa lại đường dẫn css/js/images sau khi di chuyển.
- Trang gốc `/` trở thành Page 1 – Thiệp mời.
- Page 2: `/ticket?id={uuid}` (hoặc phương án tương đương không cần router; nếu cần rewrite rule thì ghi rõ cho Apache/Nginx).

## 3. Tài liệu tham khảo
- Ảnh 1 `yep2026.png` (NGUỒN CHÍNH): màu, hoa watercolor xanh ở góc, chữ script trung tâm, đốm gold, logo góc phải trên.
- Ảnh 2 (phụ): chỉ lấy khung hình học viền gold và cách trình bày ngày `THỨ | SỐ NGÀY | THÁNG` có đường kẻ.
- Ảnh 3 (phụ): chỉ lấy bố cục lệch một bên + đường kẻ dọc phân tách ngày/giờ.
- KHÔNG lấy nội dung chữ của ảnh 2, 3 (đây là thiệp cưới).
- Nếu đã có thiết kế từ Claude Design (`docs/prompts/yep2026-claude-design.md`), bám theo thiết kế đó, design tokens và thông số motion đi kèm.

## 4. Asset (tôi đính kèm, KHÔNG tự vẽ hoa bằng CSS/SVG)
- `public/images/yep2026.png` – ảnh thiệp gốc, dùng cho download và og:image.
- Các file hoa watercolor nền trong suốt: [tên file theo bộ đính kèm].
- `public/images/thk.png`, `thk-logo.png` – logo.
- Ảnh hiển thị được phép tối ưu (WebP, resize); file download phải là ảnh gốc, không nén.
- Asset cho motion (được phép tự tạo):
  - SVG chữ "Year End Party": chuyển font script đã chọn sang path. Nếu tôi cung cấp path nét bút (centerline) vẽ tay thì dùng làm mask cho hiệu ứng viết tay; nếu không, dùng phương án vẽ viền rồi tô (xem mục 9).
  - Mask vệt màu nước (SVG/PNG grayscale) để reveal hoa.
  - Phong bì/lớp giấy mở thiệp dựng bằng HTML/CSS/SVG, cùng tông giấy trắng + viền gold.

## 5. Thông tin sự kiện – cấu hình động
Tạo `config/event.php` (trả về array), view chỉ đọc từ config, không hardcode:
- `name`, `date`, `time`, `venue_name`, `venue_address`, `map_url` (tuỳ chọn), `monogram` (tuỳ chọn), `dress_code` (tuỳ chọn), `invitation_image`.
- Field tuỳ chọn để trống → ẩn luôn block đó trên giao diện, không hiện nhãn rỗng.
- Thứ trong tuần tự tính từ `date`, không nhập tay.

## 6. Định hướng thiết kế
- Màu: nền trắng giấy, primary navy đậm, secondary xanh pastel, accent gold. Dùng CSS variables, không thêm màu ngoài bảng.
- Font (Google Fonts, BẮT BUỘC có subset `vietnamese` cho mọi text tiếng Việt):
  - Script: chỉ dùng cho chữ tiếng Anh ("Year End Party").
  - Serif hỗ trợ tiếng Việt: tên nhân viên, tiêu đề phụ.
  - Sans-serif hỗ trợ tiếng Việt: thông tin, nút.
- Trang trí tập trung ở góc/viền, vùng nội dung giữa thoáng.

### 6.1 Đồng bộ với `public/css/app.css`
Trang thiệp dùng **bảng màu và font riêng của thiệp** (navy, gold, watercolor; Script/Serif/Sans tiếng Việt), KHÔNG dùng màu xanh `--blue: #0071e3` hay font hệ thống của admin. Nhưng phải theo cùng quy ước code và tương tác:
- File CSS riêng (ví dụ `public/css/invite.css`), không sửa và không import `app.css` vào trang thiệp; trang thiệp không tải Bootstrap.
- Design tokens khai báo trong `:root`, đặt tên ngắn theo vai trò như `app.css`: giữ các tên `--bg`, `--surface`, `--text`, `--muted`, `--focus`, `--font`; thêm token mới cùng kiểu (ví dụ `--navy`, `--gold`, `--font-script`, `--font-serif`).
- Đặt tên class kebab-case theo khối (`.ticket`, `.ticket-name`, `.ticket-stub`), trạng thái dùng tiền tố `is-` (`is-muted`, `is-danger`…) như `app.css`.
- Chia section bằng comment `/* ===== Tên khối ===== */` bằng tiếng Việt.
- Nút: dạng pill (`border-radius: 980px`), `min-height: 44px`, `:active { transform: scale(.97) }`, `:disabled` giảm opacity; focus bằng `:focus-visible` + `box-shadow: var(--focus)` (vòng focus đổi sang màu navy/gold của thiệp).
- Số (Lucky Draw Code, số bàn, ngày giờ) dùng `font-variant-numeric: tabular-nums`.
- Breakpoint mobile `@media (max-width: 640px)` như `app.css` (được thêm breakpoint nếu cần cho tablet/desktop).
- Khối `@media (prefers-reduced-motion: reduce)` đặt cuối file.
- Phần admin bổ sung (cảnh báo thiếu `uuid`, trùng `luckyDrawCode`, lỗi thiếu `uuid` khi gửi) dùng lại class có sẵn trong `app.css` (`.test-banner`, `.is-danger`, `.status-*`, `.manual-result`), không tạo style mới cho admin.

## 7. Page 1 – Thiệp mời (`/`)
Nội dung: logo, monogram (nếu có), "Year End Party 2026", ngày giờ, địa điểm, nút "Xem thông tin tham dự", nút "Tải thiệp".
URL có `id` hợp lệ → thêm dòng "Thân mời {tên}" và CTA dẫn tới vé của người đó.
Motion: theo kịch bản Page 1 ở mục 9.

Download:
- Link trực tiếp tới ảnh gốc, có `download`.
- Fallback cho Zalo in-app browser / iOS: mở ảnh trong tab mới kèm hướng dẫn "Nhấn giữ ảnh để lưu".
- Không chụp màn hình website.

## 8. Page 2 – Vé tham dự cá nhân (`/ticket?id={uuid}`)
Dạng vé VIP, có đường răng cưa phân tách cuống vé:
- Tên nhân viên: nổi bật nhất (Serif tiếng Việt).
- Lucky Draw Code: chữ lớn, letter-spacing rộng, dễ đọc.
- Số bàn: badge lớn ở cuống vé.
- Khu vực nhỏ: tên sự kiện, ngày giờ, địa điểm (từ `config/event.php`).
- Hoa watercolor đồng bộ Page 1. Không dùng QR Code.
- Nút "Quay lại thiệp".
Motion: theo kịch bản Page 2 ở mục 9.

## 9. Motion graphic
Phong cách: chậm, mềm, sang trọng như mở thiệp giấy thật. Không glitch, neon, parallax liên tục, rung lắc hay nhấp nháy mạnh.

### Kịch bản Page 1 (tổng ≤ 5s đến khi CTA bấm được)
| # | Phần tử | Hiệu ứng | Thời lượng gợi ý |
|---|---|---|---|
| 1 | Phong bì / lớp giấy | Nắp phong bì hoặc 2 nửa giấy mở ra, lộ thiệp bên trong | ~0.8s |
| 2 | Hoa 4 góc | Reveal bằng mask vệt màu nước loang từ góc vào, kèm scale 0.95 → 1 | ~1.2s, so le giữa các góc |
| 3 | Logo, monogram | Fade + trượt lên nhẹ | ~0.5s |
| 4 | "Year End Party" | Hiệu ứng viết tay (mask theo path nét bút) hoặc vẽ viền rồi tô màu; kết thúc bằng vệt sáng gold lướt qua chữ | ~1.5s |
| 5 | Đường kẻ gold | Tự vẽ từ giữa ra hai bên (stroke-dashoffset) | ~0.4s |
| 6 | Ngày, giờ, địa điểm | Hiện lần lượt, fade + trượt lên | ~0.6s |
| 7 | CTA | Fade-in, sau đó viền gold có ánh sáng chạy nhẹ 1 lần | ~0.4s |

Chuyển động nền (chạy liên tục sau intro, rất nhẹ):
- Hoa đung đưa (rotate 1–2°, chu kỳ 6–10s, mỗi góc lệch pha).
- Đốm gold bay chậm và lấp lánh trên canvas phía sau nội dung.

Hành vi:
- Chạm/click bất kỳ trong lúc intro → nhảy ngay tới trạng thái cuối.
- Lần mở lại trong cùng phiên (sessionStorage) → bỏ phong bì, chạy bản rút gọn ≤ 1.5s.

### Kịch bản Page 2
- Vé trượt lên và lật nhẹ từ thiệp (rotateX), ~0.8s.
- Tên nhân viên fade-in theo từng từ (SplitText tách theo word, không tách ký tự để tránh vỡ dấu tiếng Việt), Lucky Draw Code có vệt sáng gold lướt qua 1 lần.
- Số bàn scale-in kèm đếm số nhanh (00 → giá trị thật, ≤ 0.6s); nếu giá trị "Đang cập nhật" thì chỉ fade-in, không đếm.
- Đường răng cưa và viền gold tự vẽ ra.

### Kỹ thuật & hiệu năng
- Được dùng GSAP (core + DrawSVG, SplitText) cho timeline; tải từ file local hoặc CDN có pin version cụ thể; kiểm tra license trước khi dùng. Hiệu ứng nền đơn giản (sway) dùng CSS keyframes.
- Ưu tiên animate `transform`/`opacity`; mask chỉ dùng cho bước reveal, không chạy liên tục.
- Canvas đốm gold: ≤ 40 hạt trên desktop, ≤ 20 trên mobile; giới hạn devicePixelRatio tối đa 2; dừng khi tab ẩn (`visibilitychange`) và khi canvas ra khỏi viewport.
- Intro chỉ bắt đầu khi font và ảnh hoa đã tải xong (có timeout dự phòng, không để màn hình trắng quá 1.5s).
- Nội dung là HTML thật, có thể đọc được khi JS lỗi hoặc tắt (motion chỉ là lớp trang trí).
- `prefers-reduced-motion: reduce` → bỏ phong bì, bỏ particle, bỏ sway; hiện ngay toàn bộ với fade ≤ 0.3s.
- Các thông số thời lượng/easing đặt tập trung một chỗ trong JS để chỉnh dễ.

## 10. Dữ liệu & truy cập
- Thêm vào mỗi record trong `data.json`: `uuid` (UUID v4), `luckyDrawCode`, `tableNumber`. Admin điền sẵn, hệ thống KHÔNG tự sinh/random.
- Viết hàm tìm nhân viên theo `uuid` trong `bootstrap.php`, cạnh `findEmployee()`.
- Toàn vẹn dữ liệu: nếu có 2 record trùng `uuid` → báo lỗi rõ ràng (không hiển thị vé của người khác). Trang admin hiển thị cảnh báo khi thiếu `uuid` hoặc trùng `luckyDrawCode`.
- `id` không đúng định dạng UUID → trả về trạng thái "không tìm thấy", không truy vấn tiếp.
- PHP render server-side, chỉ trả dữ liệu của đúng 1 người; KHÔNG đưa danh sách nhân viên ra JS/JSON public.
- Trạng thái:
  - Không có `id`: Page 1 hiển thị chung, CTA hiện hướng dẫn "Vui lòng mở link trong email mời".
  - `id` sai: trang thông báo lịch sự + nút quay về thiệp (HTTP 404).
  - Thiếu `luckyDrawCode`/`tableNumber`: hiển thị "Đang cập nhật".
- Escape mọi output bằng `e()`. Trang public có `<meta name="robots" content="noindex">`.

## 11. Tích hợp mail
- Thêm placeholder `{invite_url}` vào `config/template.php`, tạo link từ `APP_URL` (.env) + `uuid`.
- Nhân viên thiếu `uuid` → không cho gửi, báo lỗi trên trang admin.

## 12. Kỹ thuật
- PHP cho view + vanilla CSS/JS, không framework frontend. Thư viện ngoài duy nhất được phép là GSAP (mục 9).
- Tách: config sự kiện, hàm lấy dữ liệu, view từng page, CSS, JS (module motion tách riêng khỏi logic trang).
- Thẻ Open Graph (og:title, og:description, og:image = ảnh thiệp) để preview khi chia sẻ qua Zalo.

## 13. Quy trình
1. Bước 1 – Design concept: phân tích thiệp gốc, đề xuất layout 2 page, font cụ thể (tên Google Fonts), spacing, timeline motion, kế hoạch di chuyển admin. **DỪNG lại để tôi duyệt.**
2. Bước 2 – Triển khai: di chuyển admin + bảo vệ token, Page 1, Page 2, motion, download, dữ liệu, mail.
3. Bước 3 – Kiểm tra:
   - 375px, 768px, 1440px; tên tiếng Việt dài ("Nguyễn Thị Phương Thảo").
   - uuid sai/thiếu/trùng; admin sai token (cả trang admin lẫn endpoint gửi mail).
   - Motion: chạm để bỏ qua, mở lại trong phiên, reduced-motion, tab ẩn dừng particle, không giật trên mobile (Chrome DevTools CPU throttling 4x).
   - Download trên iOS/Android; gửi mail test có link đúng.

## 14. Tiêu chí nghiệm thu
1. Page 1 có cảm giác mở một tấm thiệp thật, bám sát ảnh 1.
2. Page 2 giống vé VIP cá nhân; tên, Lucky Draw Code, số bàn đọc được ngay trên mobile.
3. Motion mượt, tinh tế, CTA bấm được trong ≤ 5s (≤ 1.5s khi mở lại), bỏ qua được bằng một chạm.
4. Tiếng Việt có dấu hiển thị đúng ở mọi font.
5. Không xem được vé người khác bằng cách đổi URL; không vào được admin hay gửi mail khi thiếu token.
6. CSS trang thiệp theo đúng quy ước ở mục 6.1; giao diện admin không thay đổi ngoài các cảnh báo bổ sung.
7. Đổi thông tin sự kiện chỉ cần sửa `config/event.php`.
8. Chức năng gửi mail hiện có hoạt động như cũ sau khi chuyển sang `/admin`.
