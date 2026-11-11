<?php

// Nội dung hiển thị của xác nhận tham dự, tra cứu vé và trang link lỗi.
// Lấy bằng message('nhóm.khoá'); placeholder {tên} được thay bằng giá trị truyền vào.
return [
    'email_label'       => 'Email',
    'email_placeholder' => 'name@thk-hd.vn',
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
        ],
    ],

    'lookup' => [
        'title'             => 'Tra cứu vé',
        'lead'              => 'Đã xác nhận tham dự? Nhập email để tìm vé của bạn.',
        'submit'            => 'Tìm vé',
        'submitting'        => 'Đang tìm…',
        'found'             => 'Đã tìm thấy vé của bạn! Vé hiển thị bên dưới.',
        // Dùng chung cho email không có trong danh sách và email chưa RSVP
        'not_found'         => 'Không tìm thấy vé của email này.',
        // Đã xác nhận tham gia nhưng chưa có mã Lucky Draw hoặc số bàn
        'ticket_pending'    => 'Không tìm thấy vé của email này.',

        'errors' => [
            'failed'         => 'Chưa tra cứu được vé, vui lòng thử lại sau ít phút.',
        ],
    ],

    'invalid' => [
        'title'             => 'Liên kết không hợp lệ',
        'text'              => 'Rất tiếc, chúng tôi không tìm thấy vé ứng với đường link này.',
        // Không đọc được dữ liệu (lỗi tạm thời)
        'unavailable_title' => 'Tạm thời chưa mở được thiệp',
        'unavailable_text'  => 'Chưa tải được thiệp mời, vui lòng thử lại sau ít phút.',
        'back'              => 'Về trang thiệp',
    ],
];
