<?php

return [
    'telephony_secret' => env('WEBHOOK_TELEPHONY_SECRET', 'telephony-secret'),
    'messenger_secret' => env('WEBHOOK_MESSENGER_SECRET', 'messenger-secret'),
    'email_secret' => env('WEBHOOK_EMAIL_SECRET', 'email-secret'),

    // лимит запросов в минуту на источник, см. routes/web.php
    'rate_limit_per_minute' => (int) env('WEBHOOK_RATE_LIMIT', 60),
];
