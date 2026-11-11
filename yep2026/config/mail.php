<?php

return [
    'host'       => getenv('SMTP_HOST') ?: '',
    'port'       => (int) (getenv('SMTP_PORT') ?: 587),
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'username'   => getenv('SMTP_USERNAME') ?: '',
    'password'   => getenv('SMTP_PASSWORD') ?: '',
    'from_email' => getenv('MAIL_FROM_ADDRESS') ?: '',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'THK HOLDINGS VIETNAM',
    // Số giây chờ máy chủ SMTP phản hồi mỗi bước
    'timeout'    => 15,
];