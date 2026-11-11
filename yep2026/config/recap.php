<?php

// Ảnh và thứ tự các phần của Recap (section đầu trang chính).
// Nội dung chữ (lời dẫn, tiêu đề, câu chuyện, chú thích ảnh) theo từng ngôn ngữ: lang/{vi,en}/recap.php,
// mỗi sự kiện nối với nội dung qua 'key' (recap.events.{key}).
return [
    'hero' => [
        'image' => '',
    ],

    'beginning' => [
        'image' => 'images/celebrations/tet/6.webp',
    ],

    // Sự kiện đáng nhớ, gom theo chủ đề.
    // Chỉnh vị trí, khung, góc nghiêng từng ảnh: config/recap-collage.php.
    'celebrations' => [
        'events' => [
            [
                'key'    => 'tet',
                'photos' => [
                    ['src' => 'images/celebrations/tet/1.webp'],
                    ['src' => 'images/celebrations/tet/2.webp'],
                    ['src' => 'images/celebrations/tet/3.webp'],
                    ['src' => 'images/celebrations/tet/4.webp'],
                    ['src' => 'images/celebrations/tet/5.webp'],
                ],
            ],
            [
                'key'    => 'womens_day',
                'photos' => [
                    ['src' => 'images/celebrations/0803/1.webp'],
                    ['src' => 'images/celebrations/0803/3.webp'],
                    ['src' => 'images/celebrations/0803/2.webp'],
                ],
            ],
            [
                'key'    => 'anniversary',
                'photos' => [
                    ['src' => 'images/celebrations/birthday/1.webp'],
                    ['src' => 'images/celebrations/birthday/2.webp'],
                ],
            ],
            [
                'key'    => 'mid_autumn',
                'photos' => [
                    ['src' => 'images/celebrations/trungthu/1.webp'],
                    ['src' => 'images/celebrations/trungthu/2.webp'],
                    ['src' => 'images/celebrations/trungthu/7.webp'],
                    ['src' => 'images/celebrations/trungthu/3.webp'],
                    ['src' => 'images/celebrations/trungthu/4.webp'],
                    ['src' => 'images/celebrations/trungthu/5.webp'],
                    ['src' => 'images/celebrations/trungthu/6.webp'],
                ],
            ],
        ],
    ],

    'trips' => [
        'events' => [
            [
                'key'    => 'mini_trip',
                'photos' => [
                    ['src' => 'images/minitrips/1.webp'],
                    ['src' => 'images/minitrips/2.webp'],
                    ['src' => 'images/minitrips/3.webp'],
                    ['src' => 'images/minitrips/4.webp'],
                    ['src' => 'images/minitrips/5.webp'],
                ],
            ],
            [
                'key'    => 'company_trip',
                'photos' => [
                    ['src' => 'images/trips/1.webp'],
                    ['src' => 'images/trips/2.webp'],
                    ['src' => 'images/trips/3.webp'],
                    ['src' => 'images/trips/4.webp'],
                    ['src' => 'images/trips/5.webp'],
                    ['src' => 'images/trips/6.webp'],
                    ['src' => 'images/trips/7.webp'],
                    ['src' => 'images/trips/8.webp'],
                ],
            ],
        ],
    ],

    'people' => [
        'photos' => [
            ['src' => 'images/people/1.webp'],
            ['src' => 'images/people/2.webp'],
            ['src' => 'images/people/3.webp'],
            ['src' => 'images/people/4.webp'],
            ['src' => 'images/people/5.webp'],
            ['src' => 'images/people/6.webp'],
        ],
    ],

    // Ảnh lẻ không thuộc sự kiện nào.
    'moments' => [
        'photos' => [
            ['src' => 'images/moments/1.webp'],
            ['src' => 'images/moments/2.webp'],
            ['src' => 'images/moments/3.webp'],
            ['src' => 'images/moments/4.webp'],
            ['src' => 'images/moments/5.webp'],
            ['src' => 'images/moments/6.webp'],
            ['src' => 'images/moments/7.webp'],
            ['src' => 'images/moments/8.webp'],
            ['src' => 'images/moments/9.webp'],
            ['src' => 'images/moments/10.webp'],
        ],
    ],
];
