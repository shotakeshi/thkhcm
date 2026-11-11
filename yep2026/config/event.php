<?php

// Thông tin sự kiện hiển thị trên thiệp và vé. Field tuỳ chọn để trống => ẩn khỏi giao diện.
// Chữ hiển thị theo ngôn ngữ (lời chào, tiêu đề chương trình, tên các mốc, nhãn trang phục...) nằm ở
// lang/{vi,en}/messages.php; file này chỉ giữ dữ liệu sự kiện, tên riêng và ảnh.
return [
    'company_name'     => 'THK Holdings Vietnam',
    'company_logo'     => 'images/thk-logo.png',
    'name'             => 'Year End Party',
    'date'             => '2026-12-18',
    'time'             => '18:00',
    'venue_name'       => 'CAPELLA GALLERY HALL',
    'venue_address'    => '24 Ba Thang Hai Street, Hoa Hung, Ho Chi Minh City',
    'map_url'          => '',
    'monogram'         => '26',
    'rsvp_deadline'    => '2026-11-18',
    'invite' => [
        // Ảnh nền riêng cho thiệp/vé. Để trống => dùng nền chung của cả landing
        // (public/css/theme.css: --page-bg-image), giữ một khung cảnh xuyên suốt.
        'background'         => '',
        'background_desktop' => 'images/4.mp4',
        'ticket_background'  => '',
        'logo'               => 'images/thk-logo.png',
        'theme_label'        => 'HCMC',
        'title_lines'        => ['Year End', 'Party'],
        // Tháng / thứ dạng chữ in hoa trên thiệp, luôn tiếng Anh cho mọi ngôn ngữ. Để trống => tự lấy từ 'date'
        'month_label'        => '',
        'weekday_label'      => '',
        'time_prefix'        => 'AT',
    ],
    // Section cuối trang. Lời chào, lời cảm ơn: lang/ (closing.*).
    // Ảnh trống => chỉ còn nền chung; lời chào và lời cảm ơn đều trống => ẩn cả section.
    'closing' => [
        'image' => 'images/all.webp',
        'copyright' => '© 2026 THK Holdings Vietnam HCMC. All rights reserved.',
        'credit'    => 'Designed & Developed by Pham Quoc Thang (タン)',
    ],
    // Tiêu đề, nhãn ngày, nhãn và ghi chú trang phục: lang/ (program.*)
    'program' => [
        'image'             => '',
        'dress_code_colors' => ['#701b1b', '#b39d2b', '#FFFFFF', '#171717'],
        // key: tên mốc ở lang/ (program.items.{key}); tên mốc để trống => ẩn mốc đó
        'schedule'          => [
            ['time' => '18:00', 'key' => 'reception'],
            ['time' => '18:30', 'key' => 'opening'],
            ['time' => '19:00', 'key' => 'dinner'],
            ['time' => '20:00', 'key' => 'performances'],
            ['time' => '21:00', 'key' => 'lucky_draw'],
            ['time' => '21:30', 'key' => 'farewell'],
        ],
    ],
];
