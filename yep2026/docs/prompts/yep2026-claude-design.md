# PROMPT (Claude Design): Thiết kế giao diện thiệp mời YEP 2026

> Dùng prompt này trong Claude Design để chốt giao diện trước khi code.
> Đính kèm: `yep2026.png` (thiệp gốc), các file hoa watercolor nền trong suốt, logo `thk.png`, và 2 ảnh thiệp cưới tham khảo.

---

Thiết kế giao diện cho một landing page thiệp mời điện tử **Year End Party 2026** của **THK Holdings Vietnam**, gửi cho nhân viên qua email/Zalo. Đây là thiệp mời sự kiện, cần cảm giác trang trọng, tinh tế, như đang mở một tấm thiệp thật. Không thiết kế theo kiểu website doanh nghiệp hay dashboard.

## Tài liệu tham khảo đính kèm

- **Thiệp gốc `yep2026.png` – nguồn chính.** Bám sát: nền giấy trắng có vân watercolor xanh nhạt, hoa watercolor xanh dương và trắng ở các góc, đốm gold rải nhẹ, chữ script lớn ở giữa, ngày tháng dạng số có đường kẻ, logo đặt ở góc phải trên.
- **Ảnh thiệp cưới số 2 – phụ.** Chỉ mượn khung hình học viền gold và cách trình bày ngày `THỨ | SỐ NGÀY | THÁNG` có đường kẻ hai bên.
- **Ảnh thiệp cưới số 3 – phụ.** Chỉ mượn bố cục lệch một bên và đường kẻ dọc phân tách ngày với thứ/giờ.
- Không dùng bất kỳ chữ nào của 2 ảnh thiệp cưới.
- **Hoa watercolor:** dùng đúng các file PNG đính kèm, đặt ở góc/viền. Không vẽ lại hoa bằng hình vector đơn giản.

## Ngôn ngữ thiết kế

- **Màu:** nền trắng giấy/trắng kem; primary navy đậm (chữ chính); secondary xanh pastel; accent gold (đường kẻ, viền, điểm nhấn). Không thêm màu khác.
- **Font (Google Fonts):**
  - Script thanh lịch, gần với chữ "Year End Party" trên thiệp gốc. Chỉ dùng cho chữ tiếng Anh.
  - Serif có hỗ trợ tiếng Việt đầy đủ dấu: tên nhân viên, tiêu đề phụ.
  - Sans-serif có hỗ trợ tiếng Việt: thông tin, nhãn, nút.
  - Ghi rõ tên font đã chọn. Kiểm tra chữ có dấu như "Nguyễn Thị Phương Thảo" hiển thị đúng.
- **Bố cục:** trang trí dồn ra góc và viền, vùng nội dung giữa thoáng, nhiều khoảng trắng.
- **Đồng bộ với hệ thống hiện có** (trang admin gửi thư mời, phong cách tối giản kiểu Apple). Giữ màu và font của thiệp, nhưng theo cùng quy ước tương tác:
  - Nút dạng pill (bo tròn hoàn toàn), cao tối thiểu 44px, nhấn vào co nhẹ.
  - Có vòng focus rõ ràng khi điều hướng bằng bàn phím (đổi sang màu navy/gold).
  - Số (mã Lucky Draw, số bàn, ngày giờ) dùng chữ số đều độ rộng (tabular).
  - Mốc mobile ≤ 640px.
  - Đặt tên design tokens ngắn theo vai trò: `--bg`, `--surface`, `--text`, `--muted`, `--focus`, `--font`, thêm `--navy`, `--gold`, `--font-script`, `--font-serif`.

## Màn hình cần thiết kế

Thiết kế cho **mobile 375px trước**, sau đó **desktop 1440px**.

### 1. Thiệp mời (Page 1)
- Logo THK, monogram (tuỳ chọn, có thể ẩn), tiêu đề script "Year End Party", "2026".
- Dòng cá nhân hoá: "Thân mời **Nguyễn Văn An**" (biến thể có tên và không có tên).
- Ngày (kèm thứ), giờ, tên địa điểm, địa chỉ.
- 2 nút: **"Xem thông tin tham dự"** (chính) và **"Tải thiệp"** (phụ, dạng outline hoặc link).
- Biến thể không có tên người nhận: thay CTA bằng dòng hướng dẫn nhỏ "Vui lòng mở link trong email mời để xem vé của bạn".

