<?php

return [
    'telephony_secret' => env('WEBHOOK_TELEPHONY_SECRET', 'telephony-secret'),
    'messenger_secret' => env('WEBHOOK_MESSENGER_SECRET', 'messenger-secret'),
    'email_secret' => env('WEBHOOK_EMAIL_SECRET', 'email-secret'),
];