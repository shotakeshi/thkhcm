<?php

// Bố cục ảnh cắt dán cho chương 03–04 (views/partials/recap-events.php).
//
// Cấu hình theo [style ('party' | 'trip')][số ảnh].
// Hỗ trợ bố cục riêng cho 1, 3, 5 ảnh; party hỗ trợ thêm 2 ảnh, trip thêm 8 ảnh.
// Các trường hợp khác dùng bố cục xếp chảy nhiều cột và xoay vòng khung 5 ảnh.
//
// Mỗi bố cục gồm:
//
// - grid: Cấu hình lưới ảnh; null nếu chỉ có 1 ảnh.
//   + desktop / mobile: rows, aspect_ratio (tỉ lệ rộng / cao của khung).
//   + Số cột cố định: desktop 12, mobile 6 (max-width: 640px).
//
// - floating_decorations: Hoạ tiết trang trí theo vị trí % của khung.
//   + type: 'sparkle' hoặc 'route'.
//   + left, top: Vị trí.
//   + scale: Kích thước sparkle (1 = chuẩn).
//   + width_percent: Chiều rộng route theo %.
//
// - items: Danh sách ảnh theo thứ tự file 1.jpg, 2.jpg…
//   Ảnh khai báo sau hiển thị đè lên ảnh trước.
//   + frame: 'print' (viền kem) hoặc 'torn' (giấy xé).
//   + decoration: 'tape' hoặc 'clip' (không bắt buộc).
//   + fit: 'natural' để khung ôm sát ảnh theo aspect_ratio, hiện trọn ảnh,
//     không cắt, không dải trống (không bắt buộc; mặc định 'cover' phủ kín ô
//     lưới). Khung căn giữa theo chiều dọc trong ô. Chỉ áp dụng cho bố cục lưới.
//   + tilt_degrees: Góc nghiêng (âm: trái, dương: phải); mobile còn 60%.
//   + width_pixels, height_pixels: Kích thước ảnh (chỉ áp dụng cho bố cục
//     1 ảnh). Khi màn hình nhỏ hơn width_pixels, ảnh tự thu nhỏ và giữ tỉ lệ.
//   + aspect_ratio: Tỉ lệ ảnh (chỉ áp dụng cho bố cục xếp chảy).
//   + desktop / mobile: Vị trí và kích thước ảnh trên lưới, tính từ 1:
//     top_row, left_column, row_count, column_count.
return [
    // sự kiện có đúng N ảnh thì dùng khối N.
    'party' => [
        // ----- Bố cục 1 ẢNH · Tiệc sinh nhật -----
        // Một ảnh in đứng, kẹp giấy ở trên, nghiêng nhẹ phải, kích thước đặt
        // theo pixel (không có lưới), ba điểm lấp lánh quanh ảnh.
        1 => [
            'grid'  => null,
            'floating_decorations' => [
                ['type' => 'sparkle', 'left' => '6%', 'top' => '12%', 'scale' => 1.3],
                ['type' => 'sparkle', 'left' => '90%', 'top' => '30%', 'scale' => .8],
                ['type' => 'sparkle', 'left' => '84%', 'top' => '86%', 'scale' => 1],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'clip',
                    'tilt_degrees' => 2.5,
                    'width_pixels' => 520,
                    'height_pixels' => 650,
                ],
            ],
        ],
        // ----- Bố cục 2 ẢNH · Tiệc sinh nhật -----
        // Hai ảnh in lớn đặt cạnh nhau, chồng nhẹ lên mép nhau: ảnh trái kẹp
        // giấy nghiêng phải, ảnh phải dán băng dính nghiêng trái, thấp hơn một
        // chút. Mobile: xếp dọc, ảnh dưới lệch phải.
        2 => [
            'grid'  => [
                'desktop' => ['rows' => 10, 'aspect_ratio' => '2 / 1'],
                'mobile'  => ['rows' => 12, 'aspect_ratio' => '5 / 8'],
            ],
            'floating_decorations' => [
                ['type' => 'sparkle', 'left' => '3%', 'top' => '8%', 'scale' => 1.3],
                ['type' => 'sparkle', 'left' => '96%', 'top' => '14%', 'scale' => .8],
                ['type' => 'sparkle', 'left' => '50%', 'top' => '96%', 'scale' => 1],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'torn',
                    'decoration' => 'clip',
                    'fit' => 'natural',
                    'tilt_degrees' => 2.5,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 9,
                        'column_count' => 7,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 6,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'torn',
                    'decoration' => 'tape',
                    'fit' => 'natural',
                    'tilt_degrees' => -2,
                    'aspect_ratio' => '7 / 5',
                    'desktop' => [
                        'top_row' => 2,
                        'left_column' => 7,
                        'row_count' => 9,
                        'column_count' => 6,
                    ],
                    'mobile' => [
                        'top_row' => 7,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 6,
                    ],
                ],
            ],
        ],
        // ----- Bố cục 3 ẢNH · 8/3 -----
        // Bên trái: một ảnh giấy xé lớn, cao suốt khối. Bên phải: hai ảnh in
        // xếp chồng, ảnh trên dán băng dính nghiêng phải, ảnh dưới nghiêng
        // trái.
        3 => [
            'grid'  => [
                'desktop' => ['rows' => 10, 'aspect_ratio' => '5 / 4'],
                'mobile'  => ['rows' => 12, 'aspect_ratio' => '10 / 11'],
            ],
            'floating_decorations' => [
                ['type' => 'sparkle', 'left' => '94%', 'top' => '64%', 'scale' => 1.1],
                ['type' => 'sparkle', 'left' => '2%', 'top' => '94%', 'scale' => .8],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'torn',
                    'fit' => 'natural',
                    'tilt_degrees' => -1.5,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 10,
                        'column_count' => 6,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 7,
                        'row_count' => 5,
                        'column_count' => 5,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 4,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 3 (file 3.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -2.5,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 7,
                        'row_count' => 5,
                        'column_count' => 6,
                    ],
                    'mobile' => [
                        'top_row' => 8,
                        'left_column' => 2,
                        'row_count' => 5,
                        'column_count' => 5,
                    ],
                ],
            ],
        ],
        // ----- Bố cục 5 ẢNH · Tết -----
        // Trên cùng: ảnh giấy xé ngang lớn (desktop 9/12 cột, mobile hết bề
        // ngang). Ảnh in dán băng dính đè lên góc phải trên của ảnh lớn.
        // Phía dưới: ảnh giấy xé ngang chồng lên mép dưới ảnh lớn, ảnh giấy
        // xé dọc ở giữa, ảnh in dọc nghiêng ở phải.
        5 => [
            'grid'  => [
                'desktop' => ['rows' => 10, 'aspect_ratio' => '4 / 3'],
                'mobile'  => ['rows' => 16, 'aspect_ratio' => '2 / 3'],
            ],
            'floating_decorations' => [
                ['type' => 'sparkle', 'left' => '94%', 'top' => '7%', 'scale' => 1.2],
                ['type' => 'sparkle', 'left' => '97%', 'top' => '46%', 'scale' => .8],
                ['type' => 'sparkle', 'left' => '4%', 'top' => '92%', 'scale' => 1],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg) — ảnh ngang lớn
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -1.5,
                    'aspect_ratio' => '16 / 9',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 9,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 6,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 2,
                        'left_column' => 9,
                        'row_count' => 4,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 6,
                        'left_column' => 4,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 3 (file 3.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => 2.5,
                    'aspect_ratio' => '4 / 5',
                    'desktop' => [
                        'top_row' => 7,
                        'left_column' => 7,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                    'mobile' => [
                        'top_row' => 7,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 4 (file 4.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -4,
                    'aspect_ratio' => '3 / 4',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 10,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                    'mobile' => [
                        'top_row' => 12,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 5 (file 5.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => 2,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 6,
                    ],
                    'mobile' => [
                        'top_row' => 10,
                        'left_column' => 4,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
            ],
        ],
    ],
    // ===== Chương 04 · Những chuyến đi (Team building, Company trip) =====
    // Cùng quy tắc: số bên dưới là số ảnh của sự kiện. Số ảnh không có khối
    // riêng => ảnh xếp chảy nhiều cột.
    'trip' => [
        // ----- Bố cục 1 ẢNH · chuyến đi -----
        // Một ảnh giấy xé ngang nghiêng trái, kèm đường bay nét đứt phía dưới bên trái.
        1 => [
            'grid'  => null,
            'floating_decorations' => [
                ['type' => 'route', 'left' => '-6%', 'top' => '78%', 'width_percent' => 46],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -2,
                    'width_pixels' => 460,
                    'height_pixels' => 345,
                ],
            ],
        ],
        // ----- Bố cục 3 ẢNH · chuyến đi -----
        // Ảnh in ngang rộng hết bề ngang ở trên. Bên dưới: ảnh giấy xé dọc ở
        // trái và ảnh in dán băng dính ở phải, cả hai chồng nhẹ lên ảnh trên.
        3 => [
            'grid'  => [
                'desktop' => ['rows' => 10, 'aspect_ratio' => '4 / 3'],
                'mobile'  => ['rows' => 12, 'aspect_ratio' => '20 / 21'],
            ],
            'floating_decorations' => [
                ['type' => 'route', 'left' => '40%', 'top' => '50%', 'width_percent' => 26],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -1,
                    'aspect_ratio' => '16 / 9',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 12,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 6,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -3,
                    'aspect_ratio' => '3 / 4',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 2,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 5,
                        'left_column' => 1,
                        'row_count' => 8,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 3 (file 3.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 2.5,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 7,
                        'row_count' => 5,
                        'column_count' => 5,
                    ],
                    'mobile' => [
                        'top_row' => 6,
                        'left_column' => 4,
                        'row_count' => 6,
                        'column_count' => 3,
                    ],
                ],
            ],
        ],
        // ----- Bố cục 5 ẢNH · Mini Trip -----
        // Trên cùng bên trái: ảnh in ngang lớn. Bên phải: ảnh in vuông dán băng
        // dính đè lên góc ảnh lớn, và ảnh in ngang ở dưới.
        // Giữa phía dưới: hai ảnh giấy xé dọc nghiêng ngược chiều nhau, có đường bay nét đứt.
        5 => [
            'grid'  => [
                'desktop' => ['rows' => 12, 'aspect_ratio' => '20 / 17'],
                'mobile'  => ['rows' => 16, 'aspect_ratio' => '5 / 7'],
            ],
            'floating_decorations' => [
                ['type' => 'route', 'left' => '24%', 'top' => '44%', 'width_percent' => 30],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -1,
                    'aspect_ratio' => '16 / 9',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 7,
                        'column_count' => 10,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 6,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -4,
                    'aspect_ratio' => '3 / 4',
                    'desktop' => [
                        'top_row' => 7,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 5,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 3 (file 3.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '3 / 4',
                    'desktop' => [
                        'top_row' => 8,
                        'left_column' => 5,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 6,
                        'left_column' => 4,
                        'row_count' => 6,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 4 (file 4.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 2,
                        'left_column' => 9,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 11,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 5 (file 5.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -2,
                    'aspect_ratio' => '3 / 2',
                    'desktop' => [
                        'top_row' => 8,
                        'left_column' => 9,
                        'row_count' => 4,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 12,
                        'left_column' => 4,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
            ],
        ],
        // ----- Bố cục 8 ẢNH · Company trip -----
        // Hàng trên: hai ảnh cạnh nhau (ảnh in và ảnh giấy xé, nghiêng ngược
        // chiều), ảnh in vuông dán băng dính đè lên góc phải.
        // Hàng giữa: hai ảnh giấy xé dọc và một ảnh in ngang.
        // Hàng dưới: ảnh in vuông dán băng dính và ảnh giấy xé ngang chồng nhẹ
        // lên nhau, có đường bay nét đứt.
        8 => [
            'grid'  => [
                'desktop' => ['rows' => 16, 'aspect_ratio' => '10 / 11'],
                'mobile'  => ['rows' => 22, 'aspect_ratio' => '20 / 37'],
            ],
            'floating_decorations' => [
                ['type' => 'route', 'left' => '22%', 'top' => '28%', 'width_percent' => 30],
            ],
            'items' => [
                // Ảnh 1 (file 1.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -2,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 5,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 2 (file 2.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => 2,
                    'aspect_ratio' => '4 / 5',
                    'desktop' => [
                        'top_row' => 1,
                        'left_column' => 6,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 1,
                        'left_column' => 4,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 3 (file 3.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 2,
                        'left_column' => 9,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 5,
                        'left_column' => 4,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 4 (file 4.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -4,
                    'aspect_ratio' => '3 / 4',
                    'desktop' => [
                        'top_row' => 6,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 5,
                        'left_column' => 1,
                        'row_count' => 6,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 5 (file 5.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => 3,
                    'aspect_ratio' => '4 / 5',
                    'desktop' => [
                        'top_row' => 7,
                        'left_column' => 5,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 10,
                        'left_column' => 4,
                        'row_count' => 5,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 6 (file 6.jpg)
                [
                    'frame' => 'print',
                    'tilt_degrees' => -2,
                    'aspect_ratio' => '3 / 2',
                    'desktop' => [
                        'top_row' => 7,
                        'left_column' => 9,
                        'row_count' => 4,
                        'column_count' => 4,
                    ],
                    'mobile' => [
                        'top_row' => 11,
                        'left_column' => 1,
                        'row_count' => 4,
                        'column_count' => 3,
                    ],
                ],
                // Ảnh 7 (file 7.jpg)
                [
                    'frame' => 'print',
                    'decoration' => 'tape',
                    'tilt_degrees' => 2,
                    'aspect_ratio' => '1',
                    'desktop' => [
                        'top_row' => 11,
                        'left_column' => 2,
                        'row_count' => 6,
                        'column_count' => 5,
                    ],
                    'mobile' => [
                        'top_row' => 15,
                        'left_column' => 1,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                ],
                // Ảnh 8 (file 8.jpg)
                [
                    'frame' => 'torn',
                    'tilt_degrees' => -2.5,
                    'aspect_ratio' => '4 / 3',
                    'desktop' => [
                        'top_row' => 11,
                        'left_column' => 7,
                        'row_count' => 6,
                        'column_count' => 6,
                    ],
                    'mobile' => [
                        'top_row' => 18,
                        'left_column' => 3,
                        'row_count' => 5,
                        'column_count' => 4,
                    ],
                ],
            ],
        ],
    ],
];