### 2. Vé tham dự cá nhân (Page 2)
Thiết kế như một **tấm vé VIP**: thân vé và cuống vé phân tách bằng đường răng cưa hoặc đục lỗ, viền gold mảnh, hoa watercolor ở góc đồng bộ Page 1.
- **Tên nhân viên:** nổi bật nhất, font Serif.
- **Lucky Draw Code:** chữ lớn, giãn chữ rộng, dễ đọc và dễ đọc to lên (ví dụ `YEP26-083`).
- **Số bàn:** khối/badge lớn đặt ở cuống vé (ví dụ `08`).
- Khu vực nhỏ: tên sự kiện, ngày giờ, địa điểm.
- Nút "Quay lại thiệp".
- Không có QR Code.

### 3. Các trạng thái phụ
- Vé có Lucky Draw Code và số bàn đang hiển thị "Đang cập nhật".
- Link không hợp lệ: thông báo lịch sự cùng phong cách thiệp, có nút quay về thiệp.
- Tên rất dài (ví dụ "Tôn Nữ Hoàng Phương Thảo Nguyên") không vỡ layout trên 375px.
- Lớp hướng dẫn "Nhấn giữ ảnh để lưu" khi tải thiệp trên điện thoại.

## Motion graphic (làm prototype tương tác)

Phong cách chậm, mềm, sang trọng như mở thiệp giấy thật. Không glitch, neon, parallax liên tục, rung lắc hay nhấp nháy mạnh.

### Page 1 – intro mở thiệp (tổng ≤ 5 giây đến khi CTA bấm được)
1. **Phong bì / lớp giấy mở ra** (~0.8s): nắp phong bì hoặc 2 nửa giấy tách ra, lộ thiệp bên trong. Phong bì cùng tông giấy trắng, viền gold mảnh.
2. **Hoa reveal từ 4 góc** (~1.2s, so le): lộ ra qua mask hình vệt màu nước loang từ góc vào, kèm phóng nhẹ 0.95 → 1.
3. **Logo, monogram** (~0.5s): fade và trượt lên nhẹ.
4. **"Year End Party" viết tay** (~1.5s): chữ hiện như đang được viết bằng bút, kết thúc bằng một vệt sáng gold lướt qua chữ.
5. **Đường kẻ gold** (~0.4s): tự vẽ từ giữa ra hai bên.
6. **Ngày, giờ, địa điểm** (~0.6s): hiện lần lượt.
7. **CTA** (~0.4s): fade-in, viền gold có ánh sáng chạy qua 1 lần.

Sau intro, chuyển động nền rất nhẹ: hoa đung đưa 1–2° theo chu kỳ 6–10 giây (mỗi góc lệch pha), đốm gold bay chậm và lấp lánh phía sau nội dung.

Chạm bất kỳ đâu trong lúc intro → nhảy ngay tới trạng thái cuối.

### Page 2 – vé VIP
- Vé trượt lên và lật nhẹ ra từ thiệp (~0.8s).
- Tên nhân viên hiện lần lượt theo từng từ.
- Lucky Draw Code có vệt sáng gold lướt qua 1 lần.
- Số bàn phóng to vào kèm đếm số nhanh 00 → 08 (≤ 0.6s).
- Đường răng cưa và viền gold tự vẽ ra.

### Phiên bản giảm chuyển động
Thể hiện cả trạng thái `prefers-reduced-motion`: không phong bì, không đốm bay, không đung đưa; nội dung hiện ngay với fade ngắn.

## Dữ liệu mẫu (chỉ để minh hoạ)

- Nhân viên: Nguyễn Văn An · Lucky Draw Code: YEP26-083 · Bàn: 08
- Sự kiện: Year End Party 2026 · Thứ Sáu, 18/12/2026 · 18:00
- Địa điểm: "Tên địa điểm" · "Địa chỉ địa điểm" (placeholder, sẽ thay bằng dữ liệu thật)

## Kết quả cần bàn giao

1. Thiết kế các màn hình trên cho mobile và desktop.
2. Bảng **design tokens**: mã màu, tên font và cỡ chữ theo cấp bậc, spacing, bo góc, độ dày đường kẻ gold.
3. Ghi chú vị trí đặt từng file hoa watercolor ở mỗi màn hình (góc nào, kích thước tương đối, có cắt mép không).
4. Prototype tương tác thể hiện intro Page 1 và chuyển sang Page 2.
5. Bảng thông số motion: thứ tự, thời lượng, độ trễ, easing cho từng phần tử; ghi rõ bước nào cần asset riêng (mask vệt màu nước, path nét bút của chữ "Year End Party").
