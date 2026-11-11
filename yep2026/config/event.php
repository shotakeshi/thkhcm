<?php

// Thông tin sự kiện hiển thị trên thiệp và vé. Field tuỳ chọn để trống => ẩn khỏi giao diện.
return [
    'company_name'     => 'THK Holdings Vietnam',
    'company_logo'     => 'images/thk-logo.png',
    'name'             => 'Year End Party',
    'date'             => '2026-12-18',
    'time'             => '18:00',
    'venue_name'       => 'ZEN PLAZA',
    'venue_address'    => '54+56 Nguyễn Trãi, Bến Thành, Hồ Chí Minh, Vietnam',
    'map_url'          => '',
    'monogram'         => '26',
    'dress_code'       => 'Trang phục dự tiệc',
    'rsvp_deadline'    => '2026-12-01',
    'invite' => [
        // Ảnh nền riêng cho thiệp/vé. Để trống => dùng nền chung của cả landing
        // (public/css/theme.css: --page-bg-image), giữ một khung cảnh xuyên suốt.
        'background'         => '',
        'background_desktop' => 'images/4.mp4',
        'ticket_background'  => '',
        'logo'               => 'images/thk-logo.png',
        'theme_label'        => 'HCMC',
        'title_lines'        => ['Year End', 'Party'],
        // Tháng / thứ dạng chữ in hoa trên thiệp. Để trống => tự lấy từ 'date'
        'month_label'        => '',
        'weekday_label'      => '',
        'time_prefix'        => 'AT',
        'location_label'     => '',
    ],
    // Section cuối trang
    // Ảnh trống => chỉ còn nền chung; lời chào và lời cảm ơn đều trống => ẩn cả section.
    'closing' => [
        'image' => 'images/all.webp',
        'lead'  => 'Hẹn gặp bạn trong đêm tiệc đáng nhớ',
        'title' => 'Trân trọng cảm ơn!',
        'note'  => '',
    ],
    'program' => [
        'image'             => '',
        'title'             => 'Lịch trình đêm tiệc',
        'date_label'        => 'SAVE THE DATE',
        'dress_code_label'  => 'Trang phục',
        'dress_code_colors' => ['#D2042D', '#FFD700', '#FFFFFF', '#171717'],
        'dress_code_note'   => '',
        'schedule'          => [
            ['time' => '18:00', 'title' => 'Đón khách'],
            ['time' => '18:30', 'title' => 'Khai mạc'],
            ['time' => '19:00', 'title' => 'Tiệc tối'],
            ['time' => '20:00', 'title' => 'Chương trình văn nghệ'],
            ['time' => '21:00', 'title' => 'Bốc thăm may mắn'],
            ['time' => '21:30', 'title' => 'Kết thúc chương trình'],
        ],
    ],
];
