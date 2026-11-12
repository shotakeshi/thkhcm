<?php

// Nội dung hiển thị tiếng Việt (ngôn ngữ mặc định) của trang công khai: thiệp, Recap, xác nhận tham dự,
// tra cứu vé, chương trình, trang link lỗi (vé luôn tiếng Anh, không nằm ở đây). Lấy bằng message('nhóm.khoá'); placeholder {tên} được thay
// bằng giá trị truyền vào. Trình duyệt dùng cùng khoá (data-i18n, public/js/i18n.js) để đổi ngôn ngữ tại chỗ.
// lang/en/messages.php phải có đúng bộ khoá này. Ngôn ngữ khác thiếu khoá => dùng bản tiếng Việt.
// Chuỗi rỗng là chủ ý ẩn phần tử đó (không dùng bản dự phòng).
return [
    // <title> của trang. {event}: tên sự kiện kèm năm
    'meta' => [
        'title'       => 'Thiệp mời {event}',
        // Mô tả khi chia sẻ link (og:description). {weekday}, {date}: theo ngôn ngữ; {time}, {venue}: config/event.php
        'description' => '{weekday}, {date} · {time} · {venue}',
    ],

    // Nút chuyển ngôn ngữ (tên từng ngôn ngữ nằm ở LOCALES trong bootstrap.php)
    'lang' => [
        'label' => 'Chọn ngôn ngữ',
    ],

    // Ngày tháng. format: {d} ngày, {dd} ngày 2 chữ số, {mm} tháng 2 chữ số, {month} tên tháng, {yyyy} năm
    'date' => [
        'format'       => '{dd}/{mm}/{yyyy}',
        // Theo thứ tự date('w'): 0 = Chủ Nhật
        'weekdays'     => ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'],
        'months'       => [
            1 => 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
            'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12',
        ],
    ],

    // Lớp giấy xé phủ trang khi mới vào
    'cover' => [
        'hint_swipe' => 'Vuốt xuống để mở thiệp',
        'hint_drag'  => 'Nhấp và kéo xuống để mở thiệp',
        // Người dùng bật giảm chuyển động: chỉ cần nhấn
        'hint_tap'   => 'Nhấn để mở thiệp',
    ],

    // Thiệp mời (tên sự kiện, logo, nhãn thành phố nằm ở config/event.php: invite)
    'invite' => [
        // Nhãn phía trên tên địa điểm, để trống => ẩn
        'location_label' => '',
    ],

    'recap' => require __DIR__ . '/recap.php',

    'email_label'       => 'Email',
    'email_placeholder' => '@thk-hd.vn, @core-tech.jp',
    'thanks_declined'   => 'Cảm ơn bạn đã phản hồi. Rất tiếc vì lần này chưa thể gặp bạn!',

    'errors' => [
        'email_required' => 'Vui lòng nhập email.',
        'email_invalid'  => 'Email không hợp lệ.',
    ],

    'rsvp' => [
        'title'             => 'Xác nhận tham dự',
        // {deadline}: hạn phản hồi (config/event.php: rsvp_deadline)
        'lead'              => 'Vui lòng phản hồi trước ngày {deadline} để chúng tôi kịp chuẩn bị chu đáo.',
        'name_label'        => 'Họ và tên',
        'name_placeholder'  => 'Nhập họ và tên',
        'attendance_label'  => 'Bạn sẽ tham gia chứ?',
        'attending'         => 'Tham gia được',
        'declined'          => 'Rất tiếc, tôi không thể đến',
        'hint'              => 'Sau khi gửi, bạn không thể thay đổi phản hồi.',
        'submit'            => 'Xác nhận',
        'submitting'        => 'Đang gửi…',

        // Đã xác nhận. {event}: tên sự kiện kèm năm
        'thanks_attending'  => 'Cảm ơn bạn đã xác nhận! Hẹn gặp bạn tại {event}.',
        'status_label'      => 'Trạng thái',
        'status_attending'  => 'Tham gia',
        'recorded'          => '',
        'ticket_below'      => 'Vé của bạn ở bên dưới.',
        'ticket_pending'    => '',
        'already_responded' => 'Phản hồi của bạn đã được ghi nhận và không thể thay đổi.',

        'errors' => [
            'save_failed'         => 'Chưa lưu được phản hồi, vui lòng thử lại sau ít phút.',
            'name_required'       => 'Vui lòng nhập họ và tên.',
            // {max}: số ký tự tối đa
            'name_too_long'       => 'Họ và tên tối đa {max} ký tự.',
            'attendance_required' => 'Vui lòng chọn bạn có tham gia được hay không.',
            // {domains}: danh sách domain được phép, vd "@thk-hd.vn, @core-tech.jp"
            'email_domain'        => 'Vui lòng dùng email công ty ({domains}).',
        ],
    ],

    'lookup' => [
        'title'             => 'Tra cứu vé',
        'lead'              => 'Đã xác nhận tham dự? Nhập email đã đăng ký để tìm vé của bạn.',
        'submit'            => 'Tìm vé',
        'submitting'        => 'Đang tìm…',
        'found'             => 'Đã tìm thấy vé của bạn! Vé hiển thị bên dưới.',
        'not_found'         => 'Không tìm thấy thư mời hoặc đăng ký nào với email này.',
        'not_responded'     => 'Bạn chưa gửi xác nhận tham dự.',
        'declined'          => 'Bạn đã xác nhận không thể tham dự sự kiện này. Không có vé tham dự.',
        // Đã xác nhận tham gia nhưng chưa có mã Lucky Draw hoặc số bàn
        'ticket_pending'    => 'Vé của bạn đang được cập nhật.',

        'errors' => [
            'failed'         => 'Chưa tra cứu được vé, vui lòng thử lại sau ít phút.',
        ],
    ],

    'attendees' => [
        'label'       => 'Người tham gia',
        'title'       => 'Danh sách tham gia',
        'count_label' => 'Đã xác nhận',
        'empty'       => 'Chưa có người xác nhận tham gia.',
    ],

    // Section Chương trình. Giờ từng mốc nằm ở config/event.php (program.schedule), tên mốc ở items theo 'key'
    'program' => [
        'label'            => 'Chương trình',
        'title'            => 'Lịch trình đêm tiệc',
        'date_label'       => 'Hẹn ngày',
        'dress_code'       => 'Trang phục dự tiệc',
        'dress_code_label' => 'Trang phục',
        'dress_code_note'  => '',
        'palette_label'    => 'Bảng màu trang phục',
        // Để trống một mốc => ẩn mốc đó
        'items' => [
            'reception'    => 'Đón khách & check-in',
            'opening'      => 'Khai mạc & chương trình đặc biệt',
            'dinner'       => 'Tiệc tối & giao lưu',
            'performances' => 'Chương trình tri ân & ghi nhận',
            'lucky_draw'   => 'Bốc thăm may mắn – Lucky Draw',
            'farewell'     => 'Bế mạc & kết thúc',
        ],
    ],

    // Section cuối trang. Lời chào và lời cảm ơn đều trống => ẩn cả section
    'closing' => [
        'lead'  => 'Hẹn gặp bạn trong đêm tiệc đáng nhớ',
        'title' => 'Trân trọng cảm ơn!',
        'note'  => '',
    ],

    'invalid' => [
        'title'             => 'Liên kết không hợp lệ',
        'text'              => 'Rất tiếc, chúng tôi không tìm thấy vé ứng với đường link này.',
        // Không đọc được dữ liệu (lỗi tạm thời)
        'unavailable_title' => 'Tạm thời chưa mở được thiệp',
        'unavailable_text'  => 'Chưa tải được thiệp mời, vui lòng thử lại sau ít phút.',
        'back'              => 'Về trang thiệp',
    ],

    // Xem ảnh phóng to (public/js/lightbox.js)
    'lightbox' => [
        'label' => 'Xem ảnh phóng to',
        'close' => 'Đóng',
    ],
];
