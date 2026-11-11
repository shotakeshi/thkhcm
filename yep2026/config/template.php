<?php

// Template mẫu. Placeholder: {name}, {code}, {email}, {invite_url}
return [
    'subject' => '[THK] Thư mời Year End Party 2026',
    'body'    => <<<HTML
        <p>Xin chào <strong>{name}</strong> ({code}),</p>
        <p>
            Trân trọng kính mời bạn tham dự <strong>Year End Party 2026</strong>
            của THK HOLDINGS VIETNAM.
        </p>
        <p>Thiệp mời và thông tin tham dự của bạn: <a href="{invite_url}">{invite_url}</a></p>
        <p>Thông tin chi tiết sẽ được cập nhật sau.</p>
        <p>Trân trọng,<br>THK HOLDINGS VIETNAM</p>
        HTML,
];
