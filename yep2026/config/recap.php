<?php
return [
    'hero' => [
        'lead'  => 'Mười hai tháng, những ngày hội và vô số khoảnh khắc. Hãy cùng '
            . 'nhau mở lại cuốn nhật ký của năm vừa qua.',
        'image' => '',
    ],

    'beginning' => [
        'intro'      => 'Mọi hành trình đều bắt đầu từ một khoảnh khắc. Và năm vừa qua, '
            . 'chúng ta bắt đầu bằng những lời chúc, những mong ước và niềm háo '
            . 'hức của ngày đầu năm.',
        'date'       => 'Đầu năm 2026',
        'direction'  => 'Năm mới mở ra bằng những phong bao đỏ, những cái bắt tay và rất '
            . 'nhiều tiếng cười.',
        'image'      => 'images/celebrations/tet/6.webp',
        'image_note' => 'Những ngày đầu năm 2026',
        'goals'      => [
            'Sức khoẻ và bình an cho mọi người',
            'Thêm thật nhiều nụ cười bên đồng đội',
            'Cùng nhau tạo nên những kỷ niệm đẹp',
        ],
        'first_steps' => 'Và năm mới bắt đầu bằng ngày hội đầu tiên: Tết sum vầy.',
    ],

    // Sự kiện đáng nhớ, gom theo chủ đề, mỗi sự kiện xuất hiện
    // Chỉnh vị trí, khung, góc nghiêng từng ảnh: config/recap-collage.php.
    'celebrations' => [
        'intro'  => 'Có những ngày cả công ty cùng dừng lại: để đón một mùa xuân mới, '
            . 'để gửi một lời chúc, hay để cùng thổi nến mừng sinh nhật.',
        'events' => [
            [
                'time'   => 'Tết cổ truyền',
                'title'  => 'Tết sum vầy',
                'story'  => 'Những ngày đầu xuân rộn ràng, cùng nhau đón năm mới bằng '
                    . 'lời chúc, phong bao đỏ và thật nhiều tiếng cười.',
                'photos' => [
                    ['src' => 'images/celebrations/tet/1.webp', 'note' => 'TET · ảnh 1'],
                    ['src' => 'images/celebrations/tet/2.webp', 'note' => 'TET · ảnh 2'],
                    ['src' => 'images/celebrations/tet/3.webp', 'note' => 'TET · ảnh 3'],
                    ['src' => 'images/celebrations/tet/4.webp', 'note' => 'TET · ảnh 4'],
                    ['src' => 'images/celebrations/tet/5.webp', 'note' => 'TET · ảnh 5'],
                ],
            ],
            [
                'time'   => '08/03',
                'title'  => 'Ngày của một nửa thế giới',
                'story'  => 'Một ngày dành riêng để gửi lời cảm ơn tới những người '
                    . 'phụ nữ đã làm cho mỗi ngày đi làm thêm dịu dàng.',
                'photos' => [
                    ['src' => 'images/celebrations/0803/1.webp', 'note' => 'Women Day · ảnh 1'],
                    ['src' => 'images/celebrations/0803/3.webp', 'note' => 'Women Day · ảnh 2'],
                    ['src' => 'images/celebrations/0803/2.webp', 'note' => 'Women Day · ảnh 3'],
                ],
            ],
            [
                'time'   => '15/05/2026',
                'title'  => 'Sinh nhật 2 tuổi THK HOLDINGS VIETNAM',
                'story'  => 'Một chiếc bánh sinh nhật, những nụ cười và lời chúc cho hành trình phía trước.',
                'photos' => [
                    ['src' => 'images/celebrations/birthday/1.webp', 'note' => 'birthday · ảnh 1'],
                    ['src' => 'images/celebrations/birthday/2.webp', 'note' => 'Manager Team'],
                ],
            ],
            [
                'time'   => '21/03/2026',
                'title'  => 'Trung thu',
                'story'  => 'Đêm rằm rộn ràng với đèn lồng, bánh nướng và những tiếng cười '
                    . 'của cả công ty bên nhau.',
                // Thay src bằng  … 7.jpg khi có ảnh.
                'photos' => [
                    ['src' => 'images/celebrations/trungthu/1.webp', 'note' => 'Trung thu · ảnh 1'],
                    ['src' => 'images/celebrations/trungthu/2.webp', 'note' => 'Trung thu · ảnh 2'],
                    ['src' => 'images/celebrations/trungthu/7.webp', 'note' => 'Trung thu · ảnh 3'],
                    ['src' => 'images/celebrations/trungthu/3.webp', 'note' => 'Trung thu · ảnh 4'],
                    ['src' => 'images/celebrations/trungthu/4.webp', 'note' => 'Trung thu · ảnh 5'],
                    ['src' => 'images/celebrations/trungthu/5.webp', 'note' => 'Trung thu · ảnh 6'],
                    ['src' => 'images/celebrations/trungthu/6.webp', 'note' => 'Trung thu · ảnh 7'],
                ],
            ],
        ],
    ],

    'trips' => [
        'intro'  => 'Có những ngày chúng ta rời xa bàn làm việc, cùng nhau lên đường '
            . 'và mang về thật nhiều kỷ niệm.',
        'events' => [
            [
                'time'   => '21/03',
                'title'  => 'Mini trip',
                'story'  => 'Một ngày ngoài trời: cùng chạy, cùng hô, cùng thắng và '
                    . 'cùng cười đến khàn giọng.',
                'photos' => [
                    ['src' => 'images/minitrips/1.webp', 'note' => 'Mini trip · ảnh 1'],
                    ['src' => 'images/minitrips/2.webp', 'note' => 'Mini trip · ảnh 2'],
                    ['src' => 'images/minitrips/3.webp', 'note' => 'Mini trip · ảnh 3'],
                    ['src' => 'images/minitrips/4.webp', 'note' => 'Mini trip · ảnh 4'],
                    ['src' => 'images/minitrips/5.webp', 'note' => 'Mini trip · ảnh 5'],
                ],
            ],
            [
                'time'   => '[Cần bổ sung: thời gian]',
                'title'  => 'Company trip',
                'story'  => 'Cùng nhau xách balo lên đường, để lại phía sau những '
                    . 'ngày bận rộn và mang về thật nhiều kỷ niệm.',
                'photos' => [
                    ['src' => 'images/trips/1.webp', 'note' => 'Company trip · ảnh 1'],
                    ['src' => 'images/trips/2.webp', 'note' => 'Company trip · ảnh 2'],
                    ['src' => 'images/trips/3.webp', 'note' => 'Company trip · ảnh 3'],
                    ['src' => 'images/trips/4.webp', 'note' => 'Company trip · ảnh 4'],
                    ['src' => 'images/trips/5.webp', 'note' => 'Company trip · ảnh 5'],
                    ['src' => 'images/trips/6.webp', 'note' => 'Company trip · ảnh 6'],
                    ['src' => 'images/trips/7.webp', 'note' => 'Company trip · ảnh 7'],
                    ['src' => 'images/trips/8.webp', 'note' => 'Company trip · ảnh 8'],
                ],
            ],
        ],
    ],

    'people' => [
        'intro'  => 'Mỗi khoảnh khắc đáng nhớ đều có những con người cùng tạo nên. '
            . 'Đây là gương mặt và nụ cười của chúng ta.',
        'photos' => [
            ['src' => 'images/people/1.webp', 'note' => ''],
            ['src' => 'images/people/2.webp', 'note' => ''],
            ['src' => 'images/people/3.webp', 'note' => ''],
            ['src' => 'images/people/4.webp', 'note' => ''],
            ['src' => 'images/people/5.webp', 'note' => ''],
            ['src' => 'images/people/6.webp', 'note' => ''],
        ],
    ],

    // Ảnh lẻ không thuộc sự kiện nào.
    'moments' => [
        'intro'  => 'Những khoảnh khắc nhỏ làm nên một năm không thể quên.',
        'photos' => [
            ['src' => 'images/moments/1.webp', 'note' => ''],
            ['src' => 'images/moments/2.webp', 'note' => ''],
            ['src' => 'images/moments/3.webp', 'note' => ''],
            ['src' => 'images/moments/4.webp', 'note' => ''],
            ['src' => 'images/moments/5.webp', 'note' => ''],
            ['src' => 'images/moments/6.webp', 'note' => ''],
            ['src' => 'images/moments/7.webp', 'note' => ''],
            ['src' => 'images/moments/8.webp', 'note' => ''],
            ['src' => 'images/moments/9.webp', 'note' => ''],
            ['src' => 'images/moments/10.webp', 'note' => ''],
            
        ],
    ],

    'closing' => [
        'looking_back' => 'Nhìn lại năm 2026, điều đọng lại không phải những con số mà là '
            . 'tiếng cười, những cái ôm và những ngày chúng ta có nhau.',
        'thanks'       => 'Cảm ơn mỗi người đã góp một phần vào những khoảnh khắc ấy.',
        'signature'    => 'Ban lãnh đạo',
        'next_year'    => 'Năm tới, hãy cùng nhau tạo thêm thật nhiều kỷ niệm đáng nhớ.',
        'message'      => 'Một hành trình khép lại, nhưng những điều chúng ta cùng nhau tạo '
            . 'nên sẽ còn tiếp tục. Cảm ơn vì đã là một phần của câu chuyện '
            . 'này.',
    ],
];
